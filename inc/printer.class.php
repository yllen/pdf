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

class PluginPdfPrinter extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Printer());
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Certificate_Item$1']);
      unset($onglets['Impact$1']);
      unset($onglets['Appliance_Item$1']);
      unset($onglets['PrinterLog$0']);
      unset($onglets['Glpi\Socket$1']);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Printer $printer) {

      $dbu = new DbUtils();

       PluginPdfCommon::mainTitle($pdf, $printer);

       PluginPdfCommon::mainLine($pdf, $printer, 'name-status');
       PluginPdfCommon::mainLine($pdf, $printer, 'location-type');
       PluginPdfCommon::mainLine($pdf, $printer, 'tech-manufacturer');
       PluginPdfCommon::mainLine($pdf, $printer, 'group-model');
       PluginPdfCommon::mainLine($pdf, $printer, 'contactnum-serial');
       PluginPdfCommon::mainLine($pdf, $printer, 'contact-otherserial');
       PluginPdfCommon::mainLine($pdf, $printer, 'user-management');

       $pdf->displayLine(
          '<b><i>'.sprintf(__('%1$s: %2$s'), __('Sysdescr').'</i></b>',
                           $printer->fields['sysdescr']),
          '<b><i>'.sprintf(__('%1$s: %2$s'), __('User').'</i></b>',
                           $dbu->getUserName($printer->fields['users_id'])));

       $pdf->displayLine(
          '<b><i>'.sprintf(__('%1$s: %2$s'), __('Management type').'</i></b>',
                           ($printer->fields['is_global']?__('Global management')
                                                         :__('Unit management'))),
          '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                           Toolbox::stripTags(Dropdown::getDropdownName('glpi_networks',
                                                                $printer->fields['networks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Dropdown::getDropdownName('glpi_groups', $printer->fields['groups_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('UUID').'</i></b>',
                          $printer->fields['uuid']));


      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Memory').'</i></b>',
                          $printer->fields['memory_size']),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Initial page counter').'</i></b>',
                          $printer->fields['init_pages_counter']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Current counter of pages').'</i></b>',
                          $printer->fields['last_pages_counter']));

      $opts = ['have_serial'   => __('Serial'),
               'have_parallel' => __('Parallel'),
               'have_usb'      => __('USB'),
               'have_ethernet' => __('Ethernet'),
               'have_wifi'     => __('Wifi')];

      foreach ($opts as $key => $val) {
         if (!$printer->fields[$key]) {
            unset($opts[$key]);
         }
      }

      $pdf->setColumnsSize(100);
      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'),
                                         _n('Port', 'Ports', count($opts)).'</i></b>',
                                         (count($opts) ? implode(', ',$opts) : __('None'))));

      PluginPdfCommon::mainLine($pdf, $printer, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
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