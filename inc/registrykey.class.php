<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2012 by the pdf Development Team.

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

class PluginPdfRegistryKey extends PluginPdfCommon {

   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new RegistryKey());
   }

   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item) {
      global $DB,$LANG;

      $ID = $item->getField('id');

      $REGISTRY_HIVE = array("HKEY_CLASSES_ROOT",
                             "HKEY_CURRENT_USER",
                             "HKEY_LOCAL_MACHINE",
                             "HKEY_USERS",
                             "HKEY_CURRENT_CONFIG",
                             "HKEY_DYN_DATA");

      $query = "SELECT `id`
                FROM `glpi_registrykeys`
                WHERE `computers_id` = '$ID'";

      $pdf->setColumnsSize(100);
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $pdf->displayTitle('<b>'.$DB->numrows($result)." ".$LANG["registry"][4].'</b>');

            $pdf->setColumnsSize(25,25,25,25);
            $pdf->displayTitle('<b>'.$LANG["registry"][6].'</b>',
                               '<b>'.$LANG["registry"][1].'</b>',
                               '<b>'.$LANG["registry"][2].'</b>',
                               '<b>'.$LANG["registry"][3].'</b>');

            $reg = new RegistryKey;

            while ($regid = $DB->fetch_row($result)) {
               if ($reg->getfromDB(current($regid))) {
                  $pdf->displayLine($reg->fields['ocs_name'],
                                    $REGISTRY_HIVE[$reg->fields['hive']],
                                    $reg->fields['path'],
                                    $reg->fields['value']);
               }
            }

         } else {
            $pdf->displayTitle('<b>'.$LANG["registry"][5].'</b>');
         }
      }
      $pdf->displaySpace();
   }
}