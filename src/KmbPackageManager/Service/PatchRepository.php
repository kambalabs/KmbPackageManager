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

use GtnPersistZendDb\Infrastructure\ZendDb\Repository;
use GtnPersistBase\Model\AggregateRootInterface;
use GtnPersistBase\Model\RepositoryInterface;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;


class PatchRepository extends Repository implements PatchRepositoryInterface
{
    /** @var  string */
    protected $hostTableName;

    /** @var  string */
    protected $joinTableName;

    /**
     * @param AggregateRootInterface $aggregateRoot
     * @return McollectiveAgentInterface
     * @throws \Zend\Db\Exception\ExceptionInterface
     */
    public function add(AggregateRootInterface $aggregateRoot)
    {
        return $this;
    }

    /**
     * @param AggregateRootInterface $aggregateRoot
     * @return RepositoryInterface
     */
    public function update(AggregateRootInterface $aggregateRoot)
    {
        return $this;
    }
        
    /**
     * @param array $hostlist
     * @return McollectiveAgentInterface
     */
    public function getAllByHostList($hostlist, $query = null, $orderBy=null, $limit=null , $offset=null)
    {
        $selectPatch = $this->getPatchSelect($limit,$offset,$orderBy,$query);
        $select = $this->getJoinSelect($selectPatch);
        
        
        $select->where([
            'hosts.hostname' => $hostlist
        ]);
        if($query) {
            $select->where
                ->like('publicid', '%'. $query .'%');
        }        
        if($orderBy != null) {
            $sort = explode(' ',$orderBy);
            if($sort[0] == "criticity") {
                $select->order(new \Zend\Db\Sql\Expression("CASE 
                                                  WHEN criticity = 'low' THEN 2
                                                  WHEN criticity = 'medium' THEN 1
                                                  WHEN criticity = 'high' THEN 0 
                                                  END ". $sort[1]));
            } else {
                $select->order($orderBy);
            }
        } else {
            $select->order("publicid DESC");
        }
        $result = $this->hydrateAggregateRootsFromResult($this->performRead($select));
        return $result;
    }

    public function countAllByHostList($hostlist)
    {       
        $selectPatch=$this->getSlaveSql()->select()->from($this->tableName);
        $select = $this->getJoinSelect($selectPatch);
        
        
        $select->where([
            'hosts.hostname' => $hostlist
        ]);                
        return count($this->performRead($select));
    }
    

    public function getAll() {
        $select = $this->getJoinSelect($this->getSlaveSql()->select()->from($this->tableName));
        return $this->hydrateAggregateRootsFromResult($this->performRead($select));
    }

    public function getByPublicId($patch) {
        $select=$this->getJoinSelect($this->getPatchSelect(null,null,null,$patch,true));
        return $this->hydrateAggregateRootsFromResult($this->performRead($select))[0];
    }

    public function getPatchSelect($limit=null,$offset=null,$orderBy=null,$query=null,$strict=false){

        $selectPatch=$this->getSlaveSql()->select()->from($this->tableName);

        if(isset($limit)) {
            $selectPatch->limit($limit);
        }
        if(isset($offset)) {
            $selectPatch->offset($offset);
        }
        if(isset($query) && $strict) {
            $selectPatch->where
                -> equalTo('publicid',$query);
        }elseif(isset($query) && !$strict) {
            $selectPatch->where
                ->like('publicid', '%'.$query.'%');
        }

        if($orderBy != null) {
            $sort = explode(' ',$orderBy);
            if($sort[0] == "criticity") {
                $selectPatch->order(new \Zend\Db\Sql\Expression("CASE 
                                                  WHEN criticity = 'low' THEN 0
                                                  WHEN criticity = 'medium' THEN 1
                                                  WHEN criticity = 'high' THEN 2 
                                                  END ". $sort[1]));
            } else {
                $selectPatch->order($orderBy);
            }
        } else {
            $selectPatch->order("publicid DESC");
        }
        return $selectPatch;
    }

    public function getJoinSelect($patchSelect) {
        return $this
            ->getSlaveSql()
            ->select()
            ->from($this->joinTableName)
            ->join(
                ['vuln' => $patchSelect],
                'vuln.id = '.$this->joinTableName.'.vulnerability_id',
                ['*' => '*'],
                Select::JOIN_RIGHT
            )
            ->join(
                ['hosts' => $this->getHostTableName()],
                'hosts.id = '. $this->joinTableName .'.host_id',
                [
                    'hosts.id' => 'id',
                    'hosts.hostname' => 'hostname'
                ],
                Select::JOIN_LEFT
            );
    }

    /**
     * Set HostTableName.
     *
     * @param string $hostTableName
     * @return PatchRepository
     */
    public function setHostTableName($hostTableName)
    {
        $this->hostTableName = $hostTableName;
        return $this;
    }

    /**
     * Get HostTableName.
     *
     * @return string
     */
    public function getHostTableName()
    {
        return $this->hostTableName;
    }

    /**
     * Set JoinTableName.
     *
     * @param string $joinTableName
     * @return PatchRepository
     */
    public function setJoinTableName($joinTableName)
    {
        $this->joinTableName = $joinTableName;
        return $this;
    }

    /**
     * Get JoinTableName.
     *
     * @return string
     */
    public function getJoinTableName()
    {
        return $this->joinTableName;
    }

    // protected function getSelect()
    // {
    //     $select =  parent::getSelect()
    //         ->join(
    //             ['j' => $this->getJoinTableName()],
    //             $this->getTableName() . '.id = j.vulnerability_id',
    //             [
    //                 'j.id' => 'id',
    //                 'j.vulnerability_id' => 'vulnerability_id',
    //                 'j.host_id' => 'host_id',
    //             ],
    //             Select::JOIN_LEFT
    //         )
    //         ->join(
    //             ['hosts' => $this->getHostTableName()],
    //             'hosts.id = j.host_id',
    //             [
    //                 'hosts.id' => 'id',
    //                 'hosts.hostname' => 'hostname'
    //             ],
    //             Select::JOIN_LEFT
    //         );
    //     return $select;
    // }

    /**
     * @param ResultInterface $result
     * @return array
     */
    protected function hydrateAggregateRootsFromResult(ResultInterface $result)
    {
        $aggregateRootClassName = $this->getAggregateRootClass();
        $aggregateRoots = [];
        foreach ($result as $row) {
            $fixId = $row['id'];
            if (!array_key_exists($fixId, $aggregateRoots)) {
                $aggregateRoot = new $aggregateRootClassName;
                $aggregateRoots[$fixId] = $this->aggregateRootHydrator->hydrate($row, $aggregateRoot);
            } else {
                $aggregateRoot = $aggregateRoots[$fixId];
            }

            if (isset($row['hosts.hostname'])) {
                /** @var RevisionLog $revisionLog */
                $aggregateRoot->addAffectedHost($row['hosts.hostname']);               
            }
            if (isset($row['hosts.package'])) {
                /** @var RevisionLog $revisionLog */
                foreach(explode(',',$row['hosts.package']) as $index => $package) {
                    $aggregateRoot->addPackage($package);
                }
            }
        }
        return array_values($aggregateRoots);
    }


}
