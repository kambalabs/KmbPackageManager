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


use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


class Patch implements PatchInterface, ServiceLocatorAwareInterface
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $publicid;

    /** @var string */
    protected $criticity;

    /** @var array */
    protected $packages;

    /** @var array */
    protected $affectedHosts;

    protected $serviceLocator;

    /**
     * @param string   $name
     * @param string   $description
     * @param McollectiveAction[]   $relatedActions
     */
    public function __construct($publicid = null, $packages = [], $affectedHosts = [])
    {
        $this->setPublicId($publicid);
        $this->setPackages($packages);
        $this->setAffectedHosts($affectedHosts);
    }

    /**
     * Set Id.
     *
     * @param int $id
     * @return McollectiveLog
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get Id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set PublicId.
     *
     * @param string $publicId
     * @return Patch
     */
    public function setPublicId($publicId)
    {
        $this->publicid = $publicId;
        return $this;
    }

    /**
     * Get Public Id.
     *
     * @return string
     */
    public function getPublicId()
    {
        return $this->publicid;
    }

    /**
     * Set Criticity
     *
     * @param string $criticity
     * @return Patch
     */
    public function setCriticity($criticity)
    {
        $this->criticity = $criticity;
        return $this;
    }

    /**
     * Get Criticity
     *
     * @return string
     */
    public function getCriticity()
    {
        return $this->criticity;
    }

    /**
     * Set Packages
     *
     * @param array $packages
     * @return Patch
     */
    public function setPackages($packages)
    {
        $this->packages = $packages;
        return $this;
    }


    /**
     * Add Package
     *
     * @param string $package
     * @return Patch
     */
    public function addPackage($package)
    {
        $this->packages[] = $package;
        return $this;
    }


    /**
     * Get Packages.
     *
     * @return string
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * Set Affected hosts
     *
     * @param string[]
     * @return Patch
     */
    public function setAffectedHosts($affectedHosts)
    {
        $this->affectedHosts = $affectedHosts;
        return $this;
    }

    /**
     * Get affectedHosts
     *
     * @return string[]
     */
    public function getAffectedHosts()
    {
        return $this->affectedHosts;
    }

    public function getAffectedHostsFor($environment)
    {
        //CHECK
        $perm = $this->serviceLocator->get('KmbPermission\Service\Environment')->getAllReadable($environment);
        $queryBuilder = $this->serviceLocator->get('KmbPuppetDb\Query\NodesEnvironmentsQueryBuilder');
        $nodeService = $this->serviceLocator->get('KmbPuppetDb\Service\Node');
        if (!empty($perm)) {
            $queryEnvironment = $queryBuilder->build($perm)->getData();
        }
        $nodesCollection = $nodeService->getAll($queryEnvironment);
        $nodeList = array_map(function($item) {
            return $item->getName();
        },$nodesCollection->getData());
        return array_intersect($this->affectedHosts,$nodeList);
    }

    /**
     * Add an affected Host
     *
     * @param string affectedHost
     * @return PatchInterface
     */
    public function addAffectedHost($affectedHost)
    {
        $this->affectedHosts[] = $affectedHost;
        return $this;
    }

    /**
     * Check if there is host affected
     *
     * @return boolean
     */
    public function hasAffectedHosts()
    {
        return !empty($this->affectedHosts);
    }

    /**
     * Check if a specific host is affected
     *
     * @return boolean
     */
    public function isaffected($host)
    {
        return in_array($host,$this->affectedHosts);
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }

    public function getServiceLocator() {
        return $this->serviceLocator;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getPublicId();
    }

}
