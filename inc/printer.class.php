<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2013 by the pdf Development Team.

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

class PluginPdfPrinter extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Printer());
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Printer $printer) {

       PluginPdfCommon::mainTitle($pdf, $printer);

       PluginPdfCommon::mainLine($pdf, $printer, 'name-status');
       PluginPdfCommon::mainLine($pdf, $printer, 'location-type');
       PluginPdfCommon::mainLine($pdf, $printer, 'tech-manufacturer');
       PluginPdfCommon::mainLine($pdf, $printer, 'group-model');
       PluginPdfCommon::mainLine($pdf, $printer, 'usernum-serial');
       PluginPdfCommon::mainLine($pdf, $printer, 'user-otherserial');


      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('User').'</i></b>',
                          getUserName($printer->fields['users_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Management type').'</i></b>',
                          ($printer->fields['is_global']
                           ? __('Global management') : __('Unit management'))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_groups',
                                                                 $printer->fields['groups_id']))),
        '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                         Html::clean(Dropdown::getDropdownName('glpi_networks',
                                                                $printer->fields['networks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Domain').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_domains',
                                                                $printer->fields['domains_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Initial page counter').'</i></b>',
                          $printer->fields['init_pages_counter']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Memory').'</i></b>',
                          $printer->fields['memory_size']));

      $opts = array('have_serial'   => __('Serial'),
                    'have_parallel' => __('Parallel'),
                    'have_usb'      => __('USB'),
                    'have_ethernet' => __('Ethernet'),
                    'have_wifi'     => __('Wiifi'));

      foreach ($opts as $key => $val) {
         if (!$printer->fields[$key]) {
            unset($opts[$key]);
         }
      }

      $pdf->setColumnsSize(100);
      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), _n('Port', 'Ports', count($opts)).'</i></b>',
                                         (count($opts) ? implode(', ',$opts) : __('None'))));

      PluginPdfCommon::mainLine($pdf, $printer, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
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