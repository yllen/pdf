<?php
/*
 * @version $Id:
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


class PluginPdfProblemTask extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ProblemTask());
   }


   static function pdfForProblem(PluginPdfSimplePDF $pdf, Problem $job) {
      global $CFG_GLPI, $DB;

      $ID = $job->getField('id');

      //////////////Tasks///////////

      $query = "SELECT *
                FROM `glpi_problemtasks`
                WHERE `problems_id` = '$ID'
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      if (!$DB->numrows($result)) {
         $pdf->setColumnsSize(100);
         $pdf->displayLine(__('No task found.'));
      } else {
         $pdf->displayTitle("<b>".ProblemTask::getTypeName($DB->numrows($result)."</b>"));

         $pdf->setColumnsSize(30,10,20,20,20);
         $pdf->displayTitle("<i>".__('Type'), __('Date'), __('Duration'), __('Writer'),
                                     __('Planning')."</i>");

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
            $pdf->displayLine("<b>".Html::clean($lib),
                              Html::convDateTime($data["date"]),
                              Html::timestampToString($data["actiontime"], 0),
                              Html::clean(getUserName($data["users_id"])),
                              Html::clean($planification)."</b>",1);
            $pdf->displayText("<i>".sprintf(__('%1$s: %2$s')."</i>", __('Description'), ''),
                                               "</b>".Html::clean($data["content"])."</b>", 1);
         }
      }
      $pdf->displaySpace();
   }
}