<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
*/

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfSoftwareLicense extends PluginPdfCommon {


   function __construct(SoftwareLicense $obj=NULL) {

      $this->obj = ($obj ? $obj : new SoftwareLicense());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, SoftwareLicense $license, $main=true, $cpt=true) {
      global $DB,$LANG;

      $ID = $license->getField('id');

      $pdf->setColumnsSize(100);
      $entity = '';
      if (Session::isMultiEntitiesMode() && !$main) {
         $entity = ' ('.Html::clean(Dropdown::getDropdownName('glpi_entities',
                                                             $license->fields['entities_id'])).')';
      }
      $pdf->displayTitle('<b><i>'.$LANG['common'][2]."</i> : $ID</b>$entity");

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         '<b><i>'.$LANG['help'][31].'</i></b>: '.
            Html::clean(Dropdown::getDropdownName('glpi_softwares', $license->fields['softwares_id'])),
         '<b><i>'.$LANG['common'][17].'</i></b>: '.
            Html::clean(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                 $license->fields['softwarelicensetypes_id'])));

      $pdf->displayLine('<b><i>'.$LANG['common'][16].'</i></b>: '.$license->fields['name'],
                        '<b><i>'.$LANG['common'][19].'</i></b>: '.$license->fields['serial']);

      $pdf->displayLine(
         '<b><i>'.$LANG['software'][1].'</i></b>: '.
            Html::clean(Dropdown::getDropdownName('glpi_softwareversions',
                                                 $license->fields['softwareversions_id_buy'])),
         '<b><i>'.$LANG['common'][20].'</i></b>: '.$license->fields['otherserial']);

      $pdf->displayLine(
         '<b><i>'.$LANG['software'][2].'</i></b>: '.
            Html::clean(Dropdown::getDropdownName('glpi_softwareversions',
                                                 $license->fields['softwareversions_id_use'])),
         '<b><i>'.$LANG['software'][32].'</i></b>: '.Html::convDate($license->fields['expire']));

      $col2 = '';
      if ($cpt) {
         $col2 = '<b><i>'.$LANG['software'][9].'</i></b>: '.
                 Computer_SoftwareLicense::countForLicense($ID);
      }
      $pdf->displayLine(
         '<b><i>'.$LANG['tracking'][29].'</i></b>: '.
            ($license->fields['number']>0?$license->fields['number']:$LANG['software'][4]),
         $col2);

      $pdf->setColumnsSize(100);
      $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $license->fields['comment'], 1);

      if ($main) {
         $pdf->displaySpace();
      }
   }


   static function pdfForSoftware(PluginPdfSimplePDF $pdf, Software $software, $infocom=false) {
      global $DB,$LANG;

      $sID = $software->getField('id');
      $license = new SoftwareLicense();

      $query = "SELECT `id`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` = '$sID' " .
                getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true) . "
                ORDER BY `name`";

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG['software'][11].'</b>');

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
            $pdf->displayLine($LANG['search'][15]);
         }
      } else {
         $pdf->displayLine($LANG['search'][15]."!");
      }
      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            $cpt = !(isset($_REQUEST['item']['Computer_SoftwareLicense####1'])
                     || isset($_REQUEST['item']['Computer_SoftwareLicense####2']));
            self::pdfMain($pdf, $item, true, $cpt);
            break;

         case 'Computer_SoftwareLicense####1' :
            PluginPdfComputer_SoftwareLicense::pdfForLicenseByEntity($pdf, $item);
            break;

         case 'Computer_SoftwareLicense####2' :
            PluginPdfComputer_SoftwareLicense::pdfForLicenseByComputer($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}