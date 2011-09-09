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


function plugin_pdf_main_computer(PluginPdfSimplePDF $pdf, Computer $computer) {
   global $LANG;

   $ID = $computer->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$computer->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.Html::convDateTime($computer->fields['date_mod']);
   if(!empty($computer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$computer->fields['template_name'].')';
   } else if($computer->fields['is_ocs_import']) {
      $col2 = ' ('.$LANG['ocsng'][7].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$computer->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_states',$computer->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_locations', $computer->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_computertypes',
                                              $computer->fields['computertypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($computer->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $computer->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$computer->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_computermodels',
                                              $computer->fields['computermodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$computer->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$computer->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($computer->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$computer->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_groups',$computer->fields['groups_id'])),
      '<b><i>'.$LANG['setup'][88].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_networks', $computer->fields['networks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['setup'][89].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_domains', $computer->fields['domains_id'])),
      '<b><i>'.$LANG['computers'][53].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_operatingsystemservicepacks',
                                              $computer->fields['operatingsystemservicepacks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['computers'][9].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                              $computer->fields['operatingsystems_id'])),
      '<b><i>'.$LANG['computers'][52].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_operatingsystemversions',
                                              $computer->fields['operatingsystemversions_id'])));


   $pdf->displayLine(
      '<b><i>'.$LANG['computers'][11].' :</i></b> '.$computer->fields['os_licenseid'],
      '<b><i>'.$LANG['computers'][10].' :</i></b> '.$computer->fields['os_license_number']);

   if ($computer->fields['is_ocs_import']) {
      $col1 = '<b><i>'.$LANG['ocsng'][6].' '.$LANG['Menu'][33].' :</i></b> '.$LANG['choice'][1];
   } else {
      $col1 = '<b><i>'.$LANG['ocsng'][6].' '.$LANG['Menu'][33].' :</i></b> '.$LANG['choice'][0];
   }

   $pdf->displayLine($col1,'<b><i>'.$LANG['computers'][51].' :</i></b> '.
      Html::clean(Dropdown::getDropdownName('glpi_autoupdatesystems',
                                           $computer->fields['autoupdatesystems_id'])));

   $pdf->setColumnsSize(100);
   $pdf->displayLine(
      '<b><i>'.$LANG['computers'][58].' :</i></b> '.$computer->fields['uuid']);


   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $computer->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_printer(PluginPdfSimplePDF $pdf, Printer $printer) {
   global $LANG;

   $ID = $printer->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$printer->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.Html::convDateTime($printer->fields['date_mod']);
   if(!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$printer->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$printer->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_states', $printer->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_locations', $printer->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_printertypes',
                                              $printer->fields['printertypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($printer->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $printer->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$printer->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_printermodels',
                                              $printer->fields['printermodels_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][18].' :</i></b> '.$printer->fields['contact'],
      '<b><i>'.$LANG['common'][19].' :</i></b> '.$printer->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($printer->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$printer->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_groups', $printer->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
         ($printer->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine(
      '<b><i>'.$LANG['setup'][89].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_domains', $printer->fields['domains_id'])),
     '<b><i>'.$LANG['setup'][88].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_networks', $printer->fields['networks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['devices'][6].' :</i></b> '.$printer->fields['memory_size'],
      '<b><i>'.$LANG['printers'][30].' :</i></b> '.$printer->fields['init_pages_counter']);

   $opts = array(
      'have_serial'   => $LANG['printers'][14],
      'have_parallel' => $LANG['printers'][15],
      'have_usb'      => $LANG['printers'][27],
      'have_ethernet' => $LANG['printers'][28],
      'have_wifi'     => $LANG['printers'][29],
   );
   foreach ($opts as $key => $val) {
      if (!$printer->fields[$key]) {
         unset($opts[$key]);
      }
   }

   $pdf->setColumnsSize(100);
   $pdf->displayLine('<b><i>'.$LANG['printers'][18].' : </i></b>'.implode(', ',$opts));


   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $printer->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_monitor(PluginPdfSimplePDF $pdf, Monitor $item) {
   global $LANG;

   $ID = $item->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_monitortypes',
                                                 $item->fields['monitortypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                 $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_monitormodels',
                                                 $item->fields['monitormodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
            ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine( '<b><i>'.$LANG['monitors'][21].' :</i></b> '.$item->fields['size']);


   $opts = array(
      'have_micro'         => $LANG['monitors'][14],
      'have_speaker'       => $LANG['monitors'][15],
      'have_subd'          => $LANG['monitors'][19],
      'have_bnc'           => $LANG['monitors'][20],
      'have_dvi'           => $LANG['monitors'][32],
      'have_pivot'         => $LANG['monitors'][33],
      'have_hdmi'          => $LANG['monitors'][34],
      'have_displayport'   => $LANG['monitors'][31],
   );
   foreach ($opts as $key => $val) {
      if (!$item->fields[$key]) {
         unset($opts[$key]);
      }
   }
   $pdf->setColumnsSize(100);
   $pdf->displayLine(
      '<b><i>'.$LANG['monitors'][18].' : </i></b>'.
         (count($opts) ? implode(', ',$opts) : $LANG['job'][32]));

   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_network(PluginPdfSimplePDF $pdf, NetworkEquipment $item) {
   global $LANG;

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_networkequipmenttypes', $item->fields['networkequipmenttypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                 $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_networkequipmentmodels',
                                                 $item->fields['networkequipmentmodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['setup'][88].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_networks', $item->fields['networks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['setup'][89].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_domains', $item->fields['domains_id'])),
      '<b><i>'.$LANG['setup'][71].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_networkequipmentfirmwares', $item->fields['networkequipmentfirmwares_id'])));

   $pdf->displayLine('<b><i>'.$LANG['networking'][14].' :</i></b> '.$item->fields['ip'],
                     '<b><i>'.$LANG['networking'][5].' :</i></b> '.$item->fields['ram']);

   $pdf->displayLine('<b><i>'.$LANG['networking'][15].' :</i></b> '.$item->fields['mac'],
                     '');

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_phone(PluginPdfSimplePDF $pdf, Phone $item) {
   global $LANG;

   $ID = $item->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_phonetypes', $item->fields['phonetypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                 $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_phonemodels',
                                                 $item->fields['phonemodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
            Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
            ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine(
      '<b><i>'.$LANG['peripherals'][18].' :</i></b> '.$item->fields['brand'],
      '<b><i>'.$LANG['phones'][36].' :</i></b> '.Dropdown::getYesNo($item->fields['phonepowersupplies_id']));

   $pdf->displayLine('<b><i>'.$LANG['setup'][71].' :</i></b> '.$item->fields['firmware'],
                     '<b><i>'.$LANG['phones'][40].' :</i></b> '.$item->fields['number_line']);

   $opts = array(
      'have_headset' => $LANG['phones'][38],
      'have_hp'      => $LANG['phones'][39],
   );
   foreach ($opts as $key => $val) {
      if (!$item->fields[$key]) {
         unset($opts[$key]);
      }
   }

   $pdf->setColumnsSize(100);
   $pdf->displayLine('<b><i>'.$LANG['monitors'][18].' : </i></b>'.implode(', ',$opts));

   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_peripheral(PluginPdfSimplePDF $pdf, Peripheral $item) {
   global $LANG;

   $ID = $item->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_peripheraltypes',
                                              $item->fields['peripheraltypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_peripheralmodels',
                                              $item->fields['peripheralmodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
         ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->setColumnsSize(100);
   $pdf->displayLine('<b><i>'.$LANG['peripherals'][18].' :</i></b> '.$item->fields['brand']);

   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_cartridges(PluginPdfSimplePDF $pdf, Printer $p, $old=false) {
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


function plugin_pdf_financial(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   global $CFG_GLPI,$LANG;

   $ID = $item->getField('id');

   if (!Session::haveRight("infocom","r")) {
      return false;
   }

   $ic = new Infocom();

   $pdf->setColumnsSize(100);
   if ($ic->getFromDBforDevice(get_class($item),$ID)) {
      $pdf->displayTitle("<b>".$LANG["financial"][3]."</b>");

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][26]." :</i></b> ".
            Html::clean(Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"])),
         "<b><i>".$LANG["financial"][87]." :</i></b> ".
            Html::clean(Dropdown::getDropdownName("glpi_budgets", $ic->fields["budgets_id"])));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][18]." :</i></b> ".$ic->fields["order_number"],
         "<b><i>".$LANG["financial"][28]." :</i></b> ".Html::convDate($ic->fields["order_date"]));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][20]." :</i></b> ".$ic->fields["immo_number"],
         "<b><i>".$LANG["financial"][14]." :</i></b> ".Html::convDate($ic->fields["buy_date"]));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][82]." :</i></b> ".$ic->fields["bill"],
         "<b><i>".$LANG["financial"][27]." :</i></b> ".Html::convDate($ic->fields["delivery_date"]));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][19]." :</i></b> ".$ic->fields["delivery_number"],
         "<b><i>".$LANG["financial"][76]." :</i></b> ".Html::convDate($ic->fields["use_date"]));

      $pdf->displayLine(
         "<b><i>".$LANG["rulesengine"][13]." :</i></b> ".Html::clean(Html::formatNumber($ic->fields["value"])),
         "<b><i>".$LANG["financial"][114]." :</i></b> ".Html::convDate($ic->fields["inventory_date"]));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][78]." :</i></b> ".Html::clean(Html::formatNumber($ic->fields["warranty_value"])),
         "<b><i>".$LANG["financial"][23]." :</i></b> ".$ic->fields["sink_time"]." ". $LANG['financial'][9].
            ' ('.Infocom::getAmortTypeName($ic->fields["sink_type"]).')');

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][81]." :</i></b> ".
            Infocom::Amort($ic->fields["sink_type"], $ic->fields["value"], $ic->fields["sink_time"],
                           $ic->fields["sink_coeff"], $ic->fields["buy_date"], $ic->fields["use_date"],
                           $CFG_GLPI['date_tax'],"n"),
         "<b><i>".$LANG["financial"][77]." :</i></b> ".$ic->fields["sink_coeff"]);

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][89]." :</i></b> ".
            Html::clean(Infocom::showTco($item->getField('ticket_tco'), $ic->fields["value"])),
         "<b><i>".$LANG["financial"][90]." :</i></b> ".
            Html::clean(Infocom::showTco($item->getField('ticket_tco'), $ic->fields["value"], $ic->fields["buy_date"])));

      $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $ic->fields["comment"], 1);

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG["financial"][7]."</b>");

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][29]." :</i></b> ".Html::convDate($ic->fields["warranty_date"]),
         "<b><i>".$LANG["financial"][15]." :</i></b> ".$ic->fields["warranty_duration"].' '.$LANG['financial'][57].
               ', '.$LANG['financial'][88].Infocom::getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]));

      $col1 = "<b><i>".$LANG["setup"][247]." :</i></b> ";
      if ($ic->fields["alert"] == 0) {
         $col1 .= $LANG['choice'][0];
      } else if ($ic->fields["alert"] == 4) {
         $col1 .= $LANG["financial"][80];
      }
      $pdf->displayLine(
         "<b><i>".$LANG["financial"][16]." :</i></b> ".$ic->fields["warranty_info"],
         $col1);
   } else {
      $pdf->displayTitle("<b>".$LANG['plugin_pdf']['financial'][1]."</b>");
   }

   $pdf->displaySpace();
}


