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
 @copyright Copyright (c) 2009-2015 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.indepnet.net/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/

class PluginPdfCartridge extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   /**
    * @param $obj (defult NULL)
   **/
   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Cartridge());
   }


   /**
    * @param $pdf                PluginPdfSimplePDF object
    * @param $p                  Printer object
    * @param $old
   **/
   static function pdfForPrinter(PluginPdfSimplePDF $pdf, Printer $p, $old=false) {
      global $DB,$CFG_GLPI;

      $instID = $p->getField('id');

      if (!Session::haveRight("cartridge", READ)) {
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
                WHERE `glpi_cartridges`.`date_out` ".$dateout."
                      AND `glpi_cartridges`.`printers_id` = '".$instID."'
                      AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                ORDER BY `glpi_cartridges`.`date_out` ASC,
                         `glpi_cartridges`.`date_use` DESC,
                         `glpi_cartridges`.`date_in`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i      = 0;
      $pages  = $p->fields['init_pages_counter'];

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".($old ? __('Worn cartridges') : __('Used cartridges') )."</b>");

      if (!$number) {
         $pdf->displayLine(__('No item found'));
      } else {
         $pdf->setColumnsSize(25,13,12,12,12,26);
         $pdf->displayTitle('<b><i>'.__('Cartridge type'), __('State'), __('Add date'),
                                     __('Use date'), __('End date'), __('Printer counter').
                            '</b></i>');

         $stock_time       = 0;
         $use_time         = 0;
         $pages_printed    = 0;
         $nb_pages_printed = 0;
         while ($data = $DB->fetch_array($result)) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_use = Html::convDate($data["date_use"]);
            $date_out = Html::convDate($data["date_out"]);

            $col1 = sprintf(__('%1$s - %2$s'), $data["name"], $data["ref"]);
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
                  $pages_printed   += $data['pages'] - $pages;
                  $nb_pages_printed++;
                  $col6  = sprintf(__('%1$s (%2$s)'), $col6,
                                   __('%d printed pages'), ($data['pages']-$pages));
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

            $nbstock = round($stock_time/$number/60/60/24/30.5,1);
            $nbuse   = round($use_time/$number/60/60/24/30.5,1);
            $pdf->setColumnsSize(33,33,34);
            $pdf->displayTitle(
               "<b><i>".sprintf(__('%1$s: %2$s'), __('Average time in stock')."</i></b>",
                                _n('%d month', '%d months', $nbstock), $nbstock),
               "<b><i>".sprintf(__('%1$s: %2$s'),__('Average time in use')."</i></b>",
                                _n('%d month', '%d months', $nbuse), $nbuse),
               "<b><i>".sprintf(__('%1$s: %2$s'), __('Average number of printed pages')."</i></b>",
                                round($pages_printed/$nb_pages_printed)));
         }
         $pdf->displaySpace();
      }
   }
}