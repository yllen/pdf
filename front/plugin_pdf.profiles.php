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

// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array("profile");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '../../..'); 
}
include_once (GLPI_ROOT . "/inc/includes.php");
checkRight("profile","r");

commonHeader($LANG['pdf']["config"][1], $_SERVER["PHP_SELF"],"plugins","reports");

$prof = new PluginPdfProfile();

if(!isset($_POST["ID"])) $ID=0;	
else $ID=$_POST["ID"];

if (isset($_POST["add"])){
	checkRight("profile","w");
	$prof->add($_POST);
	if ($_SESSION['glpiactiveprofile']['ID']==$ID)
		$_SESSION["glpi_plugin_pdf_profile"]=$prof->fields;	
}
else  if (isset($_POST["delete"])){
	checkRight("profile","w");

	$prof->delete($_POST);
	if ($_SESSION['glpiactiveprofile']['ID']==$ID)
		unset($_SESSION["glpi_plugin_pdf_profile"]);
}
else  if (isset($_POST["update"])){
	checkRight("profile","w");
	$prof->update($_POST);
	if ($_SESSION['glpiactiveprofile']['ID']==$ID)
		$_SESSION["glpi_plugin_pdf_profile"]=$prof->fields;
}

echo "<div align='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
echo $LANG['pdf']["config"][1]."<br />" . $LANG['pdf']["config"][6] . "</th></tr>\n";

echo "<tr class='tab_bg_1'><td>" . $LANG["profiles"][22] . "&nbsp;: ";
$query="SELECT ID, name FROM glpi_profiles ORDER BY name";
$result=$DB->query($query);

echo "<select name='ID'>";
while ($data=$DB->fetch_assoc($result)){
	echo "<option value='".$data["ID"]."' ".($ID==$data["ID"]?"selected":"").">".$data['name']."</option>";
}
echo "</select>";
echo "<td><input type='submit' value=\"".$LANG["buttons"][2]."\" class='submit' ></td></tr>";
echo "</table></form></div>";

if ($ID>0){	
	$prof->showForm($_SERVER['PHP_SELF'],$ID);
}

commonFooter();
?>

