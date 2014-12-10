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

interface PatchInterface extends  AggregateRootInterface
{
    /**
     * @param string   $name
     * @param string   $description
     * @param McollectiveAction[]   $relatedActions
     */
    public function __construct($publicid = null, $packages = [], $affectedHosts = []);

    /**
     * Set Id.
     *
     * @param int $id
     * @return McollectiveLog
     */
    public function setId($id);

    /**
     * Get Id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set PublicId.
     *
     * @param string $publicId
     * @return Patch
     */
    public function setPublicId($publicId);

    /**
     * Get Public Id.
     *
     * @return string
     */
    public function getPublicId();

    /**
     * Set Packages
     *
     * @param array $packages
     * @return Patch
     */
    public function setPackages($packages);


    /**
     * Add Package
     *
     * @param string $package
     * @return Patch
     */
    public function addPackage($package);

    
    /**
     * Get Packages.
     *
     * @return string
     */
    public function getPackages();

    /**
     * Set Affected hosts
     *
     * @param string[]
     * @return Patch
     */
    public function setAffectedHosts($affectedHosts);

    /**
     * Get affectedHosts
     *
     * @return string[]
     */
    public function getAffectedHosts();
    
    /**
     * Add an affected Host
     *
     * @param string affectedHost
     * @return PatchInterface
     */
    public function addAffectedHost($affectedHost);

    /**
     * Check if there is host affected
     *
     * @return boolean
     */
    public function hasAffectedHosts();

    /**
     * Check if a specific host is affected
     *
     * @return boolean
     */
    public function isaffected($host);


    
    /**
     * @return string
     */
    public function __toString();

}
