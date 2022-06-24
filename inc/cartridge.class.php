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
      global $DB;

      $instID = $p->getField('id');

      if (!Session::haveRight("cartridge", READ)) {
         return false;
      }

      $dateout = "IS NULL ";
      if ($old) {
         $dateout = " IS NOT NULL ";
      }
      $query = "SELECT `glpi_cartridgeitems`.`id` AS tID,
                       `glpi_cartridgeitems`.`is_deleted`,
                       `glpi_cartridgeitems`.`ref`,
                       `glpi_cartridgeitems`.`name` AS type,
                       `glpi_cartridges`.`id`,
                       `glpi_cartridges`.`pages`,
                       `glpi_cartridges`.`date_use`,
                       `glpi_cartridges`.`date_out`,
                       `glpi_cartridges`.`date_in`,
                       `glpi_cartridgeitemtypes`.`name` AS typename
                FROM `glpi_cartridges`,
                     `glpi_cartridgeitems`
                LEFT JOIN `glpi_cartridgeitemtypes`
                  ON (`glpi_cartridgeitems`.`cartridgeitemtypes_id` = `glpi_cartridgeitemtypes`.`id`)
                WHERE `glpi_cartridges`.`date_out` ".$dateout."
                      AND `glpi_cartridges`.`printers_id` = '".$instID."'
                      AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                ORDER BY `glpi_cartridges`.`date_out` ASC,
                         `glpi_cartridges`.`date_use` DESC,
                         `glpi_cartridges`.`date_in`";

      $result = $DB->request($query);
      $number = count($result);
      $i      = 0;
      $pages  = $p->fields['init_pages_counter'];

      $pdf->setColumnsSize(100);
      $title = "<b>".($old ? __('Worn cartridges') : __('Used cartridges') )."</b>";

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, $number);

         $pdf->displayTitle($title);

         if (!$old) {
            $pdf->setColumnsSize(5,35,30,15,15);
            $pdf->displayTitle('<b><i>'.__('ID'), __('Cartridge model'), __('Cartridge type'),
                                        __('Add date'), __('Use date').
                               '</b></i>');
         } else {
            $pdf->setColumnsSize(4,20,20,10,10,10,13,13);
            $pdf->displayTitle('<b><i>'.__('ID'), __('Cartridge model'), __('Cartridge type'),
                                        __('Add date'), __('Use date'), __('End date'),
                                        __('Printer counter'),  __('Printed pages').
                               '</b></i>');
         }

         $stock_time       = 0;
         $use_time         = 0;
         $pages_printed    = 0;
         $nb_pages_printed = 0;
         foreach ($result as $data) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_use = Html::convDate($data["date_use"]);
            $date_out = Html::convDate($data["date_out"]);

            $col1 = $data['id'];
            $col2 = sprintf(__('%1$s - %2$s'), $data["type"], $data["ref"]);

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
               $col7 ='';

               if ($pages < $data['pages']) {
                  $pages_printed   += $data['pages'] - $pages;
                  $nb_pages_printed++;
                  $col7  = sprintf(__('%1$s (%2$s)'), $col6,
                                   __('%d printed pages'), ($data['pages']-$pages));
                  $pages = $data['pages'];
               }
            }
            if (!$old) {
               $pdf->displayLine($col1, $col2, $data["typename"], $date_in, $date_use);
            } else {
               $pdf->displayLine($col1, $col2, $data["typename"], $date_in, $date_use, $date_out,
                                 $col6, $col7);
            }

         } // Each cartridge
      }

      if ($old) {
         if ($number > 0) {
            if ($nb_pages_printed == 0) {
               $nb_pages_printed = 1;
            }

            $time_stock = round($stock_time/$number/60/60/24/30.5,1);
            $time_use = round($use_time/$number/60/60/24/30.5,1);
            $pdf->setColumnsSize(33,33,34);
            $pdf->displayTitle(
               "<b><i>".sprintf(__('%1$s: %2$s'), __('Average time in stock')."</i></b>",
                                sprintf(_n('%d month', '%d months', $time_stock), $time_stock)),
               "<b><i>".sprintf(__('%1$s: %2$s'),__('Average time in use')."</i></b>",
                                sprintf(_n('%d month', '%d months', $time_use), $time_use)),
               "<b><i>".sprintf(__('%1$s: %2$s'), __('Average number of printed pages')."</i></b>",
                                round($pages_printed/$nb_pages_printed)));
         }
         $pdf->displaySpace();
      }
   }


   static function pdfForCartridgeItem(PluginPdfSimplePDF $pdf, CartridgeItem $cartitem, $state) {
      global $DB;

      $tID = $cartitem->getField('id');
      if (!$cartitem->can($tID, READ)) {
         return false;
      }

      $where = ['glpi_cartridges.cartridgeitems_id' => $tID];
      $order = ['glpi_cartridges.date_use ASC',
                'glpi_cartridges.date_out DESC',
                'glpi_cartridges.date_in'];

      if ($state == "new") {
         $where['glpi_cartridges.date_out'] = null;
         $where['glpi_cartridges.date_use'] = null;
         $order = ['glpi_cartridges.date_out ASC',
                   'glpi_cartridges.date_use ASC',
                   'glpi_cartridges.date_in'];
      } else if ($state == "used") {
         $where['glpi_cartridges.date_out'] = null;
         $where['NOT'] = ['glpi_cartridges.date_use' => null];
      } else { //OLD
         $where['NOT'] = ['glpi_cartridges.date_out' => null];
      }

      $stock_time       = 0;
      $use_time         = 0;
      $pages_printed    = 0;
      $nb_pages_printed = 0;

      $iterator = $DB->request(Cartridge::gettable(),
                               ['SELECT'    => ['glpi_cartridges.*',
                                                'glpi_printers.id AS printID',
                                                'glpi_printers.name AS printname',
                                                'glpi_printers.init_pages_counter'],
                                'LEFT JOIN' => ['glpi_printers'
                                                => ['FKEY' => [Cartridge::getTable()  => 'printers_id',
                                                               'glpi_printers'   => 'id']]],
                                'WHERE'     => $where,
                                'ORDER'     => $order]);

      $number = count($iterator);

      $pages = [];

      if ($number) {
         if ($state == 'new') {
            $pdf->setColumnsSize(25,25,25,25);
            $pdf->displayTitle("<b><i>".__('Total')."</i></b>",
                               "<b><i>".Cartridge::getTotalNumber($tID)."</i></b>",
                               "<b><i>".sprintf(__('%1$s %2$s'), _n('Cartridge', 'Cartridges', $number),
                                                 _nx('cartridge', 'New', 'New', $number))."</i></b>",
                               "<b><i>".Cartridge::getUnusedNumber($tID)."</i></b>");
            $pdf->displayTitle("<b><i>".__('Used cartridges')."</i></b>",
                               "<b><i>".Cartridge::getUsedNumber($tID),
                               "<b><i>".__('Worn cartridges')."</i></b>",
                               "<b><i>".Cartridge::getOldNumber($tID));

            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".sprintf(__('%1$s %2$s'), _n('Cartridge', 'Cartridges', $number),
                                                 _nx('cartridge', 'New', 'New', $number))."</b>");
         } else if ($state == "used") {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".__('Used cartridges')."</b>");
         } else { // Old
            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".__('Worn cartridges')."</b>");
         }

         if ($state != 'old') {
            $pdf->setColumnsSize(5,20,20,20,35);
            $pdf->displayLine("<b>".__('ID')."</b>", "<b>"._x('item', 'State')."</b>",
                              "<b>".__('Add date')."</b>", "<b>".__('Use date')."</b>",
                              "<b>".__('Used on')."</b>");
         } else {
            $pdf->setColumnsSize(5,20,15,15,15,15,15);
            $pdf->displayLine("<b>".__('ID')."</b>", "<b>"._x('item', 'State')."</b>",
                              "<b>".__('Add date')."</b>", "<b>".__('Use date')."</b>",
                              "<b>".__('Used on')."</b>", "<b>".__('End date')."</b>",
                              "<b>".__('Printer counter')."</b>");
         }

         foreach ($iterator as $data) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_use = Html::convDate($data["date_use"]);
            $date_out = Html::convDate($data["date_out"]);
            $printer  = $data["printers_id"];

            if (!is_null($date_use)) {
               $tmp_dbeg       = explode("-", $data["date_in"]);
               $tmp_dend       = explode("-", $data["date_use"]);
               $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                                 - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
               $stock_time    += $stock_time_tmp;
            }
            $pdfpages = '';
            if ($state == 'old') {
               $tmp_dbeg      = explode("-", $data["date_use"]);
               $tmp_dend      = explode("-", $data["date_out"]);
               $use_time_tmp  = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
               - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
               $use_time     += $use_time_tmp;

               // Get initial counter page
               if (!isset($pages[$printer])) {
                  $pages[$printer] = $data['init_pages_counter'];
               }
               if ($pages[$printer] < $data['pages']) {
                  $pages_printed   += $data['pages']-$pages[$printer];
                  $nb_pages_printed++;
                  $pp               = $data['pages']-$pages[$printer];
                  $pdfpages = sprintf(_n('%d printed page', '%d printed pages', $pp), $pp);
                  $pages[$printer]  = $data['pages'];
               } else if ($data['pages'] != 0) {
                  $pdfpages = __('Counter error');
               }
            }
            $pdf->displayLine($data["id"], Cartridge::getStatus($data["date_use"], $data["date_out"]),
                  $date_in, $date_use, $data["printname"], $date_out,
                  $pdfpages);
         }

         if (($state == 'old') && ($number > 0)) {
            if ($nb_pages_printed == 0) {
               $nb_pages_printed = 1;
            }
            $pdf->setColumnsSize(33,33,34);
            $pdf->displayLine("<b>".__('Average time in stock')."</b>",
                              "<b>".__('Average time in use')."</b>",
                              "<b>".__('Average number of printed pages')."</b>");

            $pdf->displayLine(round($stock_time/$number/60/60/24/30.5, 1)." ".__('month'),
                              round($use_time/$number/60/60/24/30.5, 1)." ".__('month'),
                              round($pages_printed/$nb_pages_printed));
         }
         $pdf->displaySpace();
      }
   }

}