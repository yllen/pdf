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


class PluginPdfComputer extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Computer());
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Lock$1']);
      unset($onglets['Appliance_Item$1']);
      unset($onglets['Certificate_Item$1']);
      unset($onglets['Impact$1']);
      unset($onglets['GLPI\Socket$1']);
      unset($onglets['Item_RemoteManagement$1']);
      unset($onglets['DatabaseInstance$1']);
      unset($onglets['RuleMatchedLog$1']);
      unset($onglets['Glpi\Socket$1']);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Computer $computer){

      $dbu = new DbUtils();

      PluginPdfCommon::mainTitle($pdf, $computer);

      PluginPdfCommon::mainLine($pdf, $computer, 'name-status');
      PluginPdfCommon::mainLine($pdf, $computer, 'location-type');
      PluginPdfCommon::mainLine($pdf, $computer, 'tech-manufacturer');
      PluginPdfCommon::mainLine($pdf, $computer, 'group-model');
      PluginPdfCommon::mainLine($pdf, $computer, 'contactnum-serial');
      PluginPdfCommon::mainLine($pdf, $computer, 'contact-otherserial');


      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('User').'</i></b>',
                          $dbu->getUserName($computer->fields['users_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Network').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_networks',
                                                                       $computer->fields['networks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Group').'</i></b>',
                          Dropdown::getDropdownName('glpi_groups',
                                                    $computer->fields['groups_id'])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('UUID').'</i></b>', $computer->fields['uuid']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Update Source').'</i></b>',
                          Dropdown::getDropdownName('glpi_autoupdatesystems',
                                                     $computer->fields['autoupdatesystems_id'])));

      PluginPdfCommon::mainLine($pdf, $computer, 'comment');

      $pdf->displaySpace();
   }


   static function pdfOperatingSystem(PluginPdfSimplePDF $pdf, Computer $computer) {

      $ID = $computer->getField('id');
      if (!$computer->can($ID, READ)) {
         return false;
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.Toolbox::ucfirst(OperatingSystem::getTypeName(2)).'</b>');

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_operatingsystems',
                                                                       $computer->fields['operatingsystems_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Version').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_operatingsystemversions',
                                                                       $computer->fields['operatingsystemversions_id']))));
      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Architecture').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_operatingsystemarchitectures',
                                                                       $computer->fields['operatingsystemarchitectures_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Service pack').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_operatingsystemservicepacks',
                                                                       $computer->fields['operatingsystemservicepacks_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Kernel version').'</i></b>',
                          $computer->fields['os_kernel_version']),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Product ID').'</i></b>',
                          $computer->fields['os_licenseid']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Serial number').'</i></b>',
                          $computer->fields['os_license_number']));
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'ComputerVirtualMachine$1' :
            PluginPdfComputerVirtualMachine::pdfForComputer($pdf, $item);
            break;

         case 'ComputerAntivirus$1' :
            PluginPdfComputerAntivirus::pdfForComputer($pdf, $item);
            break;

         case 'RegistryKey$1' :
            PluginPdfRegistryKey::pdfForComputer($pdf, $item);
            break;

         case 'Computer_Item$1' :
            PluginPdfComputer_Item::pdfForComputer($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}