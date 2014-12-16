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
namespace KmbPackageManager\Model;

use GtnPersistBase\Model\AggregateRootInterface;

interface SecurityLogsInterface extends  AggregateRootInterface
{
    public function __construct($updated_at = null, $username = null, $package = null, $from_version = null, $to_version = null, $server = null, $status = 'pending', $actionid = null );

    public function setId($id);

    public function getId();

    public function setUpdatedAt($date);

    public function getUpdatedAt();

    public function setUsername($user);

    public function getUsername();

    public function setPackage($package);

    public function getPackage();

    public function setFromVersion($from_version);

    public function getFromVersion();

    public function setToVersion($to_version);

    public function getToVersion();

    public function setServer($server);

    public function getServer();

    public function setStatus($status);

    public function getStatus();

    public function setActionId($actionid);

    public function getActionId();

    public function __toString();
}
