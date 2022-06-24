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


class PluginPdfComputer_Item extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Computer_Item());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $comp) {
      global $DB;

      $dbu = new DbUtils();

      $ID  = $comp->getField('id');

      $items = ['Printer'    => _n('Printer', 'Printers', 2),
                'Monitor'    => _n('Monitor', 'Monitors', 2),
                'Peripheral' => _n('Device', 'Devices', 2),
                'Phone'      => _n('Phone', 'Phones', 2)];

      $info = new Infocom();

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.__('Direct connections').'</b>');

      foreach ($items as $type => $title) {
         if (!($item = $dbu->getItemForItemtype($type))) {
            continue;
         }
         if (!$item->canView()) {
            continue;
         }
         $query = "SELECT `glpi_computers_items`.`id` AS assoc_id,
                      `glpi_computers_items`.`computers_id` AS assoc_computers_id,
                      `glpi_computers_items`.`itemtype`,
                      `glpi_computers_items`.`items_id`,
                      `glpi_computers_items`.`is_dynamic` AS assoc_is_dynamic,
                      ".$dbu->getTableForItemType($type).".*
                      FROM `glpi_computers_items`
                      LEFT JOIN `".$dbu->getTableForItemType($type)."`
                        ON (`".$dbu->getTableForItemType($type)."`.`id`
                              = `glpi_computers_items`.`items_id`)
                      WHERE `computers_id` = '$ID'
                            AND `itemtype` = '".$type."'
                            AND `glpi_computers_items`.`is_deleted` = '0'";
         if ($item->maybetemplate()) {
            $query.= " AND NOT `".$dbu->getTableForItemType($type)."`.`is_template` ";
         }

         $result = $DB->request($query);
         $resultnum = count($result);
         if ($resultnum > 0) {
            foreach ($result as $row) {
               $tID    = $row['items_id'];
               $connID = $row['id'];
               $item->getFromDB($tID);
               $info->getFromDBforDevice($type,$tID) || $info->getEmpty();

               $line1 = $item->getName();
               if ($item->getField("serial") != null) {
                  $line1 = sprintf(__('%1$s - %2$s'), $line1,
                                   sprintf(__('%1$s: %2$s'), __('Serial number'),
                                           $item->getField("serial")));
               }

               $line1 = sprintf(__('%1$s - %2$s'), $line1,
                                Toolbox::stripTags(Dropdown::getDropdownName("glpi_states",
                                                                             $item->getField('states_id'))));

               $line2 = "";
               if ($item->getField("otherserial") != null) {
                  $line2 = sprintf(__('%1$s: %2$s'), __('Inventory number'),
                                   $item->getField("otherserial"));
               }
               if ($info->fields["immo_number"]) {
                  $line2 = sprintf(__('%1$s - %2$s'), $line2,
                                   sprintf(__('%1$s: %2$s'), __('Immobilization number'),
                                           $info->fields["immo_number"]));
               }
               if ($line2) {
                  $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), $item->getTypeName().'</b>',''),
                                    $line1 . "\n" . $line2, 2);
               } else {
                  $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), $item->getTypeName().'</b>',''),
                                    $line1, 1);
               }
            }

         } else { // No row
            switch ($type) {
               case 'Printer' :
                  $pdf->displayLine(sprintf(__('No printer', 'pdf')));
                  break;

               case 'Monitor' :
                  $pdf->displayLine(sprintf(__('No monitor', 'pdf')));
                  break;

               case 'Peripheral' :
                  $pdf->displayLine(sprintf(__('No peripheral', 'pdf')));
                  break;

               case 'Phone' :
                  $pdf->displayLine(sprintf(__('No phone', 'pdf')));
                  break;
            }
         } // No row
      } // each type
      $pdf->displaySpace();
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $ID   = $item->getField('id');
      $type = $item->getType();

      $info = new Infocom();
      $comp = new Computer();

      $pdf->setColumnsSize(100);
      $title = '<b>'.__('Direct connections').'</b>';

      if ($result = $DB->request('glpi_computers_items',
                                 ['items_id' => $ID,
                                  'itemtype' => $type])) {
         $resultnum = count($result);

         if (!$resultnum) {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
         } else {
            $pdf->displayTitle($title);

            foreach ($result as $row) {
               $tID    = $row["computers_id"];
               $connID = $row["id"];
               $comp->getFromDB($tID);
               $info->getFromDBforDevice('Computer',$tID) || $info->getEmpty();

               $line1 = (isset($comp->fields['name'])?$comp->fields['name']:"(".$comp->fields['id'].")");
               if (isset($comp->fields['states_id'])) {
                  $line1 = sprintf(__('%1$s - %2$s'), $line1,
                                   sprintf(__('%1$s: %2$s'), '<b>'.__('Status').'</b>',
                                           Toolbox::stripTags(Dropdown::getDropdownName("glpi_states",
                                                                                        $comp->fields['states_id']))));
               }
               if (isset($comp->fields['serial'])) {
                  $line1 = sprintf(__('%1$s - %2$s'), $line1,
                                   sprintf(__('%1$s: %2$s'), '<b>'.__('Serial number').'</b>',
                                           $comp->fields['serial']));
               }


               if (isset($comp->fields['otherserial'])) {
                  $line1 = sprintf(__('%1$s - %2$s'), $line1,
                                   sprintf(__('%1$s: %2$s'), '<b>'.__('Inventory number').'</b>',
                                   $item->getField("otherserial")));
               }
               $line2 = '';
               if ($info->fields['immo_number']) {
                  $line2 = sprintf(__('%1$s - %2$s'), $line2,
                                   sprintf(__('%1$s: %2$s'), '<b>'.__('Immobilization number').'</b>',
                                           $info->fields["immo_number"]));
               }
               if ($line2) {
                  $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), __('Computer').'</b>', ''),
                                                  $line1 . "\n" . $line2, 2);
               } else {
                  $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), __('Computer').'</b>', ''),
                                                  $line1, 1);
               }
            }// each device   of current type
         } // No row
      } // Result
      $pdf->displaySpace();
   }
}