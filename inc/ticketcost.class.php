<?php
/*
 * @version $Id: ticketcost.class.php 336 2012-10-20 16:34:42Z remi $
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

class PluginPdfTicketCost extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new TicketCost());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $job) {
      global $CFG_GLPI, $DB;

      $ID = $job->getField('id');

      //////////////followups///////////


      $query = "SELECT *
                FROM `glpi_ticketcosts`
                WHERE `tickets_id` = '$ID'
                ORDER BY `begin_date`";
      $result=$DB->query($query);

      if (!$DB->numrows($result)) {
         $pdf->setColumnsSize(100);
         $pdf->displayLine(__('No ticket cost for this ticket', 'pdf'));
      } else {
         $pdf->setColumnsSize(60,20,20);
         $pdf->displayTitle("<b>".TicketCost::getTypeName($DB->numrows($result)),
                            __('Ticket duration'),
                            CommonITILObject::getActionTime($job->fields['actiontime'])."</b>");

         $pdf->setColumnsSize(20,10,10,10,10,10,10,10,10);
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

         while ($data=$DB->fetch_array($result)) {

            $cost = TicketCost::computeTotalCost($data['actiontime'], $data['cost_time'],
                                           $data['cost_fixed'], $data['cost_material']);
            $pdf->displayLine($data['name'],
                              Html::convDate($data['begin_date']),
                              Html::convDate($data['end_date']),
                              Dropdown::getDropdownName('glpi_budgets', $data['budgets_id']),
                              CommonITILObject::getActionTime($data['actiontime']),
                              Html::formatNumber($data['cost_time']),
                              Html::formatNumber($data['cost_fixed']),
                              Html::formatNumber($data['cost_material']),
                              Html::formatNumber($cost));

            $total_time     += $data['actiontime'];
            $total_costtime += ($data['actiontime']*$data['cost_time']/HOUR_TIMESTAMP);
            $total_fixed    += $data['cost_fixed'];
            $total_material += $data['cost_material'];
            $total          += $cost;
         }
         $pdf->setColumnsSize(50,10,10,10,10,10);
         $pdf->setColumnsAlign('right','right','right','right','right','right');
         $pdf->displayLine('<b>'.__('Total'), CommonITILObject::getActionTime($total_time),
                           Html::formatNumber($total_costtime), Html::formatNumber($total_fixed),
                           Html::formatNumber($total_material), Html::formatNumber($total));
      }
      $pdf->displaySpace();
   }
}