function plugin_pdf_main_software(PluginPdfSimplePDF $pdf, Software $software) {
   global $LANG;

   $ID = $software->getField('id');

   $col1 = '<b>'.$LANG['common'][2].' '.$software->fields['id'].'</b>';
   $col2 = '<b>'.$LANG['common'][26].' : '.Html::convDateTime($software->fields['date_mod']).'</b>';

   if (!empty($software->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$software->fields['template_name'].')';
   }

   $pdf->setColumnsSize(50,50);
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$software->fields['name'],
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_manufacturers', $software->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_locations', $software->fields['locations_id'])),
      '<b><i>'.$LANG['common'][36].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_softwarecategories', $software->fields['softwarecategories_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($software->fields['users_id_tech']),
      '<b><i>'.$LANG['software'][46].' :</i></b> ' .
         ($software->fields['is_helpdesk_visible']?$LANG['choice'][1]:$LANG['choice'][0]));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($software->fields['users_id']),
      '<b><i>'.$LANG['software'][29].' :</i></b> '.
         ($software->fields['is_update']?$LANG['choice'][1]:$LANG['choice'][0]), $col2);

   if ($software->fields['softwares_id']>0) {
      $col2 = '<b><i> '.$LANG['pager'][2].' </i></b> '.
               Html::clean(Dropdown::getDropdownName('glpi_softwares',
                                                    $software->fields['softwares_id']));
   } else {
      $col2 = '';
   }
   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id'])),
      $col2);


   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $software->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_device(PluginPdfSimplePDF $pdf, Computer $computer) {
   global $DB, $LANG;

   $devtypes = Computer_Device::getDeviceTypes();

   $ID = $computer->getField('id');
   if (!$computer->can($ID, 'r')) {
      return false;
   }

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG["title"][30].'</b>');

   $pdf->setColumnsSize(3,14,42,41);

   foreach ($devtypes as $itemtype) {
      $device = new $itemtype;

      $specificities = $device->getSpecifityLabel();
      $specif_fields = array_keys($specificities);
      $specif_text = implode(',',$specif_fields);
      if (!empty($specif_text)) {
         $specif_text=" ,".$specif_text." ";
      }

      $linktable = getTableForItemType('Computer_'.$itemtype);
      $fk = getForeignKeyFieldForTable(getTableForItemType($itemtype));

      $query = "SELECT count(*) AS NB, `id`, `$fk` $specif_text
               FROM `$linktable`
               WHERE `computers_id` = '$ID'
               GROUP BY `$fk` $specif_text";

      foreach($DB->request($query) as $data) {

         if ($device->getFromDB($data[$fk])) {

            $spec = $device->getFormData();
            $col4 = '';
            if (isset($spec['label']) && count($spec['label'])) {
               $colspan = (60/count($spec['label']));
               foreach ($spec['label'] as $i => $label) {
                  if (isset($spec['value'][$i])) {
                     $col4 .= '<b><i>'.$spec['label'][$i].' :</i></b> '.$spec['value'][$i]." ";
                  } else {
                     $col4 .= '<b><i>'.$spec['label'][$i].' :</i></b> '.$data['specificity']." ";
                  }
               }
            }
            $pdf->displayLine($data['NB'], $device->getTypeName(), $device->getName(), $col4);
         }
      }
   }

   $pdf->displaySpace();
}


function plugin_pdf_versions(PluginPdfSimplePDF $pdf, Software $item){
   global $DB,$LANG;

   $sID = $item->getField('id');

   $query = "SELECT `glpi_softwareversions`.*,
                    `glpi_states`.`name` AS sname,
                    `glpi_operatingsystems`.`name` AS osname
             FROM `glpi_softwareversions`
             LEFT JOIN `glpi_states`
                  ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
             LEFT JOIN `glpi_operatingsystems`
                  ON (`glpi_operatingsystems`.`id` = `glpi_softwareversions`.`operatingsystems_id`)
             WHERE (`softwares_id` = '$sID')
             ORDER BY `name`";

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG['software'][5].'</b>');

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         $pdf->setColumnsSize(13,13,30,14,30);
         $pdf->displayTitle('<b><i>'.$LANG['software'][5].'</i></b>',
                            '<b><i>'.$LANG['state'][0].'</i></b>',
                            '<b><i>'.$LANG['computers'][9].'</i></b>',
                            '<b><i>'.$LANG['software'][19].'</i></b>',
                            '<b><i>'.$LANG['common'][25].'</i></b>');
         $pdf->setColumnsAlign('left','left','left','right','left');

         for ($tot=$nb=0 ; $data=$DB->fetch_assoc($result) ; $tot+=$nb) {
            $nb = Computer_SoftwareVersion::countForVersion($data['id']);
            $pdf->displayLine(
               (empty($data['name'])?"(".$data['id'].")":$data['name']),
               $data['sname'],
               $data['osname'],
               $nb,
               str_replace(array("\r","\n")," ",$data['comment'])
            );
         }
         $pdf->setColumnsAlign('left','right','left', 'right','left');
         $pdf->displayTitle('','',"<b>".$LANG['common'][33]." : </b>",$tot, '');
      } else {
         $pdf->displayLine($LANG['search'][15]);
      }
   } else {
      $pdf->displayLine($LANG['search'][15]."!");
   }
   $pdf->displaySpace();
}


