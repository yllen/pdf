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


class PluginPdfChange_Problem extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Change_Problem());
   }


   static function pdfForChange(PluginPdfSimplePDF $pdf, Change $change) {
      global $DB;

      $ID = $change->getField('id');

      if (!$change->can($ID, READ)) {
         return false;
      }

      $query = "SELECT DISTINCT `glpi_changes_problems`.`id` AS linkID,
                                `glpi_problems`.*
                FROM `glpi_changes_problems`
                LEFT JOIN `glpi_problems`
                     ON (`glpi_changes_problems`.`problems_id` = `glpi_problems`.`id`)
                WHERE `glpi_changes_problems`.`changes_id` = '$ID'
                ORDER BY `glpi_problems`.`name`";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $problems = array();
      $used     = array();

      $pdf->setColumnsSize(100);
      if (!$number) {
         $pdf->displayTitle('<b>'.__('No problem found.', 'pdf').'</b>');
      } else {
         $pdf->displayTitle("<b>".Problem::getTypeName($number)."</b>");

         $job = new Problem();
         while ($data = $DB->fetch_assoc($result)) {
            if (!$job->getFromDB($data["id"])) {
               continue;
            }
            $pdf->setColumnsAlign('center');
            $col = '<b><i>ID '.$job->fields["id"].'</i></b>, '.
                    sprintf(__('%1$s: %2$s'), __('Status'),
                            Problem::getStatus($job->fields["status"]));

            if (count($_SESSION["glpiactiveentities"]) > 1) {
               if ($job->fields['entities_id'] == 0) {
                  $col = sprintf(__('%1$s (%2$s)'), $col, __('Root entity'));
               } else {
                  $col = sprintf(__('%1$s (%2$s)'), $col,
                                 Dropdown::getDropdownName("glpi_entities",
                                                           $job->fields['entities_id']));
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
            if ($job->fields['due_date']) {
               $col = sprintf(__('%1$s, %2$s'), $col,
                              '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', __('Due date'),
                                               Html::convDateTime($job->fields['due_date'])));
            }
            $pdf->displayLine($col);

            $lastupdate = Html::convDateTime($job->fields["date_mod"]);
            if ($job->fields['users_id_lastupdater'] > 0) {
               $lastupdate = sprintf(__('%1$s by %2$s'), $lastupdate,
                                     getUserName($job->fields["users_id_lastupdater"]));
            }

            $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Last update').'</i></b>',
                                               $lastupdate));

            $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Priority').'</i></b>',
                                               Ticket::getPriorityName($job->fields["priority"])));

            if ($job->fields["itilcategories_id"]) {
               $pdf->displayLine(
                  '<b><i>'.sprintf(__('%1$s: %2$s'), __('Category').'</i></b>',
                                   Dropdown::getDropdownName('glpi_itilcategories',
                                                             $job->fields["itilcategories_id"])));
            }

            $col   = '';
            $users = $job->getUsers(CommonITILActor::REQUESTER);
            if (count($users)) {
               foreach ($users as $d) {
                  if (empty($col)) {
                     $col = getUserName($d['users_id']);
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col, getUserName($d['users_id']));
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
                     $col = getUserName($d['users_id']);
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col, getUserName($d['users_id']));
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
               $texte = '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', ('Assigned to'), '');
               $pdf->displayText($texte, $col, 1);
            }

            $texte = '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', ('Title'), '');
            $pdf->displayText($texte, $job->fields["name"], 1);
         }
      }
      $pdf->displaySpace();
   }


   static function pdfForProblem(PluginPdfSimplePDF $pdf, Problem $problem) {
      global $DB;

      $ID = $problem->getField('id');

      if (!$problem->can($ID, READ)) {
         return false;
      }

      $query = "SELECT DISTINCT `glpi_changes_problems`.`id` AS linkID,
                                `glpi_changes`.*
                FROM `glpi_changes_problems`
                LEFT JOIN `glpi_changes`
                     ON (`glpi_changes_problems`.`changes_id` = `glpi_changes`.`id`)
                WHERE `glpi_changes_problems`.`problems_id` = '$ID'
                ORDER BY `glpi_changes`.`name`";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $problems = array();
      $used     = array();

      $pdf->setColumnsSize(100);
      if (!$number) {
         $pdf->displayTitle('<b>'.__('No change found.', 'pdf').'</b>');
      } else {
         $pdf->displayTitle("<b>".Change::getTypeName($number)."</b>");

         $job = new Change();
         while ($data = $DB->fetch_assoc($result)) {
            if (!$job->getFromDB($data["id"])) {
               continue;
            }
            $pdf->setColumnsAlign('center');
            $col = '<b><i>ID '.$job->fields["id"].'</i></b>, '.
                    sprintf(__('%1$s: %2$s'), __('Status'),
                            Problem::getStatus($job->fields["status"]));

            if (count($_SESSION["glpiactiveentities"]) > 1) {
               if ($job->fields['entities_id'] == 0) {
                  $col = sprintf(__('%1$s (%2$s)'), $col, __('Root entity'));
               } else {
                  $col = sprintf(__('%1$s (%2$s)'), $col,
                                 Dropdown::getDropdownName("glpi_entities",
                                                           $job->fields['entities_id']));
               }
            }
            $pdf->displayLine($col);

            $pdf->setColumnsAlign('left');

            $col = '<b><i>'.sprintf(__('Opened on %s').'</i></b>',
                                    Html::convDateTime($job->fields['date']));
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
            if ($job->fields['due_date']) {
               $col = sprintf(__('%1$s, %2$s'), $col,
                              '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', __('Due date'),
                                               Html::convDateTime($job->fields['due_date'])));
            }
            $pdf->displayLine($col);

            $lastupdate = Html::convDateTime($job->fields["date_mod"]);
            if ($job->fields['users_id_lastupdater'] > 0) {
               $lastupdate = sprintf(__('%1$s by %2$s'), $lastupdate,
                                     getUserName($job->fields["users_id_lastupdater"]));
            }

            $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Last update').'</i></b>',
                                               $lastupdate));

            $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Priority').'</i></b>',
                                               Ticket::getPriorityName($job->fields["priority"])));
            if ($job->fields["itilcategories_id"]) {
               $pdf->displayLine(
                  '<b><i>'.sprintf(__('%1$s: %2$s'), __('Category').'</i></b>',
                                   Dropdown::getDropdownName('glpi_itilcategories',
                                                             $job->fields["itilcategories_id"])));
            }

            $col   = '';
            $users = $job->getUsers(CommonITILActor::REQUESTER);
            if (count($users)) {
               foreach ($users as $d) {
                  if (empty($col)) {
                     $col = getUserName($d['users_id']);
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col, getUserName($d['users_id']));
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
                     $col = getUserName($d['users_id']);
                  } else {
                     $col = sprintf(__('%1$s, %2$s'), $col, getUserName($d['users_id']));
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

            $texte = '<b><i>'.sprintf(__('%1$s: %2$s').'</i></b>', ('Title'), '');
            $pdf->displayText($texte, $job->fields["name"], 1);
         }
      }
      $pdf->displaySpace();
   }
}