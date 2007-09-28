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

global $LANGPDF,$LANG,$DB;
	
if (!defined('GLPI_ROOT')) {
	define('GLPI_ROOT', '../../..');
}

include_once (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANGPDF["title"][1],$_SERVER['PHP_SELF'],"plugins","pdf");

echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>";
echo "<div align='center'>";
echo "<table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$LANGPDF["config"][4]."</th></tr>";
echo "<tr class='tab_bg_1' align='center'><td>";
echo $LANGPDF["config"][5]." <select name='inventaire'>";

if(isset($_POST['inventaire']))
	{
		if($_POST['inventaire']==COMPUTER_TYPE)
			{
			echo "<option value='".COMPUTER_TYPE."' selected='selected'>".$LANG["Menu"][0]."</option>";
			echo "<option value='".SOFTWARE_TYPE."'>".$LANG["Menu"][4]."</option>";
			}
		else
			{
			echo "<option value='".COMPUTER_TYPE."'>".$LANG["Menu"][0]."</option>";
			echo "<option value='".SOFTWARE_TYPE."' selected='selected'>".$LANG["Menu"][4]."</option>";	
			}
	}
else
	{
	echo "<option value='".COMPUTER_TYPE."'>".$LANG["Menu"][0]."</option>";
	echo "<option value='".SOFTWARE_TYPE."'>".$LANG["Menu"][4]."</option>";	
	}
echo "</select>";
echo "</td></tr>";
echo "<tr class='tab_bg_1' align='center'><td>";
echo "<input type='submit' name='valide' value='Valider' class='submit' />";
echo "</td></tr>";
echo "</table>";
echo "</div>";
echo "</form>";

if(isset($_POST['save']))
{
	$user_id = $_SESSION['glpiID'];
	$cat = $_POST['inventaire'];
	
	$query = "DELETE from glpi_plugin_pdf_preference WHERE user_id =".$user_id." and cat=".$cat;
	$DB->query($query) or die($DB->error());
	
	for($i=0;$i<$_POST['indice'];$i++)
	{
		if(isset($_POST["check".$i]))
			{
			$query = "INSERT INTO `glpi_plugin_pdf_preference` (`id` ,`user_id` ,`cat` ,`table_num`) VALUES (NULL , '$user_id', '$cat', '".$_POST["check".$i]."');";
			$DB->query($query) or die($DB->error());
			}
	}
}

if(isset($_POST['valide']) || isset($_POST['save']))
	{
	switch($_POST['inventaire'])
		{
		case COMPUTER_TYPE:
			echo "<form action='".$_SERVER['PHP_SELF']."' method='post' style='margin-top: 50px'>";
			echo "<div align=\"center\">";
			echo "<table class='tab_cadre'>";
			echo "<tr><th colspan='6'>".$LANGPDF["title"][2]."</th></tr>";
		
			echo "<tr class='tab_bg_1'>";
			echo "<td><input type='checkbox' name='check0' id='check0' value='0' /> ".$LANG["Menu"][26]."</td>";

			echo "<td><input type='checkbox' name='check2' id='check2' value='2' /> ".$LANG["devices"][10]."</td>";

			echo "<td><input type='checkbox' name='check4' id='check4' value='4' /> ".$LANG["title"][28]."</td>";

			echo "<td><input type='checkbox' name='check6' id='check6' value='6' /> ".$LANG["title"][43]."</td>";

			echo "<td><input type='checkbox' name='check8' id='check8' value='8' /> ".$LANG["title"][37]."</td>";

			echo "<td><input type='checkbox' name='check10' id='check10' value='10' /> ".$LANG["title"][38]."</td>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_1'>";
			echo "<td><input type='checkbox' name='check1' id='check1' value='1' /> ".$LANG["title"][27]."</td>";

			echo "<td><input type='checkbox' name='check3' id='check3' value='3' /> ".$LANG["title"][12]."</td>";

			echo "<td><input type='checkbox' name='check5' id='check5' value='5' /> ".$LANG["title"][25]."</td>";

			echo "<td><input type='checkbox' name='check7' id='check7' value='7' /> ".$LANG["title"][34]."</td>";

			echo "<td><input type='checkbox' name='check9' id='check9' value='9' /> ".$LANG["title"][35]."</td>";
			
			echo "<td></td>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td colspan='6' align='center'>";
			echo "<input type='hidden' name='inventaire' value='".$_POST['inventaire']."' />";
			echo "<input type='hidden' name='indice' value='11' />";
			echo "<input type='submit' value='".$LANGPDF["button"][2]."' name='save' class='submit' /></td></tr>";
			echo "</table></div></form>";
			
			$user_id = $_SESSION['glpiID'];
			$query = "select table_num from glpi_plugin_pdf_preference WHERE user_id =".$user_id." and cat=".COMPUTER_TYPE;
			$result = $DB->query($query);
			
			while  ($data = $DB->fetch_array($result))
				echo "<script type='text/javascript'>document.getElementById('check'+".$data['table_num'].").checked = true</script>";
			
		break;
		case SOFTWARE_TYPE:
			echo "<form action='".$_SERVER['PHP_SELF']."' method='post' style='margin-top: 50px'>";
			echo "<div align=\"center\">";
			echo "<table class='tab_cadre'>";
			echo "<tr><th colspan='6'>".$LANGPDF["title"][2]."</th></tr>";
			
			echo "<tr class='tab_bg_1'>";
			echo "<td><input type='checkbox' name='check0' id='check0' value='0' /> ".$LANG["title"][26]."</td>";

			echo "<td><input type='checkbox' name='check2' id='check2' value='2' /> ".$LANG["Menu"][26]."</td>";

			echo "<td><input type='checkbox' name='check4' id='check4' value='4' /> ".$LANG["title"][28]."</td>";

			echo "<td><input type='checkbox' name='check6' id='check6' value='6' /> ".$LANG["title"][37]."</td>";

			echo "<td><input type='checkbox' name='check8' id='check8' value='8' /> ".$LANG["title"][38]."</td>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_1'>";
			echo "<td><input type='checkbox' name='check1' id='check1' value='1' /> ".$LANG["software"][19]."</td>";
			
			echo "<td><input type='checkbox' name='check3' id='check3' value='3' /> ".$LANG["title"][25]."</td>";

			echo "<td><input type='checkbox' name='check5' id='check5' value='5' /> ".$LANG["title"][34]."</td>";

			echo "<td><input type='checkbox' name='check7' id='check7' value='7' /> ".$LANG["title"][35]."</td>";
			
			echo "<td></td>";
			echo "</tr>";
			
			echo "<tr class='tab_bg_2'><td colspan='6' align='center'>";
			echo "<input type='hidden' name='inventaire' value='".$_POST['inventaire']."' />";
			echo "<input type='hidden' name='indice' value='9' />";
			echo "<input type='submit' value='".$LANGPDF["button"][2]."' name='save' class='submit' /></td></tr>";
			echo "</table></div></form>";
			
			$user_id = $_SESSION['glpiID'];
			$query = "select table_num from glpi_plugin_pdf_preference WHERE user_id =".$user_id." and cat=".SOFTWARE_TYPE;
			$result = $DB->query($query);
			
			while  ($data = $DB->fetch_array($result))
				echo "<script type='text/javascript'>document.getElementById('check'+".$data['table_num'].").checked = true</script>";
			
		break;
		}
	}

commonFooter();
?>