function plugin_pdf_versionbyentity(PluginPdfSimplePDF $pdf, SoftwareVersion $version) {
   global $DB, $CFG_GLPI, $LANG;

   $softwareversions_id = $version->getField('id');

   $pdf->setColumnsSize(75,25);
   $pdf->setColumnsAlign('left', 'right');

   $pdf->displayTitle('<b>'.$LANG['entity'][0], $LANG['software'][19].'</b>');

   $lig = $tot = 0;
   if (in_array(0, $_SESSION["glpiactiveentities"])) {
      $nb = Computer_SoftwareVersion::countForVersion($softwareversions_id,0);
      if ($nb>0) {
         $pdf->displayLine($LANG['entity'][2], $nb);
         $tot += $nb;
         $lig++;
      }
   }
   $sql = "SELECT `id`, `completename`
           FROM `glpi_entities` " .
           getEntitiesRestrictRequest('WHERE', 'glpi_entities') ."
           ORDER BY `completename`";

   foreach ($DB->request($sql) as $ID => $data) {
      $nb = Computer_SoftwareVersion::countForVersion($softwareversions_id,$ID);
      if ($nb>0) {
         $pdf->displayLine($data["completename"], $nb);
         $tot += $nb;
         $lig++;
      }
   }

   if ($tot>0) {
      if ($lig>1) {
         $pdf->displayLine($LANG['common'][33], $tot);
      }
   } else {
      $pdf->setColumnsSize(100);
      $pdf->displayLine($LANG['search'][15]);
   }
   $pdf->displaySpace();
}


function plugin_pdf_licensecomputer(PluginPdfSimplePDF $pdf, SoftwareLicense $license) {
   global $DB, $LANG;

   $ID = $license->getField('id');

      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_computers_softwarelicenses`
                       INNER JOIN `glpi_computers`
                           ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                       WHERE `glpi_computers_softwarelicenses`.`softwarelicenses_id` = '$ID'" .
                             getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                             AND `glpi_computers`.`is_deleted` = '0'
                             AND `glpi_computers`.`is_template` = '0'";

      $number = 0;
      if ($result =$DB->query($query_number)) {
         $number  = $DB->result($result,0,0);
      }

      $pdf->setColumnsSize(100);
      $pdf->setColumnsAlign('center');
      $title = '<b>'.$LANG['software'][9].' : </b>';
      if ($number) {
         if ($number>$_SESSION['glpilist_limit']) {
            $title .= $_SESSION['glpilist_limit'].' / '.$number;
         } else {
            $title .= $number;
         }
         $pdf->displayTitle($title);

         $query = "SELECT `glpi_computers_softwarelicenses`.*,
                          `glpi_computers`.`name` AS compname,
                          `glpi_computers`.`id` AS cID,
                          `glpi_computers`.`serial`,
                          `glpi_computers`.`otherserial`,
                          `glpi_users`.`name` AS username,
                          `glpi_softwarelicenses`.`name` AS license,
                          `glpi_softwarelicenses`.`id` AS vID,
                          `glpi_softwarelicenses`.`name` AS vername,
                          `glpi_entities`.`name` AS entity,
                          `glpi_locations`.`completename` AS location,
                          `glpi_states`.`name` AS state,
                          `glpi_groups`.`name` AS groupe,
                          `glpi_softwarelicenses`.`name` AS lname,
                          `glpi_softwarelicenses`.`id` AS lID
                   FROM `glpi_computers_softwarelicenses`
                   INNER JOIN `glpi_softwarelicenses`
                        ON (`glpi_computers_softwarelicenses`.`softwarelicenses_id`
                             = `glpi_softwarelicenses`.`id`)
                   INNER JOIN `glpi_computers`
                        ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                   LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_locations`
                        ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                   LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                   LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                   LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                   WHERE (`glpi_softwarelicenses`.`id` = '$ID') " .
                          getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                          AND `glpi_computers`.`is_deleted` = '0'
                          AND `glpi_computers`.`is_template` = '0'
                   ORDER BY `entity`, `compname`
                   LIMIT 0," . intval($_SESSION['glpilist_limit']);
         $result=$DB->query($query);

         $showEntity = ($license->isRecursive());
         if ($showEntity) {
            $pdf->setColumnsSize(12,12,12,12,16,12,12,12);
            $pdf->displayTitle(
               '<b><i>'.$LANG['entity'][0],  // entity
               $LANG['common'][16],          // name
               $LANG['common'][19],          // serial
               $LANG['common'][20],          // otherserial
               $LANG['common'][15],          // location
               $LANG['state'][0],            // state
               $LANG['common'][35] ,         // groupe
               $LANG['common'][34].'</i></b>'   // user
            );
         } else {
            $pdf->setColumnsSize(14,14,14,18,14,13,13);
            $pdf->displayTitle(
               '<b><i>'.$LANG['common'][16], // name
               $LANG['common'][19],          // serial
               $LANG['common'][20],          // otherserial
               $LANG['common'][15],          // location
               $LANG['state'][0],            // state
               $LANG['common'][35] ,         // groupe
               $LANG['common'][34].'</i></b>'   // user
            );
         }
         while ($data=$DB->fetch_assoc($result)) {
            $compname = $data['compname'];
            if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
               $compname .= " (".$data['cID'].")";
            }
            $entname = (empty($data['entity']) ? $LANG['entity'][2] : $data['entity']);

            if ($showEntity) {
               $pdf->displayLine(
                  $entname,
                  $compname,
                  $data['serial'],
                  $data['otherserial'],
                  $data['location'],
                  $data['state'],
                  $data['groupe'],
                  $data['username']
               );
            } else {
               $pdf->displayLine(
                  $compname,
                  $data['serial'],
                  $data['otherserial'],
                  $data['location'],
                  $data['state'],
                  $data['groupe'],
                  $data['username']
               );
            }
         }
      } else {
         $pdf->displayTitle($title.$LANG['search'][15]);
      }
      $pdf->displaySpace();
}


function plugin_pdf_licensebyentity(PluginPdfSimplePDF $pdf, SoftwareLicense $license) {
   global $DB, $LANG;

   $ID = $license->getField('id');

   $pdf->setColumnsSize(65,35);
   $pdf->setColumnsAlign('left', 'right');
   $pdf->displayTitle(
      '<b><i>'.$LANG['entity'][0].'</i></b>',
      '<b><i>'.$LANG['software'][9]." - ".$LANG['tracking'][29].'</i></b>');

   $tot = 0;
   if (in_array(0,$_SESSION["glpiactiveentities"])) {
      $nb = Computer_SoftwareLicense::countForLicense($ID, 0);
      if ($nb>0) {
         $pdf->displayLine($LANG['entity'][2], $nb);
         $tot += $nb;
      }
   }
   $sql = "SELECT `id`, `completename`
           FROM `glpi_entities` " .
           getEntitiesRestrictRequest('WHERE', 'glpi_entities') ."
           ORDER BY `completename`";

   foreach ($DB->request($sql) as $entity => $data) {
      $nb = Computer_SoftwareLicense::countForLicense($ID,$entity);
      if ($nb>0) {
         $pdf->displayLine($data["completename"], $nb);
         $tot += $nb;
      }
   }

   if ($tot>0) {
      $pdf->displayLine($LANG['common'][33], $tot);
   } else {
      $pdf->setColumnsSize(100);
      $pdf->setColumnsAlign('center');
      $pdf->displayLine($LANG['search'][15]);
   }
   $pdf->displaySpace();
}


