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

class PluginPdfLog extends PluginPdfCommon {

   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new Log());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $LANG;

      // Get the Full history for the item (really a good idea ?, should we limit this)
      $changes = Log::getHistoryData($item);

      $pdf->setColumnsSize(100);
      if (count($changes) > 0) {
         $pdf->displayTitle("<b>".$LANG["title"][38]."</b>");

         $pdf->setColumnsSize(14,15,20,51);
         $pdf->displayTitle('<b><i>'.$LANG["common"][27].'</i></b>',
                            '<b><i>'.$LANG["common"][34].'</i></b>',
                            '<b><i>'.$LANG["event"][18].'</i></b>',
                            '<b><i>'.$LANG["event"][19].'</i></b>');

         foreach ($changes as $data) {
            if ($data['display_history']) {
               $pdf->displayLine($data['date_mod'], $data['user_name'], $data['field'], Html::clean($data['change']));
            }
         } // Each log
      } else {
         $pdf->displayTitle("<b>".$LANG["event"][20]."</b>");
      }
      $pdf->displaySpace();
   }
}