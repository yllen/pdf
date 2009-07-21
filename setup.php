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


function plugin_init_pdf() {
	global $PLUGIN_HOOKS;
	
	$PLUGIN_HOOKS['change_profile']['pdf'] = 'plugin_pdf_changeprofile';
	$PLUGIN_HOOKS['plugin_types'][PROFILE_TYPE]='pdf';
	
	if (isset($_SESSION["glpi_plugin_pdf_profile"]) && $_SESSION["glpi_plugin_pdf_profile"]["use"])
	{
		$PLUGIN_HOOKS['use_massive_action']['pdf']=1;
		$PLUGIN_HOOKS['headings']['pdf'] = 'plugin_get_headings_pdf';
		$PLUGIN_HOOKS['headings_action']['pdf'] = 'plugin_headings_actions_pdf';
		$PLUGIN_HOOKS['pre_item_delete']['pdf'] = 'plugin_pre_item_delete_pdf';
	}
	
	// Define the type for which we know how to generate PDF, need :
	// - plugin_pdf_prefPDF($type)
	// - plugin_pdf_generatePDF($type, $tab_id, $tab, $page=0)
	$PLUGIN_HOOKS['plugin_pdf'][COMPUTER_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][SOFTWARE_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][SOFTWARELICENSE_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][SOFTWAREVERSION_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][PRINTER_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][MONITOR_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][PHONE_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][PERIPHERAL_TYPE]='pdf';
	$PLUGIN_HOOKS['plugin_pdf'][TRACKING_TYPE]='pdf';
}

	
function plugin_version_pdf() {
	global $LANG;

	return array( 
		'name'    => $LANG['plugin_pdf']["title"][1],
		'version' => '0.6.1',
		'author' => 'Dévi Balpe, Remi Collet, Walid Nouh',
		'homepage'=> $LANG['plugin_pdf']["config"][8],
		'minGlpiVersion' => '0.72', // Not needed in 0.72, only to avoid installation on 0.71 
		);
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_pdf_check_prerequisites(){
	if (GLPI_VERSION >= 0.72){
		return true;
	} else {
		echo "GLPI version not compatible need 0.72";
	}
}

// Config process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_pdf_check_config(){
	return TableExists("glpi_plugin_pdf_profiles");
}

?>