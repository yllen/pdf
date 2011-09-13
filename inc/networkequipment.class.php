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

class PluginPdfNetworkEquipment extends PluginPdfCommon {


   function __construct(NetworkEquipment $obj=NULL) {

      $this->obj = ($obj ? $obj : new NetworkEquipment());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, NetworkEquipment $item) {
      global $LANG;

      $pdf->setColumnsSize(50,50);
      $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
      $col2 = $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod']);

      if (!empty($printer->fields['template_name'])) {
         $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
      }
      $pdf->displayTitle($col1, $col2);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
         '<b><i>'.$LANG['state'][0].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][15].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
         '<b><i>'.$LANG['common'][17].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_networkequipmenttypes', $item->fields['networkequipmenttypes_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
         '<b><i>'.$LANG['common'][5].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                    $item->fields['manufacturers_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][109].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id_tech'])),
         '<b><i>'.$LANG['common'][22].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_networkequipmentmodels',
                                                    $item->fields['networkequipmentmodels_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
                        '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

      $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
         '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
         '<b><i>'.$LANG['setup'][88].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_networks', $item->fields['networks_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][35].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
         '<b><i>'.$LANG['setup'][71].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_networkequipmentfirmwares', $item->fields['networkequipmentfirmwares_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['setup'][89].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_domains', $item->fields['domains_id'])),
                        '<b><i>'.$LANG['networking'][5].' :</i></b> '.$item->fields['ram']);

      $pdf->displayLine('<b><i>'.$LANG['networking'][14].' :</i></b> '.$item->fields['ip'],
                        '<b><i>'.$LANG['networking'][15].' :</i></b> '.$item->fields['mac']);

      $pdf->setColumnsSize(100);
      $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}