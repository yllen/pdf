<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

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
                WHERE (`computers_id` = '$ID')";

      $result=$DB->query($query);

      $pdf->setColumnsSize(100);
      if ($DB->numrows($result) > 0) {
         $pdf->displayTitle("<b>".$LANG['computers'][8]."</b>");

         $pdf->setColumnsSize(22,23,22,11,11,11);
         $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                            '<b>'.$LANG['computers'][6].'</b>',
                            '<b>'.$LANG['computers'][5].'</b>',
                            '<b>'.$LANG['common'][17].'</b>',
                            '<b>'.$LANG['computers'][3].'</b>',
                            '<b>'.$LANG['computers'][2].'</b>');

         $pdf->setColumnsAlign('left','left','left','center','right','right');

         while ($data = $DB->fetch_assoc($result)) {
            $pdf->displayLine('<b>'.Toolbox::decodeFromUtf8((empty($data['name'])?$data['ID']:$data['name']),"windows-1252").'</b>',
                              $data['device'],
                              $data['mountpoint'],
                              Html::clean(Dropdown::getDropdownName('glpi_filesystems',$data["filesystems_id"])),
                              Html::clean(Html::formatNumber($data['totalsize'], false, 0))." ".$LANG['common'][82],
                              Html::clean(Html::formatNumber($data['freesize'], false, 0))." ".$LANG['common'][82]);
         }
      } else {
         $pdf->displayTitle("<b>".$LANG['computers'][8] . " - " . $LANG['search'][15]."</b>");
      }
      $pdf->displaySpace();
   }
}