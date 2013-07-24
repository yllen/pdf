<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2012 by the pdf Development Team.

 https://forge.indepnet.net/projects/pdf
 -------------------------------------------------------------------------

 LICENSE

 This file is part of pdf.

 pdf is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 pdf is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with pdf. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfNetworkEquipment extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new NetworkEquipment());
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, NetworkEquipment $item) {
      global $LANG;

      PluginPdfCommon::mainTitle($pdf, $item);

      PluginPdfCommon::mainLine($pdf, $item, 'name-status');
      PluginPdfCommon::mainLine($pdf, $item, 'location-type');
      PluginPdfCommon::mainLine($pdf, $item, 'tech-manufacturer');
      PluginPdfCommon::mainLine($pdf, $item, 'group-model');
      PluginPdfCommon::mainLine($pdf, $item, 'usernum-serial');
      PluginPdfCommon::mainLine($pdf, $item, 'user-otherserial');



      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('User').'</i></b>',
                          getUserName($item->fields['users_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_networks',
                                                                $item->fields['networks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_groups',
                                                                $item->fields['groups_id']))),
         '<b><i>'.$LANG['setup'][71].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_networkequipmentfirmwares', $item->fields['networkequipmentfirmwares_id'])));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Domain').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_domains',
                                                                $item->fields['domains_id']))),
                        '<b><i>'.$LANG['networking'][5].' :</i></b> '.$item->fields['ram']);

      $pdf->displayLine('<b><i>'.$LANG['networking'][14].' :</i></b> '.$item->fields['ip'],
                        '<b><i>'.$LANG['networking'][15].' :</i></b> '.$item->fields['mac']);

      $pdf->setColumnsSize(100);
      PluginPdfCommon::mainLine($pdf, $item, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         default :
            return false;
      }
      return true;
   }
}