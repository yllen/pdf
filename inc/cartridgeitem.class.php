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
 @copyright Copyright (c) 2018-2019 PDF plugin team
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


   function defineAllTabs($options=[]) {

      $onglets = parent::defineAllTabs($options);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, CartridgeItem $cartitem){

      $dbu = new DbUtils();

      PluginPdfCommon::mainTitle($pdf, $cartitem);

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>', $cartitem->fields['name']),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Type').'</i></b>',
                             Html::clean(Dropdown::getDropdownName('glpi_cartridgeitemtypes',
                                                                   $cartitem->fields['cartridgeitemtypes_id']))));
      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Reference').'</i></b>', $cartitem->fields['ref']),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Manufacturer').'</i></b>',
                             Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                                   $cartitem->fields['manufacturers_id']))));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Technician in charge of the hardware').'</i></b>',
                             $dbu->getUserName($cartitem->fields['users_id_tech'])),
            '<b><i>'.sprintf(__('%1$s: %2$s'),  __('Group in charge of the hardware').'</i></b>',
                             Dropdown::getDropdownName('glpi_groups',
                                                       $cartitem->fields['groups_id_tech'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Stock location').'</i></b>',
                             Dropdown::getDropdownName('glpi_locations',
                                                       $cartitem->fields['locations_id'])),
            '<b><i>'.sprintf(__('%1$s: %2$s'),  __('Alert threshold').'</i></b>',
                             $cartitem->getField('alarm_threshold')));

      PluginPdfCommon::mainLine($pdf, $cartitem, 'comment');

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'Cartridge$1' :
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, false);
            PluginPdfCartridge::pdfForCartridgeItem($pdf, $item, true);
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
      if (!$number) {
         $pdf->displayTitle(_('No printel model associated', 'pdf'));
      } else {
         $pdf->displayTitle("<b>"._n('Printer model', 'Printer models', $number)."</b>");

         foreach ($datas as $data) {
            $pdf->displayLine($data['name']);
         }
      }
   }
}