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
 @copyright Copyright (c) 2009-2021 PDF plugin team
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

   static function getFields(){
      return array_merge(parent::getFields(), [
         'network' => 'Network',
         'memory_size' => 'Memory',
         'init_pages_counter' => 'Initial page counter',
         'last_pages_counter' => 'Current counter of pages',
         'ports' => 'Ports'
      ]);
   }

   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Certificate_Item$1']);
      unset($onglets['Impact$1']);
      unset($onglets['Appliance_Item$1']);
      return $onglets;
   }

   static function displayLines($pdf, $lines){
      if (null !== $ports = $lines['ports']){
         unset($lines['ports']);
         parent::displayLines($pdf, $lines);
         $pdf->setColumnsSize(100);
         $pdf->displayline($ports);
      } else {
         parent::displayLines($pdf, $lines);
      }
   }

   static function defineField($pdf, $item, $field){
      $print = static::getFields()[$field];
      if(isset(parent::getFields()[$field])){
         return PluginPdfCommon::mainField($pdf, $item, $field);
      } else {
         switch($field) {
            case 'network':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                                       Toolbox::stripTags(Dropdown::getDropdownName('glpi_networks',
                                                                              $printer->fields['networks_id'])));
            case 'memory_size':
            case 'init_pages_counter':
            case 'last_pages_counter':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __($print).'</i></b>',
                                       $printer->fields[$field]);
            case 'ports':
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

               return '<b><i>'.sprintf(__('%1$s: %2$s'),
                                       _n('Port', 'Ports', count($opts)).'</i></b>',
                                       (count($opts) ? implode(', ',$opts) : __('None')));

         }
      }
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