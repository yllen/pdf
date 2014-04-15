<?php
/*
 * @version $Id:
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2014 by the pdf Development Team.

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


class PluginPdfItem_Problem extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_Problem());
   }


   static function pdfForProblem(PluginPdfSimplePDF $pdf, Problem $problem) {
      global $DB;

      $instID = $problem->fields['id'];

      if (!$problem->can($instID,'r')) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_items_problems`
                WHERE `glpi_items_problems`.`problems_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if (!$number) {
         $pdf->setColumnsSize(100);
         $pdf->displayLine(__('No item found.'));
      } else {
         $pdf->displayTitle('<b>'._n('Item', 'Items', $number).'</b>');

         $pdf->setColumnsSize(20,30,25,25);
         $pdf->displayTitle("<b><i>".__('Type'), __('Name'), __('Serial number'),
                                  __('Inventory number')."</b></i>");

                                        $totalnb = 0;
         for ($i=0 ; $i<$number ; $i++) {
            $itemtype = $DB->result($result, $i, "itemtype");
            if (!($item = getItemForItemtype($itemtype))) {
               continue;
            }

            if ($item->canView()) {
               $itemtable = getTableForItemType($itemtype);
            $query = "SELECT `$itemtable`.*,
                             `glpi_items_problems`.`id` AS IDD,
                             `glpi_entities`.`id` AS entity
                      FROM `glpi_items_problems`,
                           `$itemtable`";

            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }

            $query .= " WHERE `$itemtable`.`id` = `glpi_items_problems`.`items_id`
                              AND `glpi_items_problems`.`itemtype` = '$itemtype'
                              AND `glpi_items_problems`.`problems_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
            }

            $query .= getEntitiesRestrictRequest(" AND", $itemtable, '', '',
                                                 $item->maybeRecursive())."
                      ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
               $name = $data["name"];
               if (empty($data["name"])) {
                  $name = "(".$data["id"].")";
               }
               if ($prem) {
                  $typename = $item->getTypeName($nb);
                  $pdf->displayLine(Html::clean(sprintf(__('%1$s: %2$s'), $typename, $nb)),
                                    Html::clean($name),
                                    Html::clean($data["serial"]),
                                    Html::clean($data["otherserial"]),$nb);
               } else {
                  $pdf->displayLine('',
                                    Html::clean($name),
                                    Html::clean($data["serial"]),
                                    Html::clean($data["otherserial"]),$nb);
               }
            }
            $totalnb += $nb;
         }
         }
      }
      $pdf->displayLine("<b><i>".sprintf(__('%1$s = %2$s')."</b></i>", __('Total'), $totalnb));
   }


}