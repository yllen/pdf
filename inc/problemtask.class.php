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


class PluginPdfProblemTask extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ProblemTask());
   }


   static function pdfForProblem(PluginPdfSimplePDF $pdf, Problem $job) {
      global $DB;

      $dbu = new DbUtils();

      $ID  = $job->getField('id');

      //////////////Tasks///////////

      $query = ['FROM'  => 'glpi_problemtasks',
                'WHERE' => ['problems_id' => $ID],
                'ORDER' => 'date DESC'];

      $result = $DB->request($query);

      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.ProblemTask::getTypeName($number).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s (%2$s)'), $title, $_SESSION['glpilist_limit']."/".$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(30,10,20,20,20);
         $pdf->displayTitle("<i>".__('Type'), __('Date'), __('Duration'), __('Writer'),
                                     __('Planning')."</i>");

         foreach ($result as $id => $data) {
            $actiontime = Html::timestampToString($data['actiontime'], false);
            $planification = '';
            if (empty($data['begin'])) {
               if (isset($data["state"])) {
                  $planification = Planning::getState($data["state"])."<br>";
               }
            } else {
               if (isset($data["state"]) && $data["state"]) {
                  $planification = sprintf(__('%1$s: %2$s'), _x('item', 'State'),
                                           Planning::getState($data["state"]));
               }
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('Begin'),
                                                Html::convDateTime($data["begin"]));
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('End'),
                                                Html::convDateTime($data["end"]));
               $planification .= "<br>".sprintf(__('%1$s: %2$s'), __('By'),
                                                $dbu->getUserName($data["users_id_tech"]));
                           }


            if ($data['taskcategories_id']) {
               $lib = Dropdown::getDropdownName('glpi_taskcategories', $data['taskcategories_id']);
            } else {
               $lib = '';
            }
            $pdf->displayLine("</b>".Toolbox::stripTags($lib),
                              Html::convDateTime($data["date"]),
                              Html::timestampToString($data["actiontime"], 0),
                              Toolbox::stripTags($dbu->getUserName($data["users_id"])),
                              Toolbox::stripTags($planification),1);
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s')."</i></b>", __('Description'), ''),
                                               Toolbox::stripTags($data["content"]), 1);
         }
      }
      $pdf->displaySpace();
   }
}