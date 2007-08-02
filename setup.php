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

include_once ("inc/plugin_pdf.functions.php");

function plugin_init_pdf() {
	global $PLUGIN_HOOKS;
	
	$PLUGIN_HOOKS['menu_entry']['pdf'] = true;
		
	$PLUGIN_HOOKS['config_page']['pdf'] = 'config.php';
		
	$PLUGIN_HOOKS['headings']['pdf'] = 'plugin_get_headings_pdf';
	$PLUGIN_HOOKS['headings_action']['pdf'] = 'plugin_headings_actions_pdf';
}

	
function plugin_version_pdf() {
	global $LANGPDF;

		return array ('name' => $LANGPDF["title"][1], 'version' => '0.2');
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
?>