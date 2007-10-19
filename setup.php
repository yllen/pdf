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

// Original Author of file: BALPE Dévi
// Purpose of file:
// ----------------------------------------------------------------------

include_once ("plugin_pdf.includes.php");

function plugin_init_pdf() {
	global $PLUGIN_HOOKS;
	
	$PLUGIN_HOOKS['init_session']['pdf'] = 'plugin_pdf_initSession';
	$PLUGIN_HOOKS['change_profile']['pdf'] = 'plugin_pdf_changeprofile';
	
	if (isset($_SESSION["glpi_plugin_pdf_installed"]) && $_SESSION["glpi_plugin_pdf_installed"]==1)
	{
		if (isset($_SESSION["glpi_plugin_pdf_profile"]) && $_SESSION["glpi_plugin_pdf_profile"]["use"])
		{
			$PLUGIN_HOOKS['menu_entry']['pdf'] = true;
	
			$PLUGIN_HOOKS['use_massive_action']['pdf']=1;
			
			$PLUGIN_HOOKS['headings']['pdf'] = 'plugin_get_headings_pdf';
			$PLUGIN_HOOKS['headings_action']['pdf'] = 'plugin_headings_actions_pdf';
		}
		if (haveRight("config","w") || haveRight("profile","r")) {
			$PLUGIN_HOOKS['config_page']['pdf'] = 'front/plugin_pdf.config.form.php';
		}		
	}
	else if (haveRight("config","w")) {
		$PLUGIN_HOOKS['config_page']['pdf'] = 'front/plugin_pdf.config.form.php';
	}
	$PLUGIN_HOOKS['pre_item_delete']['pdf'] = 'plugin_pre_item_delete_pdf';
}

	
function plugin_version_pdf() {
	global $LANGPDF;

		return array ('name' => $LANGPDF["title"][1], 'version' => '0.4');
}

// Hook done on delete item case

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

function plugin_get_headings_pdf($type,$withtemplate){	

	global $LANGPDF;

	switch ($type){
		case COMPUTER_TYPE :
			if ($withtemplate)
				return array();
			else 
				return array(1 => $LANGPDF["title"][1]);
		break;

		case SOFTWARE_TYPE :
			if ($withtemplate)
				return array();
			else 
				return array(1 => $LANGPDF["title"][1]);
		break;
	}
	return false;	
}
	 
function plugin_headings_actions_pdf($type){

	switch ($type){
		case COMPUTER_TYPE :
			return array(
					1 => "plugin_headings_pdf_computer",
				    );
			break;

		case SOFTWARE_TYPE :
			return array(
					1 => "plugin_headings_pdf_software",
				    );
			break;
	}
	return false;
}

function plugin_headings_pdf_computer($type,$ID,$withtemplate=0){

	echo "<div align='center'>";
	echo plugin_pdf_menu_computer($type,$ID);
	echo "</div>";
}

function plugin_headings_pdf_software($type,$ID,$withtemplate=0){

	echo "<div align='center'>";
	echo plugin_pdf_menu_software($type,$ID);
	echo "</div>";
}

function plugin_pdf_MassiveActions($type){
	global $LANG;
	switch ($type){
		case COMPUTER_TYPE :
			return array(
				"plugin_pdf_DoIt"=>"Imprimer en pdf",
			);
			break;
		case SOFTWARE_TYPE:
			return array(
				"plugin_pdf_DoIt"=>"Imprimer en pdf",
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
	if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) 
		$_SESSION["MESSAGE_AFTER_REDIRECT"]="";

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

?>