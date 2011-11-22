<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfSoftware extends PluginPdfCommon {


   function __construct(Software $obj=NULL) {

      $this->obj = ($obj ? $obj : new Software());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Software $software) {
      global $LANG;

      $ID = $software->getField('id');

      $col1 = '<b>'.$LANG['common'][2].' '.$software->fields['id'].'</b>';
      $col2 = '<b>'.$LANG['common'][26].' : '.Html::convDateTime($software->fields['date_mod']).'</b>';

      if (!empty($software->fields['template_name'])) {
         $col2 .= ' ('.$LANG['common'][13].' : '.$software->fields['template_name'].')';
      }

      $pdf->setColumnsSize(50,50);
      $pdf->displayTitle($col1, $col2);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][16].' :</i></b> '.$software->fields['name'],
         '<b><i>'.$LANG['common'][5].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_manufacturers', $software->fields['manufacturers_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][15].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_locations', $software->fields['locations_id'])),
         '<b><i>'.$LANG['common'][36].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_softwarecategories', $software->fields['softwarecategories_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($software->fields['users_id_tech']),
         '<b><i>'.$LANG['software'][46].' :</i></b> ' .
            ($software->fields['is_helpdesk_visible']?$LANG['choice'][1]:$LANG['choice'][0]));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][109].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id_tech'])),
         '<b><i>'.$LANG['software'][29].' :</i></b> '.
            ($software->fields['is_update']?$LANG['choice'][1]:$LANG['choice'][0]), $col2);

      if ($software->fields['softwares_id']>0) {
         $col2 = '<b><i> '.$LANG['pager'][2].' </i></b> '.
                  Html::clean(Dropdown::getDropdownName('glpi_softwares',
                                                       $software->fields['softwares_id']));
      } else {
         $col2 = '';
      }
      $pdf->displayLine(
         '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($software->fields['users_id']),
         $col2);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][35].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id'])));


      $pdf->setColumnsSize(100);
      $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $software->fields['comment']);

      $pdf->displaySpace();
   }


    function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['Software$1']); // Merge tab can't be exported
      return $onglets;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

         case 'SoftwareVersion$1' :
            PluginPdfSoftwareVersion::pdfForSoftware($pdf, $item);
            break;

         case 'SoftwareLicense$1' :
            $infocom = isset($_REQUEST['item']['Infocom$1']);
            PluginPdfSoftwareLicense::pdfForSoftware($pdf, $item, $infocom);
            break;

         case 'Computer_SoftwareVersion$1' :
            PluginPdfComputer_SoftwareVersion::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}