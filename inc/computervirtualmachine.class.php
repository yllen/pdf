<?php
/**
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
 @copyright Copyright (c) 2009-2022 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfComputerVirtualMachine extends PluginPdfCommon {

   static $rightname = "plugin_pdf";

   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ComputerVirtualMachine());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item) {

      $dbu = new DbUtils();

      $ID = $item->getField('id');

      // From ComputerVirtualMachine::showForComputer()
      $virtualmachines = $dbu->getAllDataFromTable('glpi_computervirtualmachines',
                                              ['computers_id' => $ID]);
      $pdf->setColumnsSize(100);
      $title = "<b>".__('List of virtualized environments')."</b>";

      $number = count($virtualmachines);

      if (!$number) {
         $pdf->displayTitle("<b>".__('No virtualized environment associated with the computer')."</b>");
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(19,11,11,8,20,8,8,15);
         $pdf->displayTitle(__('Name'), __('Virtualization system'), __('Virtualization model'),
                            __('State'), __('UUID'), _x('quantity', 'Processors number'),
                              sprintf(__('%1$s (%2$s)'), __('Memory'), __('Mio')),
                            __('Machine'));
         $pdf->setColumnsAlign('left', 'center', 'center', 'center', 'left', 'right', 'right', 'left');

         foreach ($virtualmachines as $virtualmachine) {
            $name = '';
            if ($link_computer = ComputerVirtualMachine::findVirtualMachine($virtualmachine)) {
               $computer = new Computer();
               if ($computer->getFromDB($link_computer)) {
                  $name = $computer->getName();
               }
            }
            $pdf->displayLine(
               $virtualmachine['name'],
               Toolbox::stripTags(Dropdown::getDropdownName('glpi_virtualmachinetypes',
                                                            $virtualmachine['virtualmachinetypes_id'])),
               Toolbox::stripTags(Dropdown::getDropdownName('glpi_virtualmachinesystems',
                                                            $virtualmachine['virtualmachinesystems_id'])),
               Toolbox::stripTags(Dropdown::getDropdownName('glpi_virtualmachinestates',
                                                            $virtualmachine['virtualmachinestates_id'])),
               $virtualmachine['uuid'],
               $virtualmachine['vcpu'],

               Toolbox::stripTags(Html::formatNumber($virtualmachine['ram'],false,0)),
               $name
            );
         }
      }

      // From ComputerVirtualMachine::showForVirtualMachine()
      if ($item->fields['uuid']) {
         $hosts = $dbu->getAllDataFromTable($item::getTable(),
                                            ['RAW'
                                             => ['LOWER(uuid)'
                                                 => ComputerVirtualMachine::getUUIDRestrictCriteria($item->fields['uuid'])
                                                ]
                                            ]);

         if (count($hosts)) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".__('List of virtualized environments')."</b>");

            $pdf->setColumnsSize(26,37,37);
            $pdf->displayTitle(__('Name'), __('Operating system'), __('Entity'));

            $computer = new Computer();
            foreach ($hosts as $host) {
               if ($computer->getFromDB($host['id'])) {
                  $pdf->displayLine(
                     $computer->getName(),
                     Toolbox::stripTags(Dropdown::getDropdownName('glpi_operatingsystems',
                                                                  $computer->getField('operatingsystems_id'))),
                     Dropdown::getDropdownName('glpi_entities', $computer->getEntityID()));
               }
            }
         }
      }
      $pdf->displaySpace();
   }
}