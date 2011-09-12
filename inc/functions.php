<?php

/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
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

// Original Author of file: BALPE Dévi
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Display the Page header = type name, object name, entity name
 *
 * @param $pdf object for output (SimplePDF)
 * @param $ID of the item
 * @param $type of the item
 *
 * @return boolean : true if object found and readable
 */
function plugin_pdf_add_header(PluginPdfSimplePDF $pdf, $ID, CommonDBTM $item) {
   global $LANG;

   $entity = '';
   if ($item->getFromDB($ID) && $item->can($ID,'r')) {
      if (get_class($item)!='Ticket' && get_class($item)!='KnowbaseItem' && $item->fields['name']) {
         $name = $item->fields['name'];
      } else {
         $name = $LANG["common"][2].' '.$ID;
      }
      if (Session::isMultiEntitiesMode() && isset($item->fields['entities_id'])) {
         $entity = ' ('.Html::clean(Dropdown::getDropdownName('glpi_entities',
                                                             $item->fields['entities_id'])).')';
      }
      $pdf->setHeader($item->getTypeName()." - <b>$name</b>$entity");

      return true;
   }
   return false;
}


function plugin_pdf_main_ticket(PluginPdfSimplePDF $pdf, Ticket $job) {
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
            Html::clean(Dropdown::getDropdownName("glpi_ticketcategories",
                                                 $job->fields["ticketcategories_id"])));

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
   if ($job->fields["itemtype"] && class_exists($job->fields["itemtype"])) {
      $item = new $job->fields["itemtype"]();
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

function plugin_pdf_cost(PluginPdfSimplePDF $pdf, Ticket $job) {
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

function plugin_pdf_solution(PluginPdfSimplePDF $pdf, Ticket $job) {
   global $LANG, $CFG_GLPI, $DB;

   $pdf->setColumnsSize(100);
   $pdf->displayTitle("<b>".$LANG['jobresolution'][1]."</b>");

   if ($job->fields['ticketsolutiontypes_id'] || !empty($job->fields['solution'])) {
      if ($job->fields['ticketsolutiontypes_id']) {
         $title = Html::clean(Dropdown::getDropdownName('glpi_ticketsolutiontypes',
                                        $job->getField('ticketsolutiontypes_id')));
      } else {
         $title = $LANG['jobresolution'][1];
      }
      $sol = Html::clean(Toobox::unclean_cross_side_scripting_deep(
                        html_entity_decode($job->getField('solution'),
                                           ENT_QUOTES, "UTF-8")));
      $pdf->displayText("<b><i>$title</i></b> : ", $sol);
   } else {
      $pdf->displayLine($LANG['job'][32]);
   }

   $pdf->displaySpace();
}


function plugin_pdf_ticketstat(PluginPdfSimplePDF $pdf, Ticket $job) {
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

function plugin_pdf_validation(PluginPdfSimplePDF $pdf, Ticket $ticket) {
   global $LANG, $CFG_GLPI, $DB;

   $pdf->setColumnsSize(100);
   $pdf->displayTitle("<b>".$LANG['validation'][7]."</b>");

   if (!Session::haveRight('validate_ticket',1) && !Session::haveRight('create_validation ',1)) {
      return false;
   }
   $ID = $ticket->getField('id');

   $query = "SELECT *
             FROM `glpi_ticketvalidations`
             WHERE `tickets_id` = '".$ticket->getField('id')."'
             ORDER BY submission_date DESC";
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   if ($number) {
      $pdf->setColumnsSize(20,19,21,19,21);
      $pdf->displayTitle($LANG['validation'][2],
                         $LANG['validation'][3],
                         $LANG['validation'][18],
                         $LANG['validation'][4],
                         $LANG['validation'][21]);

      while ($row = $DB->fetch_assoc($result)) {
         $pdf->setColumnsSize(20,19,21,19,21);
         $pdf->displayLine(TicketValidation::getStatus($row['status']),
                           Html::convDateTime($row["submission_date"]),
                           getUserName($row["users_id"]),
                           Html::convDateTime($row["validation_date"]),
                           getUserName($row["users_id_validate"]));
         $tmp = trim($row["comment_submission"]);
         $pdf->displayText("<b><i>".$LANG['validation'][5]."</i></b> : ",
            (empty($tmp) ? $LANG['common'][49] : $tmp), 1);

         if ($row["validation_date"]) {
            $tmp = trim($row["comment_validation"]);
            $pdf->displayText("<b><i>".$LANG['validation'][6]."</i></b> : ",
               (empty($tmp) ? $LANG['common'][49] : $tmp), 1);
         }
      }
   } else {
      $pdf->displayLine($LANG['search'][15]);
   }
   $pdf->displaySpace();
}

function plugin_pdf_followups(PluginPdfSimplePDF $pdf, Ticket $job, $private) {
   global $LANG, $CFG_GLPI, $DB;

   $ID = $job->getField('id');

   //////////////followups///////////
   $pdf->setColumnsSize(100);
   $pdf->displayTitle("<b>".$LANG['plugin_pdf']['ticket'][1]."</b>");

   $RESTRICT = "";
   if (!$private) {
      // Don't show private'
      $RESTRICT=" AND `is_private` = '0' ";
   } else if (!Session::haveRight("show_full_ticket","1")) {
      // No right, only show connected user private one
      $RESTRICT=" AND (`is_private` = '0'
                       OR `users_id` ='".Session::getLoginUserID()."' ) ";
   }

   $query = "SELECT *
             FROM `glpi_ticketfollowups`
             WHERE `tickets_id` = '$ID'
             $RESTRICT
             ORDER BY `date` DESC";
   $result=$DB->query($query);

   if (!$DB->numrows($result)) {
      $pdf->displayLine($LANG['job'][12]);
   } else {
      while ($data=$DB->fetch_array($result)) {
         $pdf->setColumnsSize(44,14,42);
         $pdf->displayTitle("<b><i>".$LANG['job'][45]."</i></b>", // Source
                            "<b><i>".$LANG['common'][27]."</i></b>", // Date
                            "<b><i>".$LANG['common'][37]."</i></b>"); // Author

         if ($data['requesttypes_id']) {
            $lib = Dropdown::getDropdownName('glpi_requesttypes', $data['requesttypes_id']);
         } else {
            $lib = '';
         }
         if ($data['is_private']) {
            $lib .= ' ('.$LANG['common'][77].')';
         }
         $pdf->displayLine(Html::clean($lib),
                           Html::convDateTime($data["date"]),
                           Html::clean(getUserName($data["users_id"])));
         $pdf->displayText("<b><i>".$LANG['joblist'][6]."</i></b> : ", $data["content"]);
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_tasks(PluginPdfSimplePDF $pdf, Ticket $job, $private) {
   global $LANG, $CFG_GLPI, $DB;

   $ID = $job->getField('id');

   //////////////Tasks///////////
   $pdf->setColumnsSize(100);
   $pdf->displayTitle("<b>".$LANG['plugin_pdf']['ticket'][2]."</b>");

   $RESTRICT = "";
   if (!$private) {
      // Don't show private'
      $RESTRICT=" AND `is_private` = '0' ";
   } else if (!Session::haveRight("show_full_ticket","1")) {
      // No right, only show connected user private one
      $RESTRICT=" AND (`is_private` = '0'
                       OR `users_id` ='".Session::getLoginUserID()."' ) ";
   }

   $query = "SELECT *
             FROM `glpi_tickettasks`
             WHERE `tickets_id` = '$ID'
             $RESTRICT
             ORDER BY `date` DESC";
   $result=$DB->query($query);

   if (!$DB->numrows($result)) {
      $pdf->displayLine($LANG['job'][50]);
   } else {
      while ($data=$DB->fetch_array($result)) {

         $actiontime = Html::timestampToString($data['actiontime'], false);

         $query2 = "SELECT *
                    FROM `glpi_ticketplannings`
                    WHERE `tickettasks_id` = '".$data['id']."'";
         $result2=$DB->query($query2);

         if ($DB->numrows($result2)==0) {
            $planification=$LANG['job'][32];
         } else {
            $data2 = $DB->fetch_array($result2);
            $planification = Planning::getState($data2["state"])." - ".Html::convDateTime($data2["begin"]).
                             " -> ".Html::convDateTime($data2["end"])." - ".getUserName($data2["users_id"]);
         }

         $pdf->setColumnsSize(40,14,30,16);
         $pdf->displayTitle("<b><i>".$LANG['common'][17]."</i></b>", // Source
                            "<b><i>".$LANG['common'][27]."</i></b>", // Date
                            "<b><i>".$LANG['common'][37]."</i></b>", // Author
                            "<b><i>".$LANG['job'][31]."</i></b>"); // Durée

         if ($data['taskcategories_id']) {
            $lib = Dropdown::getDropdownName('glpi_taskcategories', $data['taskcategories_id']);
         } else {
            $lib = '';
         }
         if ($data['is_private']) {
            $lib .= ' ('.$LANG['common'][77].')';
         }
         $pdf->displayLine(Html::clean($lib),
                           Html::convDateTime($data["date"]),
                           Html::clean(getUserName($data["users_id"])),
                           Html::clean($actiontime));
         $pdf->displayText("<b><i>".$LANG['joblist'][6]."</i></b> : ", Html::clean($data["content"]),1);
         $pdf->displayText("<b><i>".$LANG['job'][35]."</i></b> : ", Html::clean($planification),1);
      }
   }
   $pdf->displaySpace();
}



function plugin_pdf_pluginhook($onglet, PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   global $PLUGIN_HOOKS;

   if (preg_match("/^(.*)_([0-9]*)$/",$onglet,$split)) {
      $plug = $split[1];
      $ID_onglet = $split[2];

      if (isset($PLUGIN_HOOKS["headings_actionpdf"][$plug])) {
         if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
            include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
         }

         $function = $PLUGIN_HOOKS["headings_actionpdf"][$plug];
         if (is_callable($function)) {
            $actions = call_user_func($function, $item);

            if (isset($actions[$ID_onglet]) && is_callable($actions[$ID_onglet])) {
               call_user_func($actions[$ID_onglet], $pdf, $item);
               return true;
            }
         }
      }
   }
}

function plugin_pdf_general(CommonDBTM $item, $tab_id, $tab, $page=0, $render=true) {

   $pdf = new PluginPdfSimplePDF('a4', ($page ? 'landscape' : 'portrait'));

   $nb_id = count($tab_id);

   foreach ($tab_id as $key => $id) {
      if (plugin_pdf_add_header($pdf, $id, $item)) {
         $pdf->newPage();
      } else {
         // Object not found or no right to read
         continue;
      }

         case 'Ticket' :
            plugin_pdf_main_ticket($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) { // Value not from Job::defineTabs but from plugin_pdf_prefPDF
                  case 'private':
                     break;

                  case 1:
                     plugin_pdf_followups($pdf, $item, in_array('private',$tab));
                     break;

                  case 2:
                     plugin_pdf_tasks($pdf, $item, in_array('private',$tab));
                     break;

                  case 4:
                     plugin_pdf_solution($pdf, $item);
                     break;

                  case 3:
                     plugin_pdf_cost($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_validation($pdf, $item);
                     break;

                  case 8:
                     plugin_pdf_ticketstat($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

      } // Switch type
   } // Each ID

   if($render) {
      $pdf->render();
   } else {
      return $pdf->output();
   }
}

?>