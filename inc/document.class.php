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
 @copyright Copyright (c) 2009-2016 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfDocument extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Document());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $ID   = $item->getField('id');
      $type = get_class($item);

      $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                       `glpi_documents_items`.`date_mod` AS assocdate,
                       `glpi_documents`.*,
                       `glpi_entities`.`id` AS entityID,
                       `glpi_entities`.`completename` AS entity
                FROM `glpi_documents_items`
                LEFT JOIN `glpi_documents`
                     ON (`glpi_documents_items`.`documents_id` = `glpi_documents`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                WHERE `glpi_documents_items`.`items_id` = '".$ID."'
                      AND `glpi_documents_items`.`itemtype` = '".$type."'";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $pdf->setColumnsSize(100);
      if (!$number) {
         $pdf->displayTitle('<b>'.__('No associated documents', 'pdf').'</b>');
      } else {
         $pdf->displayTitle('<b>'.__('Associated documents', 'pdf').'</b>');

         $pdf->setColumnsSize(32,15,14,11,8,8,9);
         $pdf->displayTitle('<b>'.__('Name'), __('Entity'), __('File'), __('Web link'), __('Heading'),
                                  __('MIME type'), __('Date').'</b>');

         while ($data = $DB->fetch_assoc($result)) {
            $pdf->displayLine($data["name"], $data['entity'], basename($data["filename"]), $data["link"],
                              Html::clean(Dropdown::getDropdownName("glpi_documentcategories",
                                                                    $data["documentcategories_id"])),
                              $data["mime"], Html::convDateTime($data["assocdate"]));
         }
      }
      $pdf->displaySpace();
   }
}