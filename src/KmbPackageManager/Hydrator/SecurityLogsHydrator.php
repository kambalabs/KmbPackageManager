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
namespace KmbPackageManager\Hydrator;

use KmbPackageManager\Model\SecurityLogsInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;

class SecurityLogsHydrator implements HydratorInterface
{
    public function extract($object)
    {
        $data = [
            'updated_at'   => $object->getUpdatedAt(),
            'username'     => $object->getUsername(),
            'package'      => $object->getPackage(),
            'from_version' => $object->getFromVersion(),
            'to_version'   => $object->getToVersion(),
            'server'       => $object->getServer(),
            'status'       => $object->getStatus(),
            'actionid'     => $object->getActionId(),
            'requestid'    => $object->getRequestId(),
        ];
        return $data;
    }

    public function hydrate(array $data, $object)
    {
        $object->setId($data['id']);
        $object->setUpdatedAt($data['updated_at']);
        $object->setUsername($data['username']);
        $object->setPackage($data['package']);
        $object->setFromVersion($data['from_version']);
        $object->setToVersion($data['to_version']);
        $object->setServer($data['server']);
        $object->setStatus($data['status']);
        $object->setActionId($data['actionid']);
        $object->setRequestId($data['requestid']);
        return $object;
    }
}
