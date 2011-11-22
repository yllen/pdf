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

class PluginPdfSoftwareVersion extends PluginPdfCommon {


   function __construct(SoftwareVersion $obj=NULL) {

      $this->obj = ($obj ? $obj : new SoftwareVersion());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, SoftwareVersion $version) {
      global $DB,$LANG;

      $ID = $version->getField('id');

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b><i>'.$LANG['common'][2]."</i> : $ID</b>");

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         '<b><i>'.$LANG['common'][16].'</i></b>: '.$version->fields['name'],
         '<b><i>'.$LANG['help'][31].'</i></b>: '.
            Html::clean(Dropdown::getDropdownName('glpi_softwares', $version->fields['softwares_id'])));

      $pdf->displayLine(
         '<b><i>'.$LANG["state"][0].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_states', $version->fields['states_id'])),
         '<b><i>'.$LANG['computers'][9].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                                 $version->fields['operatingsystems_id'])));

      $pdf->setColumnsSize(100);
      $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $version->fields['comment']);
      $pdf->displaySpace();
   }


   static function pdfForSoftware(PluginPdfSimplePDF $pdf, Software $item){
      global $DB,$LANG;

      $sID = $item->getField('id');

      $query = "SELECT `glpi_softwareversions`.*,
                       `glpi_states`.`name` AS sname,
                       `glpi_operatingsystems`.`name` AS osname
                FROM `glpi_softwareversions`
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_operatingsystems`
                     ON (`glpi_operatingsystems`.`id` = `glpi_softwareversions`.`operatingsystems_id`)
                WHERE (`softwares_id` = '$sID')
                ORDER BY `name`";

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG['software'][5].'</b>');

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $pdf->setColumnsSize(13,13,30,14,30);
            $pdf->displayTitle('<b><i>'.$LANG['software'][5].'</i></b>',
                               '<b><i>'.$LANG['state'][0].'</i></b>',
                               '<b><i>'.$LANG['computers'][9].'</i></b>',
                               '<b><i>'.$LANG['software'][19].'</i></b>',
                               '<b><i>'.$LANG['common'][25].'</i></b>');
            $pdf->setColumnsAlign('left','left','left','right','left');

            for ($tot=$nb=0 ; $data=$DB->fetch_assoc($result) ; $tot+=$nb) {
               $nb = Computer_SoftwareVersion::countForVersion($data['id']);
               $pdf->displayLine(
                  (empty($data['name'])?"(".$data['id'].")":$data['name']),
                  $data['sname'],
                  $data['osname'],
                  $nb,
                  str_replace(array("\r","\n")," ",$data['comment'])
               );
            }
            $pdf->setColumnsAlign('left','right','left', 'right','left');
            $pdf->displayTitle('','',"<b>".$LANG['common'][33]." : </b>",$tot, '');
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
            self::pdfMain($pdf, $item);
            break;

         case 'Computer_SoftwareVersion$1' :
            PluginPdfComputer_SoftwareVersion::pdfForVersionByEntity($pdf, $item);
            break;

         case 'Computer_SoftwareVersion$2' :
            PluginPdfComputer_SoftwareVersion::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}