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


class PluginPdfSoftwareLicense extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new SoftwareLicense());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, SoftwareLicense $license, $main=true, $cpt=true) {

      $ID = $license->getField('id');

      $pdf->setColumnsSize(100);
      $entity = '';
      if (Session::isMultiEntitiesMode() && !$main) {
         $entity = ' ('.Dropdown::getDropdownName('glpi_entities', $license->fields['entities_id']).')';
      }
      $pdf->displayTitle('<b><i>'.sprintf(__('%1$s: %2$s'), __('ID')."</i>", $ID."</b>".$entity));

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), Software::getTypeName(1).'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwares',
                                                                       $license->fields['softwares_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'),__('Type').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                                       $license->fields['softwarelicensetypes_id']))));

      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>',
                                         $license->fields['name']),
                        '<b><i>'.sprintf(__('%1$s: %2$s'),__('Serial number').'</i></b>',
                                         $license->fields['serial']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Purchase version').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwareversions',
                                                                       $license->fields['softwareversions_id_buy']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Inventory number').'</i></b>',
                          $license->fields['otherserial']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Version in use').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwareversions',
                                                                       $license->fields['softwareversions_id_use']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Expiration').'</i></b>',
                          Html::convDate($license->fields['expire'])));

      $col2 = '';
      if ($cpt) {
         $col2 = '<b><i>'.sprintf(__('%1$s: %2$s'),__('Affected computers').'</i></b>',
                                  Item_SoftwareLicense::countForLicense($ID));
      }
      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), _x('quantity', 'Number').'</i></b>',
                          (($license->fields['number'] > 0)?$license->fields['number']
                                                           :__('Unlimited'))),
         $col2);

      $pdf->setColumnsSize(100);
      PluginPdfCommon::mainLine($pdf, $license, 'comment');

      if ($main) {
         $pdf->displaySpace();
      }
   }


   static function pdfForSoftware(PluginPdfSimplePDF $pdf, Software $software, $infocom=false) {
      global $DB;

      $sID     = $software->getField('id');
      $license = new SoftwareLicense();
      $dbu     = new DbUtils();

      $query = ['SELECT' => 'id',
                'FROM'   => 'glpi_softwarelicenses',
                'WHERE'  => ['softwares_id' => $sID]
                             + $dbu->getEntitiesRestrictCriteria('glpi_softwarelicenses', '', '', true),
                'ORDER'   => 'name'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('License', 'Licenses', $number).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(15,15,10,10,10,10,10,10,10);
         $pdf->setColumnsAlign('left', 'left', 'left', 'right', 'right');
         $pdf->displayTitle('<b><i>'.__('Name').'</i></b>', '<b><i>'.__('Entity').'</i></b>',
                            '<b><i>'.__('Serial number').'</i></b>',
                            '<b><i>'. _x('quantity', 'Number').'</i></b>',
                            '<b><i>'.__('Affected computers').'</i></b>',
                            '<b><i>'.__('Type').'</i></b>',
                            '<b><i>'.__('Purchase version').'</i></b>',
                            '<b><i>'.__('Version in use').'</i></b>',
                            '<b><i>'.__('Expiration').'</i></b>');
         $totnbre = $totaffect = 0;
         foreach ($result as $data) {
            if ($license->getFromDB($data['id'])) {
               $totnbre   += $license->fields['number'];
               $totaffect += Item_SoftwareLicense::countForLicense($license->getField('id'));

               $pdf->displayLine($license->fields['name'],
                                 Dropdown::getDropdownName('glpi_entities', $license->fields['entities_id']),
                                 $license->fields['serial'],
                                 ($license->fields['number'] > 0) ? $license->fields['number']
                                                                  :__('Unlimited'),
                                 Item_SoftwareLicense::countForLicense($license->getField('id')),
                                 Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                                              $license->fields['softwarelicensetypes_id'])),
                                 Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwareversions',
                                                                              $license->fields['softwareversions_id_buy'])),
                                 Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwareversions',
                                                                              $license->fields['softwareversions_id_use'])),
                                 Html::convDate($license->fields['expire']));
            }
            $pdf->setColumnsAlign('left', 'left', 'right', 'right', 'right');
            $pdf->displayLine("&nbsp;","&nbsp;","<b>".__('Total')."</b>",
                              "<b>".$totnbre."</b>", "<b>".$totaffect."</b>",
                              "&nbsp;","&nbsp;","&nbsp;","&nbsp;");
         }
      }
      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            $cpt = !(isset($_REQUEST['item']['Item_SoftwareLicense$1'])
                     || isset($_REQUEST['item']['Item_SoftwareLicense$2']));
            self::pdfMain($pdf, $item, true, $cpt);
            break;

         case 'Item_SoftwareLicense$1' :
            PluginPdfItem_SoftwareLicense::pdfForLicenseByEntity($pdf, $item);
            break;

         case 'Item_SoftwareLicense$2' :
            PluginPdfItem_SoftwareLicense::pdfForLicenseByComputer($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}