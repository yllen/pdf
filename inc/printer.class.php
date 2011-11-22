<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

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

class PluginPdfPrinter extends PluginPdfCommon {


   function __construct(Printer $obj=NULL) {

      $this->obj = ($obj ? $obj : new Printer());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Printer $printer) {
      global $LANG;

      $ID = $printer->getField('id');

      $pdf->setColumnsSize(50,50);
      $col1 = '<b>'.$LANG['common'][2].' '.$printer->fields['id'].'</b>';
      $col2 = $LANG['common'][26].' : '.Html::convDateTime($printer->fields['date_mod']);
      if(!empty($printer->fields['template_name'])) {
         $col2 .= ' ('.$LANG['common'][13].' : '.$printer->fields['template_name'].')';
      }
      $pdf->displayTitle($col1, $col2);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][16].' :</i></b> '.$printer->fields['name'],
         '<b><i>'.$LANG['state'][0].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_states', $printer->fields['states_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][15].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_locations', $printer->fields['locations_id'])),
         '<b><i>'.$LANG['common'][17].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_printertypes',
                                                 $printer->fields['printertypes_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($printer->fields['users_id_tech']),
         '<b><i>'.$LANG['common'][5].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                 $printer->fields['manufacturers_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][109].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $printer->fields['groups_id_tech'])),
         '<b><i>'.$LANG['common'][22].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_printermodels',
                                                 $printer->fields['printermodels_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][21].' :</i></b> '.$printer->fields['contact_num'],
         '<b><i>'.$LANG['common'][19].' :</i></b> '.$printer->fields['serial']);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][18].' :</i></b> '.$printer->fields['contact'],
         '<b><i>'.$LANG['common'][20].' :</i></b> '.$printer->fields['otherserial']);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($printer->fields['users_id']),
         '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
            ($printer->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][35].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $printer->fields['groups_id'])),
        '<b><i>'.$LANG['setup'][88].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_networks', $printer->fields['networks_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['setup'][89].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_domains', $printer->fields['domains_id'])),
         '<b><i>'.$LANG['printers'][30].' :</i></b> '.$printer->fields['init_pages_counter']);

      $pdf->displayLine(
         '<b><i>'.$LANG['devices'][6].' :</i></b> '.$printer->fields['memory_size']);

      $opts = array(
         'have_serial'   => $LANG['printers'][14],
         'have_parallel' => $LANG['printers'][15],
         'have_usb'      => $LANG['printers'][27],
         'have_ethernet' => $LANG['printers'][28],
         'have_wifi'     => $LANG['printers'][29],
      );
      foreach ($opts as $key => $val) {
         if (!$printer->fields[$key]) {
            unset($opts[$key]);
         }
      }

      $pdf->setColumnsSize(100);
      $pdf->displayLine('<b><i>'.$LANG['printers'][18].' : </i></b>'.
                        (count($opts) ? implode(', ',$opts) : $LANG['common'][49]));

      $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $printer->fields['comment']);

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

          case 'Computer_Item$1' :
            PluginPdfComputer_Item::pdfForItem($pdf, $item);
            break;

         case 'Cartridge$1' :
            PluginPdfCartridge::pdfForPrinter($pdf, $item, false);
            PluginPdfCartridge::pdfForPrinter($pdf, $item, true);
            break;
         default :
            return false;
      }
      return true;
   }
}