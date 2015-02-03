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
namespace KmbPackageManager\View\Decorator;

use GtnDataTables\View\AbstractDecorator;

class CriticityDecorator extends AbstractDecorator
{
    /**
     * @return string
     */
    public function decorateTitle()
    {
        return $this->translate('Criticity');
    }

    /**
     * @param McollectiveLogInterface $object
     * @return string
     */
    public function decorateValue($object,$context = null)
    {
        switch($object->getCriticity()) {
        case "high":
            return '<span class="label label-uniform-large label-danger">' . $this->translate('high') .'</span>';
            break;
        case "medium":
            return '<span class="label label-uniform-large label-warning">' . $this->translate('medium') .'</span>';
            break;
        case "low":
            return '<span class="label label-uniform-large label-info">' . $this->translate('low') .'</span>';
            break;
        default:
            return '<span class="label label-uniform-large label-default">' . sprintf($this->translate('unknown : %s'),$object->getCriticity()) .'</span>';
            break;
        }
    }
}
