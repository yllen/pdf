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

class PluginPdfPreferences extends CommonDBTM {

	function PluginPdfPreferences() {
		$this->table = "glpi_plugin_pdf_preference";
	}

	function showForm($target,$post) {
		global $LANGPDF, $LANG, $DB, $CFG_GLPI;

		//Save user preferences
		if (isset ($post['plugin_pdf_user_preferences_save'])) {
			$DB->query("DELETE from glpi_plugin_pdf_preference WHERE user_id =" . $_SESSION["glpiID"] . " and cat=" . $post["plugin_pdf_inventory_type"]);
			
			for ($i = 0; $i < $post['indice']; $i++)
				if (isset ($post["check" . $i]))
					$DB->query("INSERT INTO `glpi_plugin_pdf_preference` (`id` ,`user_id` ,`cat` ,`table_num`) VALUES (NULL , '".$_SESSION["glpiID"]."', '".$post["plugin_pdf_inventory_type"]."', '" . $i . "');");
		
		}

		echo "<div align='center' id='pdf_type'>";
		//echo "<table class='tab_cadre_fixe'>";
		//echo "<tr class='tab_bg_1' align='center'><th colspan='6'>".$LANGPDF["title"][1]."</th></tr>";		
		//echo "</td></tr>";
		//echo "<tr class='tab_bg_1' align='center'><td>"; 
		plugin_pdf_menu_computer($CFG_GLPI['root_doc']."/front/user.form.my.php",-1,false);
		//echo "</td></tr>";		
		//echo "<tr class='tab_bg_1' align='center'><td>"; 
		plugin_pdf_menu_software($CFG_GLPI['root_doc']."/front/user.form.my.php",-1,false);
		//echo "</td></tr>";		
		//echo "</table>";
		echo "</div>";
	}
}
?>
