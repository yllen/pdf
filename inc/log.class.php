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
 @copyright Copyright (c) 2009-2017 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfLog extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Log());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {

      // Get the Full history for the item (really a good idea ?, should we limit this)
      $changes = Log::getHistoryData($item);

      $pdf->setColumnsSize(100);
      if (count($changes) > 0) {
         $pdf->displayTitle("<b>".__('Historical')."</b>");

         $pdf->setColumnsSize(10,15,24,11,40);
         $pdf->displayTitle('<b><i>'.__('ID'), __('Date'), __('User'), __('Field'),
                            _x('name', 'Update').'</i></b>');

         foreach ($changes as $data) {
            if ($data['display_history']) {
               $pdf->displayLine($data['id'], $data['date_mod'], $data['user_name'], $data['field'],
                                 Html::clean($data['change']));
            }
         } // Each log
      } else {
         $pdf->displayTitle("<b>".__('No historical')."</b>");
      }
      $pdf->displaySpace();
   }
}