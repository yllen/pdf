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


class PluginPdfTicket extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Ticket());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Ticket $job) {

      $dbu = new DbUtils();

      $infouser = isset($_REQUEST['item']['_inforequester_']);

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

      $pdf->setColumnsSize(50,50);
      $recipient_name='';
      if ($job->fields["users_id_recipient"]) {
         $recipient      = new User();
         $recipient->getFromDB($job->fields["users_id_recipient"]);
         $recipient_name = $recipient->getName();
      }

      $due = $commenttto = $commentttr = $interntto = '';
      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Opening date')."</i></b>",
                          Html::convDateTime($job->fields["date"])),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('By')."</i></b>", $recipient_name));

      $ttr = "<b><i>".sprintf(__('%1$s: %2$s'), __('Time to resolve')."</b></i>",
                              Html::convDateTime($job->fields['time_to_resolve']));

      $tto = "<b><i>".sprintf(__('%1$s: %2$s'), __('Internal time to own')."</b></i>",
                              Html::convDateTime($job->fields["internal_time_to_own"]));

      if ($job->fields["olas_id_tto"] > 0) {
         $tto .= "<b><i>".sprintf(__('%1$s: %2$s'), __('OLA')."</b></i>",
                                  Toolbox::stripTags(Dropdown::getDropdownName("glpi_olas",
                                                                               $job->fields["olas_id_tto"])));

         $olalevel = new OlaLevel();
         $nextaction = new OlaLevel_Ticket();
         if ($nextaction->getFromDBForTicket($job->fields["id"], SLM::TTO)) {
            $tto .= "<br /><b><i>".sprintf(__('%1$s: %2$s'),
                                          sprintf(__('Next escalation: %s')."</b></i>",''),
                                                  Html::convDateTime($nextaction->fields['date']));

            if ($olalevel->getFromDB($nextaction->fields['olalevels_id'])) {
               $tto .= " <b><i>".sprintf(__('%1$s: %2$s'), __('Escalation level')."</b></i>",
                                         $olalevel->getName());
            }
         }
      }

      if ($job->fields["slas_id_ttr"] > 0) {
         $ttr .= "<b><i>".sprintf(__('%1$s: %2$s'), __('SLA')."</b></i>",
                                  Toolbox::stripTags(Dropdown::getDropdownName("glpi_slas",
                                                                               $job->fields["slas_id_ttr"])));

         $slalevel = new SlaLevel();
         $nextaction = new SlaLevel_Ticket();
         if ($nextaction->getFromDBForTicket($job->fields["id"], SLM::TTR)) {
            $ttr .= "<br /><b><i>".sprintf(__('%1$s: %2$s'),
                                           sprintf(__('Next escalation: %s')."</b></i>",''),
                                                   Html::convDateTime($nextaction->fields['date']));

            if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
               $ttr .= " <b><i>".sprintf(__('%1$s: %2$s'), __('Escalation level')."</b></i>",
                                         $slalevel->getName());
            }
         }
      }

      $pdf->setColumnsSize(50,50);
      $lastupdate = Html::convDateTime($job->fields["date_mod"]);
      if ($job->fields['users_id_lastupdater'] > 0) {
         $lastupdate = sprintf(__('%1$s by %2$s'), $lastupdate,
                               $dbu->getUserName($job->fields["users_id_lastupdater"]));
      }

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Last update').'</i></b>', $lastupdate));

      $pdf->displayLine(
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Time to own')."</b></i>",
                             Html::convDateTime($job->fields["time_to_own"])),
            $ttr);

      $pdf->displayLine($tto,
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Internal time to resolve')."</b></i>",
                             Html::convDateTime($job->fields['internal_time_to_resolve'])));

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Type')."</i></b>",
                          Toolbox::stripTags(Ticket::getTicketTypeName($job->fields["type"]))),
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
                          Toolbox::stripTags($job->getStatus($job->fields["status"])). $status),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Request source')."</i></b>",
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_requesttypes',
                                                                       $job->fields['requesttypes_id']))));

      $pdf->displayLine(
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Urgency')."</i></b>",
                          Toolbox::stripTags($job->getUrgencyName($job->fields["urgency"]))),
         "<b><i>".sprintf(__('%1$s: %2$s'), __('Approval')."</i></b>",
                          TicketValidation::getStatus($job->fields['global_validation'])));

      $pdf->displayLine(
            "<b><i>". sprintf(__('%1$s: %2$s'), __('Impact')."</i></b>",
                              Toolbox::stripTags($job->getImpactName($job->fields["impact"]))),
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Location')."</i></b>",
                             Dropdown::getDropdownName("glpi_locations",
                                                       $job->fields["locations_id"])));

      $pdf->displayLine(
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Priority')."</i></b>",
                             Toolbox::stripTags($job->getPriorityName($job->fields["priority"]))));

      $pdf->setColumnsSize(50,50);

      // Requester
      $users     = [];
      $listusers = '';
      $requester = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Requester'), $listusers);
      foreach ($job->getUsers(CommonITILActor::REQUESTER) as $d) {
         if ($d['users_id']) {
            $tmp = "<i>".Toolbox::stripTags($dbu->getUserName($d['users_id']))."</i>";
            if ($d['alternative_email']) {
               $tmp .= ' ('.$d['alternative_email'].')';
            }
         } else {
            $tmp = $d['alternative_email'];
         }

         $user = new User();
         if ($info = $user->getFromDB($d['users_id'])
             && $infouser) {
            if ($user->fields['phone'] || $user->fields['mobile']
                || $user->fields['usercategories_id'] || $user->fields['locations_id']) {

               $tmp .= " (";
               $first = true;
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

      $groups         = [];
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
      $users     = [];
      $listusers = '';
      $watcher   = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Watcher'), $listusers);
      foreach ($job->getUsers(CommonITILActor::OBSERVER) as $d) {
         if ($d['users_id']) {
            $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
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

      $groups       = [];
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
      $users = [];
      $listusers = '';
      $assign    = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Assigned to technicians'),
                                    $listusers);
      foreach ($job->getUsers(CommonITILActor::ASSIGN) as $d) {
         if ($d['users_id']) {
            $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
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

      $groups     = [];
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
      $suppliers      = [];
      $listsuppliers  = '';
      $assignsupplier = '<b><i>'.sprintf(__('%1$s: %2$s')."</i></b>", __('Assigned to a supplier'),
                                         $listsuppliers);
      foreach ($job->getSuppliers(CommonITILActor::ASSIGN) as $d) {
         $suppliers[] = Toolbox::stripTags(Dropdown::getDropdownName("glpi_suppliers",
                                                                     $d['suppliers_id']));
      }
      if (count($suppliers)) {
         $listsuppliers = implode(', ', $suppliers);
      }
      $pdf->displayText($assignsupplier, $listsuppliers, 1);

      $pdf->setColumnsSize(100);
      $pdf->displayLine(
            "<b><i>".sprintf(__('%1$s: %2$s'), __('Title')."</i></b>", $job->fields["name"]));

      $content = Glpi\Toolbox\Sanitizer::unsanitize(Html::entity_decode_deep( $job->fields['content']));

      $content = preg_replace('#data:image/[^;]+;base64,#', '@', $content);

      preg_match_all('/<img [^>]*src=[\'"]([^\'"]*docid=([0-9]*)(|&tickets_id=[0-9]*))[\'"][^>]*>/', $content, $res, PREG_SET_ORDER);

      foreach ($res as $img) {
         $docimg = new Document();
         $docimg->getFromDB($img[2]);

         $path = '<img src="file://'.GLPI_DOC_DIR.'/'.$docimg->fields['filepath'].'"/>';
         $content = str_replace($img[0], $path, $content);
      }

      $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>", __('Description'), ''),
                                         $content, 1);

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


   static function pdfStat(PluginPdfSimplePDF $pdf, Ticket $job) {

      $now                      = time();
      $date_creation            = strtotime($job->fields['date']);
      $date_takeintoaccount     = $date_creation + $job->fields['takeintoaccount_delay_stat'];
      if ($date_takeintoaccount == $date_creation) {
         $date_takeintoaccount  = 0;
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>"._n('Date', 'Dates', 2)."</b>");

      $pdf->setColumnsSize(50, 50);
      $pdf->setColumnsAlign('right', 'left');

      $pdf->displayLine(Html::convDateTime($job->fields['date']), __('Opening date'));

      if (!empty($job->fields['internal_time_to_own'])) {
         $pdf->displayLine(Html::convDateTime($job->fields['internal_time_to_own']),
                           __('Internal time to own'));
      }
      if (!empty($job->fields['takeintoaccount_delay_stat'])) {
         $pdf->displayLine(Html::convDateTime(date("Y-m-d H:i:s", $date_takeintoaccount)),
                           __('Take into account'));
      }
      if (!empty($job->fields['time_to_own'])) {
         $pdf->displayLine(Html::convDateTime($job->fields['time_to_own']), __('Time to own'));
      }
      if (!empty($job->fields['internal_time_to_resolve'])) {
         $pdf->displayLine(Html::convDateTime($job->fields['internal_time_to_resolve']),
                           __('Internal time to resolve'));
      }
      if (!empty($job->fields['time_to_resolve'])) {
          $pdf->displayLine(Html::convDateTime($job->fields['time_to_resolve']),
                            __('Time to resolve'));
      }
      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $pdf->displayLine(Html::convDateTime($job->fields['solvedate']), __('Resolution date'));
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         $pdf->displayLine(Html::convDateTime($job->fields['closedate']), __('Closing date'));
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>"._n('Time', 'Times', 2)."</b>");

      $pdf->setColumnsSize(50, 50);
      if ($job->fields['takeintoaccount_delay_stat'] > 0) {
         $pdf->displayLine(__('Take into account'),
                           Toolbox::stripTags(Html::timestampToString($job->fields['takeintoaccount_delay_stat'],0)));
      }

      if (in_array($job->fields["status"], $job->getSolvedStatusArray())
          || in_array($job->fields["status"], $job->getClosedStatusArray())) {
               if ($job->fields['solve_delay_stat'] > 0) {
            $pdf->displayLine(__('Solution'),
                              Toolbox::stripTags(Html::timestampToString($job->fields['solve_delay_stat'],0)));
         }
      }
      if (in_array($job->fields["status"], $job->getClosedStatusArray())) {
         if ($job->fields['close_delay_stat'] > 0) {
            $pdf->displayLine(__('Closing'),
                              Toolbox::stripTags(Html::timestampToString($job->fields['close_delay_stat'],1)));
         }
      }
      if ($job->fields['waiting_duration'] > 0) {
         $pdf->displayLine(__('Pending'),
                           Toolbox::stripTags(Html::timestampToString($job->fields['waiting_duration'],0)));
      }

      $pdf->displaySpace();
   }


   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['ProjectTask_Ticket$1']);
      unset($onglets['Itil_Project$1']);

      if (Session::haveRight('ticket', Ticket::READALL) // for technician
          || Session::haveRight('followup', ITILFollowup::SEEPRIVATE)
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

         case 'Ticket$main' : // 0.90+
            self::pdfMain($pdf, $item);
            PluginPdfItilFollowup::pdfForItem($pdf, $item, $private);
            PluginPdfTicketTask::pdfForTicket($pdf, $item, $private);
            if (Session::haveRight('document', READ)) {
               PluginPdfDocument::pdfForItem($pdf, $item);
            }
             PluginPdfITILSolution::pdfForItem($pdf, $item);
            break;

         case 'TicketValidation$1' : // 0.85
            PluginPdfTicketValidation::pdfForTicket($pdf, $item);
            break;

         case 'TicketCost$1' :
            PluginPdfCommonItilCost::pdfForItem($pdf, $item);
            break;

         case 'Ticket$3' :
            PluginPdfTicketSatisfaction::pdfForTicket($pdf, $item);
            break;

         case 'Problem_Ticket$1' :
            PluginPdfProblem_Ticket::pdfForTicket($pdf, $item);
            break;

         case 'Ticket$4' :
            self::pdfStat($pdf, $item);
            break;

         case 'Item_Ticket$1' :
            PluginPdfItem_Ticket::pdfForTicket($pdf, $item);
            break;

         case 'Change_Ticket$1' :
            if (Change::canView()) {
               PluginPdfChange_Ticket::pdfForTicket($pdf, $item);
            }
            break;

         case 'Ticket_Contract$1' :
            PluginPdfTicket_Contract::pdfForTicket($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }


}

