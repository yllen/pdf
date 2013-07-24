<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2013 by the pdf Development Team.

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



class PluginPdfComputerDisk extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ComputerDisk());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item) {
      global $DB, $LANG;

      $ID = $item->getField('id');

      $query = "SELECT `glpi_filesystems`.`name` AS fsname, `glpi_computerdisks`.*
                FROM `glpi_computerdisks`
                LEFT JOIN `glpi_filesystems`
                  ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
                WHERE (`computers_id` = '".$ID."')";

      $result = $DB->query($query);

      $pdf->setColumnsSize(100);
      if ($DB->numrows($result) > 0) {
         $pdf->displayTitle("<b>"._n('Volume', 'Volumes', 2)."</b>");

         $pdf->setColumnsSize(22,23,22,11,11,11);
         $pdf->displayTitle('<b>'.__('Name'), __('Partition'), _('Mount point'), __('Type'),
                                  __('Global size'), __('Free size').'</b>');

         $pdf->setColumnsAlign('left','left','left','center','right','right');

         while ($data = $DB->fetch_assoc($result)) {
            $pdf->displayLine('<b>'.Toolbox::decodeFromUtf8((empty($data['name'])
                                                              ?$data['ID']:$data['name']),
                                                            "windows-1252").'</b>',
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