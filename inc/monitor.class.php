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

class PluginPdfMonitor extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new Monitor());
   }



   static function pdfMain(PluginPdfSimplePDF $pdf, Monitor $item) {
      global $LANG;

      $ID = $item->getField('id');

      $pdf->setColumnsSize(50,50);
      $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
      $col2 = $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod']);

      if (!empty($printer->fields['template_name'])) {
         $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
      }
      $pdf->displayTitle($col1, $col2);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
         '<b><i>'.$LANG['state'][0].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][15].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
         '<b><i>'.$LANG['common'][17].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_monitortypes',
                                                    $item->fields['monitortypes_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
         '<b><i>'.$LANG['common'][5].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                    $item->fields['manufacturers_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][109].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups',$item->fields['groups_id_tech'])),
         '<b><i>'.$LANG['common'][22].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_monitormodels',
                                                    $item->fields['monitormodels_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
                        '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

      $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
         '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
         '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
               ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][35].' :</i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
         '<b><i>'.$LANG['monitors'][21].' :</i></b> '.$item->fields['size'].'"');

      $opts = array(
         'have_micro'         => $LANG['monitors'][14],
         'have_speaker'       => $LANG['monitors'][15],
         'have_subd'          => $LANG['monitors'][19],
         'have_bnc'           => $LANG['monitors'][20],
         'have_dvi'           => $LANG['monitors'][32],
         'have_pivot'         => $LANG['monitors'][33],
         'have_hdmi'          => $LANG['monitors'][34],
         'have_displayport'   => $LANG['monitors'][31],
      );
      foreach ($opts as $key => $val) {
         if (!$item->fields[$key]) {
            unset($opts[$key]);
         }
      }
      $pdf->setColumnsSize(100);
      $pdf->displayLine(
         '<b><i>'.$LANG['monitors'][18].' : </i></b>'.
            (count($opts) ? implode(', ',$opts) : $LANG['job'][32]));

      $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

          case 'Computer_Item$1' :
            PluginPdfComputer_Item::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}