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
 @copyright Copyright (c) 2009-2015 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/

class PluginPdfTicketFollowup extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new TicketFollowup());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $job, $private) {
      global $CFG_GLPI, $DB;

      $ID = $job->getField('id');

      //////////////followups///////////
      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Ticket followup')."</b>");

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
         $pdf->displayLine(__('No followup for this ticket.'));
      } else {
         while ($data=$DB->fetch_array($result)) {
            $pdf->setColumnsSize(44,14,42);
            $pdf->displayTitle("<b><i>".__('Source of followup')."</i></b>", // Source
                               "<b><i>".__('Date')."</i></b>", // Date
                               "<b><i>".__('Requester')."</i></b>"); // Author

            if ($data['requesttypes_id']) {
               $lib = Dropdown::getDropdownName('glpi_requesttypes', $data['requesttypes_id']);
            } else {
               $lib = '';
            }
            if ($data['is_private']) {
               $lib = sprintf(__('%1$s (%2$s)'), $lib, __('Private'));
            }
            $pdf->displayLine(Html::clean($lib),
                              Html::convDateTime($data["date"]),
                              Html::clean(getUserName($data["users_id"])));
            $pdf->displayText('<b><i>'.sprintf(__('%1$s: %2$s'), __('Comments').'</i></b>', ''),
                                               $data["content"]);
         }
      }
      $pdf->displaySpace();
   }
}