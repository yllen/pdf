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
 @copyright Copyright (c) 2009-2016 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfComputer extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Computer());
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['OcsLink$1']); // TODO add method to print OCS
      unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
      unset($onglets['Change_Item$1']); // TODO add method to print linked Changes
      unset($onglets['Lock$1']); // TODO add method to print Lock fields
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Computer $computer){
      global $DB;

      PluginPdfCommon::mainTitle($pdf, $computer);

      PluginPdfCommon::mainLine($pdf, $computer, 'name-status');
      PluginPdfCommon::mainLine($pdf, $computer, 'location-type');
      PluginPdfCommon::mainLine($pdf, $computer, 'tech-manufacturer');
      PluginPdfCommon::mainLine($pdf, $computer, 'group-model');
      PluginPdfCommon::mainLine($pdf, $computer, 'contactnum-serial');
      PluginPdfCommon::mainLine($pdf, $computer, 'contact-otherserial');


      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('User').'</i></b>',
                          getUserName($computer->fields['users_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                           Html::clean(Dropdown::getDropdownName('glpi_networks',
                                                                 $computer->fields['networks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_groups',
                                                                $computer->fields['groups_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Service pack').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_operatingsystemservicepacks',
                                                                $computer->fields['operatingsystemservicepacks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Domain').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_domains',
                                                                $computer->fields['domains_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Version of the operating system').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_operatingsystemversions',
                                                                $computer->fields['operatingsystemversions_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Operating system').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                                                $computer->fields['operatingsystems_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Serial of the operating system').'</i></b>',
                          $computer->fields['os_license_number']));


      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Product ID of the operating system').'</i></b>',
                          $computer->fields['os_licenseid']),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Update source').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_autoupdatesystems',
                                                                $computer->fields['autoupdatesystems_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('UUID').'</i></b>', $computer->fields['uuid']));

      PluginPdfCommon::mainLine($pdf, $computer, 'comment');

      $pdf->displaySpace();
   }


   static function pdfDevice(PluginPdfSimplePDF $pdf, Computer $computer) {
      global $DB;

      $devtypes = Item_Devices::getDeviceTypes();

      $ID = $computer->getField('id');
      if (!$computer->can($ID, READ)) {
         return false;
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.Toolbox::ucfirst(_n('Component', 'Components', 2)).'</b>');

      $pdf->setColumnsSize(3,14,42,41);

      foreach ($devtypes as $itemtype) {

         $devicetypes   = new $itemtype();
         $specificities = $devicetypes->getSpecificities();
         $specif_fields = array_keys($specificities);
         $specif_text   = implode(',',$specif_fields);
         if (!empty($specif_text)) {
            $specif_text=" ,".$specif_text." ";
         }
         $associated_type  = str_replace('Item_', '', $itemtype);
         $linktable        = getTableForItemType($itemtype);
         $fk               = getForeignKeyFieldForTable(getTableForItemType($associated_type));

         $query = "SELECT count(*) AS NB, `id`, `".$fk."`".$specif_text."
                   FROM `".$linktable."`
                   WHERE `items_id` = '".$ID."'
                   AND `itemtype` = 'Computer'
                   GROUP BY `".$fk."`".$specif_text;

         $device = new $associated_type();
         foreach ($DB->request($query) as $data) {

            if ($device->getFromDB($data[$fk])) {

               $spec = $device->getAdditionalFields();
               $col4 = '';
               if (count($spec)) {
                  $colspan = (60/count($spec));
                  foreach ($spec as $i => $label) {
                     if (isset($device->fields[$label["name"]])
                         && !empty($device->fields[$label["name"]])) {

                        if (($label["type"] == "dropdownValue")
                            && ($device->fields[$label["name"]] != 0)) {
                           $table = getTableNameForForeignKeyField($label["name"]);
                           $value = Dropdown::getDropdownName($table,
                                                              $device->fields[$label["name"]]);
                            $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $label["label"].'</i></b>',
                                                     Html::clean($value)." ");
                        } else {
                           $value = $device->fields[$label["name"]];
                           if ($label["type"] == "bool") {
                               if ($value == 1) {
                                  $value = __('Yes');
                               } else {
                                  $value = __('No');
                               }
                           }
                           $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $label["label"].'</i></b>',
                                                     $value." ");
                        }
                     } else if (isset($device->fields[$label["name"]."_default"])
                                && !empty($device->fields[$label["name"]."_default"])) {
                        $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $label["label"].'</i></b>',
                                                  $device->fields[$label["name"]."_default"]." ");
                     }
                  }
               }
               $pdf->displayLine($data['NB'], $device->getTypeName(), $device->getName(), $col4);
            }
         }
      }

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'Item_Devices$1' :
            self::pdfDevice($pdf, $item);
            break;

         case 'ComputerDisk$1' :
            PluginPdfComputerDisk::pdfForComputer($pdf, $item);
            break;

         case 'Computer_SoftwareVersion$1' :
            PluginPdfComputer_SoftwareVersion::pdfForComputer($pdf, $item);
            break;

         case 'Computer_Item$1' :
            PluginPdfComputer_Item::pdfForComputer($pdf, $item);
            break;

         case 'ComputerVirtualMachine$1' :
            PluginPdfComputerVirtualMachine::pdfForComputer($pdf, $item);
            break;

         case 'RegistryKey$1' :
            PluginPdfRegistryKey::pdfForComputer($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}