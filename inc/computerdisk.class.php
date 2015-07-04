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
 @copyright Copyright (c) 2009-2015 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.indepnet.net/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfComputerDisk extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ComputerDisk());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item) {
      global $DB;

      $ID = $item->getField('id');

      $query = "SELECT `glpi_filesystems`.`name` AS fsname, `glpi_computerdisks`.*
                FROM `glpi_computerdisks`
                LEFT JOIN `glpi_filesystems`
                  ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
                WHERE (`computers_id` = '".$ID."')";

      $result = $DB->query($query);

      $pdf->setColumnsSize(100);
      if ($DB->numrows($result) > 0) {
         $pdf->displayTitle("<b>"._n('Volume', 'Volumes', $DB->numrows($result))."</b>");

         $pdf->setColumnsSize(22,23,22,11,11,11);
         $pdf->displayTitle('<b>'.__('Name'), __('Partition'), __('Mount point'), __('Type'),
                                  __('Global size'), __('Free size').'</b>');

         $pdf->setColumnsAlign('left','left','left','center','right','right');

         while ($data = $DB->fetch_assoc($result)) {
            $pdf->displayLine('<b>'.$data['name'].'</b>',
                              $data['device'],
                              $data['mountpoint'],
                              Html::clean(Dropdown::getDropdownName('glpi_filesystems',
                                                                    $data["filesystems_id"])),
                              sprintf(__('%s Mio'),
                                      Html::clean(Html::formatNumber($data['totalsize'], false, 0))),
                              sprintf(__('%s Mio'),
                                      Html::clean(Html::formatNumber($data['freesize'], false, 0))));
         }
      } else {
         $pdf->displayTitle("<b>".__('No volume found', 'pdf')."</b>");
      }
      $pdf->displaySpace();
   }
}