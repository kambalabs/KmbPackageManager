<?php
/**
 * @copyright Copyright (c) 2014 Orange Applications for Business
 * @link      http://github.com/kambalabs for the sources repositories
 *
 * This file is part of Kamba.
 *
 * Kamba is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Kamba is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kamba.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace KmbPackageManager\Controller;

use GtnDataTables\Service\DataTable;
use KmbAuthentication\Controller\AuthenticatedControllerInterface;
use KmbMcollective\Model\McollectiveLog;
use KmbMcollective\Model\ActionLog;
use KmbMcollective\Model\CommandLog;
use KmbPackageManager\Model\SecurityLogs;
use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class PackageController extends AbstractActionController implements AuthenticatedControllerInterface
{
    protected $acceptCriteria = array(
        'Zend\View\Model\JsonModel' => array(
            'application/json',
        ),
        'Zend\View\Model\ViewModel' => array(
            'text/html',
        ),
    );

    public function availableUpgradeAction()
    {
        $viewModel = $this->acceptableViewModelSelector($this->acceptCriteria);
        $variables = [];

        if ($viewModel instanceof JsonModel) {
            /** @var DataTable $datatable */
            $params = $this->params()->fromQuery();
            $node = $this->params()->fromRoute('node');
            if ($node !== null) {
                $params['node'] = $node;
                $datatable = $this->getServiceLocator()->get('nodefixlist');
            } else {
                $datatable = $this->getServiceLocator()->get('fixlist');
            }

            $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
            if ($environment !== null) {
                $params['environment'] = $environment;
            }
            $result = $datatable->getResult($params, ['node' => $node]);
            $variables = [
                'draw' => $result->getDraw(),
                'recordsTotal' => $result->getRecordsTotal(),
                'recordsFiltered' => $result->getRecordsFiltered(),
                'data' => $result->getData(),
            ];
        }

        return $viewModel->setVariables($variables);
    }

    public function hostListAction()
    {
        $host = $this->params()->fromRoute('hostname');
        $patchRepository = $this->getServiceLocator()->get('PatchRepository')->getAllByHostList([$host]);
        $patchList = array_map(function ($object) {
            return (array)$object;
        }, $patchRepository);
        return new JsonModel($patchList);
    }

    public function hostFullPatchAction()
    {
        $host = $this->params()->fromRoute('hostname');
        $patchRepository = $this->getServiceLocator()->get('PatchRepository')->getAllByHostList([$host]);
        $patchList = array_map(function ($object) {
            return (array)$object;
        }, $patchRepository);
        return new JsonModel($patchList);
    }

    public function prePatchAction()
    {
        // Get from service locator
        $envId = $this->params()->fromRoute('envId');
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById(isset($envId) ? $envId : "0");
        $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');

        $actionLogRepository = $this->getServiceLocator()->get('ActionLogRepository');
        $watcher = $this->getServiceLocator()->get('ResultWatcher');
        $fixCollector = $this->getServiceLocator()->get('KmbPackageManager\Service\AvailableFix');

        // Get from params
        $host = $this->params()->fromRoute('server');
        $patchName = $this->params()->fromRoute('patch');
        $package = [];
        if ($patchName === 'all' && isset($host)) {
            $patches = $this->getServiceLocator()->get('PatchRepository')->getAllPackagesToUpgradeFor($host);
            $config = $this->getServiceLocator()->get('Config');
            $blacklist = $config['mcollective']['blacklist'];
            $message = $this->translate('The following packages have been blacklisted and will not be updated :<br/>');
            $message .= implode(', ', $blacklist);
            $message .= $this->translate('<br/>These packages need to be updated separatly');
            foreach ($patches as $idx => $patch) {
                foreach ($patch->getPackages() as $index => $pkg) {
                    if (!in_array($pkg, $package) && !in_array($pkg, $blacklist)) {
                        $package[] = $pkg;
                    }
                }
            }
            //error_log('Packages : ' . print_r($package,true));
        } else {
            $patch = $fixCollector->getPatchInContext($patchName, $environment)->getData()[0];
            if (!isset($host)) {
                $host = $patch->getAffectedHostsInContext();
            }
            $package = $patch->getPackages();
        }

        $action = $mcProxyPatchService->prepatch($host, $package, $environment->getNormalizedName(), $this->identity()->getLogin());
        $alog = new ActionLog($action[0]->actionid);
        $alog->setEnvironment($environment->getId());
        $alog->setParameters(json_encode($package));
        $alog->setDescription("Checking pre-patch for ". implode(', ',$package));
        $alog->setLogin($this->identity()->getLogin());
        $alog->setFullName($this->identity()->getName());
        $alog->setSource('kamba');
        $alog->setIhmIcon('glyphicon-check');

        $command = new CommandLog($action[0]->result[0]);
        $alog->addCommand($command);
        $actionLogRepository->add($alog);

        $result = $watcher->watchFor($action[0]->actionid, count($action[0]->discovered_nodes), 10);
        error_log(print_r($result,true));
        if (count($result) != 0) {
            $packageAction = [];
            foreach ($result as $index => $resp) {
                $response_package = json_decode($resp->getResult());
                $hostname = $resp->getHostname();
                if (!isset($packageAction[$resp->getHostname()])) {
                    $packageAction[$resp->getHostname()] = [];
                }
                if (isset($response_package->outdated_packages) && count($response_package->outdated_packages) != 0) {
                    $packageAction[$resp->getHostname()] = array_merge($response_package->outdated_packages, $packageAction[$resp->getHostname()]);
                }
                foreach ($packageAction as $hostname => $pkg) {
                    $packageAction[$hostname] = array_unique($packageAction[$hostname], SORT_REGULAR);
                }
            }
        }
        foreach ($packageAction as $host => $pkg_list) {
            $packageAction[$host] = array_unique($pkg_list, SORT_REGULAR);
        }

        $checkResult = $this->globalActionStatus($result);
        $divalert = ($checkResult['status'] === 'success') ? 'success' : 'danger';

        error_log(print_r($packageAction,true));
        $html = new ViewModel(['packages' => $packageAction, 'host' => $host, 'actionid' => $action[0]->actionid, 'result' => $checkResult, 'divalert' => $divalert, 'agent' => $result[0]->getAgent(), 'action' => $result[0]->getAction(), 'patch' => $patch, 'message' => $message]);
        if ($this->params()->fromRoute('server') != null) {
            $html->setTemplate('kmb-package-manager/package/pre-patch-host.phtml');
        } else {
            $html->setTemplate('kmb-package-manager/package/pre-patch-all-host.phtml');
        }
        $html->setTerminal(true);
        return $html;
    }

    public function translationAction()
    {
        $translation = [
            'patchTitle' => $this->translate('Patch'),
            'patchSuccess' => $this->translate('Patch applied successfully'),
            'patchPartially' => $this->translate('Patch partially applied.<br/>See logs for details'),
            'patchNotApplied' => $this->translate('Patch NOT applied.<br/>See logs for details'),
            'infoTitle' => $this->translate('Information'),
            'patchWaitCheck' => $this->translate('Please wait while checking patches'),
            'patchApply' => $this->translate('Applying patch'),
            'patchError' => $this->translate('Error while applying Patch'),

        ];
        return new JsonModel($translation);
    }

    public function patchAction()
    {
        $this->debug("Starting patch Action");
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        $actionid = $this->params()->fromPost('actionid');
        $packages = $this->params()->fromPost('package');
        error_log(print_r($packages,true));
        $hosts = [];
        $pkglist = [];
        foreach($packages as $hostname => $pkgs)
        {
            if(! in_array($hostname,$hosts))
            {
                $hosts[] = $hostname;
            }
            foreach(array_keys($pkgs) as $pkg )
            {
                if(! in_array($pkg,$pkglist))
                {
                    $pkglist[] = $pkg;
                }
            }

        }

        $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');
        $actionLogRepository = $this->getServiceLocator()->get('ActionLogRepository');
        $watcher = $this->getServiceLocator()->get('ResultWatcher');
        $actionLog = $actionLogRepository->getById($actionid);
        if(! isset($actionLog)){
            error_log('!!! Creation action log at patch step ... it should not happen');
            $actionLog = new ActionLog($actionid);
            $actionLog->setLogin($this->identity()->getLogin());
            $actionLog->setFullName($this->identity()->getName());
        }
        $actionLog->setDescription(sprintf($this->translate('Upgrading packages %s'),implode(' ,', $pkglist)));
        $actionLogRepository->update($actionLog);

        $patchName = $this->params()->fromPost('patch');
        $patch = $this->getServiceLocator()->get('KmbPackageManager\Service\AvailableFix')->getPatchInContext($patchName, $environment)->getData()[0];
        $host = $this->params()->fromRoute('server');
        if (!isset($host)) {
            $host = $patch->getAffectedHostsInContext();
        }
        $donepkg = [];
        $requestids = [];
        $affectedHosts = [];
        do {
            /**
             *Some explanations needed ...
             * To avoid installing packages on hosts and reducing the number of mco req.
             * groups are made to apply patches with the smallest common packages at each run.
             * * common_pkg is the list of packages to apply
             * * host_list is the list of hosts to apply those packages
             * * diff keep tracks of excluded packages (not in common packages)
             * * donepkg keep tracks of already installed patches
             *
             **/
            $hostlist = [];
            $common_pkg = [];
            foreach ($packages as $hostname => $pkgs) {
                $group = $this->getSmallestGroup($common_pkg, $pkgs, $donepkg);
                if (!empty($group)) {
                    $hostlist[] = $hostname;
                    $common_pkg = $group;
                }
            }
            $donepkg = array_merge($donepkg, $common_pkg);
            if (!empty($common_pkg)) {
                $pkg_arg = [];
                foreach ($common_pkg as $name => $detail) {
                    $version = $detail['version'];
                    $pkg_arg[] = ['name' => $name, 'version' => $version];
                }
                $action = $mcProxyPatchService->patch($hostlist, $pkg_arg, $environment->getNormalizedName(), $this->identity()->getLogin(), $actionid);
                $command = new CommandLog($action->result[0]);
                $actionLog->addCommand($command);
                $actionLogRepository->update($actionLog);
                $result = $watcher->watchFor($actionid, count($action->discovered_nodes), 60, $action->result[0]);
                foreach ($action->discovered_nodes as $idx => $identity) {
                    if (!in_array($identity, $affectedHosts)) {
                        array_push($affectedHosts, $identity);
                    }
                }
                $this->insertSecurityLog($common_pkg, $hostlist, $actionid, $action->result[0], $this->identity());
                $requestids[$action->result[0]] = ['packages' => $common_pkg, 'hosts' => $hostlist];
            }
        } while (!empty($common_pkg));
        $register = $mcProxyPatchService->registrationRun('('.implode('|',$hosts).')',$environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
        $command = new CommandLog($register->result[0]);
        $actionLog->addCommand($command);
        $actionLogRepository->update($actionLog);

        //        $mcoLog = new McollectiveLog($actionid, $this->identity()->getLogin(), $this->identity()->getName(), 'patch', is_string($hostlist) ? $hostlist : '(' . implode('|', $hostlist) . ')', $affectedHosts, $environment->getNormalizedName(), json_encode($pkg_arg));
        try {
            $this->getServiceLocator()->get('McollectiveLogRepository')->add($mcoLog);
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
            $this->debug($e->getTraceAsString());
        }

        return new JsonModel(['actionid' => $actionid, 'requestid' => $requestids]);
    }

    public function insertSecurityLog($packages, $hosts, $actionid, $requestid, $identity)
    {
        foreach ($hosts as $index => $host) {
            foreach ($packages as $name => $detail) {
                $repository = $this->getServiceLocator()->get('SecurityLogsRepository');
                $log = new SecurityLogs(date('Y-m-d G:i:s'), $identity->getLogin(), $name, $detail['from_version'], $detail['version'], $host, 'pending', $actionid, $requestid);
                $repository->add($log);
                $logs[$name] = $log;
            }
        }
    }

    public function getSmallestGroup($reference, $array, $strip = [])
    {
        $reference = array_diff_key($reference, $strip);
        ksort($reference);
        ksort($array);
        ksort($strip);
        if (empty($reference)) {
            $reference = array_diff_key($array, $strip);
        } else {
            $reference = array_intersect_key($reference, $array);
        }
        return $reference;
    }

    public function globalActionStatus($result)
    {
        $status = "";
        $errors = [];
        foreach ($result as $actionResult) {
            if ($actionResult->getStatusCode() == 0 && $status == "") {
                $status = "success";
            } elseif ($actionResult->getStatusCode() == 0 && $status == "error") {
                $status = "partial";
            } elseif ($actionResult->getStatusCode() != 0 && $status == "") {
                $status = "error";
                $errors[$actionResult->getAgent() . "::" . $actionResult->getAction()][] = $actionResult->getResult();
            } elseif ($actionResult->getStatusCode() != 0 && $status == "success") {
                $status = "partial";
                $errors[$actionResult->getAgent() . "::" . $actionResult->getAction()][] = $actionResult->getResult();
            }
        }
        return ['status' => $status, 'errors' => $errors];
    }

    /**
     * @param string $message
     * @return IndexController
     */
    public function debug($message)
    {
        /** @var Logger $logger */
        $logger = $this->getServiceLocator()->get('Logger');
        if ($logger != null) {
            $logger->debug($message);
        }
        return $this;
    }
}
