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


class SecurityLogsRepository extends Repository implements SecurityLogsRepositoryInterface
{
    public function getAllFiltered($query = null, $orderBy=null, $limit=null , $offset=null)
    {
        $select = $this->getSelect();

        if($query) {
            $select->where
                ->like('server', '%'. $query .'%')
                ->or
                ->like('package', '%', $query . '%')
                ->or
                ->like('username', '%', $query . '%');
        }
        if($orderBy != null) {
            $select->order($orderBy);
        } else {
            $select->order("updated_at DESC");
        }
        //        error_log(print_r($select->getSqlString(),true));
        $result = $this->hydrateAggregateRootsFromResult($this->performRead($select));
        return $result;
    }

    public function getLogForHostByActionIdRequestId($actionid,$requestid,$hostname) {
        $select = $this->getSlaveSql()->select()->from($this->tableName);
        $select ->where
            ->equalTo('actionid',$actionid)
            ->and
            ->equalTo('requestid',$requestid)
            ->and
            ->equalTo('server',$hostname)
            ->and
            ->equalTo('status','pending');
        $result = $this->hydrateAggregateRootsFromResult($this->performRead($select));
        return count($result) > 0 ? $result : null;
    }

    public function getAll() {
        $select = $this->getSlaveSql()->select()->from($this->tableName);
        return $this->hydrateAggregateRootsFromResult($this->performRead($select));
    }

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
        }
        return array_values($aggregateRoots);
    }
}
