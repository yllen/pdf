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
 @copyright Copyright (c) 2009-2019 PDF plugin team
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

      $dbu = new DbUtils();

      $instID = $change->fields['id'];

      if (!$change->can($instID, READ)) {
         return false;
      }

      $result = $DB->request('glpi_changes_items',
                             ['SELECT DISTINCT' => 'itemtype',
                              'WHERE'           => ['changes_id' => $instID],
                              'ORDER'           => 'itemtype']);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Item', 'Items', 2).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, $number);
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(20,20,26,17,17);
         $pdf->displayTitle("<b><i>".__('Type'), __('Name'), __('Entity'), __('Serial number'),
                                  __('Inventory number')."</i></b>");

         $totalnb = 0;
         for ($i=0 ; $i<$number ; $i++) {
            $row = $result->next();
            $itemtype = $row['itemtype'];
            if (!($item = $dbu->getItemForItemtype($itemtype))) {
               continue;
            }

            if ($item->canView()) {
               $itemtable = $dbu->getTableForItemType($itemtype);

               $query = ['FIELDS'   => [$itemtable.'.*', 'glpi_changes_items.id AS IDD',
                                        'glpi_entities.id AS entity'],
                         'FROM'     => 'glpi_changes_items',
                         'LEFT JOIN' => [$itemtable => ['FKEY' => [$itemtable => 'id',
                                                                  'glpi_changes_items' => 'items_id'],
                                                                  'glpi_changes_items.itemtype'   => $itemtype,
                                                                  'glpi_changes_items.changes_id' => $instID]]];

               if ($itemtype != 'Entity') {
                  $query['LEFT JOIN']['glpi_entities'] = ['FKEY' => [$itemtable      => 'entities_id',
                                                                     'glpi_entities' => 'id']];
               }

               $query['WHERE'] = $dbu->getEntitiesRestrictCriteria($itemtable, '', '',
                                                                   $item->maybeRecursive());

               if ($item->maybeTemplate()) {
                  $query['WHERE'][$itemtable.'.is_template'] = 0;
               }

               $query['ORDER'] = ['glpi_entities.completename', $itemtable.'.name'];

               $result_linked = $DB->request($query, '',true);
               $nb            = count($result_linked);

               for ($prem=true ; $data=$result_linked->next() ; $prem=false) {
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
                                       Dropdown::getDropdownName("glpi_entities", $data['entity']),
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


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item, $tree=false) {
      global $DB;

      $dbu = new DbUtils();

      $restrict = '';
      $order    = '';

      switch ($item->getType()) {
         case 'User' :
            $restrict   = "(`glpi_changes_users`.`users_id` = '".$item->getID()."')";
            $order      = '`glpi_changes`.`date_mod` DESC';
            break;

         case 'Supplier' :
            $restrict   = "(`glpi_changes_suppliers`.`suppliers_id` = '".$item->getID()."')";
            $order      = '`glpi_changes`.`date_mod` DESC';
            break;

         case 'Group' :
            if ($tree) {
               $restrict = "IN (".implode(',', $dbu->getSonsOf('glpi_groups', $item->getID())).")";
            } else {
               $restrict = "='".$item->getID()."'";
            }
            $restrict   = "(`glpi_changes_groups`.`groups_id` $restrict
                            AND `glpi_changes_groups`.`type` = ".CommonITILActor::REQUESTER.")";
            $order      = '`glpi_changes`.`date_mod` DESC';
            break;

         default :
            $restrict   = "(`items_id` = '".$item->getID()."'
                            AND `itemtype` = '".$item->getType()."')";
            $order      = '`glpi_changes`.`date_mod` DESC';
            break;
      }

      $query = "SELECT ".Change::getCommonSelect()."
                FROM `glpi_changes`
                LEFT JOIN `glpi_changes_items`
                  ON (`glpi_changes`.`id` = `glpi_changes_items`.`changes_id`) ".
                      Change::getCommonLeftJoin()."
                WHERE $restrict ".
                      $dbu->getEntitiesRestrictRequest("AND","glpi_changes")."
                ORDER BY $order
                LIMIT ".intval($_SESSION['glpilist_limit']);

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.Change::getTypeName(2).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $pdf->displayTitle("<b>".sprintf(_n('Last %d change','Last %d changes', $number)."</b>",
                                          $number));

         $job = new Change();
         while ($data = $result->next()) {
            if (!$job->getFromDB($data["id"])) {
               continue;
            }
            $pdf->setColumnsAlign('center');
            $col = '<b><i>ID '.$job->fields["id"].'</i></b>, '.
                  sprintf(__('%1$s: %2$s'), __('Status'), Change::getStatus($job->fields["status"]));

            if (count($_SESSION["glpiactiveentities"]) > 1) {
               if ($job->fields['entities_id'] == 0) {
                  $col = sprintf(__('%1$s (%2$s)'), $col, __('Root entity'));
               } else {
                  $col = sprintf(__('%1$s (%2$s)'), $col,
                        Dropdown::getDropdownName("glpi_entities", $job->fields['entities_id']));
               }
            }
            $pdf->displayLine($col);

            $pdf->setColumnsAlign('left');

            $col = '<b><i>'.sprintf(__('Opened on %s').'</i></b>',
                                    Html::convDateTime($job->fields['date']));
            if ($job->fields['begin_waiting_date']) {
               $col = sprintf(__('%1$s, %2$s'), $col,
                              '<b><i>'.sprintf(__('Put on hold on %s').'</i></b>',
                                               Html::convDateTime($job->fields['begin_waiting_date'])));
            }
            if (in_array($job->fields["status"], $job->getSolvedStatusArray())
                || in_array($job->fields["status"], $job->getClosedStatusArray())) {

               $col = sprintf(__('%1$s, %2$s'), $col,
                              '<b><i>'.sprintf(__('Solved on %s').'</i></b>',
                                               Html::convDateTime($job->fields['solvedate'])));
            }
            if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
               $col = sprintf(__('%1$s, %2$s'), $col,
                              '<b><i>'.sprintf(__('Closed on %s').'</i></b>',
                                               Html::convDateTime($job->fields['closedate'])));
            }
            if ($job->fields['time_to_resolve']) {
               $col = sprintf(__('%1$s, %2$s'), $col,
                              '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', __('Time_to_resolve'),
                                               Html::convDateTime($job->fields['time_to_resolve'])));
            }
            $pdf->displayLine($col);

            $col = '<b><i>'.sprintf(__('%1$s: %2$s'), __('Priority').'</i></b>',
                                    Change::getPriorityName($job->fields["priority"]));
            if ($job->fields["itilcategories_id"]) {
               $cat = '<b><i>'.sprintf(__('%1$s: %2$s'), __('Category').'</i></b>',
                                       Dropdown::getDropdownName('glpi_itilcategories',
                                                                 $job->fields["itilcategories_id"]));
               $col = sprintf(__('%1$s - %2$s'), $col, $cat);
            }
            $pdf->displayLine($col);

            $lastupdate = Html::convDateTime($job->fields["date_mod"]);
            if ($job->fields['users_id_lastupdater'] > 0) {
               $lastupdate = sprintf(__('%1$s by %2$s'), $lastupdate,
                                     $dbu->getUserName($job->fields["users_id_lastupdater"]));
            }

            $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Last update').'</i></b>',
                  $lastupdate));

            $col   = '';
            $users = $job->getUsers(CommonITILActor::REQUESTER);
            if (count($users)) {
               foreach ($users as $d) {
                  if (empty($col)) {
                     $col = $dbu->getUserName($d['users_id']);
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col, $dbu->getUserName($d['users_id']));
                  }
               }
            }
            $grps = $job->getGroups(CommonITILActor::REQUESTER);
            if (count($grps)) {
               if (empty($col)) {
                  $col = sprintf(__('%1$s %2$s'), $col, _n('Group', 'Groups', 2).' </i></b>');
               } else {
                  $col = sprintf(__('%1$s - %2$s'), $col, _n('Group', 'Groups', 2).' </i></b>');
               }
               $first = true;
               foreach ($grps as $d) {
                  if ($first) {
                     $col = sprintf(__('%1$s  %2$s'), $col,
                                    Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col,
                                    Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
                  }
                  $first = false;
               }
            }
            if ($col) {
               $texte = '<b><i>'.sprintf(__('%1$s: %2$s'), __('Requester').'</i></b>', '');
               $pdf->displayText($texte, $col, 1);
            }

            $col   = '';
            $users = $job->getUsers(CommonITILActor::ASSIGN);
            if (count($users)) {
               foreach ($users as $d) {
                  if (empty($col)) {
                     $col = $dbu->getUserName($d['users_id']);
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col, $dbu->getUserName($d['users_id']));
                  }
               }
            }
            $grps = $job->getGroups(CommonITILActor::ASSIGN);
            if (count($grps)) {
               if (empty($col)) {
                  $col = sprintf(__('%1$s %2$s'), $col, _n('Group', 'Groups', 2).' </i></b>');
               } else {
                  $col = sprintf(__('%1$s - %2$s'), $col, _n('Group', 'Groups', 2).' </i></b>');
               }
               $first = true;
               foreach ($grps as $d) {
                  if ($first) {
                     $col = sprintf(__('%1$s  %2$s'), $col,
                                    Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col,
                                    Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
                  }
                  $first = false;
               }
            }
            if ($col) {
               $texte = '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', __('Assigned to'), '');
               $pdf->displayText($texte, $col, 1);
            }

            $texte = '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', __('Associated items'), '');

            $texte = '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', __('Title'), '');
            $pdf->displayText($texte, $job->fields["name"], 1);
         }
         $pdf->displaySpace();
      }
   }
}