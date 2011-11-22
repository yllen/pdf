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

class PluginPdfTicket extends PluginPdfCommon {

   function __construct(Ticket $obj=NULL) {

      $this->obj = ($obj ? $obj : new Ticket());
   }

   static function pdfMain(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $LANG, $CFG_GLPI, $DB;

      $ID = $job->getField('id');
      if (!$job->can($ID, 'r')) {
         return false;
      }

      $pdf->setColumnsSize(100);

      $pdf->displayTitle('<b>'.
               (empty($job->fields["name"])?$LANG['reminder'][15]:$name=$job->fields["name"]).'</b>');

      if (count($_SESSION['glpiactiveentities'])>1) {
         $entity = " (".Dropdown::getDropdownName("glpi_entities",$job->fields["entities_id"]).")";
      } else {
         $entity = '';
      }

      $pdf->setColumnsSize(50,50);
      $recipient_name='';
      if ($job->fields["users_id_recipient"]) {
         $recipient = new User();
         $recipient->getFromDB($job->fields["users_id_recipient"]);
         $recipient_name = $recipient->getName();
      }
      $pdf->displayLine("<b><i>".$LANG['joblist'][11]."</i></b> : ".Html::convDateTime($job->fields["date"]).", ".
                           '<b><i>'.$LANG['common'][95]."</i></b> : ".$recipient_name,
                        $LANG['common'][26]." : ".Html::convDateTime($job->fields["date_mod"]));

      $status = Html::clean($job->getStatus($job->fields["status"]));
      switch ($job->getField('status')) {
         case 'closed':
            $status = $LANG['joblist'][12].' : '.Html::convDateTime($job->fields['closedate']);
            break;

         case 'solved':
            $status = $LANG['joblist'][14].' : '.Html::convDateTime($job->fields['solvedate']);
            break;

         case 'waiting':
            $status .= ' - '.$LANG['knowbase'][27].' : '.Html::convDateTime($job->fields['begin_waiting_date']);
            break;
      }
      $sla = $due = '';
      if ($job->fields["slas_id"]>0) {
         $sla = "<b><i>".$LANG['sla'][1]." : </b></i>".
                  Html::clean(Dropdown::getDropdownName("glpi_slas", $job->fields["slas_id"]));
      }
      if ($job->fields['due_date']) {
         $due .= "<b><i>".$LANG['sla'][5]." : </b></i>".Html::convDateTime($job->fields['due_date']);
      }

      // status, due date
      $pdf->displayLine(
         "<b><i>".$LANG['joblist'][0]."</i></b> : $status", $due);

      // Urgence, SLA
      $pdf->displayLine(
         "<b><i>".$LANG['joblist'][29]."</i></b> : ".
               Html::clean($job->getUrgencyName($job->fields["urgency"])), $sla);

      // Impact / Type
      $pdf->displayLine(
         "<b><i>".$LANG['joblist'][30]."</i></b> : ".
               Html::clean($job->getImpactName($job->fields["impact"])),
         "<b><i>".$LANG['common'][17]."</i></b> : ".
               Html::clean(Ticket::getTicketTypeName($job->fields["type"])));

      // Priority / Category
      $pdf->displayLine(
         "<b><i>".$LANG['joblist'][2]."</i></b> : ".
               Html::clean($job->getPriorityName($job->fields["priority"])),
         "<b><i>".$LANG['common'][36]."</i></b> : ".
               Html::clean(Dropdown::getDropdownName("glpi_itilcategories",
                                                    $job->fields["itilcategories_id"])));

      // Source / Validation
      $pdf->displayLine(
         "<b><i>".$LANG['job'][44]."</i></b> : ".
               Html::clean(Dropdown::getDropdownName('glpi_requesttypes',
                                                    $job->fields['requesttypes_id'])),
         "<b><i>".$LANG['validation'][0]."</i></b> : ".
               Html::clean(TicketValidation::getStatus($job->fields['global_validation'])));

      // Item
      $serial_item = '';
      $location_item = '';
      $otherserial_item = '';

      $pdf->setColumnsSize(100);
      if ($job->fields["itemtype"] && ($item = getItemForItemtype($job->fields["itemtype"]))) {
         if ($item->getFromDB($job->fields["items_id"])) {
            if (isset($item->fields["serial"])) {
               $serial_item =
                  ", <b><i>".$LANG['common'][19]."</i></b> : ".
                                 Html::clean($item->fields["serial"]);
            }
            if (isset($item->fields["otherserial"])) {
               $otherserial_item =
                  ", <b><i>".$LANG['common'][20]."</i></b> : ".
                                 Html::clean($item->fields["otherserial"]);
            }
            if (isset($item->fields["locations_id"])) {
               $location_item =
                  "\n<b><i>".$LANG['common'][15]."</i></b> : ".
                     Html::clean(Dropdown::getDropdownName("glpi_locations",
                                                          $item->fields["locations_id"]));
            }
         }
         $pdf->displayText(
            "<b><i>".$LANG['document'][14]."</i></b> : ",
            Html::clean($item->getTypeName())." ".Html::clean($item->getNameID()).
                  $serial_item . $otherserial_item . $location_item,
            1);
      } else {
         $pdf->displayLine("<b><i>".$LANG['common'][1]."</i></b> : ".$LANG['help'][30]);
      }

      // Requester
      $users = array();
      foreach ($job->getUsers(Ticket::REQUESTER) as $d) {
         if ($d['users_id']) {
            $tmp = Html::clean(getUserName($d['users_id']));
            if ($d['alternative_email']) {
               $tmp .= ' ('.$d['alternative_email'].')';
            }
         } else {
            $tmp = $d['alternative_email'];
         }
         $users[] = $tmp;
      }
      if (count($users)) {
         $pdf->displayText('<b><i>'.$LANG['job'][4].'</i></b> : ', implode(', ', $users), 1);
      }
      $groups = array();
      foreach ($job->getGroups(Ticket::REQUESTER) as $d) {
         $groups[] = Html::clean(Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
      }
      if (count($groups)) {
         $pdf->displayText('<b><i>'.$LANG['setup'][249].'</i></b> : ', implode(', ', $groups), 1);
      }
      // Observer
      $users = array();
      foreach ($job->getUsers(Ticket::OBSERVER) as $d) {
         if ($d['users_id']) {
            $tmp = Html::clean(getUserName($d['users_id']));
            if ($d['alternative_email']) {
               $tmp .= ' ('.$d['alternative_email'].')';
            }
         } else {
            $tmp = $d['alternative_email'];
         }
         $users[] = $tmp;
      }
      if (count($users)) {
         $pdf->displayText('<b><i>'.$LANG['common'][104].'</i></b> : ', implode(', ', $users), 1);
      }
      $groups = array();
      foreach ($job->getGroups(Ticket::OBSERVER) as $d) {
         $groups[] = Html::clean(Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
      }
      if (count($groups)) {
         $pdf->displayText('<b><i>'.$LANG['setup'][251].'</i></b> : ', implode(', ', $groups), 1);
      }
      // Assign to
      $users = array();
      foreach ($job->getUsers(Ticket::ASSIGN) as $d) {
         if ($d['users_id']) {
            $tmp = Html::clean(getUserName($d['users_id']));
            if ($d['alternative_email']) {
               $tmp .= ' ('.$d['alternative_email'].')';
            }
         } else {
            $tmp = $d['alternative_email'];
         }
         $users[] = $tmp;
      }
      if (count($users)) {
         $pdf->displayText('<b><i>'.$LANG['job'][5].' ('.$LANG['job'][3].')</i></b> : ', implode(', ', $users), 1);
      }
      $groups = array();
      foreach ($job->getGroups(Ticket::ASSIGN) as $d) {
         $groups[] = Html::clean(Dropdown::getDropdownName("glpi_groups", $d['groups_id']));
      }
      if (count($groups)) {
         $pdf->displayText('<b><i>'.$LANG['job'][5].' ('.$LANG['Menu'][36].')</i></b> : ', implode(', ', $groups), 1);
      }
      if ($job->fields["suppliers_id_assign"]) {
         $pdf->displayText('<b><i>'.$LANG['job'][5].' ('.$LANG['financial'][26].')</i></b> : ', implode(', ', $groups), 1);
      }

      // Linked tickets
      $tickets   = Ticket_Ticket::getLinkedTicketsTo($ID);
      if (is_array($tickets) && count($tickets)) {
         $ticket = new Ticket();
         foreach ($tickets as $linkID => $data) {
            $tmp = Ticket_Ticket::getLinkName($data['link']).' '.$LANG['common'][2].' '.$data['tickets_id'].' : ';
            if ($ticket->getFromDB($data['tickets_id'])) {
               $tmp .= ' : '.$ticket->getName();
            }
            $jobs[] = $tmp;
         }
         $pdf->displayText('<b><i>'.$LANG['job'][55].'</i></b> : ', implode("\n", $jobs), 1);
      }

      // Description
      $pdf->displayText("<b><i>".$LANG['joblist'][6]."</i></b> : ", $job->fields['content']);
      $pdf->displaySpace();
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


   static function pdfCost(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $LANG, $CFG_GLPI, $DB;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG['job'][47]."</b>");

      $pdf->setColumnsSize(20,20,20,20,20);
      $pdf->displayTitle($LANG['job'][20],$LANG['job'][40], $LANG['job'][41],
                         $LANG['job'][42], $LANG['job'][43]);
      $pdf->setColumnsAlign('center','right','right','right','right');

      $total = Ticket::trackingTotalCost($job->fields["actiontime"], $job->fields["cost_time"],
                                         $job->fields["cost_fixed"], $job->fields["cost_material"]);

      $pdf->displayLine(Html::clean(Ticket::getActionTime($job->fields["actiontime"])),
                        Html::clean(Html::formatNumber($job->fields["cost_time"])),
                        Html::clean(Html::formatNumber($job->fields["cost_fixed"])),
                        Html::clean(Html::formatNumber($job->fields["cost_material"])),
                        Html::clean(Html::formatNumber($total)));
      $pdf->displaySpace();
   }


   static function pdfSolution(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $LANG, $CFG_GLPI, $DB;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG['jobresolution'][1]."</b>");

      if ($job->fields['solutiontypes_id'] || !empty($job->fields['solution'])) {
         if ($job->fields['solutiontypes_id']) {
            $title = Html::clean(Dropdown::getDropdownName('glpi_solutiontypes',
                                           $job->getField('solutiontypes_id')));
         } else {
            $title = $LANG['jobresolution'][1];
         }
         $sol = Html::clean(Toolbox::unclean_cross_side_scripting_deep(
                           html_entity_decode($job->getField('solution'),
                                              ENT_QUOTES, "UTF-8")));
         $pdf->displayText("<b><i>$title</i></b> : ", $sol);
      } else {
         $pdf->displayLine($LANG['job'][32]);
      }

      $pdf->displaySpace();
   }


   static function pdfStat(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $LANG;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG['common'][99]."</b>");

      $pdf->setColumnsSize(50, 50);
      $pdf->displayLine($LANG['reports'][60].' : ', Html::convDateTime($job->fields['date']));
      $pdf->displayLine($LANG['sla'][5].' : ', Html::convDateTime($job->fields['due_date']));
      if ($job->fields['status']=='solved' || $job->fields['status']=='closed') {
         $pdf->displayLine($LANG['reports'][64].' : ', Html::convDateTime($job->fields['solvedate']));
      }
      if ($job->fields['status']=='closed') {
         $pdf->displayLine($LANG['reports'][61].' : ', Html::convDateTime($job->fields['closedate']));
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG['common'][100]."</b>");

      $pdf->setColumnsSize(50, 50);
      if ($job->fields['takeintoaccount_delay_stat']>0) {
         $pdf->displayLine($LANG['stats'][12].' : ', Html::clean(Html::timestampToString($job->fields['takeintoaccount_delay_stat'],0)));
      }

      if ($job->fields['status']=='solved' || $job->fields['status']=='closed') {
         if ($job->fields['solve_delay_stat']>0) {
            $pdf->displayLine($LANG['stats'][9].' : ', Html::clean(Html::timestampToString($job->fields['solve_delay_stat'],0)));
         }
      }
      if ($job->fields['status']=='closed') {
         if ($job->fields['close_delay_stat']>0) {
            $pdf->displayLine($LANG['stats'][10].' : ', Html::clean(Html::timestampToString($job->fields['close_delay_stat'],0)));
         }
      }
      if ($job->fields['ticket_waiting_duration']>0) {
         $pdf->displayLine($LANG['joblist'][26].' : ', Html::clean(Html::timestampToString($job->fields['ticket_waiting_duration'],0)));
      }

      $pdf->displaySpace();
   }


   function defineAllTabs($options=array()) {
      global $LANG;

      $onglets = parent::defineAllTabs($options);

      if (Session::haveRight("show_full_ticket","1")) {
         $onglets['_private_'] = $LANG['common'][77];
      }
      unset($onglets['Problem$1']); // TODO add method to print linked Problems
      unset($onglets['Change$1']);  // TODO add method to print linked Changes

      return $onglets;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $private = isset($_REQUEST['item']['_private_']);

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

         case '_private_' :
            // nothing to export, just a flag
            break;

         case 'TicketFollowup$1' :
            PluginPdfTicketFollowup::pdfForTicket($pdf, $item, $private);
            break;

         case 'TicketTask$1' :
            PluginPdfTicketTask::pdfForTicket($pdf, $item, $private);
            break;

         case 'TicketValidation$1' :
            PluginPdfTicketValidation::pdfForTicket($pdf, $item);
            break;

         case 'Ticket$1' :
            self::pdfCost($pdf, $item);
            break;

         case 'Ticket$2' :
            self::pdfSolution($pdf, $item);
            break;

         case 'Ticket$3' :
            PluginPdfTicketSatisfaction::pdfForTicket($pdf, $item);
            break;

         case 'Ticket$4' :
            self::pdfStat($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}