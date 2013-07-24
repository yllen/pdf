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


class PluginPdfComputer extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Computer());
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['OcsLink$1']); // TODO add method to print OCS
      unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Computer $computer){
      global $DB;

      PluginPdfCommon::mainTitle($pdf, $computer);

      PluginPdfCommon::mainLine($pdf, $computer, 'name-status');
      PluginPdfCommon::mainLine($pdf, $computer, 'location-type');
      PluginPdfCommon::mainLine($pdf, $computer, 'tech-manufacturer');
      PluginPdfCommon::mainLine($pdf, $computer, 'group-model');
      PluginPdfCommon::mainLine($pdf, $computer, 'usernum-serial');
      PluginPdfCommon::mainLine($pdf, $computer, 'user-otherserial');


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
//TODO plugin OCS
/*
      $pdf->setColumnsSize(100);
      if ($computer->fields['is_ocs_import'] && Session::haveRight("view_ocsng","r")) {
         $tmp = '';
         $query = "SELECT *
                   FROM `glpi_ocslinks`
                   WHERE `computers_id` = '$ID'";

         $result = $DB->query($query);
         if ($DB->numrows($result)==1) {
            $dataocs = $DB->fetch_array($result);
            $tmp .= '<b>'.$LANG['ocsng'][14].'</b> : '.Html::convDateTime($dataocs["last_ocs_update"]).', ';
            $tmp .= '<b>'.$LANG['ocsng'][13].'</b> : '.Html::convDateTime($dataocs["last_update"]).', ';
         }

         $tmp .= '<b>'.$LANG['ocsng'][6].'</b> : '.
                 ($computer->getField('use_auto_update') ? $LANG['choice'][1] : $LANG['choice'][0]);

         $pdf->displayText('<b>'.$LANG['ocsng'][0].'</b> : ', $tmp);
      }
*/

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('UUID').'</i></b>', $computer->fields['uuid']));

      PluginPdfCommon::mainLine($pdf, $computer, 'comment');

      $pdf->displaySpace();
   }


   static function pdfDevice(PluginPdfSimplePDF $pdf, Computer $computer) {
      global $DB;

      $devtypes = Computer_Device::getDeviceTypes();

      $ID = $computer->getField('id');
      if (!$computer->can($ID, 'r')) {
         return false;
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.Toolbox::ucfirst(_n('Component', 'Components', 2)).'</b>');

      $pdf->setColumnsSize(3,14,42,41);

      foreach ($devtypes as $itemtype) {
         $device = new $itemtype;

         $specificities = $device->getSpecifityLabel();
         $specif_fields = array_keys($specificities);
         $specif_text   = implode(',',$specif_fields);
         if (!empty($specif_text)) {
            $specif_text=" ,".$specif_text." ";
         }

         $linktable = getTableForItemType('Computer_'.$itemtype);
         $fk        = getForeignKeyFieldForTable(getTableForItemType($itemtype));

         $query = "SELECT count(*) AS NB, `id`, `".$fk."`".$specif_text."
                   FROM `".$linktable."`
                   WHERE `computers_id` = '".$ID."'
                   GROUP BY `".$fk."`".$specif_text;

         foreach ($DB->request($query) as $data) {

            if ($device->getFromDB($data[$fk])) {

               $spec = $device->getFormData();
               $col4 = '';
               if (isset($spec['label']) && count($spec['label'])) {
                  $colspan = (60/count($spec['label']));
                  foreach ($spec['label'] as $i => $label) {
                     if (isset($spec['value'][$i])) {
                        $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $spec['label'][$i].'</i></b>',
                                                  $spec['value'][$i]." ");
                     } else {
                        $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $spec['label'][$i].'</i></b>',
                                                  $data['specificity']." ");
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
         case 'DeviceProcessor$1' :
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

         case 'Document$1' :
            PluginPdfDocument::pdfForItem($pdf, $item);
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