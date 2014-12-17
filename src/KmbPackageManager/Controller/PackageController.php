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
        $this->debug("Packages : " . print_r($packages,true));

        $pkg_arg = [];
        $logs = [];
        foreach($packages as $name => $detail) {
            $version = explode('-',$detail['version']);
            $pkg_arg[] = [ 'name' => $name, 'version' => $version[0], 'release' => $version[1] ];
            $repository = $this->getServiceLocator()->get('SecurityLogsRepository');
            $log = new SecurityLogs(date('Y-m-d G:i:s'),$this->identity()->getLogin(),$name,$detail['from_version'],$detail['version'],$host,'pending',$actionid);
            $repository->add($log);
            $logs[$name] = $log;
        }
        $action = $mcProxyPatchService->patch($host,$pkg_arg, $environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
        $requestid = $action->result[0];

        return new JsonModel(['actionid' => $actionid, 'requestid' => $requestid]);
    }


    //     public function patchHostAction() {
    //     $pkg_arg = [];
    //     $logs = [];
    //     foreach($packages as $name => $detail) {
    //         $version = explode('-',$detail['version']);
    //         $pkg_arg[] = [ 'name' => $name, 'version' => $version[0], 'release' => $version[1] ];

    //         $repository = $this->getServiceLocator()->get('SecurityLogsRepository');
    //         $log = new SecurityLogs(date('Y-m-d G:i:s'),$this->identity()->getLogin(),$name,$detail['from_version'],$detail['version'],$host,'pending',$actionid);
    //         $repository->add($log);

    //         $logs[$name] = $log;
    //     }

    //     $action = $mcProxyPatchService->patchHost($host,$pkg_arg, $environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
    //     $requestid = $action->result[0];

    //     $this->debug("Actionid : " . $actionid. ", Requestid : ". print_r($requestid,true));
    //     $result = $actionHistory->getResultsByActionidRequestId($actionid,$requestid,count($action->discovered_nodes),10);
    //     $registration = $mcProxyPatchService->registrationRun($host,$environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
    //     $actionHistory->getResultsByActionidRequestId($actionid,$registration->result[0],1,10);

    //     $status = $this->globalActionStatus($result);

    //     foreach ($result as $r) {
    //         $this->debug('RRRRR : ' . print_r(json_decode($r->getResult()),true));
    //         foreach(json_decode($r->getResult())->packages as $pkg) {
    //             $log = $logs[$pkg->name];
    //             $pkg_status = ($pkg->status) == 0 ? 'success' : 'failure';
    //             $log->setRequestId($r->getRequestId());
    //             $log->setStatus($pkg_status);
    //             $repository->update($log);
    //         }
    //     }

    //     return new JsonModel($status);
    // }

    // public function patchAllHostAction() {
    //     $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
    //     $actionid = $this->params()->fromPost('actionid');
    //     $packages =  $this->params()->fromPost('package');

    //     $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');
    //     $actionHistory = $this->getServiceLocator()->get('McollectiveHistoryRepository');
    //     $patch = $this->getServiceLocator()->get('PatchRepository')->getByPublicId( $this->params()->fromRoute('patch'));
    //     $patch->setServiceLocator($this->getServiceLocator());


    //     $pkg_arg = [];
    //     foreach($packages as $name => $detail) {
    //         $version = explode('-',$detail['version']);
    //         $pkg_arg[] = [ 'name' => $name, 'version' => $version[0], 'release' => $version[1] ];
    //     }

    //     $action = $mcProxyPatchService->patchBatch($patch->getAffectedHostsFor($environment),$pkg_arg, $environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
    //     $requestid = $action->result[0];
    //     $this->debug("Actionid : " . $actionid. ", Requestid : ". print_r($requestid,true));
    //     $result = $actionHistory->getResultsByActionidRequestId($actionid,$requestid,count($action->discovered_nodes),29);
    //     $registration = $mcProxyPatchService->registrationRun($patch->getAffectedHostsFor($environment),$environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
    //     $actionHistory->getResultsByActionidRequestId($actionid,$registration->result[0],count($registration->discovered_nodes),10);

    //     $status = $this->globalActionStatus($result);
    //     return new JsonModel($status);
    // }

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
