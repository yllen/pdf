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

class PluginPdfCartridge extends PluginPdfCommon {


   function __construct(Cartridge $obj=NULL) {

      $this->obj = ($obj ? $obj : new Cartridge());
   }

   static function pdfForPrinter(PluginPdfSimplePDF $pdf, Printer $p, $old=false) {
      global $DB,$CFG_GLPI, $LANG;

      $instID = $p->getField('id');

      if (!Session::haveRight("cartridge","r")) {
         return false;
      }

      $dateout = "IS NULL ";
      if ($old) {
         $dateout = " IS NOT NULL ";
      }
      $query = "SELECT `glpi_cartridgeitems`.`id` AS tid,
                       `glpi_cartridgeitems`.`ref`,
                       `glpi_cartridgeitems`.`name`,
                       `glpi_cartridges`.`id`,
                       `glpi_cartridges`.`pages`,
                       `glpi_cartridges`.`date_use`,
                       `glpi_cartridges`.`date_out`,
                       `glpi_cartridges`.`date_in`
                FROM `glpi_cartridges`, `glpi_cartridgeitems`
                WHERE `glpi_cartridges`.`date_out` $dateout
                      AND `glpi_cartridges`.`printers_id` = '$instID'
                      AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                ORDER BY `glpi_cartridges`.`date_out` ASC,
                         `glpi_cartridges`.`date_use` DESC,
                         `glpi_cartridges`.`date_in`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;
      $pages=$p->fields['init_pages_counter'];

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".($old ? $LANG['cartridges'][35] : $LANG['cartridges'][33] )."</b>");

      if (!$number) {
         $pdf->displayLine($LANG['search'][15]);
      } else {
         $pdf->setColumnsSize(25,13,12,12,12,26);
         $pdf->displayTitle('<b><i>'.$LANG['cartridges'][12],
                                     $LANG['consumables'][23],
                                     $LANG['cartridges'][24],
                                     $LANG['consumables'][26],
                                     $LANG['search'][9],
                                     $LANG['cartridges'][39].'</b></i>');

         $stock_time = 0;
         $use_time = 0;
         $pages_printed = 0;
         $nb_pages_printed = 0;
         while ($data=$DB->fetch_array($result)) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_use = Html::convDate($data["date_use"]);
            $date_out = Html::convDate($data["date_out"]);

            $col1 = $data["name"]." - ".$data["ref"];
            $col2 = Cartridge::getStatus($data["date_use"], $data["date_out"]);
            $col6 = '';

            $tmp_dbeg = explode("-",$data["date_in"]);
            $tmp_dend = explode("-",$data["date_use"]);

            $stock_time_tmp = mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
                              - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
            $stock_time += $stock_time_tmp;

            if ($old) {
               $tmp_dbeg = explode("-",$data["date_use"]);
               $tmp_dend = explode("-",$data["date_out"]);

               $use_time_tmp = mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
                               - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
               $use_time += $use_time_tmp;

               $col6 = $data['pages'];

               if ($pages < $data['pages']) {
                  $pages_printed += $data['pages'] - $pages;
                  $nb_pages_printed++;
                  $col6 .= " (". ($data['pages']-$pages)." ".$LANG['printers'][31].")";
                  $pages = $data['pages'];
               }
            }
            $pdf->displayLine($col1, $col2, $date_in, $date_use, $date_out, $col6);
         } // Each cartridge
      }

      if ($old) {
         if ($number > 0) {
            if ($nb_pages_printed == 0) {
               $nb_pages_printed = 1;
            }

            $pdf->setColumnsSize(33,33,34);
            $pdf->displayTitle(
               "<b><i>".$LANG['cartridges'][40]." :</i></b> ".
                  round($stock_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57],
               "<b><i>".$LANG['cartridges'][41]." :</i></b> ".
                  round($use_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57],
               "<b><i>".$LANG['cartridges'][42]." :</i></b> ".round($pages_printed/$nb_pages_printed));
         }
         $pdf->displaySpace();
      }
   }
}