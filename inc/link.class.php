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
 @copyright Copyright (c) 2009-2022 PDF plugin team
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

      $query = ['SELECT'     => ['glpi_links.id', 'link', 'name', 'data'],
                'FROM'       => 'glpi_links',
                'INNER JOIN' => ['glpi_links_itemtypes'
                                 => ['FKEY' => ['glpi_links'           => 'id',
                                                'glpi_links_itemtypes' => 'links_id']]],
                'WHERE'      => ['itemtype' => $type],
                'ORDER'      => 'name'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('External link', 'External links', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         foreach ($result as $data) {
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
      }
      $pdf->displaySpace();
   }
}