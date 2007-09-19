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

// Original Author of file: Balpe Dévi
// Purpose of file:
// ----------------------------------------------------------------------
if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '../../..'); 
}
include_once (GLPI_ROOT . "/inc/includes.php");

if(!isset($_SESSION["glpi_plugin_pdf_installed"]) || $_SESSION["glpi_plugin_pdf_installed"]!=1) {
	
	commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"config","plugins");
	
	echo "<div align='center'>";
	echo "<table class='tab_cadre' cellpadding='5'>";
	echo "<tr><th>".$LANGPDF["config"][1]."</th></tr>";
	echo "<tr class='tab_bg_1' align='center'><td>";
	echo "<a href='plugin_pdf.install.php'>".$LANGPDF["config"][2]."</a>";
	echo "</td></tr>";
	echo "</table></div>";
}
else
{
	commonHeader($LANGPDF["config"][1], $_SERVER["PHP_SELF"],"plugins","pdf");
	
	echo "<div align='center'>";
	echo "<table class='tab_cadre' cellpadding='5'>";
	echo "<tr><th>".$LANGPDF["config"][1]."</th></tr>";
	echo "<tr class='tab_bg_1' align='center'><td>";
	echo "<a href=\"plugin_pdf.preference.php\">".$LANGPDF["config"][3]."</a>";
	echo "</td/></tr>";
	if (haveRight("config","w")){
		echo "<tr class='tab_bg_1' align='center'><td>";
		echo "<a href=\"plugin_pdf.uninstall.php\">".$LANGPDF["config"][4]."</a>";
		echo "</td/></tr>";
	}
	echo "</table></div>";
}

commonFooter();
?>

