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


class PluginPdfTicketValidation extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new TicketValidation());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $ticket) {
      global $CFG_GLPI, $DB;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Approvals for the ticket','pdf')."</b>");

      if (!Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
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
                                               ''), (empty($tmp) ? __('None') : $tmp), 1);

            if ($row["validation_date"]) {
               $tmp = trim($row["comment_validation"]);
               $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'),
                                                  __('Approval comments')."</i></b>", ''),
                                                  (empty($tmp) ? __('None') : $tmp), 1);
            }
         }
      } else {
         $pdf->displayLine(__('No item found'));
      }
      $pdf->displaySpace();
   }
}