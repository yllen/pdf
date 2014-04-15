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


class PluginPdfTicketTask extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new TicketTask());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $job, $private) {
      global $CFG_GLPI, $DB;

      $ID = $job->getField('id');

      //////////////Tasks///////////

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
      $result = $DB->query($query);

      if (!$DB->numrows($result)) {
         $pdf->setColumnsSize(100);
         $pdf->displayLine(__('No task found.'));
      } else {
         $pdf->displayTitle("<b>".TicketTask::getTypeName($DB->numrows($result)."</b>"));

         $pdf->setColumnsSize(20,20,20,20,20);
         $pdf->displayTitle("<b><i>".__('Type')."</i></b>",
               "<b><i>". __('Date')."</i></b>",
               "<b><i>". __('Duration')."</i></b>",
               "<b><i>".__('Writer')."</i></b>",
               "<b><i>".__('Planning')."</i></b>");

         while ($data=$DB->fetch_array($result)) {

            $actiontime = Html::timestampToString($data['actiontime'], false);
            $planification = '';
            if (empty($data['begin'])) {
               if (isset($data["state"])) {
                  $planification = Planning::getState($data["state"])."<br>";
               }
               $planification .= _e('None');
            } else {
               if (isset($data["state"])) {
                  $planification = sprintf(__('%1$s: %2$s'), _x('item', 'State'),
                                           Planning::getState($data["state"]));
               }
               $planificiation = sprintf(__('%1$s - %2$s'), $planification,
                                         Html::convDateTime($data["begin"])." -> ".
                                         Html::convDateTime($data["end"]));
               $planificiation = sprintf(__('%1$s - %2$s'), $planification,
                                         sprintf(__('%1$s  %2$s'), __('By'),
                                                 getUserName($data["users_id_tech"])));
            }


            if ($data['taskcategories_id']) {
               $lib = Dropdown::getDropdownName('glpi_taskcategories', $data['taskcategories_id']);
            } else {
               $lib = '';
            }
            if ($data['is_private']) {
               $lib = sprintf(__('%1$s (%2$s)'), $lib, __('Private'));
            }

            $pdf->displayLine(Html::clean($lib),
                              Html::convDateTime($data["date"]),
                              Html::timestampToString($data["actiontime"], 0),
                              Html::clean(getUserName($data["users_id"])),
                              Html::clean($planification),1);
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'), __('Description')."</i></b>", ''),
                                               Html::clean($data["content"]), 1);
         }
      }
      $pdf->displaySpace();
   }
}