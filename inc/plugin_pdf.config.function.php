<?php
/*
  ----------------------------------------------------------------------
  GLPI - Gestionnaire Libre de Parc Informatique
  Copyright (C) 2003-2005 by the INDEPNET Development Team.
  
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

// ----------------------------------------------------------------------
// Original Author of file: Balpe Dévi
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

include_once (GLPI_ROOT . "/inc/includes.php");
	
function plugin_pdf_Install() {
	$DB = new DB;
			
	$query= "CREATE TABLE `glpi_plugin_pdf_preference` (
  	`id` int(11) NOT NULL auto_increment,
  	`user_id` int(11) NOT NULL,
  	`cat` varchar(255) NOT NULL,
  	`table_num` int(11) NOT NULL,
  	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM;";
			
	$DB->query($query) or die($DB->error());
}

function plugin_pdf_uninstall() {
	$DB = new DB;
		
	$query = "DROP TABLE `glpi_plugin_pdf_preference`;";
	$DB->query($query) or die($DB->error());
}

function plugin_pdf_initSession()
{
	if (TableExists("glpi_plugin_pdf_preference"))
		$_SESSION["glpi_plugin_pdf_installed"]=1;	
}
?>
