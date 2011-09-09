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

class PluginPdfTicket extends PluginPdfCommon {

   function __construct(Ticket $obj=NULL) {

      $this->obj = ($obj ? $obj : new Ticket());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB,$CFG_GLPI, $LANG;

      $ID = $item->getField('id');
      $type = $item->getType();

      if (!Session::haveRight("show_all_ticket","1")) {
         return;
      }

      if ($type == 'Sla') {
         $restrict                 = "(`slas_id` = '$ID')";
         $order                    = '`glpi_tickets`.`due_date` DESC';
      } else {
         $restrict                 = "(`items_id` = '$ID' AND `itemtype` = '$type')";
         $order                    = '`glpi_tickets`.`date_mod` DESC';
      }

      $query = "SELECT ".Ticket::getCommonSelect()."
                FROM glpi_tickets ".
                Ticket::getCommonLeftJoin()."
                WHERE $restrict ".
                  getEntitiesRestrictRequest("AND","glpi_tickets")."
                ORDER BY $order
                LIMIT ".intval($_SESSION['glpilist_limit']);

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $pdf->setColumnsSize(100);
      if (!$number) {
         $pdf->displayTitle('<b>'.$LANG['joblist'][8].'</b>');
      } else {
         $pdf->displayTitle("<b>".$LANG["job"][$number==1 ? 10 : 8]." : $number</b>");

         $job = new Ticket();
         while ($data = $DB->fetch_assoc($result)) {
            if (!$job->getFromDB($data["id"])) {
               continue;
            }
            $pdf->setColumnsAlign('center');
            $col = '<b><i>ID '.$job->fields["id"].'</i></b>,   '.$LANG["state"][0].' : '.
                              Ticket::getStatus($job->fields["status"]);

            if (count($_SESSION["glpiactiveentities"]) > 1) {
               if ($job->fields['entities_id'] == 0) {
                  $col .= "   (".$LANG['entity'][2].")";
               } else {
                  $col .= "   (".Dropdown::getDropdownName("glpi_entities", $job->fields['entities_id']).")";
               }
            }
            $pdf->displayLine($col);

            $pdf->setColumnsAlign('left');

            $col = '<b><i>'.$LANG['joblist'][11].' : </i></b>'.Html::convDateTime($job->fields['date']);
            if ($job->fields['begin_waiting_date']) {
               // TODO 0.83, $LANG['joblist'][15]
               $col .= ', '.$LANG['joblist'][26]." : ".Html::convDateTime($job->fields['begin_waiting_date']);
            }
            if ($job->fields['status']=='solved' || $job->fields['status']=='closed') {
               $col .= ', '.$LANG['joblist'][14]." : ".Html::convDateTime($job->fields['solvedate']);
            }
            if ($job->fields['status']=='closed') {
               $col .= ', '.$LANG['joblist'][12]." : ".Html::convDateTime($job->fields['closedate']);
            }
            if ($job->fields['due_date']) {
               $col .= ', '.$LANG['sla'][5]." : ".Html::convDateTime($job->fields['due_date']);
            }
            $pdf->displayLine($col);

            $col = '<b><i>'.$LANG["joblist"][2].' :</i></b> '.Ticket::getPriorityName($job->fields["priority"]);
            if ($job->fields["itilcategories_id"]) {
               $col .= '  -  <b><i>'.$LANG["common"][36].' : </i></b>';
               $col .= Dropdown::getDropdownName('glpi_itilcategories', $job->fields["itilcategories_id"]);
            }
            $pdf->displayLine($col);

            $col = '';
            $users = $job->getUsers(Ticket::REQUESTER);
            if (count($users)) {
               foreach ($users as $d) {
                  $col .= (empty($col)?'':', ').getUserName($d['users_id']);
               }
            }
            $grps = $job->getGroups(Ticket::REQUESTER);
            if (count($grps)) {
               $col .= (empty($col)?'':' - ').'<b><i>'.$LANG['Menu'][36].' : </i></b>';
               $first = true;
               foreach ($grps as $d) {
                  $col .= ($first?'':', ').Dropdown::getDropdownName("glpi_groups", $d['groups_id']);
                  $first = false;
               }
            }
            if ($col) {
               $pdf->displayText('<b><i>'.$LANG["job"][4].' : </i></b>', $col, 1);
            }

            $col = '';
            $users = $job->getUsers(Ticket::ASSIGN);
            if (count($users)) {
               foreach ($users as $d) {
                  $col .= (empty($col)?'':', ').getUserName($d['users_id']);
               }
            }
            $grps = $job->getGroups(Ticket::ASSIGN);
            if (count($grps)) {
               $col .= (empty($col)?'':' - ').'<b><i>'.$LANG['Menu'][36].' : </i></b>';
               $first = true;
               foreach ($grps as $d) {
                  $col .= ($first?'':', ').Dropdown::getDropdownName("glpi_groups", $d['groups_id']);
                  $first = false;
               }
            }
            if ($col) {
               $pdf->displayText('<b><i>'.$LANG["job"][5].' : </i></b>', $col, 1);
            }

            $pdf->displayText('<b><i>'.$LANG["common"][57].' :</i></b> ',$job->fields["name"], 1);
         }
      }
      $pdf->displaySpace();
   }
}