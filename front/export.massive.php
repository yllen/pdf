<?php

/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
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

// Original Author of file: BALPE Dévi
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");

Plugin::load('pdf', true);
include_once (GLPI_ROOT."/lib/ezpdf/class.ezpdf.php");

$type = $_SESSION["plugin_pdf"]["type"];
unset($_SESSION["plugin_pdf"]["type"]);
$item = new $type();

$tab_id = unserialize($_SESSION["plugin_pdf"]["tab_id"]);
unset($_SESSION["plugin_pdf"]["tab_id"]);

$query = "SELECT `tabref`
          FROM `glpi_plugin_pdf_preferences`
          WHERE `users_ID` = '".$_SESSION['glpiID']."'
                AND `itemtype` = '$type'";
$result = $DB->query($query);

$tab = array();
$pag = 0;
while ($data = $DB->fetch_array($result)) {
   if ($data["tabref"] == 'landscape') {
      $pag = 1;
   } else {
      $tab[]=$data["tabref"];
   }
}

if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
   $options = array('item'   => $item,
                    'tab_id' => $tab_id,
                    'tab'    => $tab,
                    'page'   => $pag);

   Plugin::doOneHook($PLUGIN_HOOKS['plugin_pdf'][$type], "generatePDF", $options);
} else {
   die("Missing hook");
}

?>