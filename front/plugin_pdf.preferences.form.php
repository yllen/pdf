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

// Original Author of file: Balpe Dévi / Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------
if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '../../..'); 
}
include_once (GLPI_ROOT . "/inc/includes.php");

//Save user preferences
if (isset ($_POST['plugin_pdf_user_preferences_save']) && isset($_POST["plugin_pdf_inventory_type"])) {
	$DB->query("DELETE from glpi_plugin_pdf_preference WHERE FK_users =" . $_SESSION["glpiID"] . " and device_type=" . $_POST["plugin_pdf_inventory_type"]);
	
	if (isset($_POST['item'])) {		
		foreach ($_POST['item'] as $key => $val) {
			$DB->query(
				"INSERT INTO `glpi_plugin_pdf_preference` (`id` ,`FK_users` ,`device_type` ,`tabref`)
				VALUES (NULL , '".$_SESSION["glpiID"]."', '".$_POST["plugin_pdf_inventory_type"]."', '" . $key . "');");		
		}
	}
	if (isset($_POST["page"]) && $_POST["page"]) {
			$DB->query(
				"INSERT INTO `glpi_plugin_pdf_preference` (`id` ,`FK_users` ,`device_type` ,`tabref`)
				VALUES (NULL , '".$_SESSION["glpiID"]."', '".$_POST["plugin_pdf_inventory_type"]."', 'landscape');");				
	}

	glpi_header($_SERVER['HTTP_REFERER']);
}

?>