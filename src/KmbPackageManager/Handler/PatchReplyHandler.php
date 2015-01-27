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
namespace KmbPackageManager\Handler;

use KmbMcollective\Model\McollectiveHistory;
use KmbMcollective\Service\ReplyHandler;
use KmbMcProxy\Service;
use KmbPuppetDb\Service\NodeInterface;
use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;


class PatchReplyHandler
{

    protected $securityLogRepository;
    protected $mcProxy;
    protected $nodeService;

    public function process($historyLog) {
        error_log('processing patch reply for '. $historyLog->getHostname());
        $node = $this->nodeService->getByName($historyLog->getHostname());
        $status = $historyLog->getStatusCode() == 0 ? 'success' : 'failure' ;
        $log = $this->securityLogRepository->getLogForHostByActionIdRequestId($historyLog->getActionId(),$historyLog->getRequestId(),$historyLog->getHostname());
        if(isset($log) && count($log) > 0 ) {
            foreach ($log as $entry) {
                $entry->setStatus($status);
                $this->securityLogRepository->update($entry);
            }
        }
        $this->mcProxy->registrationRun($historyLog->getHostname(),$node->getEnvironment(),$historyLog->getCaller(),$historyLog->getActionId());
    }


    public function setSecurityLogRepository($repository) {
        $this->securityLogRepository = $repository;
        return $this;
    }

    public function getSecurityLogRepository() {
        return $this->securityLogRepository;
    }

    public function setMcProxy($proxy) {
        $this->mcProxy = $proxy;
        return $this;
    }

    public function getMcProxy() {
        return $this->mcProxy;
    }

    public function setNodeService($nodeService) {
        $this->nodeService = $nodeService;
        return $this;
    }

    public function getNodeService() {
        return $this->nodeService;
    }

}
