<?php
/**
 * @copyright Copyright (c) 2014 Orange Applications for Business
 * @link http://github.com/kambalabs for the sources repositories
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kamba. If not, see <http://www.gnu.org/licenses/>.
 */
namespace KmbPackageManager\Service;


use GtnDataTables\Model\Collection;
use GtnDataTables\Service\CollectorInterface;
use KmbPuppetDb\Query\QueryBuilderInterface;
use KmbPuppetDb\Service;

class AvailableFixCollector implements CollectorInterface
{

    /** @var Service\NodeInterface */
    protected $nodeService;
    /** @var QueryBuilderInterface */
    protected $nodesEnvironmentsQueryBuilder;
    /** @var \KmbPermission\Service\Environment */
    protected $permissionEnvironmentService;

    /** @var Service\PatchRepositoryInterface */
    protected $patchRepository;

    /**
     * @param array $params
     * @return Collection
     */
    public function findAll(array $params = null) {
        $offset = isset($params['start']) ? $params['start'] : null;
        $limit = isset($params['length']) ? $params['length'] : null;

        $dtquery = null;
        if (isset($params['search']['value']) && !empty($params['search']['value'])) {
            $dtquery = $params['search']['value'];
        }
        $orderBy = "";
        if (isset($params['order'])) {
            foreach ($params['order'] as $clause) {
                if($clause['column'] != "") {
                    if(!empty($orderBy)) {
                        $orderBy .= ',';
                    }
                    $orderBy .= "".$clause['column']." ".$clause['dir'];
                }
            }
        }

        $environments = $this->permissionEnvironmentService->getAllReadable(isset($params['environment']) ? $params['environment'] : null);
        $queryEnvironment = null;
        if (!empty($environments)) {
            $queryEnvironment = $this->nodesEnvironmentsQueryBuilder->build($environments)->getData();
        }
        $query = array_filter([$queryEnvironment]);
        if (count($query) > 1) {
            array_unshift($query, 'and');
        } else {
            $query = array_shift($query);
        }

        $nodesCollection = $this->getNodeService()->getAll($query);
        $environment_hosts = [];
        foreach($nodesCollection as $node) {
            if(isset($params['node'])) {
                if($params['node'] == $node->getName()){
                    $environment_hosts[] = $node->getName();
                }
            }else{
                $environment_hosts[] = $node->getName();
            }
        }
        $patchList = $this->patchRepository->getAllByHostList($environment_hosts, $dtquery, $orderBy, $limit, $offset);
        //error_log(print_r($patchList,true));
        $processedPatchList = [];
        foreach($patchList as $index => $patch) {
            $processedPatchList[] = $patch->setAffectedHostsInContext(array_intersect($patch->getAffectedHosts(),$environment_hosts));
        }
        return Collection::factory($processedPatchList, $limit, $this->patchRepository->countAllByHostList($environment_hosts,$dtquery));
    }

    public function getPatchInContext($id,$environment) {
        $environments = $this->permissionEnvironmentService->getAllReadable(isset($environment) ? $environment : null);
        $queryEnvironment = null;
        if(!empty($environments)) {
            $queryEnvironment = $this->nodesEnvironmentsQueryBuilder->build($environments)->getData();
        }
        $nodesCollection = $this->getNodeService()->getAll($queryEnvironment);
        $environment_hosts = [];
        foreach($nodesCollection as $node) {
            $environment_hosts[] = $node->getName();
        }
        $patch = $this->patchRepository->getByPublicId($id);
        $processedPatchList = [];
        $processedPatchList[] = $patch->setAffectedHostsInContext(array_intersect($patch->getAffectedHosts(),$environment_hosts));
        return Collection::factory($processedPatchList, null, 1);
    }

    /**
     * Get NodeService.
     *
     * @return \KmbPuppetDb\Service\NodeInterface
     */
    public function getNodeService()
    {
        return $this->nodeService;
    }
    /**
     * Set NodeService.
     *
     * @param \KmbPuppetDb\Service\NodeInterface $nodeService
     * @return NodeCollector
     */
    public function setNodeService($nodeService)
    {
        $this->nodeService = $nodeService;
        return $this;
    }

    /**
     * Get PatchRepository.
     *
     * @return \KmbPackageManager\Service\PatchRepositoryInterface
     */
    public function getPatchRepository()
    {
        return $this->patchRepository;
    }
    /**
     * Set PatchRepository.
     *
     * @param \KmbPackageManager\Service\PatchRepositoryInterface $patchRepository
     * @return AvailableFixCollector
     */
    public function setPatchRepository($patchRepository)
    {
        $this->patchRepository = $patchRepository;
        return $this;
    }



    /**
     * Set NodesEnvironmentsQueryBuilder.
     *
     * @param \KmbPuppetDb\Query\EnvironmentsQueryBuilderInterface $nodesEnvironmentsQueryBuilder
     * @return NodeCollector
     */
    public function setNodesEnvironmentsQueryBuilder($nodesEnvironmentsQueryBuilder)
    {
        $this->nodesEnvironmentsQueryBuilder = $nodesEnvironmentsQueryBuilder;
        return $this;
    }
    /**
     * Get NodesEnvironmentsQueryBuilder.
     *
     * @return \KmbPuppetDb\Query\EnvironmentsQueryBuilderInterface
     */
    public function getNodesEnvironmentsQueryBuilder()
    {
        return $this->nodesEnvironmentsQueryBuilder;
    }
    /**
     * Set PermissionEnvironmentService.
     *
     * @param \KmbPermission\Service\Environment $permissionEnvironmentService
     * @return NodeCollector
     */
    public function setPermissionEnvironmentService($permissionEnvironmentService)
    {
        $this->permissionEnvironmentService = $permissionEnvironmentService;
        return $this;
    }
    /**
     * Get PermissionEnvironmentService.
     *
     * @return \KmbPermission\Service\Environment
     */
    public function getPermissionEnvironmentService()
    {
        return $this->permissionEnvironmentService;
    }
}
