<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
*/

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfComputer_Item extends PluginPdfCommon {

   function __construct(Computer_Item $obj=NULL) {

      $this->obj = ($obj ? $obj : new Computer_Item());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $comp) {
      global $DB,$LANG;

      $ID = $comp->getField('id');

      $items = array('Printer'    => $LANG['Menu'][2],
                     'Monitor'    => $LANG['Menu'][3],
                     'Peripheral' => $LANG['Menu'][16],
                     'Phone'      => $LANG['Menu'][34]);

      $info = new InfoCom();

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');

      foreach ($items as $type => $title) {
         if (!($item = getItemForItemtype($type))) {
            continue;
         }
         if (!$item->canView()) {
            continue;
         }
         $query = "SELECT *
                   FROM `glpi_computers_items`
                   WHERE `computers_id` = '$ID'
                         AND `itemtype` = '$type'";

         if ($result = $DB->query($query)) {
            $resultnum = $DB->numrows($result);
            if ($resultnum > 0) {
               for ($j=0 ; $j < $resultnum ; $j++) {
                  $tID = $DB->result($result, $j, "items_id");
                  $connID = $DB->result($result, $j, "id");
                  $item->getFromDB($tID);
                  $info->getFromDBforDevice($type,$tID) || $info->getEmpty();

                  $line1 = $item->getName()." - ";
                  if ($item->getField("serial") != null) {
                     $line1 .= $LANG["common"][19] . " : " .$item->getField("serial")." - ";
                  }
                  $line1 .= Html::clean(Dropdown::getDropdownName("glpi_states",
                                                                 $item->getField('states_id')));

                  $line2 = "";
                  if ($item->getField("otherserial") != null) {
                     $line2 = $LANG["common"][20] . " : " . $item->getField("otherserial");
                  }
                  if ($info->fields["immo_number"]) {
                     if ($line2) {
                        $line2 .= " - ";
                     }
                     $line2 .= $LANG["financial"][20] . " : " . $info->fields["immo_number"];
                  }
                  if ($line2) {
                     $pdf->displayText('<b>'.$item->getTypeName().' : </b>', $line1 . "\n" . $line2, 2);
                  } else {
                     $pdf->displayText('<b>'.$item->getTypeName().' : </b>', $line1, 1);
                  }
               }// each device   of current type

            } else { // No row
               switch ($type) {
                  case 'Printer' :
                     $pdf->displayLine($LANG["computers"][38]);
                     break;

                  case 'Monitor' :
                     $pdf->displayLine($LANG["computers"][37]);
                     break;

                  case 'Peripheral' :
                     $pdf->displayLine($LANG["computers"][47]);
                     break;

                  case 'Phone' :
                     $pdf->displayLine($LANG["computers"][54]);
                     break;
               }
            } // No row
         } // Result
      } // each type
      $pdf->displaySpace();
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB,$LANG;

      $ID = $item->getField('id');
      $type = $item->getType();

      $info = new InfoCom();
      $comp = new Computer();

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         $resultnum = $DB->numrows($result);
         if ($resultnum > 0) {
            for ($j=0 ; $j < $resultnum ; $j++) {
               $tID = $DB->result($result, $j, "computers_id");
               $connID = $DB->result($result, $j, "id");
               $comp->getFromDB($tID);
               $info->getFromDBforDevice('Computer',$tID) || $info->getEmpty();

               $line1 = ($comp->fields['name']?$comp->fields['name']:"(".$comp->fields['id'].")")." - ";
               if ($comp->fields['serial']) {
                  $line1 .= $LANG["common"][19] . " : " .$comp->fields['serial']." - ";
               }
               $line1 .= Html::clean(Dropdown::getDropdownName("glpi_states",$comp->fields['states_id']));

               $line2 = "";
               if ($comp->fields['otherserial']) {
                  $line2 .= $LANG["common"][20] . " : " .$comp->fields['otherserial']." - ";
               }
               if ($info->fields['immo_number']) {
                  if ($line2) {
                     $line2 .= " - ";
                  }
                  $line2 .= $LANG["financial"][20] . " : " . $info->fields['immo_number'];
               }
               if ($line2) {
                  $pdf->displayText('<b>'.$LANG['help'][25].' : </b>', $line1 . "\n" . $line2, 2);
               } else {
                  $pdf->displayText('<b>'.$LANG['help'][25].' : </b>', $line1, 1);
               }
            }// each device   of current type

         } else { // No row
            $pdf->displayLine($LANG['connect'][1]);
         } // No row
      } // Result
      $pdf->displaySpace();
   }
}