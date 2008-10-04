<?php
/*
   ----------------------------------------------------------------------
   GLPI - Gestionnaire Libre de Parc Informatique
   Copyright (C) 2003-2006 by the INDEPNET Development Team.

   http://indepnet.net/   http://glpi-project.org
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

include_once ("plugin_pdf.includes.php");

function plugin_pdf_initSession()
{
	return true;
}
function plugin_pdf_changeprofile()
{
	$prof=new PluginPdfProfile();
	if($prof->getFromDB($_SESSION['glpiactiveprofile']['ID'])) {
		$_SESSION["glpi_plugin_pdf_profile"]=$prof->fields;
	} else {
		unset($_SESSION["glpi_plugin_pdf_profile"]);
	}
}

function plugin_get_headings_pdf($type,$withtemplate){	

	global $LANG;

	switch ($type){
		case COMPUTER_TYPE :
		case SOFTWARE_TYPE :
		case "prefs" :
			if ($withtemplate)
				return array();
			else 
				return array(1 => $LANG['plugin_pdf']["title"][1]);
		break;

	}
	return false;	
}
	 
function plugin_headings_actions_pdf($type){

	switch ($type){
		case COMPUTER_TYPE :
		case SOFTWARE_TYPE :
		case "prefs" :
			return array(
					1 => "plugin_headings_pdf",
				    );
			break;
	}
	return false;
}

// action heading
function plugin_headings_pdf($type,$ID,$withtemplate=0){
	global $CFG_GLPI;

		switch ($type){
			case COMPUTER_TYPE :
				plugin_pdf_menu_computer("../plugins/pdf/front/plugin_pdf.export.php",$ID);
			break;
			case SOFTWARE_TYPE :
				plugin_pdf_menu_software("../plugins/pdf/front/plugin_pdf.export.php",$ID);
			break;
			case "prefs":
				$pref = new PluginPdfPreferences;
				$pref->showForm($CFG_GLPI['root_doc']."/plugins/pdf/front/plugin_pdf.preferences.form.php");
			break;
			default :
			break;
		}
}

function plugin_pdf_MassiveActions($type){
	global $LANG;
	switch ($type){
		case COMPUTER_TYPE :
			return array(
				"plugin_pdf_DoIt"=>$LANG['plugin_pdf']["title"][1],
			);
			break;
		case SOFTWARE_TYPE:
			return array(
				"plugin_pdf_DoIt"=>$LANG['plugin_pdf']["title"][1],
				);
		break;
	}
	return array();
}

function plugin_pdf_MassiveActionsDisplay($type,$action){
	global $LANG;
	switch ($type){
		case COMPUTER_TYPE:
			switch ($action){
				case "plugin_pdf_DoIt":
					echo "<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$LANG["buttons"][2]."\" >";
				break;
			}
			break;
		case SOFTWARE_TYPE:
			switch ($action){
				case "plugin_pdf_DoIt":
					echo "<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$LANG["buttons"][2]."\" >";
				break;
			}
		break;
	}
	return "";
}

function plugin_pdf_MassiveActionsProcess($data){

	switch ($data["action"]){
		case "plugin_pdf_DoIt":
			foreach ($data['item'] as $key => $val)
				$tab_id[]=$key;
					
			$_SESSION["plugin_pdf"]["type"] = $data["device_type"];
			$_SESSION["plugin_pdf"]["tab_id"] = serialize($tab_id);
			
			echo "<script type='text/javascript'>location.href='../plugins/pdf/front/plugin_pdf.export.massive.php'</script>)";
		break;
		}
}

function plugin_pre_item_delete_pdf($input){
	if (isset($input["_item_type_"]))
		switch ($input["_item_type_"]){
			case PROFILE_TYPE :
				// Manipulate data if needed 
				$PluginPdfProfile=new PluginPdfProfile;
				$PluginPdfProfile->cleanProfiles($input["ID"]);
				break;
		}
	return $input;
}

?>
