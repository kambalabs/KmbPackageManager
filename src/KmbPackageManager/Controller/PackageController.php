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
        $result = $actionHistory->getByActionid($action[0]->actionid,'finished');

        for($i=0; count($result) < (count($action[0]->discovered_nodes)*count($package)) ;$i++ ) {
            if($i >= 10) {
                break;
            }
            $result = $actionHistory->getByActionid($action[0]->actionid,'finished');
            sleep(1);
        }
        if(count($result) != 0) {
            $packageAction = [];
            foreach($result as $index => $resp) {
                $response_package = json_decode($resp->getResult());
                if(isset($response_package->outdated_packages)) {
                    $packageAction = array_merge($response_package->outdated_packages,$packageAction);
                }
            }
            $packageAction=array_unique($packageAction,SORT_REGULAR);
        }
        // CHECK 
        $html = new ViewModel(['packages' => $packageAction, 'host' => $host, 'actionid' => $action[0]->actionid]);
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
            $pkg_arg[] = [ 'name' ,$name];
            $pkg_arg[] = ['version' , $version[0]];
            $pkg_arg[] = ['release',  $version[1] ];
        }
        $action = $mcProxyPatchService->patchHost($host,$pkg_arg, $environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
        $result = $actionHistory->getByActionid($actionid,'finished');
        for($i=0; count($result) < (count($action->discovered_nodes)) ;$i++ ) {
            if($i >= 10) {
                break;
            }
            $result = $actionHistory->getByActionid($actionid,'finished');
            sleep(1);
        }
        $this->debug(print_r($result,true));
        $registration = $mcProxyPatchService->registrationRun($host,$environment->getNormalizedName(),$this->identity()->getLogin(),$actionid);
        $this->debug(print_r($registration,true));
        
        sleep(5);
        return new JsonModel(['foo' => 'bar']);
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
