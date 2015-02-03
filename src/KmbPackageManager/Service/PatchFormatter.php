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

use KmbMcollective\View\AbstractFormatter;

class PatchFormatter extends AbstractFormatter
{
    public function format($object){
        if($object->getIhmLog() != null) {
            $log = $object->getIhmLog()[0];
            $agent = $log->getAgent();
            $params = null;

            $params = json_decode($log->getParameters());
            $detail = sprintf($this->translatePlural('Upgrading %d package','Upgrading %d packages',count($params)), count($params));
            $icon = 'glyphicon-export';
            $object->setSummary(['detail' => $detail, 'icon' => $icon]);

        }else{
            $agent = $object->getAgent();
            $summary = ['icon' => 'glyphicon-export'];
            $summary['detail'] = preg_replace('/#.+#/', 'undefined',$this->translate('Upgrading package(s) (CLI mode)'));
            $object->setSummary(['detail' => 'foo', 'icon' => 'glyphicon-search']);
        }

        return $object;
    }


}
