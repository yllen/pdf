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
 @copyright Copyright (c) 2009-2017 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfTicket extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Ticket());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $CFG_GLPI, $DB;

      $ID = $job->getField('id');
      if (!$job->can($ID, READ)) {
         return false;
      }

      $pdf->setColumnsSize(100);

      $pdf->displayTitle('<b>'.
               (empty($job->fields["name"])?__('Without title'):$name=$job->fields["name"]).'</b>');

      if (count($_SESSION['glpiactiveentities'])>1) {
         $entity = " (".Dropdown::getDropdownName("glpi_entities",
                                                   $job->fields["entities_id"]).")";
      } else {
         $entity = '';
      }

      $pdf->setColumnsSize(100);
      $recipient_name='';
      if ($job->fields["users_id_recipient"]) {
         $recipient      = new User();
         $recipient->getFromDB($job->fields["users_id_recipient"]);
         $recipient_name = $recipient->getName();
      }

      $tto = $due = $commenttto = $commentttr = '';
      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Opening date')."</i></b>",
                          Html::convDateTime($job->fields["date"])));

      if ($job->fields['due_date']) {
         $due = "<b><i>".sprintf(__('%1$s: %2$s'), __('Time to resolve')."</b></i>",
               Html::convDateTime($job->fields['due_date']));
      }

      if ($job->fields["time_to_own"] > 0) {
         $tto = "<b><i>".sprintf(__('%1$s: %2$s'), __('Time to own')."</b></i>",
                                            Html::convDateTime($job->fields["time_to_own"]));
      }

      if ($job->fields["slts_tto_id"] > 0) {
         $commenttto = "<b><i>".sprintf(__('%1$s: %2$s'), __('SLT')."</b></i>",
                                 Html::clean(Dropdown::getDropdownName("glpi_slts",
                                                                       $job->fields["slts_tto_id"])));

         $slalevel = new SlaLevel();
         $nextaction = new SlaLevel_Ticket();
         if ($nextaction->getFromDBForTicket($job->fields["id"], SLT::TTO)) {
            $commenttto .= " <b><i>".sprintf(__('%1$s: %2$s'),
                                             sprintf(__('Next escalation: %s')."</b></i>",''),
                                             Html::convDateTime($nextaction->fields['date']));

            if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
               $commenttto .= " <b><i>".sprintf(__('%1$s: %2$s'), __('Escalation level')."</b></i>",
                                           $slalevel->getName());
            }
         }
         $pdf->displayText($tto, $commenttto, 1);
      }

      if ($job->fields["slts_ttr_id"] > 0) {
         $commentttr = "<b><i>".sprintf(__('%1$s: %2$s'), __('SLT')."</b></i>",
                                     Html::clean(Dropdown::getDropdownName("glpi_slts",
                                                                           $job->fields["slts_ttr_id"])));

         $slalevel = new SlaLevel();
         $nextaction = new SlaLevel_Ticket();
         if ($nextaction->getFromDBForTicket($job->fields["id"], SLT::TTR)) {
            $commentttr .= " <b><i>".sprintf(__('%1$s: %2$s'),
                                             sprintf(__('Next escalation: %s')."</b></i>",''),
                                             Html::convDateTime($nextaction->fields['date']));

            if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
               $commentttr .= " <b><i>".sprintf(__('%1$s: %2$s'), __('Escalation level')."</b></i>",
                                               $slalevel->getName());
            }
         }
         $pdf->displayText($due, $commentttr, 1);
      }

      $pdf->setColumnsSize(50,50);
      $lastupdate = Html::convDateTime($job->fields["date_mod"]);
      if ($job->fields['users_id_lastupdater'] > 0) {
         $lastupdate = sprintf(__('%1$s by %2$s'), $lastupdate,
                               getUserName($job->fields["users_id_lastupdater"]));
      }

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('By')."</i></b>", $recipient_name),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Last update').'</i></b>', $lastupdate));

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Type')."</i></b>",
                          Html::clean(Ticket::getTicketTypeName($job->fields["type"]))),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Category')."</i></b>",
                          Dropdown::getDropdownName("glpi_itilcategories",
                                                    $job->fields["itilcategories_id"])));

      $status = '';
      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $status = sprintf(__('%1$s %2$s'), '-', Html::convDateTime($job->fields["solvedate"]));
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $status = sprintf(__('%1$s %2$s'), '-', Html::convDateTime($job->fields["closedate"]));
      }

      if ($job->fields["status"] == Ticket::WAITING) {
         $status = sprintf(__('%1$s %2$s'), '-',
                           Html::convDateTime($job->fields['begin_waiting_date']));
      }

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Status')."</i></b>",
                          Html::clean($job->getStatus($job->fields["status"])). $status),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Request source')."</i></b>",
                          Html::clean(Dropdown::getDropdownName('glpi_requesttypes',
                                                                $job->fields['requesttypes_id']))));

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Urgency')."</i></b>",
                          Html::clean($job->getUrgencyName($job->fields["urgency"]))),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Approval')."</i></b>",
                          TicketValidation::getStatus($job->fields['global_validation'])));

      $pdf->displayLine(
            "<b><i>". sprintf(__('%1$s: %2$s'), __('Impact')."</i></b>",
                  Html::clean($job->getImpactName($job->fields["impact"]))));

      $pdf->displayLine(
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Priority')."</i></b>",
                             Html::clean($job->getPriorityName($job->fields["priority"]))),
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Location')."</i></b>",
                             Dropdown::getDropdownName("glpi_locations",
                                                       $job->fields["locations_id"])));

      $pdf->setColumnsSize(50,50);

      // Requester
      $users     = array();
      $listusers = '';
      $requester = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Requester'), $listusers);
      foreach ($job->getUsers(CommonITILActor::REQUESTER) as $d) {
         if ($d['users_id']) {
            $tmp = "<i>".Html::clean(getUserName($d['users_id']))."</i>";
         } else {
            $tmp = $d['alternative_email'];
         }

         $user = new User();
         if ($info = $user->getFromDB($d['users_id'])) {
            if ($d['alternative_email'] || $user->fields['phone'] || $user->fields['mobile']
                || $user->fields['usercategories_id'] || $user->fields['locations_id']) {

               $tmp .= " (";
               $first = false;
               if ($d['alternative_email']) {
                  $tmp .= sprintf(__('%1$s: %2$s'), __('Email'), $d['alternative_email']);
                  $first = false;
               }
               if ($user->fields['phone']) {
                  if (!$first) {
                     $tmp .= " - ";
                  }
                  $tmp .= sprintf(__('%1$s: %2$s'), __('Phone'), $user->fields['phone']);
                  $first = false;
               }
               if ($user->fields['mobile']) {
                  if (!$first) {
                     $tmp .= " - ";
                  }
                  $tmp .= sprintf(__('%1$s: %2$s'), __('Mobile phone'), $user->fields['mobile']);
                  $first = false;
               }
               if ($user->fields['usercategories_id']) {
                  if (!$first) {
                     $tmp .= " - ";
                  }
                  $tmp .= sprintf(__('%1$s: %2$s'), __('Category'),
                                  Dropdown::getDropdownName('glpi_usercategories',
                                                            $user->fields['usercategories_id']));
                  $first = false;
               }
               if ($user->fields['locations_id']) {
                  if (!$first) {
                     $tmp .= " - ";
                  }
                  $tmp .= sprintf(__('%1$s: %2$s'), __('Location'),
                                  Dropdown::getDropdownName('glpi_locations',
                                                            $user->fields['locations_id']));
               }
               $tmp .= ")";
            }
         }
         $users[] = $tmp;
      }
      if (count($users)) {
         $listusers = implode('<br /> ', $users);
      }
      $pdf->displayText($requester, $listusers, 1);

      $groups         = array();
      $listgroups     = '';
      $requestergroup = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Requester group'),
                                         $listgroups);
      foreach ($job->getGroups(CommonITILActor::REQUESTER) as $d) {
         $groups[] = Dropdown::getDropdownName("glpi_groups", $d['groups_id']);
      }
      if (count($groups)) {
      $listgroups = implode(', ', $groups);
      }
      $pdf->displayText($requestergroup, $listgroups, 1);

      // Observer
      $users     = array();
      $listusers = '';
      $watcher   = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Watcher'), $listusers);
      foreach ($job->getUsers(CommonITILActor::OBSERVER) as $d) {
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
         $listusers = implode(', ', $users);
      }
      $pdf->displayText($watcher, $listusers, 1);

      $groups       = array();
      $listgroups   = '';
      $watchergroup = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Watcher group'),
                                         $listgroups);
      foreach ($job->getGroups(CommonITILActor::OBSERVER) as $d) {
         $groups[] = Dropdown::getDropdownName("glpi_groups", $d['groups_id']);
      }
      if (count($groups)) {
         $listgroups = implode(', ', $groups);
      }
      $pdf->displayText($watchergroup, $listgroups, 1);

      // Assign to
      $users = array();
      $listusers = '';
      $assign    = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Assigned to technicians'),
                                    $listusers);
      foreach ($job->getUsers(CommonITILActor::ASSIGN) as $d) {
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
         $listusers = implode(', ', $users);
      }
      $pdf->displayText($assign, $listusers, 1);

      $groups     = array();
      $listgroups  = '';
      $assigngroup = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Assigned to groups'),
                                         $listgroups);
      foreach ($job->getGroups(CommonITILActor::ASSIGN) as $d) {
         $groups[] = Dropdown::getDropdownName("glpi_groups", $d['groups_id']);
      }
      if (count($groups)) {
         $listgroups = implode(', ', $groups);
      }
      $pdf->displayText($assigngroup, $listgroups, 1);

     // Supplier
      $suppliers      = array();
      $listsuppliers  = '';
      $assignsupplier = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Assigned to a supplier'),
                                         $listsuppliers);
      foreach ($job->getSuppliers(CommonITILActor::ASSIGN) as $d) {
         $suppliers[] = Html::clean(Dropdown::getDropdownName("glpi_suppliers", $d['suppliers_id']));
      }
      if (count($suppliers)) {
         $listsuppliers = implode(', ', $suppliers);
      }
      $pdf->displayText($assignsupplier, $listsuppliers, 1);

      $pdf->setColumnsSize(100);
      $pdf->displayLine(
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Title')."</i></b>", $job->fields["name"]));

      $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>", __('Description'), ''),
                                         Html::clean($job->fields['content']), 1);

      // Linked tickets
      $tickets   = Ticket_Ticket::getLinkedTicketsTo($ID);
      if (is_array($tickets) && count($tickets)) {
         $ticket = new Ticket();
         $pdf->displayLine("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>",
                                            _n('Linked ticket', 'Linked tickets', 2), ''));
         foreach ($tickets as $linkID => $data) {
            $tmp = sprintf(__('%1$s %2$s'), Ticket_Ticket::getLinkName($data['link']),
                           sprintf(__('%1$s %2$s'), __('ID'), $data['tickets_id']));
            if ($ticket->getFromDB($data['tickets_id'])) {
               $tmp = sprintf(__('%1$s: %2$s'), $tmp, $ticket->getName());
            }
            $pdf->displayText('',$tmp, 1);
         }
      }

      $pdf->displaySpace();
   }





   static function pdfSolution(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $CFG_GLPI, $DB;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Solution')."</b>");

      if ($job->fields['solutiontypes_id'] || !empty($job->fields['solution'])) {
         if ($job->fields['solutiontypes_id']) {
            $title = Html::clean(Dropdown::getDropdownName('glpi_solutiontypes',
                                           $job->getField('solutiontypes_id')));
         } else {
            $title = __('Solution');
         }
         $sol = Html::clean(Toolbox::unclean_cross_side_scripting_deep(
                           html_entity_decode($job->getField('solution'),
                                              ENT_QUOTES, "UTF-8")));
         $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'), $title."</i></b>", ''), $sol);
      } else {
         $pdf->displayLine(__('None'));
      }

      $pdf->displaySpace();
   }


   static function pdfStat(PluginPdfSimplePDF $pdf, Ticket $job) {

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>"._n('Date', 'Dates', 2)."</b>");

      $pdf->setColumnsSize(50, 50);
      $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Opening date'),
                                Html::convDateTime($job->fields['date'])));
      $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Due date'),
                                Html::convDateTime($job->fields['due_date'])));
      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Resolution date'),
                                   Html::convDateTime($job->fields['solvedate'])));
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Closing date'),
                                   Html::convDateTime($job->fields['closedate'])));
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>"._n('Time', 'Times', 2)."</b>");

      $pdf->setColumnsSize(50, 50);
      if ($job->fields['takeintoaccount_delay_stat'] > 0) {
         $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Take into account'),
                                   Html::clean(Html::timestampToString($job->fields['takeintoaccount_delay_stat'],0))));
      }

      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
               if ($job->fields['solve_delay_stat'] > 0) {
            $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Solution'),
                                      Html::clean(Html::timestampToString($job->fields['solve_delay_stat'],0))));
         }
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         if ($job->fields['close_delay_stat'] > 0) {
            $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Closing'),
                                      Html::clean(Html::timestampToString($job->fields['close_delay_stat'],0))));
         }
      }
      if ($job->fields['waiting_duration'] > 0) {
         $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Pending'),
                                   Html::clean(Html::timestampToString($job->fields['waiting_duration'],0))));
      }

      $pdf->displaySpace();
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['Projecttask_Ticket$1']); // TODO add method to print linked Projecttask
      unset($onglets['Change_Ticket$1']); // TODO add method to print linked Changes

      if (Session::haveRight('ticket', Ticket::READALL) // for technician
          || Session::haveRight('followup', TicketFollowup::SEEPRIVATE)
          || Session::haveRight('task', TicketTask::SEEPRIVATE)) {
         $onglets['_private_'] = __('Private');
      }

      if (Session::haveRight('user', READ)) {
         $onglets['_inforequester_'] = __('Requester information', 'pdf');
      }

      return $onglets;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $private = isset($_REQUEST['item']['_private_']);

      switch ($tab) {
         case '_private_' :
            // nothing to export, just a flag
            break;

         case '_inforequester_' :
            break;

         case 'Ticket$1' : // 0.90+
            PluginPdfTicketFollowup::pdfForTicket($pdf, $item, $private);
            PluginPdfTicketTask::pdfForTicket($pdf, $item, $private);
            if (Session::haveRight('document', READ)) {
               PluginPdfDocument::pdfForItem($pdf, $item);
            }
            self::pdfSolution($pdf, $item);
            break;

         case 'TicketFollowup$1' : // 0.85
            PluginPdfTicketFollowup::pdfForTicket($pdf, $item, $private);
            break;

         case 'TicketTask$1' : // 0.85
            PluginPdfTicketTask::pdfForTicket($pdf, $item, $private);
            break;

         case 'TicketValidation$1' : // 0.85
            PluginPdfTicketValidation::pdfForTicket($pdf, $item);
            break;

         case 'TicketCost$1' :
            PluginPdfTicketCost::pdfForTicket($pdf, $item);
            break;

         case 'Ticket$2' : // 0.85
            self::pdfSolution($pdf, $item);
            break;

         case 'Ticket$3' :
            PluginPdfTicketSatisfaction::pdfForTicket($pdf, $item);
            break;

         case 'Problem_Ticket$1' :
            PluginPdfProblem::pdfForItem($pdf, $item);
            break;

         case 'Ticket$4' :
            self::pdfStat($pdf, $item);
            break;

         case 'Item_Ticket$1' :
            PluginPdfItem_Ticket::pdfForTicket($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }


}

