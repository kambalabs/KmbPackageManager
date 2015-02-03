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

class PackagesDecorator extends AbstractDecorator
{
    /**
     * @return string
     */
    public function decorateTitle()
    {
        return $this->translate('Impacted packages');
    }

    /**
     * @param McollectiveLogInterface $object
     * @return string
     */
    public function decorateValue($object, $context = null)
    {
        $list = $object->getPackages();
        $size = count($list);
        $limit = array_slice($list,0,4);
        $field = "<ul class='list-inline'>";
        foreach($limit as $index => $package) {
            $field .= "<li>". $package . "</li>";
        }
        $others = $size - 4;
        if ($others > 0) {
            $field .= "<li><span class='small text-muted'><a title='". implode(', ',array_slice($list,4)) ."'>". sprintf($this->translatePlural("and %s more","and %s more", $others), $others) . "</a></span></li>";
        }

        $field .= "</ul>";

        return $field;
    }
}
