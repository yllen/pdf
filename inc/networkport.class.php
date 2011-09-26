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

class PluginPdfNetworkPort extends PluginPdfCommon {

   function __construct(NetworkPort $obj=NULL) {

      $this->obj = ($obj ? $obj : new NetworkPort());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB,$LANG;

      $ID = $item->getField('id');
      $type = get_class($item);

      $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '$type'
                ORDER BY `name`, `logical_number`";

      $pdf->setColumnsSize(100);
      if ($result = $DB->query($query)) {
         $nb_connect = $DB->numrows($result);
         if (!$nb_connect) {
            $pdf->displayTitle('<b>0 '.$LANG["networking"][10].'</b>');
         } else {
            $pdf->displayTitle('<b>'.ucfirst($LANG["networking"][$nb_connect>1 ? 11 : 12])." : $nb_connect</b>");

            while ($devid=$DB->fetch_row($result)) {
               $netport = new NetworkPort;
               $netport->getfromDB(current($devid));
               $pdf->displayTitle('<b>'.$LANG['networking'][4].'<i># '.$netport->fields["logical_number"].'</i>'.
                        ' : '.$netport->fields["name"].'</b>');

               $pdf->displayLine('<b><i>'.$LANG["networking"][51].' :</i></b> '.
                                 Html::clean(Dropdown::getDropdownName("glpi_netpoints",
                                                                      $netport->fields["netpoints_id"])));

               $pdf->displayLine('<b><i>'.$LANG["networking"][14].' / '.
                                 $LANG["networking"][15].' :</i></b> '.$netport->fields["ip"].' / '.
                                 $netport->fields["mac"]);

               $pdf->displayLine('<b><i>'.$LANG["networking"][60].' / '.$LANG["networking"][61].' / '.
                                 $LANG["networking"][59].' :</i></b> '.$netport->fields["netmask"].' / '.
                                 $netport->fields["subnet"].' / '.$netport->fields["gateway"]);

               $query = "SELECT *
                         FROM `glpi_networkports_vlans`
                         WHERE `networkports_id` = '".$netport->fields['id']."'";

               $result2 = $DB->query($query);
               if ($DB->numrows($result2) > 0) {
                  $line = '';
                  while ($a_line=$DB->fetch_array($result2)) {
                     $line .= (empty($line) ? '' : ', ').
                              Html::clean(Dropdown::getDropdownName("glpi_vlans", $a_line["vlans_id"]));
                  }
                  $pdf->displayText('<b><i>'.$LANG['networking'][56].' :</i></b>', $line, 1);
               }

               if ($netport->fields["networkinterfaces_id"]) {
                  $pdf->displayText(
                     '<b><i>'.$LANG["common"][65].' :</i></b> ',
                     Html::clean(Dropdown::getDropdownName("glpi_networkinterfaces",
                                                          $netport->fields["networkinterfaces_id"])),
                     1);
               }

               $contact = new NetworkPort;
               $netport2 = new NetworkPort;

               $add = $LANG["connect"][1];
               if ($cid = $contact->getContact($netport->fields["id"])) {
                  if ($netport2->getfromDB($cid)
                      && ($device2 = getItemForItemtype($netport2->fields["itemtype"]))) {
                     if ($device2->getFromDB($netport2->fields["items_id"])) {
                        $add = $netport2->getName().' '.$LANG['networking'][25].' '.
                               $device2->getName().' ('.$device2->getTypeName().')';
                     }
                  }
               }
               $pdf->displayText('<b><i>'.$LANG["networking"][17].' :</i></b> ', $add, 1);
            } // each port
         } // Found
      } // Query
      $pdf->displaySpace();
   }
}