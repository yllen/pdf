<?php
/**
 * @version $Id:  yllen $
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
 @copyright Copyright (c) 2019 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfItem_Knowbaseitem extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ComputerDisk());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB;

      $ID = $item->getField('id');

      $result = $DB->request('glpi_knowbaseitems_items',
                             ['SELECT'    => ['glpi_knowbaseitems.name',
                                              'glpi_knowbaseitems_items.*'],
                              'LEFT JOIN' => ['glpi_knowbaseitems'
                                              => ['FKEY' => ['glpi_knowbaseitems_items' => 'knowbaseitems_id',
                                                             'glpi_knowbaseitems'       => 'id']]],
                              'WHERE'     => ['items_id'   => $ID,
                                              'itemtype'   => $item->getType()]]);

      $pdf->setColumnsSize(100);

      if (!count($result)) {
         $pdf->displayTitle("<b>".__('No knowledge base entries linked')."</b>");
      } else {
         $pdf->displayTitle("<b>".__('Link a knowledge base entry')."</b>");

         $pdf->setColumnsSize(40,40,10,10);
         $pdf->displayTitle('<b>'.__('Type'), __('Item'), __('Creation date'), __('Update date').'</b>');

         while ($data = $result->next()) {
            $pdf->displayLine(__('Knowledge base'),
                              $data['name'],
                              Html::convDate($data['date_creation']),
                              Html::convDate($data['date_mod']));
         }
      }
      $pdf->displaySpace();
   }
}