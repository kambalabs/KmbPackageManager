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

class SecurityLogs implements SecurityLogsInterface
{
    protected $id;

    protected $updated_at;

    protected $username;

    protected $package;

    protected $from_version;

    protected $to_version;

    protected $server;

    public function __construct($updated_at = null, $username = null, $package = null, $from_version = null, $to_version = null, $server = null ){
        $this->setUpdatedAt($updated_at);
        $this->setUsername($username);
        $this->setPackage($package);
        $this->setFromVersion($from_version);
        $this->setToVersion($to_version);
        $this->setServer($server);

        return $this;
    }


    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getId() {
        return $this->id;
    }

    public function setUpdatedAt($date) {
        $this->updated_at = $date;
        return $this;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    public function setUsername($user) {
        $this->username = $user;
        return $this;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setPackage($package) {
        $this->package = $package;
        return $this;
    }

    public function getPackage() {
        return $this->package;
    }

    public function setFromVersion($from_version) {
        $this->from_version = $from_version;
        return $this;
    }

    public function getFromVersion() {
        return $this->from_version;
    }

    public function setToVersion($to_version) {
        $this->to_version = $to_version;
        return $this;
    }

    public function getToVersion() {
        return $this->to_version;
    }

    public function setServer($server) {
        $this->server = $server;
        return $this;
    }

    public function getServer() {
        return $this->server;
    }

    public function __toString()
    {
        return $this->getPublicId();
    }

}
