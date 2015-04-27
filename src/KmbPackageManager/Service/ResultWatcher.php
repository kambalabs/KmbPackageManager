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
namespace KmbPackageManager\Service;

class ResultWatcher  {

    protected $actionLogRepository;


    public function setActionLogRepository($repository){
        $this->actionLogRepository = $repository;
        return $this;
    }
    public function getActionlogRepository(){
        return $this->actionLogRepository;
    }

    public function watchFor($actionid,$expectedResults,$time = null,$requestid = null){
        $replies = [];
        for ($i = 0; count($replies) < $expectedResults; $i++) {
            if (isset($time)) {
                if ($i > $time) {
                    break;
                }
            }
            $action = $this->actionLogRepository->getById($actionid);
            if(isset($action)){
                foreach($action->getCommands() as $command){
                    //                    $replies = array_merge($command->getAllFinishedReplies(),$replies);
                    foreach($command->getAllFinishedReplies() as $reply)
                    {
                        if(isset($requestid))
                        {
                            if($reply->getRequestId() == $requestid){
                                $replies[] = $reply;
                            }
                        }else{
                            $replies[] = $reply;
                        }
                    }
                }
            }
            sleep(1);
        }
        return $replies;

    }


}
