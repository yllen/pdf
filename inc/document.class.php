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

class PluginPdfDocument extends PluginPdfCommon {

   function __construct(Document $obj=NULL) {

      $this->obj = ($obj ? $obj : new Document());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB,$LANG;

      $ID = $item->getField('id');
      $type = get_class($item);

      if (!Session::haveRight("document","r")) {
         return false;
      }

      $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                       `glpi_documents`.*
                FROM `glpi_documents_items`
                LEFT JOIN `glpi_documents`
                     ON (`glpi_documents_items`.`documents_id` = `glpi_documents`.`id`)
                WHERE `glpi_documents_items`.`items_id` = '$ID'
                      AND `glpi_documents_items`.`itemtype` = '$type'";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $pdf->setColumnsSize(100);
      if (!$number) {
         $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['document'][1].'</b>');
      } else {
         $pdf->displayTitle('<b>'.$LANG["document"][21].' :</b>');

         $pdf->setColumnsSize(32,15,21,19,13);
         $pdf->displayTitle('<b>'.$LANG["common"][16].'</b>',
                            '<b>'.$LANG["document"][2].'</b>',
                            '<b>'.$LANG["document"][33].'</b>',
                            '<b>'.$LANG["document"][3].'</b>',
                            '<b>'.$LANG["document"][4].'</b>');

         while ($data = $DB->fetch_assoc($result)) {
            $pdf->displayLine($data["name"], basename($data["filename"]), $data["link"],
                              Html::clean(Dropdown::getDropdownName("glpi_documentcategories",
                                                                   $data["documentcategories_id"])),
                              $data["mime"]);
         }
      }
      $pdf->displaySpace();
   }
}