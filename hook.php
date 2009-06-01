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

function plugin_pdf_getSearchOption(){
	global $LANG;
	$sopt=array();
	
	$sopt[PROFILE_TYPE][1000]['table']='glpi_plugin_pdf_profiles';
	$sopt[PROFILE_TYPE][1000]['field']='use';
	$sopt[PROFILE_TYPE][1000]['linkfield']='';
	$sopt[PROFILE_TYPE][1000]['name']=$LANG['plugin_pdf']["title"][1];
	$sopt[PROFILE_TYPE][1000]['datatype']='bool';

	return $sopt;
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

function plugin_get_headings_pdf($type,$ID,$withtemplate){	

	global $LANG;

	if ($type=="prefs") {
		return array(
			1 => $LANG['plugin_pdf']["title"][1],
			);
	}
	
	elseif ($type==COMPUTER_TYPE || $type==SOFTWARE_TYPE) {
		// template case
		if (!$withtemplate) {
			return array(
				1 => $LANG['plugin_pdf']["title"][1],
				);
		}
	}
	elseif ($type==PROFILE_TYPE) {
		if ($ID) {
			return array(
				1 => $LANG['plugin_pdf']["title"][1],
				);
		}
	}
	return false;
	
}
	 
function plugin_headings_actions_pdf($type){

	switch ($type){
		case COMPUTER_TYPE :
		case SOFTWARE_TYPE :
		case PROFILE_TYPE :
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
			case PROFILE_TYPE :
				$prof =  new PluginPdfProfile();
				if (!$prof->GetfromDB($ID)) {
					$prof->add(array(
						'ID'	=> $ID
						));
				}
				$prof->showForm($CFG_GLPI["root_doc"]."/plugins/pdf/front/plugin_pdf.profiles.php",$ID);
				break;
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
		case PROFILE_TYPE:
			return array(
				"plugin_pdf_allow"=>$LANG['plugin_pdf']["title"][1]
				);
			break;
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
		case PROFILE_TYPE:
			switch ($action){
				case "plugin_pdf_allow":
					dropdownYesNo('use');
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
		case "plugin_pdf_allow":
			$prof =  new PluginPdfProfile();
			foreach ($data['item'] as $key => $val) {
				if ($prof->getFromDB($key)) {
					$prof->update(array(
						'ID' => $key,
						'use' => $data['use']
					));
				} else if ($data['use']) {
						$prof->add(array(
							'ID' => $key,
							'use' => $data['use']
						));
				}
			}
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

function plugin_pdf_install() {
	$DB = new DB;
			
	$query= "CREATE TABLE IF NOT EXISTS `glpi_plugin_pdf_profiles` (
  	`ID` int(11),
  	`profile` varchar(255) default NULL,
  	`use` tinyint(1) default 0,
  	PRIMARY KEY  (`ID`)
	) ENGINE=MyISAM;";
			
	$DB->query($query) or die($DB->error());	

	$query= "CREATE TABLE IF NOT EXISTS `glpi_plugin_pdf_preference` (
  	`id` int(11) NOT NULL auto_increment,
  	`user_id` int(11) NOT NULL,
  	`cat` varchar(255) NOT NULL,
  	`table_num` int(11) NOT NULL default -1,
  	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM;";
			
	$DB->query($query) or die($DB->error());
	
	// Give right to current Profile
	$prof =  new PluginPdfProfile();
	$prof->add(array(
		'ID'	=> $_SESSION['glpiactiveprofile']['ID'],
		'use'	=> 1
		));
	return true;
}

function plugin_pdf_uninstall() {
	$DB = new DB;
		
	$query = "DROP TABLE IF EXISTS `glpi_plugin_pdf_preference`;";
	$DB->query($query) or die($DB->error());

	$query = "DROP TABLE IF EXISTS `glpi_plugin_pdf_profiles`;";
	$DB->query($query) or die($DB->error());

	return true;
}

?>
