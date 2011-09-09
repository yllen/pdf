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

class PluginPdfLog extends PluginPdfCommon {

   function __construct(Log $obj=NULL) {

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