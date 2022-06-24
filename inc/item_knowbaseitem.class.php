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
 @copyright Copyright (c) 2019-2022 PDF plugin team
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
      $this->obj = ($obj ? $obj : new Item_Disk());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB;

      $ID = $item->getField('id');

      $result = $DB->request('glpi_knowbaseitems',
                             ['SELECT'    => ['glpi_knowbaseitems.*',
                                              'glpi_knowbaseitems_items.itemtype',
                                              'glpi_knowbaseitems_items.items_id'],
                              'LEFT JOIN' => ['glpi_knowbaseitems_items'
                                              => ['FKEY' => ['glpi_knowbaseitems_items' => 'knowbaseitems_id',
                                                             'glpi_knowbaseitems'       => 'id']]],
                              'WHERE'     => ['items_id'   => $ID,
                                              'itemtype'   => $item->getType()]]);
      $number = count($result);

      $pdf->setColumnsSize(100);

      if (!$number) {
         $pdf->displayTitle("<b>".__('No knowledge base entries linked')."</b>");
      } else {
         $title = "<b>".__('Link a knowledge base entry')."</b>";
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(40,40,10,10);
         $pdf->displayTitle(__('Type'), __('Item'), __('Creation date'), __('Update date'));

         foreach ($result as $data) {
            $pdf->displayLine(__('Knowledge base'),
                              $data['name'],
                              Html::convDateTime($data['date_creation']),
                              Html::convDateTime($data['date_mod']));
         }
      }
      $pdf->displaySpace();
   }
}