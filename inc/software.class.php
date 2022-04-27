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


class PluginPdfSoftware extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Software());
   }

   static function getFields(){
      return [
         'name' => 'Name',
         'publisher' => 'Publisher',
         'location' => 'Location',
         'category' => 'Category',
         'tech' => 'Technician in charge of hardware',
         'ticket' => 'Associable to a ticket',
         'techgroup' => 'Group in charge of the hardware',
         'user' => 'User',
         'group' => 'Group',
         'is_update' => 'Upgrade',
         'comments' => 'Comments'
      ];
   }

   static function defineField($pdf, $software, $field){
      if(isset(parent::getFields()[$field])){
         return PluginPdfCommon::mainField($pdf, $software, $field);
      } else {
         switch($field) {
            case 'publisher':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Publisher').'</i></b>',
                     Toolbox::stripTags(Dropdown::getDropdownName('glpi_manufacturers',
                                                            $software->fields['manufacturers_id'])));
            case 'category':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Category').'</i></b>',
                     Dropdown::getDropdownName('glpi_softwarecategories',
                                                $software->fields['softwarecategories_id']));
            case 'ticket':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Associable to a ticket').'</i></b>',
                     ($software->fields['is_helpdesk_visible'] ?__('Yes'):__('No')));
            case 'is_update':
               if ($software->fields['softwares_id'] > 0) {
                  $col2 = '<b><i> '.__('from').' </i></b> '.
                           Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwares',
                                                               $software->fields['softwares_id']));
               } else {
                  $col2 = '';
               }
         
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Upgrade').'</i></b>',
                                 ($software->fields['is_update']?__('Yes'):__('No')), $col2);
         }
      }
   }

   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Appliance_Item$1']);
      unset($onglets['Impact$1']);
      return $onglets;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'SoftwareVersion$1' :
            PluginPdfSoftwareVersion::pdfForSoftware($pdf, $item);
            break;

         case 'SoftwareLicense$1' :
            $infocom = isset($_REQUEST['item']['Infocom$1']);
            PluginPdfSoftwareLicense::pdfForSoftware($pdf, $item, $infocom);
            break;

         case 'Item_SoftwareVersion$1' :
            PluginPdfItem_SoftwareVersion::pdfForSoftware($pdf, $item);
            break;

         Case 'Domain_Item$1' :
            PluginPdfDomain_Item::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}