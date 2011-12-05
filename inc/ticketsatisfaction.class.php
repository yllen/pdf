<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfTicketSatisfaction extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new TicketSatisfaction());
   }


   static function pdfForTicket(PluginPdfSimplePDF $pdf, Ticket $ticket) {
      global $LANG;

      $survey = new TicketSatisfaction();

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG['satisfaction'][3]."</b>");
      if (!$survey->getFromDB($ticket->getID())) {                   // No survey
         $pdf->displayLine($LANG['satisfaction'][2]);

      } else if ($survey->getField('type') == 2) {                   // External
         $url = EntityData::generateLinkSatisfaction($ticket);
         $pdf->displayLine($LANG['satisfaction'][10]." ($url)");

      } else if ($survey->getField('date_answered')){                // With anwser
      $sat = $survey->getField('satisfaction');
      $tabsat = array(0 => $LANG['job'][32],
                      1 => $LANG['help'][7],
                      2 => $LANG['help'][6],
                      3 => $LANG['help'][5],
                      4 => $LANG['help'][4],
                      5 => $LANG['help'][3]);
      if (isset($tabsat[$sat])) {
         $sat = $tabsat[$sat]. "  ($sat/5)";
      }
         $pdf->displayLine('<b>'.$LANG['satisfaction'][6].'</b> : '.
                           Html::convDateTime($survey->getField('date_begin')));
         $pdf->displayLine('<b>'.$LANG['satisfaction'][4].'</b> : '.
                           Html::convDateTime($survey->getField('date_answered')));
         $pdf->displayLine('<b>'.$LANG['satisfaction'][1].'</b> : '.$sat);
         $pdf->displayText('<b>'.$LANG['common'][25].'</b> : ',
                           $survey->getField('comment'));

      } else {                                                       // No answer
         $pdf->displayLine($LANG['satisfaction'][6].' : '.Html::convDateTime($survey->getField('date_begin')));
         $pdf->displayLine($LANG['plugin_pdf']['ticket'][5]);
      }
      $pdf->displaySpace();
   }
}