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


$NEEDED_ITEMS=array("computer","device","networking","monitor","printer","tracking","software","peripheral","reservation","infocom","contract","document","user","link","phone","registry");
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");
include_once (GLPI_ROOT."/lib/ezpdf/class.ezpdf.php");

if($_POST["type"]==COMPUTER_TYPE && isset($_SESSION["plugin_pdf"][COMPUTER_TYPE]))
		unset($_SESSION["plugin_pdf"][COMPUTER_TYPE]);
			
else if($_POST["type"]==SOFTWARE_TYPE && isset($_SESSION["plugin_pdf"][SOFTWARE_TYPE]))
		unset($_SESSION["plugin_pdf"][SOFTWARE_TYPE]);
	
for($i=0,$j=1;$i<$_POST["indice"];$i++)
	if(isset($_POST["check".$i])){
		if($_POST["type"]==COMPUTER_TYPE)
			$_SESSION["plugin_pdf"][COMPUTER_TYPE][]=$i;
			
		else if($_POST["type"]==SOFTWARE_TYPE)
			$_SESSION["plugin_pdf"][SOFTWARE_TYPE][]=$i;
			
		$tab[$j] = $_POST["check".$i];
		$j++;
	}
	
$tab[0]=-1;
		
$tab_id[0]=$_POST["ID"];

plugin_pdf_general($_POST["type"],$tab_id,$tab);
	
?>