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
 @copyright Copyright (c) 2009-2020 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfPhone extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Phone());
   }

   static function getFields(){
      return array_merge(parent::getFields(), [
         'power' => 'Power Supply',
         'brand' => 'Brand',
         'quantity' => 'Number of lines',
         'flags' => 'Flags']);
   }

   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Appliance_Item$1']);
      unset($onglets['Impact$1']);
      return $onglets;
   }

   static function displayLines($pdf, $lines){
      if (null !== $ports = $lines['flags']){
         unset($lines['flags']);
         parent::displayLines($pdf, $lines);
         $pdf->setColumnsSize(100);
         $pdf->displayline($ports);
      } else {
         parent::displayLines($pdf, $lines);
      }
   }

   static function defineField($pdf, $item, $field){
      if(isset(parent::getFields()[$field])){
         return PluginPdfCommon::mainField($pdf, $item, $field);
      } else {
         switch($field) {
            case 'power':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Power supply').'</i></b>',
                                       Dropdown::getYesNo($item->fields['phonepowersupplies_id']));
            case 'brand':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Brand').'</i></b>', $item->fields['brand']);
            case 'quantity':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), _x('quantity', 'Number of lines').'</i></b>',
                                       $item->fields['number_line']);
            case 'flags':
               $opts = ['have_headset' => __('Headset'),
                        'have_hp'      => __('Speaker')];
               foreach ($opts as $key => $val) {
                  if (!$item->fields[$key]) {
                     unset($opts[$key]);
                  }
               }
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Flags').'</i></b>',
                                       (count($opts) ? implode(', ',$opts) : __('None')));
         }
      }
   }

   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         default :
            return false;
      }
      return true;
   }
}