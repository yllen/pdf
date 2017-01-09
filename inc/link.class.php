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


class PluginPdfLink extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Link());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB;

      $ID   = $item->getField('id');
      $type = get_class($item);

      $query = "SELECT `glpi_links`.`id` AS ID, `glpi_links`.`link`, `glpi_links`.`name`,
                       `glpi_links`.`data`
                FROM `glpi_links`
                INNER JOIN `glpi_links_itemtypes`
                     ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
                WHERE `glpi_links_itemtypes`.`itemtype` = '".$type."'
                ORDER BY `glpi_links`.`name`";

      $result=$DB->query($query);

      $pdf->setColumnsSize(100);
      if ($DB->numrows($result) > 0) {
         $pdf->displayTitle('<b>'._n('External link', 'External links', $DB->numrows($result)).'</b>');

         while ($data = $DB->fetch_assoc($result)) {
            $name = $data["name"];
            if (empty($name)) {
               $name = $data["link"];
            }
            $link = $data["link"];
            $file = trim($data["data"]);

            if (empty($file)) {
               $links = Link::generateLinkContents($data['link'], $item, $name);
               $i     = 1;
               foreach ($links as $key => $link) {
                  $url = $link;
                  $pdf->displayLine(sprintf(__('%1$s: %2$s'), "<b>$name #$i</b>", $link));
                  $i++;
                  $i++;
               }
            } else { // Generated File
                  $files = Link::generateLinkContents($data['link'], $item);
                  $links = Link::generateLinkContents($data['data'], $item);
                  $i=1;
                  foreach ($links as $key => $data) {
                     if (isset($files[$key])) {
                        // a different name for each file, ex name = foo-[IP].txt
                        $file = $files[$key];
                     } else {
                        // same name for all files, ex name = foo.txt
                        $file = reset($files);
                     }
                     $pdf->displayText(sprintf(__('%1$s: %2$s'), "<b>$name #$i - $file</b>", ''),
                                               trim($data), 1, 10);
                     $i++;
                  }
            }
         } // Each link
      } else {
         $pdf->displayTitle('<b>'.__('No link defined').'</b>');
      }
      $pdf->displaySpace();
   }
}