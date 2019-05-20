<?php
/**
 * @version $Id:
 -------------------------------------------------------------------------
 LICENSE

 This file is part of PDF plugin for GLPI.

 PDF is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 PDF is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @package   pdf
 @authors   Nelly Mahu-Lasson, Remi Collet
 @copyright Copyright (c) 2017-2019 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfItem_OperatingSystem extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_OperatingSystem());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, $item) {
      global $DB;


      $instID = $item->fields['id'];
      $type   = $item->getType();

      if (!$item->can($instID, READ)) {
         return false;
      }

      $query = ['SELECT'    => ['glpi_items_operatingsystems.*',
                                'glpi_operatingsystemversions.name',
                                'glpi_operatingsystemarchitectures.name',
                                'glpi_operatingsystemservicepacks.name',
                                'glpi_operatingsystemkernelversions.name',
                                'glpi_operatingsystemeditions.name'],
                'FROM'      => 'glpi_items_operatingsystems',
                'LEFT JOIN' => ['glpi_operatingsystems'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystems_id',
                                                'glpi_operatingsystems'       => 'id']],
                                'glpi_operatingsystemservicepacks'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystemservicepacks_id',
                                                'glpi_operatingsystemservicepacks' => 'id']],
                                 'glpi_operatingsystemarchitectures'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystemarchitectures_id',
                                                'glpi_operatingsystemarchitectures' => 'id']],
                                 'glpi_operatingsystemversions'
                                 => ['FKEY' => ['glpi_items_operatingsystems'  => 'operatingsystemversions_id',
                                                'glpi_operatingsystemversions' => 'id']],
                                 'glpi_operatingsystemkernelversions'
                                 => ['FKEY' => ['glpi_items_operatingsystems' => 'operatingsystemkernelversions_id',
                                                'glpi_operatingsystemkernelversions' => 'id']],
                                 'glpi_operatingsystemeditions'
                                 => ['FKEY' => ['glpi_items_operatingsystems'  => 'operatingsystemeditions_id',
                                                'glpi_operatingsystemeditions' => 'id']]],
                'WHERE'     => ['items_id' => $instID,
                                'itemtype' => $type],
                'ORDER'     => 'glpi_items_operatingsystems.id'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.__('Operating system').'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
          $title = sprintf(__('%1$s: %2$s'), $title, $number);
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(17,10,14,15,10,10,12,12);
         $pdf->displayTitle(__('Name'), __('Version'), __('Architecture'), __('Service pack'),
                            __('Kernel'), __('Edition'), __('Product ID'), __('Serial number'));

      }

      while ($data = $result->next()) {
         $pdf->displayLine(Dropdown::getDropdownName('glpi_operatingsystems', $data['operatingsystems_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemversions',
                                                     $data['operatingsystemversions_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemarchitectures',
                                                     $data['operatingsystemarchitectures_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemservicepacks',
                                                     $data['operatingsystemservicepacks_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemkernelversions',
                                                     $data['operatingsystemkernelversions_id']),
                           Dropdown::getDropdownName('glpi_operatingsystemeditions',
                                                     $data['operatingsystemeditions_id']),
                           $data['licenseid'], $data['license_number']);
      }
      $pdf->displaySpace();
   }
}