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

class PluginPdfTicketFollowup extends PluginPdfCommon {


   function __construct(TicketFollowup $obj=NULL) {

      $this->obj = ($obj ? $obj : new TicketFollowup());
   }

   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $job, $private) {
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
}