function plugin_pdf_main_license(PluginPdfSimplePDF $pdf, SoftwareLicense $license, $main=true, $cpt=true) {
   global $DB,$LANG;

   $ID = $license->getField('id');

   $pdf->setColumnsSize(100);
   $entity = '';
   if (Session::isMultiEntitiesMode() && !$main) {
      $entity = ' ('.Html::clean(Dropdown::getDropdownName('glpi_entities',
                                                          $license->fields['entities_id'])).')';
   }
   $pdf->displayTitle('<b><i>'.$LANG['common'][2]."</i> : $ID</b>$entity");

   $pdf->setColumnsSize(50,50);

   $pdf->displayLine(
      '<b><i>'.$LANG['help'][31].'</i></b>: '.
         Html::clean(Dropdown::getDropdownName('glpi_softwares', $license->fields['softwares_id'])),
      '<b><i>'.$LANG['common'][17].'</i></b>: '.
         Html::clean(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                              $license->fields['softwarelicensetypes_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][16].'</i></b>: '.$license->fields['name'],
                     '<b><i>'.$LANG['common'][19].'</i></b>: '.$license->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['software'][1].'</i></b>: '.
         Html::clean(Dropdown::getDropdownName('glpi_softwareversions',
                                              $license->fields['softwareversions_id_buy'])),
      '<b><i>'.$LANG['common'][20].'</i></b>: '.$license->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['software'][2].'</i></b>: '.
         Html::clean(Dropdown::getDropdownName('glpi_softwareversions',
                                              $license->fields['softwareversions_id_use'])),
      '<b><i>'.$LANG['software'][32].'</i></b>: '.Html::convDate($license->fields['expire']));

   $col2 = '';
   if ($cpt) {
      $col2 = '<b><i>'.$LANG['software'][9].'</i></b>: '.
              Computer_SoftwareLicense::countForLicense($ID);
   }
   $pdf->displayLine(
      '<b><i>'.$LANG['tracking'][29].'</i></b>: '.
         ($license->fields['number']>0?$license->fields['number']:$LANG['software'][4]),
      $col2);

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $license->fields['comment'], 1);

   if ($main) {
      $pdf->displaySpace();
   }
}


function plugin_pdf_main_version(PluginPdfSimplePDF $pdf, SoftwareVersion $version) {
   global $DB,$LANG;

   $ID = $version->getField('id');

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b><i>'.$LANG['common'][2]."</i> : $ID</b>");

   $pdf->setColumnsSize(50,50);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].'</i></b>: '.$version->fields['name'],
      '<b><i>'.$LANG['help'][31].'</i></b>: '.
         Html::clean(Dropdown::getDropdownName('glpi_softwares', $version->fields['softwares_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG["state"][0].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_states', $version->fields['states_id'])),
      '<b><i>'.$LANG['computers'][9].' :</i></b> '.
         Html::clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                              $version->fields['operatingsystems_id'])));

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $version->fields['comment']);
   $pdf->displaySpace();
}


function plugin_pdf_licenses(PluginPdfSimplePDF $pdf, Software $software,$infocom) {
   global $DB,$LANG;

   $sID = $software->getField('id');
   $license = new SoftwareLicense();

   $query = "SELECT `id`
             FROM `glpi_softwarelicenses`
             WHERE `softwares_id` = '$sID' " .
             getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true) . "
             ORDER BY `name`";

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG['software'][11].'</b>');

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)) {
         for ($tot=0 ; $data=$DB->fetch_assoc($result) ; ) {
            if ($license->getFromDB($data['id'])) {
               plugin_pdf_main_license($pdf,$license,false);
               if ($infocom) {
                  plugin_pdf_financial($pdf,$license);
               }
            }
         }
      } else {
         $pdf->displayLine($LANG['search'][15]);
      }
   } else {
      $pdf->displayLine($LANG['search'][15]."!");
   }
   $pdf->displaySpace();
}


function plugin_pdf_installations(PluginPdfSimplePDF $pdf, CommonDBTM $item){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = $item->getType();
   $crit = ($type=='Software' ? 'softwares_id' : 'id');

   if ($type=='Software') {
      $crit = 'softwares_id';
      // Software ID
      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_computers_softwareversions`
                       INNER JOIN `glpi_softwareversions`
                           ON (`glpi_computers_softwareversions`.`softwareversions_id`
                                 = `glpi_softwareversions`.`id`)
                       INNER JOIN `glpi_computers`
                           ON (`glpi_computers_softwareversions`.`computers_id`
                                 = `glpi_computers`.`id`)
                       WHERE `glpi_softwareversions`.`softwares_id` = '$ID'" .
                             getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                             AND `glpi_computers`.`is_deleted` = '0'
                             AND `glpi_computers`.`is_template` = '0'";

   } else {
      $crit = 'id';
      //SoftwareVersion ID
      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_computers_softwareversions`
                       INNER JOIN `glpi_computers`
                           ON (`glpi_computers_softwareversions`.`computers_id`
                                 = `glpi_computers`.`id`)
                       WHERE `glpi_computers_softwareversions`.`softwareversions_id` = '$ID'" .
                             getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                             AND `glpi_computers`.`is_deleted` = '0'
                             AND `glpi_computers`.`is_template` = '0'";
   }
   $total = 0;
   if ($result =$DB->query($query_number)) {
      $total  = $DB->result($result,0,0);
   }
      $query = "SELECT DISTINCT `glpi_computers_softwareversions`.*,
                       `glpi_computers`.`name` AS compname,
                       `glpi_computers`.`id` AS cID,
                       `glpi_computers`.`serial`,
                       `glpi_computers`.`otherserial`,
                       `glpi_users`.`name` AS username,
                       `glpi_users`.`id` AS userid,
                       `glpi_users`.`realname` AS userrealname,
                       `glpi_users`.`firstname` AS userfirstname,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_softwareversions`.`id` AS vID,
                       `glpi_softwareversions`.`softwares_id` AS sID,
                       `glpi_softwareversions`.`name` AS vername,
                       `glpi_entities`.`completename` AS entity,
                       `glpi_locations`.`completename` AS location,
                       `glpi_states`.`name` AS state,
                       `glpi_groups`.`name` AS groupe
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`)
                INNER JOIN `glpi_computers`
                     ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                LEFT JOIN `glpi_locations`
                     ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                WHERE (`glpi_softwareversions`.`$crit` = '$ID') " .
                       getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                       AND `glpi_computers`.`is_deleted` = '0'
                       AND `glpi_computers`.`is_template` = '0'
                ORDER BY version, compname
                LIMIT 0," . intval($_SESSION['glpilist_limit']);

   $pdf->setColumnsSize(100);

   if (($result=$DB->query($query)) && ($number=$DB->numrows($result))>0) {
      if ($number==$total) {
         $pdf->displayTitle('<b>'.$LANG['software'][19]." : $number</b>");
      } else {
         $pdf->displayTitle('<b>'.$LANG['software'][19]." : $number / $total</b>");
      }
      $pdf->setColumnsSize(12,16,15,15,22,20);
      $pdf->displayTitle('<b><i>'.$LANG['software'][5],  // vername
                                  $LANG['common'][16],   // compname
                                  $LANG['common'][19],   // serial
                                  $LANG['common'][20],   // asset
                                  $LANG['common'][15],   // location
                                  $LANG['software'][11].'</i></b>'); // licname

      while ($data = $DB->fetch_assoc($result)) {
         $compname = $data['compname'];
         if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
            $compname .= " (".$data['cID'].")";
         }
         $lics = Computer_SoftwareLicense::GetLicenseForInstallation($data['cID'], $data['vID']);

         $tmp = array();
         if (count($lics)) {
            foreach ($lics as $lic) {
               $licname = $lic['name'];
               if (!empty($lic['type'])) {
                  $licname .= " (".$lic['type'].")";
               }
               $tmp[] = $licname;
            }
         }
         $pdf->displayLine($data['version'], $compname,$data['serial'], $data['otherserial'],
                           $data['location'], implode(', ', $tmp));
      }
   } else {
      $pdf->displayTitle('<b>'.$LANG['software'][19].'</b>');
      $pdf->displayLine($LANG['search'][15]."!");
   }
   $pdf->displaySpace();
}


