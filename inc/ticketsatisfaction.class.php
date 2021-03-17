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
 @copyright Copyright (c) 2009-2021 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfTicketSatisfaction extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new TicketSatisfaction());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $ticket) {

      $survey = new TicketSatisfaction();

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Satisfaction survey')."</b>");
      if (!$survey->getFromDB($ticket->getID())) {
         $pdf->displayLine(__('No generated survey'));

      } else if ($survey->getField('type') == 2) {
         $url = Entity::generateLinkSatisfaction($ticket);
         $pdf->displayLine(sprintf(__('%1$s (%2$s)'), __('External survey'), $url));

      } else if ($survey->getField('date_answered')){
         $sat = $survey->getField('satisfaction');
         $tabsat = [0 => __('None'),
                    1 => __('1 star', 'pdf'),
                    2 => __('2 stars', 'pdf'),
                    3 => __('3 stars', 'pdf'),
                    4 => __('4 stars', 'pdf'),
                    5 => __('5 stars', 'pdf')];
         if (isset($tabsat[$sat])) {
            $sat = $tabsat[$sat]. "  ($sat/5)";
         }
         $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'),
                                         __('Response date to the satisfaction survey').'</b>',
                                         Html::convDateTime($survey->getField('date_answered'))));
         $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'),
                                         __('Satisfaction with the resolution of the ticket').'</b>',
                                        $sat));
         $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), __('Comments').'</b>', ''),
                                         $survey->getField('comment'));

      } else {   // No answer
         $pdf->displayLine(sprintf(__('%1$s: %2$s'), __('Creation date of the satisfaction survey'),
                                   Html::convDateTime($survey->getField('date_begin'))));
         $pdf->displayLine(__('No answer', 'pdf'));
      }
      $pdf->displaySpace();
   }

}