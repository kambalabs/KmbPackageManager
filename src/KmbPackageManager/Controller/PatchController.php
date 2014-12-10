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

class PatchController extends AbstractActionController implements AuthenticatedControllerInterface
{ 
    protected $acceptCriteria = array(
        'Zend\View\Model\JsonModel' => array(
            'application/json',
        ),
        'Zend\View\Model\ViewModel' => array(
            'text/html',
        ),
    );


    public function showAction(){
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        $patch = $this->getServiceLocator()->get('PatchRepository')->getByPublicId($this->params()->fromRoute('patch'));
        return new ViewModel(['patch' => $patch]);
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
