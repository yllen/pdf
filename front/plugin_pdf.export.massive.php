<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org/
 ----------------------------------------------------------------------

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
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// Original Author of file: BALPE Dévi
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("computer","device","networking","monitor","printer","tracking","software",
	"cartridge","peripheral","reservation","infocom","contract","document","user","link","phone","registry");
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");
include_once ("../plugin_pdf.includes.php");
include_once (GLPI_ROOT."/lib/ezpdf/class.ezpdf.php");

global $DB;
	
$type = $_SESSION["plugin_pdf"]["type"];
unset($_SESSION["plugin_pdf"]["type"]);

$tab_id = unserialize($_SESSION["plugin_pdf"]["tab_id"]);
unset($_SESSION["plugin_pdf"]["tab_id"]);

$user_id = $_SESSION['glpiID'];
$query = "select table_num from glpi_plugin_pdf_preference WHERE user_id =".$user_id." and cat=".$type;
$result = $DB->query($query);

$tab = array();		
while($data = $DB->fetch_array($result)) {
	$tab[]=$data["table_num"];
}
	
if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
	doOneHook($PLUGIN_HOOKS['plugin_pdf'][$type], "generatePDF",
		$type, $tab_id, $tab);
} else {
	die("Missing hook");
}	
	
?>