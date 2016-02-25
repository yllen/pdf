<?php
/**
 * @version $Id:
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
 @copyright Copyright (c) 2009-2016 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfChange_Item extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Change_Item());
   }


   static function pdfForChange(PluginPdfSimplePDF $pdf, Change $change) {
      global $DB;

      $instID = $change->fields['id'];

      if (!$change->can($instID, READ)) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_changes_items`
                WHERE `glpi_changes_items`.`changes_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if (!$number) {
         $pdf->setColumnsSize(100);
         $pdf->displayLine(__('No item found.'));
      } else {
         $pdf->displayTitle('<b>'._n('Item', 'Items', $number).'</b>');

         $pdf->setColumnsSize(20,20,26,17,17);
         $pdf->displayTitle("<b><i>".__('Type'), __('Name'), __('Entity'), __('Serial number'),
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
                             `glpi_changes_items`.`id` AS IDD,
                             `glpi_entities`.`id` AS entity
                      FROM `glpi_changes_items`,
                           `$itemtable`";

            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }

            $query .= " WHERE `$itemtable`.`id` = `glpi_changes_items`.`items_id`
                              AND `glpi_changes_items`.`itemtype` = '$itemtype'
                              AND `glpi_changes_items`.`changes_id` = '$instID'";

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
                                    Dropdown::getDropdownName("glpi_entities", $data['entity']),
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