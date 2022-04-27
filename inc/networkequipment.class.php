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


class PluginPdfNetworkEquipment extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new NetworkEquipment());
   }

   static function getFields(){
      $fields = array_merge(parent::getFields(), [
                           'network' => 'Network',
                           'memory' => 'Memory']);
      unset($fields['management']);
      return $fields;
   }

   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['NetworkName$1']);
      unset($onglets['Certificate_Item$1']);
      unset($onglets['Impact$1']);
      unset($onglets['Appliance_Item$1']);
      return $onglets;
   }

   static function defineField($pdf, $item, $field){
      if(isset(parent::getFields()[$field])){
         return PluginPdfCommon::mainField($pdf, $item, $field);
      } else {
         switch($field) {
            case 'network':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                                       Toolbox::stripTags(Dropdown::getDropdownName('glpi_networks',
                                                                             $item->fields['networks_id'])));
            case 'memory':
               return '<b><i>'.sprintf(__('%1$s: %2$s'),
                                       sprintf(__('%1$s (%2$s)'), __('Memory'),__('Mio')).'</i></b>',
                                               $item->fields['ram']);
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