function plugin_pdf_software(PluginPdfSimplePDF $pdf, Computer $comp){
   global $DB,$LANG;

   $ID = $comp->getField('id');

   // From Computer_SoftwareVersion::showForComputer();
   $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                    `glpi_softwares`.`name` AS softname,
                    `glpi_computers_softwareversions`.`id`,
                    `glpi_states`.`name` AS state,
                    `glpi_softwareversions`.`id` AS verid,
                    `glpi_softwareversions`.`softwares_id`,
                    `glpi_softwareversions`.`name` AS version
             FROM `glpi_computers_softwareversions`
             LEFT JOIN `glpi_softwareversions`
                  ON (`glpi_computers_softwareversions`.`softwareversions_id`
                        = `glpi_softwareversions`.`id`)
             LEFT JOIN `glpi_states`
                  ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
             LEFT JOIN `glpi_softwares`
                  ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
             WHERE `glpi_computers_softwareversions`.`computers_id` = '$ID'
             ORDER BY `softwarecategories_id`, `softname`, `version`";

   $output = array();

   $software_category      = new SoftwareCategory();
   $software_version       = new SoftwareVersion();

   foreach ($DB->request($query) as $softwareversion) {
      $output[] = $softwareversion;
   }

   $installed = array();
   if (count($output)) {
      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG["software"][17].'</b>');

      $cat = -1;
      foreach ($output as $soft) {
         if ($soft["softwarecategories_id"] != $cat) {
            $cat = $soft["softwarecategories_id"];
            if ($cat && $software_category->getFromDB($cat)) {
               $catname = $software_category->getName();
            } else {
               $catname = $LANG["softwarecategories"][2];
            }

            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>'.$catname.'</b>');

            $pdf->setColumnsSize(50,13,13,24);
            $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                               '<b>'.$LANG['state'][0].'</b>',
                               '<b>'.$LANG['rulesengine'][78].'</b>',
                               '<b>'.$LANG['install'][92].'</b>');
         }

         // From Computer_SoftwareVersion::displaySoftsByCategory()
         $verid = $soft['verid'];
         $query = "SELECT `glpi_softwarelicenses`.*,
                          `glpi_softwarelicensetypes`.`name` AS type
                   FROM `glpi_computers_softwarelicenses`
                   INNER JOIN `glpi_softwarelicenses`
                        ON (`glpi_computers_softwarelicenses`.`softwarelicenses_id`
                                 = `glpi_softwarelicenses`.`id`)
                   LEFT JOIN `glpi_softwarelicensetypes`
                        ON (`glpi_softwarelicenses`.`softwarelicensetypes_id`
                                 =`glpi_softwarelicensetypes`.`id`)
                   WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$ID'
                         AND (`glpi_softwarelicenses`.`softwareversions_id_use` = '$verid'
                              OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                                  AND `glpi_softwarelicenses`.`softwareversions_id_buy` = '$verid'))";

         $lic = '';
         foreach ($DB->request($query) as $licdata) {
            $installed[] = $licdata['id'];
            $lic .= (empty($lic)?'':', ').'<b>'.$licdata['name'].'</b> '.$licdata['serial'];
            if (!empty($licdata['type'])) {
               $lic .= ' ('.$licdata['type'].')';
            }
         }

         $pdf->displayLine($soft['softname'], $soft['state'], $soft['version'], $lic);
      } // Each version

   } else {
      $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['software'][1].'</b>');
   }

   // Affected licenses NOT installed
   $query = "SELECT `glpi_softwarelicenses`.*,
                    `glpi_softwares`.`name` AS softname,
                    `glpi_softwareversions`.`name` AS version,
                    `glpi_states`.`name` AS state
             FROM `glpi_softwarelicenses`
             LEFT JOIN `glpi_computers_softwarelicenses`
                   ON (`glpi_computers_softwarelicenses`.softwarelicenses_id
                           = `glpi_softwarelicenses`.`id`)
             INNER JOIN `glpi_softwares`
                   ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id`)
             LEFT JOIN `glpi_softwareversions`
                   ON (`glpi_softwarelicenses`.`softwareversions_id_use`
                           = `glpi_softwareversions`.`id`
                        OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                            AND `glpi_softwarelicenses`.`softwareversions_id_buy`
                                    = `glpi_softwareversions`.`id`))
             LEFT JOIN `glpi_states`
                  ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
             WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$ID' ";

   if (count($installed)) {
      $query .= " AND `glpi_softwarelicenses`.`id` NOT IN (".implode(',',$installed).")";
   }

   $req = $DB->request($query);
   if ($req->numrows()) {
      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG['software'][3].'</b>');

      $pdf->setColumnsSize(50,13,13,24);
      $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                         '<b>'.$LANG['state'][0].'</b>',
                         '<b>'.$LANG['rulesengine'][78].'</b>',
                         '<b>'.$LANG['install'][92].'</b>');

      foreach ($req as $data) {
         $lic .= '<b>'.$data['name'].'</b> '.$data['serial'];
         if (!empty($data['softwarelicensetypes_id'])) {
            $lic .= ' ('.Html::clean(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                              $data['softwarelicensetypes_id'])).')';
         }
         $pdf->displayLine($data['softname'], $data['state'], $data['version'], $lic);
      }
   }

   $pdf->displaySpace();
}


function plugin_pdf_computer_connection (PluginPdfSimplePDF $pdf, Computer $comp){
   global $DB,$LANG;

   $ID = $comp->getField('id');

   $items = array('Printer'    => $LANG['Menu'][2],
                  'Monitor'    => $LANG['Menu'][3],
                  'Peripheral' => $LANG['Menu'][16],
                  'Phone'      => $LANG['Menu'][34]);

   $info = new InfoCom();

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');

   foreach ($items as $type => $title) {
      if (!class_exists($type)) {
         continue;
      }
      $item = new $type();
      if (!$item->canView()) {
         continue;
      }
      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$ID'
                      AND `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         $resultnum = $DB->numrows($result);
         if ($resultnum > 0) {
            for ($j=0 ; $j < $resultnum ; $j++) {
               $tID = $DB->result($result, $j, "items_id");
               $connID = $DB->result($result, $j, "id");
               $item->getFromDB($tID);
               $info->getFromDBforDevice($type,$tID) || $info->getEmpty();

               $line1 = $item->getName()." - ";
               if ($item->getField("serial") != null) {
                  $line1 .= $LANG["common"][19] . " : " .$item->getField("serial")." - ";
               }
               $line1 .= Html::clean(Dropdown::getDropdownName("glpi_states",
                                                              $item->getField('states_id')));

               $line2 = "";
               if ($item->getField("otherserial") != null) {
                  $line2 = $LANG["common"][20] . " : " . $item->getField("otherserial");
               }
               if ($info->fields["immo_number"]) {
                  if ($line2) {
                     $line2 .= " - ";
                  }
                  $line2 .= $LANG["financial"][20] . " : " . $info->fields["immo_number"];
               }
               if ($line2) {
                  $pdf->displayText('<b>'.$item->getTypeName().' : </b>', $line1 . "\n" . $line2, 2);
               } else {
                  $pdf->displayText('<b>'.$item->getTypeName().' : </b>', $line1, 1);
               }
            }// each device	of current type

         } else { // No row
            switch ($type) {
               case 'Printer' :
                  $pdf->displayLine($LANG["computers"][38]);
                  break;

               case 'Monitor' :
                  $pdf->displayLine($LANG["computers"][37]);
                  break;

               case 'Peripheral' :
                  $pdf->displayLine($LANG["computers"][47]);
                  break;

               case 'Phone' :
                  $pdf->displayLine($LANG["computers"][54]);
                  break;
            }
         } // No row
      } // Result
   } // each type
   $pdf->displaySpace();
}


