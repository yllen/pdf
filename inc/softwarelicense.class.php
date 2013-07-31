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

class PluginPdfSoftwareLicense extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new SoftwareLicense());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, SoftwareLicense $license, $main=true, $cpt=true) {
      global $DB;

      $ID = $license->getField('id');

      $pdf->setColumnsSize(100);
      $entity = '';
      if (Session::isMultiEntitiesMode() && !$main) {
         $entity = ' ('.Html::clean(Dropdown::getDropdownName('glpi_entities',
                                                             $license->fields['entities_id'])).')';
      }
      $pdf->displayTitle('<b><i>'.sprintf(__('%1$s: %2$s'), __('ID')."</i>", $ID."</b>".$entity));

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), Software::getTypeName(1).'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_softwares',
                                                                $license->fields['softwares_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'),__('Type').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                                $license->fields['softwarelicensetypes_id']))));

      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>',
                                         $license->fields['name']),
                        '<b><i>'.sprintf(__('%1$s: %2$s'),__('Serial number').'</i></b>',
                                         $license->fields['serial']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Purchase version').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_softwareversions',
                                                                $license->fields['softwareversions_id_buy']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Inventory number').'</i></b>',
                          $license->fields['otherserial']));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Version in use').'</i></b>',
                          Html::clean(Dropdown::getDropdownName('glpi_softwareversions',
                                                                $license->fields['softwareversions_id_use']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Expiration').'</i></b>',
                          Html::convDate($license->fields['expire'])));

      $col2 = '';
      if ($cpt) {
         $col2 = '<b><i>'.sprintf(__('%1$s: %2$s'),__('Affected computers').'</i></b>',
                                  Computer_SoftwareLicense::countForLicense($ID));
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

      $sID = $software->getField('id');
      $license = new SoftwareLicense();

      $query = "SELECT `id`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` = '".$sID."' " .
                getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true) . "
                ORDER BY `name`";

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'._n('License', 'Licenses', 2).'</b>');

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            for ($tot=0 ; $data=$DB->fetch_assoc($result) ; ) {
               if ($license->getFromDB($data['id'])) {
                  self::pdfMain($pdf, $license, false);
                  if ($infocom) {
                     PluginPdfInfocom::pdfForItem($pdf, $license);
                  }
               }
            }
         } else {
            $pdf->displayLine(__('No item found'));
         }
      } else {
         $pdf->displayLine(__('No item found'));
      }
      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            $cpt = !(isset($_REQUEST['item']['Computer_SoftwareLicense$1'])
                     || isset($_REQUEST['item']['Computer_SoftwareLicense$2']));
            self::pdfMain($pdf, $item, true, $cpt);
            break;

         case 'Computer_SoftwareLicense$1' :
            PluginPdfComputer_SoftwareLicense::pdfForLicenseByEntity($pdf, $item);
            break;

         case 'Computer_SoftwareLicense$2' :
            PluginPdfComputer_SoftwareLicense::pdfForLicenseByComputer($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}