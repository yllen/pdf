<?php
/**
 * @version $Id: setup.php 378 2014-06-08 15:12:45Z yllen $
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
 @copyright Copyright (c) 2018-2021 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfCartridgeItem extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new CartridgeItem());
   }

   static function getFields(){
      $fields = array_merge(parent::getFields(), [
         'reference' => 'Reference',
         'stock_location' => 'Stock location',
         'location' => 'Stock location',
         'alarm_threshold' => 'Alert threshold']);
      $remove = ['type', 'tech', 'techgroup', 'model', 'contactnum', 'serial', 'contact', 'otherserial', 'user', 'management', 'group'];
      foreach($remove as $removed){
         unset($fields[$removed]);
      }
      return $fields;
   }

   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      return $onglets;
   }

   static function defineField($pdf, $item, $field){
      if(isset(parent::getFields()[$field])){
         return PluginPdfCommon::mainField($pdf, $item, $field);
      } else {
         switch($field) {
            case 'reference':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Reference').'</i></b>', $item->fields['ref']);
            case 'stock_location':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Stock location').'</i></b>',
                                       Dropdown::getDropdownName('glpi_locations',
                                                               $item->fields['locations_id']));
            case 'alarm_threshold':
               return '<b><i>'.sprintf(__('%1$s: %2$s'),  __('Alert threshold').'</i></b>',
                                       $item->getField('alarm_threshold'));
         }
      }
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'Cartridge$1' :
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'new');
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'used');
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, 'old');
            break;

         case 'CartridgeItem_PrinterModel$1' :
            self::pdfForPrinterModel($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }


   static function pdfForPrinterModel(PluginPdfSimplePDF $pdf, CartridgeItem $item) {

      $instID = $item->getField('id');
      if (!$item->can($instID, READ)) {
         return false;
      }

      $iterator = CartridgeItem_PrinterModel::getListForItem($item);
      $number = count($iterator);

      while ($data = $iterator->next()) {
         $datas[$data["linkid"]]  = $data;
      }

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Printer model', 'Printer models', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(_('No printel model associated', 'pdf'));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         foreach ($datas as $data) {
            $pdf->displayLine($data['name']);
         }
      }
   }
}