function plugin_pdf_device_connection(PluginPdfSimplePDF $pdf, CommonDBTM $item){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = $item->getType();

   $info = new InfoCom();
   $comp = new Computer();

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');

   $query = "SELECT *
             FROM `glpi_computers_items`
             WHERE `items_id` = '$ID'
                   AND `itemtype` = '$type'";

   if ($result = $DB->query($query)) {
      $resultnum = $DB->numrows($result);
      if ($resultnum > 0) {
         for ($j=0 ; $j < $resultnum ; $j++) {
            $tID = $DB->result($result, $j, "computers_id");
            $connID = $DB->result($result, $j, "id");
            $comp->getFromDB($tID);
            $info->getFromDBforDevice('Computer',$tID) || $info->getEmpty();

            $line1 = ($comp->fields['name']?$comp->fields['name']:"(".$comp->fields['id'].")")." - ";
            if ($comp->fields['serial']) {
               $line1 .= $LANG["common"][19] . " : " .$comp->fields['serial']." - ";
            }
            $line1 .= Html::clean(Dropdown::getDropdownName("glpi_states",$comp->fields['states_id']));

            $line2 = "";
            if ($comp->fields['otherserial']) {
               $line2 .= $LANG["common"][20] . " : " .$comp->fields['otherserial']." - ";
            }
            if ($info->fields['immo_number']) {
               if ($line2) {
                  $line2 .= " - ";
               }
               $line2 .= $LANG["financial"][20] . " : " . $info->fields['immo_number'];
            }
            if ($line2) {
               $pdf->displayText('<b>'.$LANG['help'][25].' : </b>', $line1 . "\n" . $line2, 2);
            } else {
               $pdf->displayText('<b>'.$LANG['help'][25].' : </b>', $line1, 1);
            }
         }// each device	of current type

      } else { // No row
         $pdf->displayLine($LANG['connect'][1]);
      } // No row
   } // Result
   $pdf->displaySpace();
}


function plugin_pdf_port(PluginPdfSimplePDF $pdf, CommonDBTM $item){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   $query = "SELECT `id`
             FROM `glpi_networkports`
             WHERE `items_id` = '$ID'
                   AND `itemtype` = '$type'
             ORDER BY `name`, `logical_number`";

   $pdf->setColumnsSize(100);
   if ($result = $DB->query($query)) {
      $nb_connect = $DB->numrows($result);
      if (!$nb_connect) {
         $pdf->displayTitle('<b>0 '.$LANG["networking"][10].'</b>');
      } else {
         $pdf->displayTitle('<b>'.ucfirst($LANG["networking"][$nb_connect>1 ? 11 : 12])." : $nb_connect</b>");

         while ($devid=$DB->fetch_row($result)) {
            $netport = new NetworkPort;
            $netport->getfromDB(current($devid));
            $pdf->displayTitle('<b>'.$LANG['networking'][4].'<i># '.$netport->fields["logical_number"].'</i>'.
                     ' : '.$netport->fields["name"].'</b>');

            $pdf->displayLine('<b><i>'.$LANG["networking"][51].' :</i></b> '.
                              Html::clean(Dropdown::getDropdownName("glpi_netpoints",
                                                                   $netport->fields["netpoints_id"])));

            $pdf->displayLine('<b><i>'.$LANG["networking"][14].' / '.
                              $LANG["networking"][15].' :</i></b> '.$netport->fields["ip"].' / '.
                              $netport->fields["mac"]);

            $pdf->displayLine('<b><i>'.$LANG["networking"][60].' / '.$LANG["networking"][61].' / '.
                              $LANG["networking"][59].' :</i></b> '.$netport->fields["netmask"].' / '.
                              $netport->fields["subnet"].' / '.$netport->fields["gateway"]);

            $query = "SELECT *
                      FROM `glpi_networkports_vlans`
                      WHERE `networkports_id` = '".$netport->fields['id']."'";

            $result2 = $DB->query($query);
            if ($DB->numrows($result2) > 0) {
               $line = '';
               while ($a_line=$DB->fetch_array($result2)) {
                  $line .= (empty($line) ? '' : ', ').
                           Html::clean(Dropdown::getDropdownName("glpi_vlans", $a_line["vlans_id"]));
               }
               $pdf->displayText('<b><i>'.$LANG['networking'][56].' :</i></b>', $line, 1);
            }

            if ($netport->fields["networkinterfaces_id"]) {
               $pdf->displayText(
                  '<b><i>'.$LANG["common"][65].' :</i></b> ',
                  Html::clean(Dropdown::getDropdownName("glpi_networkinterfaces",
                                                       $netport->fields["networkinterfaces_id"])),
                  1);
            }

            $contact = new NetworkPort;
            $netport2 = new NetworkPort;

            $add = $LANG["connect"][1];
            if ($cid = $contact->getContact($netport->fields["id"])) {
               if ($netport2->getfromDB($cid)
                   && class_exists($netport2->fields["itemtype"])) {
                  $device2 = new $netport2->fields["itemtype"]();
                  if ($device2->getFromDB($netport2->fields["items_id"])) {
                     $add = $netport2->getName().' '.$LANG['networking'][25].' '.
                            $device2->getName().' ('.$device2->getTypeName().')';
                  }
               }
            }
            $pdf->displayText('<b><i>'.$LANG["networking"][17].' :</i></b> ', $add, 1);
         } // each port
      } // Found
   } // Query
   $pdf->displaySpace();
}


