<?php
/**
 * @version $Id$
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
 @copyright Copyright (c) 2009-2016 PDF plugin team
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
      global $DB;

      $ID = $item->getField('id');

      // From ComputerVirtualMachine::showForComputer()
      $virtualmachines = getAllDatasFromTable('glpi_computervirtualmachines',
                                              "`computers_id` = '$ID'");
      $pdf->setColumnsSize(100);
      if (count($virtualmachines)) {
         $pdf->displayTitle("<b>".__('List of virtual machines')."</b>");
         $pdf->setColumnsSize(20,8,8,8,25,8,8,15);
         $pdf->setColumnsAlign('left', 'center', 'center', 'center', 'left', 'right', 'right', 'left');
         $typ = explode(' ', __('Virtualization system'));
         $sys = explode(' ', __('Virtualization model'));
         $sta = explode(' ', __('State of the virtual machine'));
         $pdf->displayTitle(__('Name'), $typ[0], $sys[0], $sta[0], __('UUID'), __('CPU'), __('Mio'),
                            __('Machine'));

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
               Html::clean(Dropdown::getDropdownName('glpi_virtualmachinetypes',
                                                    $virtualmachine['virtualmachinetypes_id'])),
               Html::clean(Dropdown::getDropdownName('glpi_virtualmachinesystems',
                                                     $virtualmachine['virtualmachinesystems_id'])),
               Html::clean(Dropdown::getDropdownName('glpi_virtualmachinestates',
                                                    $virtualmachine['virtualmachinestates_id'])),
               $virtualmachine['uuid'],
               $virtualmachine['vcpu'],

               Html::clean(Html::formatNumber($virtualmachine['ram'],false,0)),
               $name
            );
         }
      } else {
         $pdf->displayTitle("<b>".__('No virtual machine associated with the computer')."</b>");
      }

      // From ComputerVirtualMachine::showForVirtualMachine()
      if ($item->fields['uuid']) {
         $where = "`uuid`".ComputerVirtualMachine::getUUIDRestrictRequest($item->fields['uuid']);
         $hosts = getAllDatasFromTable('glpi_computervirtualmachines', $where);

         if (count($hosts)) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".__('List of host machines')."</b>");

            $pdf->setColumnsSize(26,37,37);
            $pdf->displayTitle(__('Name'), __('Operating system'), __('Entity'));

            $computer = new Computer();
            foreach ($hosts as $host) {
               if ($computer->getFromDB($host['computers_id'])) {
                  $pdf->displayLine(
                     $computer->getName(),
                     Html::clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                                           $computer->getField('operatingsystems_id'))),
                     Dropdown::getDropdownName('glpi_entities', $computer->getEntityID()));
               }
            }
         }
      }
      $pdf->displaySpace();
   }
}