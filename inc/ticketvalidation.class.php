<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2013 by the pdf Development Team.

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


class PluginPdfTicketValidation extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new TicketValidation());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $ticket) {
      global $CFG_GLPI, $DB;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Approvals for the ticket')."</b>");

      if (!Session::haveRight('validate_request',1)
          && !Session::haveRight('validate_incident',1)
          && !Session::haveRight('create_incident_validation',1)
          && !Session::haveRight('create_request_validation',1)) {
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
         $pdf->displayTitle(_x('item', 'State'), __('Request date'),
                            __('Approval requester'), __('Approval date'), __('Approver'));

         while ($row = $DB->fetch_assoc($result)) {
            $pdf->setColumnsSize(20,19,21,19,21);
            $pdf->displayLine(TicketValidation::getStatus($row['status']),
                              Html::convDateTime($row["submission_date"]),
                              getUserName($row["users_id"]),
                              Html::convDateTime($row["validation_date"]),
                              getUserName($row["users_id_validate"]));
            $tmp = trim($row["comment_submission"]);
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'), __('Request comments')."</i></b>",
                                               (empty($tmp) ? __('None') : $tmp), 1));

            if ($row["validation_date"]) {
               $tmp = trim($row["comment_validation"]);
               $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'),
                                                  __('Approval comments')."</i></b>",
                                                  (empty($tmp) ? __('None') : $tmp), 1));
            }
         }
      } else {
         $pdf->displayLine(__('No item found'));
      }
      $pdf->displaySpace();
   }
}