function plugin_pdf_contract (PluginPdfSimplePDF $pdf, CommonDBTM $item){
   global $DB,$CFG_GLPI,$LANG;

   if (!Session::haveRight("contract","r")) {
      return false;
   }

   $type = $item->getType();
   $ID = $item->getField('id');
   $con = new Contract();

   $query = "SELECT *
             FROM `glpi_contracts_items`
             WHERE `glpi_contracts_items`.`items_id` = '$ID'
                   AND `glpi_contracts_items`.`itemtype` = '$type'";

   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = $j = 0;

   $pdf->setColumnsSize(100);
   if ($number > 0) {
      $pdf->displayTitle('<b>'.$LANG["financial"][66].'</b>');

      $pdf->setColumnsSize(19,19,19,16,11,16);
      $pdf->displayTitle($LANG["common"][16], $LANG["financial"][4], $LANG["financial"][6],
                         $LANG["financial"][26], $LANG["search"][8], $LANG["financial"][8]);

      $i++;

      while ($j < $number) {
         $cID = $DB->result($result, $j, "contracts_id");
         $assocID = $DB->result($result, $j, "id");

         if ($con->getFromDB($cID)) {
            $pdf->displayLine(
               (empty($con->fields["name"]) ? "(".$con->fields["id"].")" : $con->fields["name"]),
               $con->fields["num"],
               Html::clean(Dropdown::getDropdownName("glpi_contracttypes",
                                                    $con->fields["contracttypes_id"])),
               str_replace("<br>", " ", $con->getSuppliersNames()),
               Html::convDate($con->fields["begin_date"]),
               $con->fields["duration"]." ".$LANG["financial"][57]);
         }
         $j++;
      }
   } else {
      $pdf->displayTitle("<b>".$LANG['plugin_pdf']['financial'][2]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_registry(PluginPdfSimplePDF $pdf, Computer $item) {
   global $DB,$LANG;

   $ID = $item->getField('id');

   $REGISTRY_HIVE = array("HKEY_CLASSES_ROOT",
                          "HKEY_CURRENT_USER",
                          "HKEY_LOCAL_MACHINE",
                          "HKEY_USERS",
                          "HKEY_CURRENT_CONFIG",
                          "HKEY_DYN_DATA");

   $query = "SELECT `id`
             FROM `glpi_registrykeys`
             WHERE `computers_id` = '$ID'";

   $pdf->setColumnsSize(100);
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         $pdf->displayTitle('<b>'.$DB->numrows($result)." ".$LANG["registry"][4].'</b>');

         $pdf->setColumnsSize(25,25,25,25);
         $pdf->displayTitle('<b>'.$LANG["registry"][6].'</b>',
                            '<b>'.$LANG["registry"][1].'</b>',
                            '<b>'.$LANG["registry"][2].'</b>',
                            '<b>'.$LANG["registry"][3].'</b>');

         $reg = new RegistryKey;

         while ($regid = $DB->fetch_row($result)) {
            if ($reg->getfromDB(current($regid))) {
               $pdf->displayLine($reg->fields['ocs_name'],
                                 $REGISTRY_HIVE[$reg->fields['hive']],
                                 $reg->fields['path'],
                                 $reg->fields['value']);
            }
         }

      } else {
         $pdf->displayTitle('<b>'.$LANG["registry"][5].'</b>');
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_oldticket(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   // Deprecated function, just for some plugins
}


function plugin_pdf_ticket(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
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
         if ($job->fields["ticketcategories_id"]) {
            $col .= '  -  <b><i>'.$LANG["common"][36].' : </i></b>';
            $col .= Dropdown::getDropdownName('glpi_ticketcategories', $job->fields["ticketcategories_id"]);
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


function plugin_pdf_link(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!Session::haveRight("link","r")) {
      return false;
   }

   $query = "SELECT `glpi_links`.`id` AS ID, `glpi_links`.`link`, `glpi_links`.`name`,
                    `glpi_links`.`data`
             FROM `glpi_links`
             INNER JOIN `glpi_links_itemtypes`
                  ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
             WHERE `glpi_links_itemtypes`.`itemtype` = '$type'
             ORDER BY `glpi_links`.`name`";

   $result=$DB->query($query);

   $pdf->setColumnsSize(100);
   if ($DB->numrows($result) > 0) {
      $pdf->displayTitle('<b>'.$LANG["title"][33].'</b>');

      while ($data = $DB->fetch_assoc($result)) {
         $name = $data["name"];
         if (empty($name)) {
            $name = $data["link"];
         }
         $link = $data["link"];
         $file = trim($data["data"]);

         if (empty($file)) {
            $links = Link::generateLinkContents($data['link'], $item, $name);
            $i=1;
            foreach ($links as $key => $link) {
               $url = $link;
               $pdf->displayLine("<b>$name #$i</b> : $link");
               $i++;
            }
         } else { // Generated File
               $files = Link::generateLinkContents($data['link'], $item);
               $links = Link::generateLinkContents($data['data'], $item);
               $i=1;
               foreach ($links as $key => $data) {
                  if (isset($files[$key])) {
                     // a different name for each file, ex name = foo-[IP].txt
                     $file = $files[$key];
                  } else {
                     // same name for all files, ex name = foo.txt
                     $file = reset($files);
                  }
                  $pdf->displayText("<b>$name #$i - $file :</b>", trim($data), 1, 10);
                  $i++;
               }
         }
      } // Each link
   } else {
      $pdf->displayTitle('<b>'.$LANG["links"][7].'</b>');
   }
   $pdf->displaySpace();
}


function plugin_pdf_volume(PluginPdfSimplePDF $pdf, Computer $item) {
   global $DB, $LANG;

   $ID = $item->getField('id');

   $query = "SELECT `glpi_filesystems`.`name` AS fsname, `glpi_computerdisks`.*
             FROM `glpi_computerdisks`
             LEFT JOIN `glpi_filesystems`
               ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
             WHERE (`computers_id` = '$ID')";

   $result=$DB->query($query);

   $pdf->setColumnsSize(100);
   if ($DB->numrows($result) > 0) {
      $pdf->displayTitle("<b>".$LANG['computers'][8]."</b>");

      $pdf->setColumnsSize(22,23,22,11,11,11);
      $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                         '<b>'.$LANG['computers'][6].'</b>',
                         '<b>'.$LANG['computers'][5].'</b>',
                         '<b>'.$LANG['common'][17].'</b>',
                         '<b>'.$LANG['computers'][3].'</b>',
                         '<b>'.$LANG['computers'][2].'</b>');

      $pdf->setColumnsAlign('left','left','left','center','right','right');

      while ($data = $DB->fetch_assoc($result)) {
         $pdf->displayLine('<b>'.Toolbox::decodeFromUtf8((empty($data['name'])?$data['ID']:$data['name']),"windows-1252").'</b>',
                           $data['device'],
                           $data['mountpoint'],
                           Html::clean(Dropdown::getDropdownName('glpi_filesystems',$data["filesystems_id"])),
                           Html::clean(Html::formatNumber($data['totalsize'], false, 0))." ".$LANG['common'][82],
                           Html::clean(Html::formatNumber($data['freesize'], false, 0))." ".$LANG['common'][82]);
      }
   } else {
      $pdf->displayTitle("<b>".$LANG['computers'][8] . " - " . $LANG['search'][15]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_vm(PluginPdfSimplePDF $pdf, Computer $item) {
   global $DB, $LANG;

   $ID = $item->getField('id');

   // From ComputerVirtualMachine::showForComputer()
   $virtualmachines = getAllDatasFromTable('glpi_computervirtualmachines',
                                           "`computers_id` = '$ID'");
   $pdf->setColumnsSize(100);
   if (count($virtualmachines)) {
      $pdf->displayTitle("<b>".$LANG['computers'][66]."</b>");
      $pdf->setColumnsSize(12,13,12,13,12,13,12,13);
      $pdf->displayTitle(
         $LANG['common'][16],
         $LANG['computers'][62],
         $LANG['computers'][60],
         $LANG['computers'][63],
         $LANG['computers'][58],
         $LANG['computers'][61],
         $LANG['computers'][24],
         $LANG['computers'][64]
      );

      foreach ($virtualmachines as $virtualmachine) {
         $name = '';
         if ($link_computer = ComputerVirtualMachine::findVirtualMachine($virtualmachine)) {
            $computer = new Computer();
            if ($computer->getFromDB($link_computer)) {
               $name = $computer->getName();
            }
         }
         $pdf->displayLine(
            $virtualmachine['name'],
            Html::clean(Dropdown::getDropdownName('glpi_virtualmachinetypes',
                                                 $virtualmachine['virtualmachinetypes_id'])),
            Html::clean(Dropdown::getDropdownName('glpi_virtualmachinesystems',
                                                  $virtualmachine['virtualmachinesystems_id'])),
            Html::clean(Dropdown::getDropdownName('glpi_virtualmachinestates',
                                                 $virtualmachine['virtualmachinestates_id'])),
            $virtualmachine['uuid'],
            $virtualmachine['vcpu'],
            $virtualmachine['ram'],
            $name
         );
      }
   } else {
      $pdf->displayTitle("<b>".$LANG['computers'][59]."</b>");
   }

   // From ComputerVirtualMachine::showForVirtualMachine()
   if ($item->fields['uuid']) {
      $where = "`uuid`".ComputerVirtualMachine::getUUIDRestrictRequest($item->fields['uuid']);
      $hosts = getAllDatasFromTable('glpi_computervirtualmachines', $where);

      if (count($hosts)) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle("<b>".$LANG['computers'][65]."</b>");

         $pdf->setColumnsSize(26,37,37);
         $pdf->displayTitle($LANG['common'][16], $LANG['computers'][9], $LANG['entity'][0]);

         $computer = new Computer();
         foreach ($hosts as $host) {
            if ($computer->getFromDB($host['computers_id'])) {
               $pdf->displayLine(
                  $computer->getName(),
                  Html::clean(Dropdown::getDropdownName('glpi_operatingsystems', $computer->getField('operatingsystems_id'))),
                  Html::clean(Dropdown::getDropdownName('glpi_entities', $computer->getEntityID()))
               );
            }
         }
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_note(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   global $LANG;

   $ID = $item->getField('id');

   $note = trim($item->getField('notepad'));

   $pdf->setColumnsSize(100);
   if (Toolbox::strlen($note) > 0) {
      $pdf->displayTitle('<b>'.$LANG["title"][37].'</b>');
      $pdf->displayText('', $note, 5);
   } else {
      $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['note'][1].'</b>');
   }
   $pdf->displaySpace();
}


function plugin_pdf_reservation(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   global $DB,$LANG,$CFG_GLPI;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!Session::haveRight("reservation_central","r")) {
      return;
   }

   $user = new User();
   $ri = new ReservationItem;
   $pdf->setColumnsSize(100);
   if ($ri->getFromDBbyItem($type,$ID)) {
      $now = $_SESSION["glpi_currenttime"];
      $query = "SELECT *
                FROM `glpi_reservationitems`
                INNER JOIN `glpi_reservations`
                     ON (`glpi_reservations`.`reservationitems_id` = `glpi_reservationitems`.`id`)
                WHERE `end` > '".$now."'
                      AND `glpi_reservationitems`.`items_id` = '$ID'
                ORDER BY `begin`";

      $result = $DB->query($query);

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG["reservation"][35]."</b>");

      if (!$DB->numrows($result)) {
         $pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
      } else {
         $pdf->setColumnsSize(14,14,26,46);
         $pdf->displayTitle('<i>'.$LANG["search"][8].'</i>',
                            '<i>'.$LANG["search"][9].'</i>',
                            '<i>'.$LANG["reservation"][31].'</i>',
                            '<i>'.$LANG["common"][25].'</i>');

         while ($data = $DB->fetch_assoc($result)) {
            if ($user->getFromDB($data["users_id"])) {
               $name = formatUserName($user->fields["id"], $user->fields["name"],
                                      $user->fields["realname"], $user->fields["firstname"]);
            } else {
               $name = "(".$data["users_id"].")";
            }
            $pdf->displayLine(Html::convDateTime($data["begin"]), Html::convDateTime($data["end"]),
                              $name, str_replace(array("\r","\n")," ",$data["comment"]));
         }
      }

      $query = "SELECT *
                FROM `glpi_reservationitems`
                INNER JOIN `glpi_reservations`
                     ON (`glpi_reservations`.`reservationitems_id` = `glpi_reservationitems`.`id`)
                WHERE `end` <= '".$now."'
                      AND `glpi_reservationitems`.`items_id` = '$ID'
                ORDER BY `begin`
                DESC";

      $result = $DB->query($query);

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG["reservation"][36]."</b>");

      if (!$DB->numrows($result)) {
         $pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
      } else {
         $pdf->setColumnsSize(14,14,26,46);
         $pdf->displayTitle('<i>'.$LANG["search"][8].'</i>',
                            '<i>'.$LANG["search"][9].'</i>',
                            '<i>'.$LANG["reservation"][31].'</i>',
                            '<i>'.$LANG["common"][25].'</i>');

         while ($data = $DB->fetch_assoc($result)) {
            if ($user->getFromDB($data["users_id"])) {
               $name = formatUserName($user->fields["id"], $user->fields["name"],
                                      $user->fields["realname"], $user->fields["firstname"]);
            } else {
               $name = "(".$data["users_id"].")";
            }
            $pdf->displayLine(Html::convDateTime($data["begin"]), Html::convDateTime($data["end"]),$name,
                                           str_replace(array("\r","\n")," ",$data["comment"]));
         }
      }

   } else { // Not isReservable
      //$pdf->displayTitle("<b>".$LANG["reservation"][37]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_history(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
   global $LANG;

   // Get the Full history for the item (really a good idea ?, should we limit this)
   $changes = Log::getHistoryData($item);

   $pdf->setColumnsSize(100);
   if (count($changes) > 0) {
      $pdf->displayTitle("<b>".$LANG["title"][38]."</b>");

      $pdf->setColumnsSize(14,15,20,51);
      $pdf->displayTitle('<b><i>'.$LANG["common"][27].'</i></b>',
                         '<b><i>'.$LANG["common"][34].'</i></b>',
                         '<b><i>'.$LANG["event"][18].'</i></b>',
                         '<b><i>'.$LANG["event"][19].'</i></b>');

      foreach ($changes as $data) {
         if ($data['display_history']) {
            $pdf->displayLine($data['date_mod'], $data['user_name'], $data['field'], Html::clean($data['change']));
         }
      } // Each log
   } else {
      $pdf->displayTitle("<b>".$LANG["event"][20]."</b>");
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

      switch (get_class($item)) {
         case 'Computer' :
            plugin_pdf_main_computer($pdf, $item);
            foreach ($tab as $i) {
               switch($i) { // See Computer::defineTabs();
                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract ($pdf, $item);
                     break;

                  case 3 :
                     plugin_pdf_computer_connection($pdf, $item);
                     plugin_pdf_port($pdf, $item);
                     break;

                  case 1 :
                     plugin_pdf_device($pdf, $item);
                     break;

                  case 2 :
                     plugin_pdf_software($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 14 :
                     plugin_pdf_registry($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  case 20 :
                     plugin_pdf_volume($pdf, $item);
                     break;

                  case 21 :
                     plugin_pdf_vm($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'Printer' :
            plugin_pdf_main_printer($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) {  // See Printer::defineTabs();
                  case 1 :
                     plugin_pdf_cartridges($pdf, $item, false);
                     plugin_pdf_cartridges($pdf, $item, true);
                     break;

                  case 3 :
                     plugin_pdf_device_connection($pdf, $item);
                     plugin_pdf_port($pdf, $item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract ($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'Monitor' :
            plugin_pdf_main_monitor($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) { // See Monitor::defineTabs();
                  case 1 :
                     plugin_pdf_device_connection($pdf, $item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract ($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'Peripheral' :
            plugin_pdf_main_peripheral($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) { // See Peripheral::defineTabs();
                  case 1 :
                     plugin_pdf_device_connection($pdf, $item);
                     plugin_pdf_port($pdf, $item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract ($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'Phone' :
            plugin_pdf_main_phone($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) { // See Phone::defineTabs();
                  case 1 :
                     plugin_pdf_device_connection($pdf, $item);
                     plugin_pdf_port($pdf, $item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract ($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'SoftwareLicense' :
            plugin_pdf_main_license($pdf, $item, true, !(in_array(1,$tab) || in_array(2,$tab)));
            foreach ($tab as $i) {
               switch ($i) { // See SoftwareLicense::defineTabs();
                  case 1:
                     plugin_pdf_licensebyentity($pdf, $item);
                     break;

                  case 2:
                     plugin_pdf_licensecomputer($pdf, $item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'SoftwareVersion' :
            plugin_pdf_main_version($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) { // See SoftwareVersion::defineTabs();
                  case 1:
                     plugin_pdf_versionbyentity($pdf, $item);
                     break;

                  case 2 :
                     plugin_pdf_installations($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

         case 'Software' :
            plugin_pdf_main_software($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) { // See Software::defineTabs();
                  case 1 :
                     plugin_pdf_versions($pdf, $item);
                     plugin_pdf_licenses($pdf, $item, in_array(4,$tab));
                     break;

                  case 2 :
                     plugin_pdf_installations($pdf, $item);
                     break;

                  case 4 :
                     // only template - plugin_pdf_financial($pdf,$ID,SOFTWARE_TYPE);
                     plugin_pdf_contract($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i, $pdf, $item);
               }
            }
            break;

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

         case 'NetworkEquipment' :
            plugin_pdf_main_network($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) {
                  case 1 :
                     plugin_pdf_port($pdf, $item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf, $item);
                     plugin_pdf_contract ($pdf, $item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf, $item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf, $item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf, $item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf, $item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf, $item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf, $item);
                     break;
               }
            }
            break;

         case 'KnowbaseItem' :
            plugin_pdf_main_knowbaseitem($pdf, $item);
            foreach ($tab as $i) {
               switch ($i) {
                  case 1 :
                     plugin_pdf_document($pdf, $item);
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