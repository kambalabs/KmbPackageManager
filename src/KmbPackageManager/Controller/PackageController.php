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


    public function prePatchHostAction(){
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        $host = $this->params()->fromRoute('server');
        $package =  explode(',',$this->params()->fromPost('packages'));
        $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');
        $actionHistory = $this->getServiceLocator()->get('McollectiveHistoryRepository');

        $action = $mcProxyPatchService->prepatchHost($host,$package,$environment->getNormalizedName(),$this->identity()->getLogin());
        $result = $actionHistory->getResultsByActionid($action[0]->actionid,(count($action[0]->discovered_nodes)*count($package)),10);
        if(count($result) != 0) {
            $packageAction = [];
            foreach($result as $index => $resp) {
                $response_package = json_decode($resp->getResult());
                if(isset($response_package->outdated_packages)) {

                    $getVersion = $mcProxyPatchService->getPackageVersion($host,$response_package->outdated_packages[0]->package, $environment->getNormalizedName(),$this->identity()->getLogin(),$action[0]->actionid);
                    $requestid = $getVersion->result[0];
                    $resultVersion = $actionHistory->getResultsByActionidRequestId($action[0]->actionid,$requestid,count($action[0]->discovered_nodes),10);

                    if(count($resultVersion) != 0) {
                        foreach($resultVersion as $indexV => $respV) {
                            $version_package = json_decode($respV->getResult());
                            if(isset($version_package->ensure)) {
                                $packageAction[$response_package->outdated_packages[0]->package]['from_version'] = $version_package->ensure;
                            }
                            else {
                                $packageAction[$response_package->outdated_packages[0]->package]['from_version'] = 'unknown';
                            }
                        }
                    }
                    else {
                        $this->debug('No responses from Mcollective package::status agent !');
                    }

                    $packageAction[$response_package->outdated_packages[0]->package]['package'] = $response_package->outdated_packages[0]->package;
                    $packageAction[$response_package->outdated_packages[0]->package]['repo'] = $response_package->outdated_packages[0]->repo;
                    $packageAction[$response_package->outdated_packages[0]->package]['to_version'] = $response_package->outdated_packages[0]->version;
                }
            }
            $packageAction=array_unique($packageAction,SORT_REGULAR);
        }

        $checkResult = $this->globalActionStatus($result);
        $divalert = ($checkResult['status'] === 'success') ? 'success' : 'danger';
        // CHECK
        $html = new ViewModel(['packages' => $packageAction, 'host' => $host, 'actionid' => $action[0]->actionid, 'result' => $checkResult, 'divalert' => $divalert, 'agent' => $result[0]->getAgent(), 'action' => $result[0]->getAction() ]);
        $html->setTerminal(true);
        return $html;
    }


    public function patchHostAction() {
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        $host = $this->params()->fromRoute('server');
        $actionid = $this->params()->fromPost('actionid');
        $packages =  $this->params()->fromPost('package');
        $mcProxyPatchService = $this->getServiceLocator()->get('mcProxyPatchService');
        $actionHistory = $this->getServiceLocator()->get('McollectiveHistoryRepository');

        $this->debug(print_r($packages,true));
        $pkg_arg = [];
        foreach($packages as $name => $detail) {
            $version = explode('-',$detail['version']);
            $pkg_arg[] = [ 'name' => $name, 'version' => $version[0], 'release' => $version[1] ];

            $repository = $this->getServiceLocator()->get('SecurityLogsRepository');
            $new_pkg = new SecurityLogs(date('Y-m-d G:i:s'),$this->identity()->getLogin(),$name,$detail['from_version'],$detail['version'],$host);
            $repository->add($new_pkg);
            // $lastId = $this->adapter->getDriver()->getLastGeneratedValue();
            // $this->debug('lastID : ' . $lastId);
        }
        $action = $mcProxyPatchService->patchHost($host,$pkg_arg, $environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
        $requestid = $action->result[0];
        $this->debug("Actionid : " . $actionid. ", Requestid : ". print_r($requestid,true));
        $result = $actionHistory->getResultsByActionidRequestId($actionid,$requestid,count($action->discovered_nodes),10);
        $registration = $mcProxyPatchService->registrationRun($host,$environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
        $actionHistory->getResultsByActionidRequestId($actionid,$registration->result[0],1,10);

        $status = $this->globalActionStatus($result);
        return new JsonModel($status);
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
