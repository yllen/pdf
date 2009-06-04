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


$NEEDED_ITEMS=array("computer","device","networking","monitor","printer","tracking","software","peripheral","reservation","infocom","contract","document","user","link","phone","registry");
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");
include_once ("../plugin_pdf.includes.php");
include_once (GLPI_ROOT."/lib/ezpdf/class.ezpdf.php");

if (isset($_POST["plugin_pdf_inventory_type"]) && isset($_POST["itemID"])) {
		
	$type = $_POST["plugin_pdf_inventory_type"];
	
	if (isset($_SESSION["plugin_pdf"][$type])) {
		unset($_SESSION["plugin_pdf"][$type]);		
	}

	$tab=array();
	if (isset($_POST['item'])) {		
		foreach ($_POST['item'] as $key => $val) {
			$tab[] = $_SESSION["plugin_pdf"][$type][] = $key;
		}
	}
	
	$tab_id[0]=$_POST["itemID"];
	
	if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
		doOneHook($PLUGIN_HOOKS['plugin_pdf'][$type], "generatePDF",
			$type, $tab_id, $tab,
			(isset($_POST["page"]) ? $_POST["page"] : 0));
	} else {
		die("Missing hook");
	}	
} else {
	die("Missing context");
}
?>