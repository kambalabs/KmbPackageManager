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

use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use KmbAuthentication\Controller\AuthenticatedControllerInterface;
use KmbMcProxy\Service\Patch;
use KmbPackageManager\Model\SecurityLogs;
use KmbMcollective\Model\McollectiveLog;

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

    public function availableUpgradeAction() {
        $viewModel = $this->acceptableViewModelSelector($this->acceptCriteria);
        $variables = [];

        if ($viewModel instanceof JsonModel) {
            /** @var DataTable $datatable */
            $datatable = $this->getServiceLocator()->get('fixlist');
            $params = $this->params()->fromQuery();
            $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
            if ($environment !== null) {
                $params['environment'] = $environment;
            }
            $result = $datatable->getResult($params);
            $variables = [
                'draw' => $result->getDraw(),
                'recordsTotal' => $result->getRecordsTotal(),
                'recordsFiltered' => $result->getRecordsFiltered(),
                'data' => $result->getData(),
            ];
        }

        return $viewModel->setVariables($variables);
    }



    public function prePatchAction(){
        // Get from service locator
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');
        $actionHistory = $this->getServiceLocator()->get('McollectiveHistoryRepository');
        $fixCollector = $this->getServiceLocator()->get('KmbPackageManager\Service\AvailableFix');

        // Get from params
        $host = $this->params()->fromRoute('server');
        $patchName = $this->params()->fromRoute('patch');
        $patch = $fixCollector->getPatchInContext($patchName,$environment)->getData()[0];
        if(! isset($host)) {
            $host = $patch->getAffectedHostsInContext();
        }
        $package =  $patch->getPackages();

        $action = $mcProxyPatchService->prepatch($host,$package,$environment->getNormalizedName(),$this->identity()->getLogin());
        $result = $actionHistory->getResultsByActionid($action[0]->actionid,(count($action[0]->discovered_nodes)*count($package)),10);
        if(count($result) != 0) {
            $packageAction = [];
            foreach($result as $index => $resp) {
                $response_package = json_decode($resp->getResult());
                $hostname = $resp->getHostname();
                if(!isset($packageAction[$resp->getHostname()])) {
                    $packageAction[$resp->getHostname()] = [];
                }
                if(isset($response_package->outdated_packages) && count($response_package->outdated_packages) != 0 ) {
                    $packageAction[$resp->getHostname()] = array_merge($response_package->outdated_packages,$packageAction[$resp->getHostname()]);
                }
                foreach($packageAction as $hostname => $pkg) {
                    $packageAction[$hostname]=array_unique($packageAction[$hostname],SORT_REGULAR);
                }
            }
        }
        foreach($packageAction as $host => $pkg_list) {
            $packageAction[$host]=array_unique($pkg_list,SORT_REGULAR);
        }

        $checkResult = $this->globalActionStatus($result);
        $divalert = ($checkResult['status'] === 'success') ? 'success' : 'danger';

        $this->debug(print_r($packageAction,true));

        $html = new ViewModel(['packages' => $packageAction, 'host' => $host, 'actionid' => $action[0]->actionid, 'result' => $checkResult, 'divalert' => $divalert, 'agent' => $result[0]->getAgent(), 'action' => $result[0]->getAction(), 'patch' => $patch ]);
        if($this->params()->fromRoute('server') != null) {
            $html->setTemplate('kmb-package-manager/package/pre-patch-host.phtml');
        } else {
            $html->setTemplate('kmb-package-manager/package/pre-patch-all-host.phtml');
        }
        $html->setTerminal(true);
        return $html;
    }


    public function patchAction() {
        $this->debug("Starting patch Action");
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        $actionid = $this->params()->fromPost('actionid');
        $packages =  $this->params()->fromPost('package');

        $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');
        $actionHistory = $this->getServiceLocator()->get('McollectiveHistoryRepository');
        $patchName = $this->params()->fromPost('patch');
        $patch = $this->getServiceLocator()->get('KmbPackageManager\Service\AvailableFix')->getPatchInContext($patchName,$environment)->getData()[0];
        $host = $this->params()->fromRoute('server');
        if(! isset($host)) {
            $host = $patch->getAffectedHostsInContext();
        }
        $common_pkg= [];
        $diff = [];
        $donepkg = [];
        $requestids=[];
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
            $common_pkg = $diff;
            $hostlist = [];
            foreach($packages as $hostname => $pkgs)
            {
                $process = $this->getSmallestGroup($common_pkg,$pkgs,$donepkg);
                if(!empty($process['group'])) {
                    $hostlist[] = $hostname;
                }
                $common_pkg = $process['group'];
                $diff=$process['diff'];
            }
            $donepkg = array_merge($donepkg,$common_pkg);

            $pkg_arg = [];
            foreach($common_pkg as $name => $detail) {
                $version = explode('-',$detail['version']);
                $pkg_arg[] = [ 'name' => $name, 'version' => $version[0], 'release' => $version[1] ];
            }
            $logs = [];
            $action = $mcProxyPatchService->patch($hostlist,$pkg_arg, $environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
            $this->insertSecurityLog($common_pkg,$hostlist,$actionid,$action->result[0],$this->identity());
            $requestids[$action->result[0]] = ['packages' => $common_pkg, 'hosts' => $hostlist];
        } while(! empty($diff));

        $mcoLog = new McollectiveLog($actionid, $this->identity()->getLogin(),$this->identity()->getName() , 'patch', is_string($hostlist) ? $hostlist : '('.implode('|',$hostlist).')', is_string($hostlist) ? [$hostlist] : $hostlist, $environment->getNormalizedName(),json_encode($pkg_arg));
        try {
            $this->getServiceLocator()->get('McollectiveLogRepository')->add($mcoLog);
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
            $this->debug($e->getTraceAsString());
        }

        return new JsonModel(['actionid' => $actionid, 'requestid' => $requestids]);
    }


    public function insertSecurityLog($packages,$hosts,$actionid,$requestid,$identity) {
        foreach($hosts as $index => $host)
        {
            foreach($packages as $name => $detail) {
                $repository = $this->getServiceLocator()->get('SecurityLogsRepository');
                $log = new SecurityLogs(date('Y-m-d G:i:s'),$identity->getLogin(),$name,$detail['from_version'],$detail['version'],$host,'pending',$actionid,$requestid);
                $repository->add($log);
                $logs[$name] = $log;
            }
        }

    }

    public function getSmallestGroup($reference,$array,$strip = []) {
        ksort($reference);
        ksort($array);
        ksort($strip);
        if(empty($reference)) {
            $reference = $array;
            $diff = [];
        }else{
            $diff = array_diff_assoc($array,$reference);
            $reference = array_intersect_assoc($reference,$array);
        }
        return [ 'group' => array_diff_assoc($reference,$strip), 'diff' => $diff ];
    }

    public function globalActionStatus($result) {
        $status = "";
        $errors = [];
        foreach($result as $actionResult)
        {
            $this->debug(print_r($actionResult,true));
            if($actionResult->getStatusCode() == 0 && $status == "") {
                $status = "success";
            }elseif($actionResult->getStatusCode() == 0 && $status == "error") {
                $status = "partial";
            }elseif($actionResult->getStatusCode() != 0 && $status == "") {
                $status = "error";
                $errors[$actionResult->getAgent()."::".$actionResult->getAction()][] = $actionResult->getResult();

            }elseif($actionResult->getStatusCode() != 0 && $status == "success") {
                $status = "partial";
                $errors[$actionResult->getAgent()."::".$actionResult->getAction()][] = $actionResult->getResult();
            }
        }
        $this->debug('This is the result of globalActionStatus : ');
        $this->debug(print_r($status,true));
        $this->debug(print_r($errors,true));
        $this->debug(print_r($result,true));
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
