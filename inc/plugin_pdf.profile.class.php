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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

class PluginPdfProfile extends CommonDBTM {
	
	function PluginPdfProfile()
	{
		$this->table="glpi_plugin_pdf_profiles";
    	$this->type=-1;
	}
	
	//if profile deleted
	function cleanProfiles($ID) {
	
		global $DB;
		$query = "DELETE FROM glpi_plugin_pdf_profiles WHERE ID='$ID' ";
		$DB->query($query);
	}
	
	function showForm($target,$ID){
		global $LANG,$DB;

		if (!haveRight("profile","r")) return false;
		$canedit=haveRight("profile","w");

		if (!($ID && $this->getFromDB($ID))) {
			$this->getEmpty();
		}
		
		echo "<div align='center'>";
		echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>";
		echo "<table class='tab_cadre' cellpadding='5'>"; 
		echo "<tr><th colspan='2'>".$LANG['pdf']["config"][7]. "</th></tr>\n";
			

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG['pdf']["title"][1].":</td><td>";
		dropdownYesNo("use",(isset($this->fields["use"])?$this->fields["use"]:''));
		echo "</td></tr>\n";	

		if ($canedit){
			echo "<tr class='tab_bg_1'>";
			echo "<td colspan='2' align='center'>";
			echo "<input type='hidden' name='ID' value=$ID>";

			if ($this->fields["ID"]){
				echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>&nbsp;";
				echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			} else {
				echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			}
			echo "</td></tr>\n";
		}

		echo "</table>";
		echo "</form>";
		echo "</div>";	
	}	
}
	
?>