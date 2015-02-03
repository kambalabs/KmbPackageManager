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

class NodePatchDecorator extends AbstractDecorator
{
    public function decorateTitle()
    {
        return $this->translate('Action');
    }

    public function decorateValue($object,$context = null)
    {
        $patchBtn = sprintf('<button class="btn btn-xs btn-danger patch-btn" data-cve="%s" data-url="%s" data-package="%s">&nbsp;%s&nbsp;</button>',$object->getPublicId(),$this->url('package-manager-generic-prepatch',['patch' => $object->getPublicId(), 'server' => $context['node']],[],true),implode(',',$object->getPackages()),$this->translate('Patch it!') );
        return $patchBtn;
    }
}
