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


class PluginPdfMonitor extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Monitor());
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Impact$1']);
      unset($onglets['Appliance_Item$1']);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Monitor $item) {

      PluginPdfCommon::mainTitle($pdf, $item);

      PluginPdfCommon::mainLine($pdf, $item, 'name-status');
      PluginPdfCommon::mainLine($pdf, $item, 'location-type');
      PluginPdfCommon::mainLine($pdf, $item, 'tech-manufacturer');
      PluginPdfCommon::mainLine($pdf, $item, 'group-model');
      PluginPdfCommon::mainLine($pdf, $item, 'contactnum-serial');
      PluginPdfCommon::mainLine($pdf, $item, 'contact-otherserial');
      PluginPdfCommon::mainLine($pdf, $item, 'user-management');

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Size').'</i></b>',
                          sprintf(__('%1$s %2$s'), $item->fields['size'], '"')));

      $opts = ['have_micro'         => __('Microphone'),
               'have_speaker'       => __('Speakers'),
               'have_subd'          => __('Sub-D'),
               'have_bnc'           => __('BNC'),
               'have_dvi'           => __('DVI'),
               'have_pivot'         => __('Pivot'),
               'have_hdmi'          => __('HDMI'),
               'have_displayport'   => __('DisplayPort')];
      foreach ($opts as $key => $val) {
         if (!$item->fields[$key]) {
            unset($opts[$key]);
         }
      }
      $pdf->setColumnsSize(100);
      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Flags').'</i></b>',
                          (count($opts) ? implode(', ',$opts) : __('None'))));

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