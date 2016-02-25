<?php
/**
 * @version $Id:
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
 @copyright Copyright (c) 2009-2016 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/

class PluginPdfChangeCost extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new ChangeCost());
   }


   static function pdfForChange(PluginPdfSimplePDF $pdf, Change $job) {
      global $CFG_GLPI, $DB;

      $ID = $job->getField('id');

      $query = "SELECT *
                FROM `glpi_changecosts`
                WHERE `changes_id` = '$ID'
                ORDER BY `begin_date`";
      $result=$DB->query($query);

      if (!$DB->numrows($result)) {
         $pdf->setColumnsSize(100);
         $pdf->displayLine(__('No cost for this change', 'pdf'));
      } else {
         $pdf->setColumnsSize(60,20,20);
         $pdf->displayTitle("<b>".ChangeCost::getTypeName($DB->numrows($result)),
                            __('Change duration'),
                            CommonITILObject::getActionTime($job->fields['actiontime'])."</b>");

         $pdf->setColumnsSize(19,11,10,10,10,10,10,10,10);
         $pdf->setColumnsAlign('center','center','center','left', 'right','right','right',
               'right','right');
         $pdf->displayTitle("<b><i>".__('Name')."</i></b>",
               "<b><i>".__('Begin date')."</i></b>",
               "<b><i>".__('End date')."</i></b>",
               "<b><i>".__('Budget')."</i></b>",
               "<b><i>".__('Duration')."</i></b>",
               "<b><i>".__('Time cost')."</i></b>",
               "<b><i>".__('Fixed cost')."</i></b>",
               "<b><i>".__('Material cost')."</i></b>",
               "<b><i>".__('Total cost')."</i></b>");

         $total          = 0;
         $total_time     = 0;
         $total_costtime = 0;
         $total_fixed    = 0;
         $total_material = 0;

         while ($data=$DB->fetch_array($result)) {
            $cost = ChangeCost::computeTotalCost($data['actiontime'], $data['cost_time'],
                                           $data['cost_fixed'], $data['cost_material']);
            $pdf->displayLine($data['name'],
                              Html::convDate($data['begin_date']),
                              Html::convDate($data['end_date']),
                              Html::Clean(Dropdown::getDropdownName('glpi_budgets',
                                                                    $data['budgets_id'])),
                              CommonITILObject::getActionTime($data['actiontime']),
                              Html::Clean(Html::formatNumber($data['cost_time'])),
                              Html::Clean(Html::formatNumber($data['cost_fixed'])),
                              Html::Clean(Html::formatNumber($data['cost_material'])),
                              Html::Clean(Html::formatNumber($cost)));

            $total_time     += $data['actiontime'];
            $total_costtime += ($data['actiontime']*$data['cost_time']/HOUR_TIMESTAMP);
            $total_fixed    += $data['cost_fixed'];
            $total_material += $data['cost_material'];
            $total          += $cost;
         }
         $pdf->setColumnsSize(52,9,10,10,10,9);
         $pdf->setColumnsAlign('right','right','right','right','right','right');
         $pdf->displayLine('<b>'.__('Total'), CommonITILObject::getActionTime($total_time),
                           Html::Clean(Html::formatNumber($total_costtime)),
                           Html::Clean(Html::formatNumber($total_fixed)),
                           Html::Clean(Html::formatNumber($total_material)),
                           Html::Clean(Html::formatNumber($total)));
      }
      $pdf->displaySpace();
   }
}