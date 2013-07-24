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

class PluginPdfPeripheral extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new Peripheral());
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Peripheral $item) {
      global $LANG;

      PluginPdfCommon::mainTitle($pdf, $item);

      PluginPdfCommon::mainLine($pdf, $item, 'name-status');
      PluginPdfCommon::mainLine($pdf, $item, 'location-type');
      PluginPdfCommon::mainLine($pdf, $item, 'tech-manufacturer');

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][109].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id_tech'])),
         '<b><i>'.$LANG['peripherals'][18].' :</i></b> '.$item->fields['brand']);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
         '<b><i>'.$LANG['common'][22].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_peripheralmodels',
                                                 $item->fields['peripheralmodels_id'])));

      $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                        '<b><i>'.__('Serial number').' :</i></b> '.$item->fields['serial']);

      PluginPdfCommon::mainLine($pdf, $item, 'user-otherserial');


      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_groups',
                                                                $item->fields['groups_id']))),
         '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
            ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

      $pdf->setColumnsSize(100);
      PluginPdfCommon::mainLine($pdf, $item, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
          case 'Computer_Item$1' :
            PluginPdfComputer_Item::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}