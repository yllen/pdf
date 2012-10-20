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

// Original Author of file: BALPE Dévi
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");

Plugin::load('pdf', true);
include_once (GLPI_ROOT."/lib/ezpdf/class.ezpdf.php");

if (isset($_POST["plugin_pdf_inventory_type"])
    && ($item = getItemForItemtype($_POST["plugin_pdf_inventory_type"]))
    && isset($_POST["itemID"])) {

   $type = $_POST["plugin_pdf_inventory_type"];
   $item->check($_POST["itemID"], 'r');

   if (isset($_SESSION["plugin_pdf"][$type])) {
      unset($_SESSION["plugin_pdf"][$type]);
   }

   $tab = array();
   if (isset($_POST['item'])) {
      foreach ($_POST['item'] as $key => $val) {
         $tab[] = $_SESSION["plugin_pdf"][$type][] = $key;
      }
   }

   if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])
       && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {

      $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
      $itempdf->generatePDF(array($_POST["itemID"]), $tab, (isset($_POST["page"]) ? $_POST["page"] : 0));
   } else {
      die("Missing hook");
   }
} else {
   die("Missing context");
}

?>