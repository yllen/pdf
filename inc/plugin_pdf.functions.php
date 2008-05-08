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

function plugin_pdf_menu_computer($action,$compID,$export=true){
	global $LANGPDF,$LANG,$DB;
	
	echo "<form name='computer' action='$action' target='blank' method='post'><table class='tab_cadre_fixe'>";
	$values = array();
	$result = $DB->query("select table_num from glpi_plugin_pdf_preference WHERE user_id =" . $_SESSION['glpiID'] . " and cat=" . COMPUTER_TYPE);
					
	while ($data = $DB->fetch_array($result))
		$values["check".$data["table_num"]] = $data["table_num"]; 

		echo "<tr><th colspan='6'>" . $LANGPDF["title"][2]. " : ".$LANG["Menu"][0] . "</th></tr>";
		echo "<tr class='tab_bg_1'>";
		checkbox("check0",$LANG["Menu"][26],0,(isset($values["check0"])?true:false));
		checkbox("check2",$LANG["title"][30],2,(isset($values["check2"])?true:false));
		checkbox("check4",$LANG["title"][28],4,(isset($values["check4"])?true:false));
		checkbox("check6",$LANG["title"][43],6,(isset($values["check6"])?true:false));
		checkbox("check8",$LANG["title"][37],8,(isset($values["check8"])?true:false));
		checkbox("check10",$LANG["title"][38],10,(isset($values["check10"])?true:false));
		echo "</tr>";
	
		echo "<tr class='tab_bg_1'>";
		checkbox("check1",$LANG["title"][27],1,(isset($values["check1"])?true:false));
		checkbox("check3",$LANG["Menu"][4],3,(isset($values["check3"])?true:false));
		checkbox("check5",$LANG["Menu"][27],5,(isset($values["check5"])?true:false));
		checkbox("check7",$LANG["title"][34],7,(isset($values["check7"])?true:false));
		checkbox("check9",$LANG["Menu"][17],9,(isset($values["check9"])?true:false));
		echo "<td></td>";
		echo "</tr>";
	
		echo "<tr class='tab_bg_2'><td colspan='6' align='center'>";
		echo "<input type='hidden' name='plugin_pdf_inventory_type' value='" . COMPUTER_TYPE . "'>";
		echo "<input type='hidden' name='indice' value='11'>";
		echo "<input type='hidden' name='itemID' value='$compID'>";

		echo "<input type='submit' value='" . (!$export?$LANGPDF["button"][2]:$LANGPDF["button"][1]) . "' name='plugin_pdf_user_preferences_save' class='submit'></td></tr>";
		echo "</table></form>";
}

function plugin_pdf_menu_software($action,$softID){
	global $LANGPDF,$LANG,$DB;
	
	echo "<form name='software' action='$action' target='blank' method='post'><table class='tab_cadre_fixe'>";
	$values = array();
	$result = $DB->query("select table_num from glpi_plugin_pdf_preference WHERE user_id =" . $_SESSION['glpiID'] . " and cat=" . SOFTWARE_TYPE);
						
	while ($data = $DB->fetch_array($result))
		$values["check".$data["table_num"]] = $data["table_num"]; 
		
	echo "<tr><th colspan='6'>" . $LANGPDF["title"][2]." : ".$LANG["Menu"][4] . "</th></tr>";
	
	echo "<tr class='tab_bg_1'>";
	checkbox("check0",$LANG["title"][26],0,(isset($values["check0"])?true:false));
	checkbox("check2",$LANG["Menu"][26],2,(isset($values["check2"])?true:false));
	checkbox("check4",$LANG["title"][28],4,(isset($values["check4"])?true:false));
	checkbox("check6",$LANG["title"][37],6,(isset($values["check6"])?true:false));
	checkbox("check8",$LANG["title"][38],8,(isset($values["check10"])?true:false));
	echo "<td></td>";
	echo "</tr>";
	
	echo "<tr class='tab_bg_1'>";
	
	checkbox("check1",$LANG["software"][19],1,(isset($values["check1"])?true:false));
	checkbox("check3",$LANG["Menu"][27],3,(isset($values["check3"])?true:false));
	checkbox("check5",$LANG["title"][34],5,(isset($values["check5"])?true:false));
	checkbox("check7",$LANG["Menu"][17],7,(isset($values["check7"])?true:false));
	echo "<td></td>";
	echo "<td></td>";
	echo "</tr>";
						
	echo "<tr class='tab_bg_2'><td colspan='6' align='center'>";
	echo "<input type='hidden' name='plugin_pdf_inventory_type' value='" . SOFTWARE_TYPE . "'>";
	echo "<input type='hidden' name='indice' value='7'>";
	echo "<input type='hidden' name='itemID' value='$softID'>";
	
	echo "<input type='submit' value='" . $LANGPDF["button"][2] . "' name='plugin_pdf_user_preferences_save' class='submit'></td></tr>";
	echo "</table></form>";
}

function plugin_pdf_getDropdownName($table,$id){
	
	$name = getDropdownName($table,$id);
	
	if($name=="&nbsp;")
		$name="";
	
	return $name; 
}


function plugin_pdf_background($tab,$width){
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$height = $pdf->ez['pageHeight'];
	$id_pdf=$pdf->openObject();
	$pdf->saveState();
	$pdf->ezStartPageNumbers(575,10,10,'left',convDate(date("Y-m-d"))." - {PAGENUM}/{TOTALPAGENUM}");
	$pdf->setStrokeColor(0,0,0);
	$pdf->setLineStyle(1,'round','round');
	$pdf->rectangle(20,20,$width-40,$height-40);
	$pdf->addJpegFromFile("../pics/fd_logo.jpg",25,$height-50);
	$pdf->selectFont("../fonts/Times-Roman.afm");
	$pdf->setFontFamily('Times-Roman.afm',array('b'=>'Times-Bold.afm','i'=>'Times-Italic.afm','bi'=>'Times-BoldItalic.afm'));
	$pdf->restoreState();
	$pdf->closeObject();
	$pdf->addObject($id_pdf,'all');
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_add_header($pdf,$ID,$type){
	
	global $LANG;
	
	$height = $pdf->ez['pageHeight'];
	
	switch($type){
		case COMPUTER_TYPE:
			$computer = new Computer();
			$computer->getFromDB($ID);
			if($computer->fields['name'])
				$pdf->addText(220,$height-45,14,utf8_decode('<b>'.$computer->fields['name'].' ('.plugin_pdf_getDropdownName('glpi_entities',$computer->fields['FK_entities']).')</b>'));
			else
				$pdf->addText(220,$height-45,14,utf8_decode('<b>'.$LANG["common"][2].' '.$computer->fields['ID'].' ('.plugin_pdf_getDropdownName('glpi_entities',$computer->fields['FK_entities']).')</b>'));
		break;
		case SOFTWARE_TYPE:
			$software = new Software();
			$software->getFromDB($ID);
			if($software->fields['name'])
				$pdf->addText(200,$height-45,14,utf8_decode('<b>'.$software->fields['name'].' ('.plugin_pdf_getDropdownName('glpi_entities',$software->fields['FK_entities']).')</b>'));
			else	
				$pdf->addText(200,$height-45,14,utf8_decode('<b>'.$LANG["common"][2].' '.$software->fields['ID'].' ('.plugin_pdf_getDropdownName('glpi_entities',$software->fields['FK_entities']).')</b>'));
		break;
	}
	
	return $pdf;
}

function plugin_pdf_config_computer($tab,$width,$ID){
	
	global $LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$computer=new Computer();
	$computer->getFromDB($ID);
	
	$length_tab = (($width-50)/2)-2.5;
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$length_tab,15);
	$pdf->filledRectangle(25+$length_tab+5,$start_tab-5,$length_tab,15);
	$pdf->setColor(0.95,0.95,0.95);
	
	for($i=0;$i<13;$i++)
		{
		if($i<11)
			{
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$length_tab,15);
			$pdf->filledRectangle(25+$length_tab+5,($start_tab-25)-(20*$i),$length_tab,15);
			}
		else
			if($i==11)
			{
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),2*$length_tab+5,15);	
			}
			else
			{
			$i+=2;
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),2*$length_tab+5,55);
			}
		}
	$pdf->restoreState();
			
	$pdf->addText(100,$start_tab,9,utf8_decode('<b>'.$LANG["common"][2].' '.$computer->fields['ID'].' ('.plugin_pdf_getDropdownName('glpi_entities',$computer->fields['FK_entities']).')</b>'));
	$pdf->addText(30,$start_tab-20,9,utf8_decode('<b><i>'.$LANG["common"][16].' :</i></b> '.$computer->fields['name']));
	$pdf->addText(30,$start_tab-40,9,utf8_decode('<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName('glpi_type_computers',$computer->fields['type'])));
	$pdf->addText(30,$start_tab-60,9,utf8_decode('<b><i>'.$LANG["common"][22].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_model',$computer->fields['model'])));
	$pdf->addText(30,$start_tab-80,9,utf8_decode('<b><i>'.$LANG["common"][5].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_manufacturer',$computer->fields['FK_glpi_enterprise'])));
	$pdf->addText(30,$start_tab-100,9,utf8_decode('<b><i>'.$LANG["computers"][9].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os',$computer->fields['os'])));
	$pdf->addText(30,$start_tab-120,9,utf8_decode('<b><i>'.$LANG["computers"][52].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os_version',$computer->fields['os_version'])));
	$pdf->addText(30,$start_tab-140,9,utf8_decode('<b><i>'.$LANG["computers"][53].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os_sp',$computer->fields['os_sp'])));
	$pdf->addText(30,$start_tab-160,9,utf8_decode('<b><i>'.$LANG["computers"][10].' :</i></b> '.$computer->fields['os_license_number']));
	$pdf->addText(30,$start_tab-180,9,utf8_decode('<b><i>'.$LANG["computers"][11].' :</i></b> '.$computer->fields['os_license_id']));
			
	if($computer->fields['ocs_import'])
		$pdf->addText(30,$start_tab-200,9,utf8_decode('<b><i>'.$LANG["ocsng"][6].' '.$LANG["Menu"][33].' :</i></b> '.$LANG["choice"][1]));
	else
		$pdf->addText(30,$start_tab-200,9,utf8_decode('<b><i>'.$LANG["ocsng"][6].' '.$LANG["Menu"][33].' :</i></b> '.$LANG["choice"][0]));
		
	$pdf->addText(30,$start_tab-240,9,utf8_decode('<b><i>'.$LANG["common"][15].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_locations',$computer->fields['location'])));
	$pdf->addText(30,$start_tab-260,9,utf8_decode('<b><i>'.$LANG["common"][25].' :</i></b> '));
			
	$y=$start_tab-260;
	$temp=utf8_decode($computer->fields['comments']);
	while($temp = $pdf->addTextWrap(105,$y,2*$length_tab-80,9,$temp))
		$y-=9;
			
	if(!empty($computer->fields['tplname']))
		$pdf->addText($length_tab+35,$start_tab,9,utf8_decode('<b>'.$LANG["common"][26].' : '.convDateTime($computer->fields["date_mod"]).' ('.$LANG["common"][13].' : '.$computer->fields['tplname'].')</b>'));
	elseif($computer->fields['ocs_import'])
		$pdf->addText($length_tab+35,$start_tab,9,utf8_decode('<b>'.$LANG["common"][26].' : '.convDateTime($computer->fields["date_mod"]).' ('.$LANG["ocsng"][7].')</b>'));
	else
		$pdf->addText($length_tab+80,$start_tab,9,utf8_decode('<b>'.$LANG["common"][26].' : '.convDateTime($computer->fields["date_mod"]).'</b>'));
			
	$pdf->addText($length_tab+35,$start_tab-20,9,utf8_decode('<b><i>'.$LANG["common"][18].' :</i></b> '.$computer->fields['contact']));
	$pdf->addText($length_tab+35,$start_tab-40,9,utf8_decode('<b><i>'.$LANG["common"][21].' :</i></b> '.$computer->fields['contact_num']));
	$pdf->addText($length_tab+35,$start_tab-60,9,utf8_decode('<b><i>'.$LANG["common"][34].' :</i></b> '.plugin_pdf_getDropdownName('glpi_users',$computer->fields['FK_users'])));
	$pdf->addText($length_tab+35,$start_tab-80,9,utf8_decode('<b><i>'.$LANG["common"][35].' :</i></b> '.plugin_pdf_getDropdownName('glpi_groups',$computer->fields['FK_groups'])));
	$pdf->addText($length_tab+35,$start_tab-100,9,utf8_decode('<b><i>'.$LANG["common"][10].' :</i></b> '.plugin_pdf_getDropdownName('glpi_users',$computer->fields['tech_num'])));
	$pdf->addText($length_tab+35,$start_tab-120,9,utf8_decode('<b><i>'.$LANG["setup"][88].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_network',$computer->fields['network'])));
	$pdf->addText($length_tab+35,$start_tab-140,9,utf8_decode('<b><i>'.$LANG["setup"][89].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_domain',$computer->fields['domain'])));
	$pdf->addText($length_tab+35,$start_tab-160,9,utf8_decode('<b><i>'.$LANG["common"][19].' :</i></b> '.$computer->fields['serial']));
	$pdf->addText($length_tab+35,$start_tab-180,9,utf8_decode('<b><i>'.$LANG["common"][20].' :</i></b> '.$computer->fields['otherserial']));
	$pdf->addText($length_tab+35,$start_tab-200,9,utf8_decode('<b><i>'.$LANG["state"][0].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_state',$computer->fields['state'])));
	$pdf->addText($length_tab+35,$start_tab-220,9,utf8_decode('<b><i>'.$LANG["computers"][51].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_auto_update',$computer->fields['auto_update'])));
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_config_software($tab,$width,$ID){
	
	global $LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$software=new Software();
	$software->getFromDB($ID);
	
	$length_tab = (($width-50)/2)-2.5;
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$length_tab,15);
	$pdf->filledRectangle(25+$length_tab+5,$start_tab-5,$length_tab,15);
	$pdf->setColor(0.95,0.95,0.95);
	
	for($i=0;$i<7;$i++)
		{
		if($i<5)
			{
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$length_tab,15);
			$pdf->filledRectangle(25+$length_tab+5,($start_tab-25)-(20*$i),$length_tab,15);
			}
		else
			if($i==5)
			{
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),2*$length_tab+5,15);	
			}
			else
			{
			$i+=2;
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),2*$length_tab+5,55);
			}
		}
	$pdf->restoreState();
	
	$pdf->addText(100,$start_tab,9,utf8_decode('<b>'.$LANG["common"][2].' '.$software->fields['ID'].' ('.plugin_pdf_getDropdownName('glpi_entities',$software->fields['FK_entities']).')</b>'));
	$pdf->addText(30,$start_tab-20,9,utf8_decode('<b><i>'.$LANG["common"][16].' :</i></b> '.$software->fields['name']));
	$pdf->addText(30,$start_tab-40,9,utf8_decode('<b><i>'.$LANG["software"][3].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os',$software->fields['platform'])));
	$pdf->addText(30,$start_tab-60,9,utf8_decode('<b><i>'.$LANG["common"][34].' :</i></b> '.plugin_pdf_getDropdownName('glpi_users',$software->fields["FK_users"])));
	$pdf->addText(30,$start_tab-80,9,utf8_decode('<b><i>'.$LANG["common"][10].' :</i></b> '.plugin_pdf_getDropdownName('glpi_users',$software->fields["tech_num"])));
	$pdf->addText(30,$start_tab-100,9,utf8_decode('<b><i>'.$LANG["common"][15].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_locations',$software->fields['location'])));
	
	if($software->fields['is_update'])
		$pdf->addText(30,$start_tab-120,9,utf8_decode('<b><i>'.$LANG["software"][29].' :</i></b> '.$LANG["choice"][1]));
	else
		$pdf->addText(30,$start_tab-120,9,utf8_decode('<b><i>'.$LANG["software"][29].' :</i></b> '.$LANG["choice"][0]));
	
	if($software->fields["update_software"]!=null)
		$pdf->addText(100,$start_tab-120,9,utf8_decode('<b><i> '.$LANG["pager"][2].' </i></b> '.plugin_pdf_getDropdownName('glpi_software',$software->fields["update_software"])));
	
	$pdf->addText(30,$start_tab-140,9,utf8_decode('<b><i>'.$LANG["common"][25].' :</i></b> '));
	
	$y=$start_tab-140;
	$temp=utf8_decode($software->fields['comments']);
	while($temp = $pdf->addTextWrap(105,$y,2*$length_tab-80,9,$temp))
		$y-=9;
	
	if(!empty($software->fields['tplname']))
		$pdf->addText($length_tab+35,$start_tab,9,utf8_decode('<b>'.$LANG["common"][26].' : '.convDateTime($software->fields["date_mod"]).' ('.$LANG["common"][13].' : '.$software->fields['tplname'].')</b>'));
	else
		$pdf->addText($length_tab+80,$start_tab,9,utf8_decode('<b>'.$LANG["common"][26].' : '.convDateTime($software->fields["date_mod"]).'</b>'));
	
	$pdf->addText($length_tab+35,$start_tab-20,9,utf8_decode('<b><i>'.$LANG["common"][36].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_software_category',$software->fields["category"])));
	$pdf->addText($length_tab+35,$start_tab-40,9,utf8_decode('<b><i>'.$LANG["common"][5].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_manufacturer',$software->fields['FK_glpi_enterprise'])));
	$pdf->addText($length_tab+35,$start_tab-60,9,utf8_decode('<b><i>'.$LANG["common"][35].' :</i></b> '.plugin_pdf_getDropdownName('glpi_groups',$software->fields['FK_groups'])));
	$pdf->addText($length_tab+35,$start_tab-80,9,utf8_decode('<b><i>'.$LANG["state"][0].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_state',$software->fields["state"])));
	
	if($software->fields['helpdesk_visible'])
		$pdf->addText($length_tab+35,$start_tab-100,9,utf8_decode('<b><i>'.$LANG["software"][46].' :</i></b> '.$LANG["choice"][1]));
	else
		$pdf->addText($length_tab+35,$start_tab-100,9,utf8_decode('<b><i>'.$LANG["software"][46].' :</i></b> '.$LANG["choice"][0]));
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_device($tab,$width,$ID,$type){
	
	global $LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$computer=new Computer();
	$computer->getFromDBwithDevices($ID);
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
	$pdf->restoreState();
	$pdf->addText(275,$start_tab,9,'<b>'.utf8_decode($LANG["title"][30].'</b>'));
	
	$i=0;
	
	foreach($computer->devices as $key => $val) {
		$device = new Device($val["devType"]);
		$device->getFromDB($val["devID"]);
		
		$nb_x = 27	;
		$device_x = 47;
		$design_x = 127;
		$other_x = 362;
		$spec_x = 468;
		
		$pdf->saveState();
		$pdf->setColor(0.95,0.95,0.95);
		$pdf->filledRectangle(25,($start_tab-25)-(20*$i),15,15);
		$pdf->filledRectangle(45,($start_tab-25)-(20*$i),75,15);
		$pdf->filledRectangle(125,($start_tab-25)-(20*$i),230,15);
		$pdf->filledRectangle(360,($start_tab-25)-(20*$i),101,15);
		$pdf->filledRectangle(466,($start_tab-25)-(20*$i),105,15);
		$pdf->restoreState();
		
		switch($device->devtype) {
		case HDD_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][1]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			$pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["device_hdd"][4].' :</i></b> '.$val["specificity"]));
			if (!empty($device->fields["rpm"]))	$pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["device_hdd"][0].' :</i></b> '.$device->fields["rpm"]));
			else if (!empty($device->fields["interface"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["common"][65].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_interface",$device->fields["interface"])));
			else if (!empty($device->fields["cache"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["device_hdd"][1].' :</i></b> '.$device->fields["cache"]));
			break;
		case GFX_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][2]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["ram"])) $pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["device_gfxcard"][0].' :</i></b> '.$device->fields["ram"]));
			if (!empty($device->fields["interface"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["common"][65].' :</i></b> '.$device->fields["interface"]));
			break;
		case NETWORK_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][3]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			$pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["networking"][15].' :</i></b> '.$val["specificity"]));
			if (!empty($device->fields["bandwidth"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["device_iface"][0].' :</i></b> '.$device->fields["bandwidth"]));
			break;
		case MOBOARD_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][5]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["chipset"])) $pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["device_moboard"][0].' :</i></b> '.$device->fields["chipset"]));
			break;
		case PROCESSOR_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][4]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			$pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["device_ram"][1].' :</i></b> '.$val["specificity"]));
			break;
		case RAM_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][6]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			$pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["monitors"][21].' :</i></b> '.$val["specificity"]));
			if (empty($device->fields["frequence"])) {
				if (!empty($device->fields["type"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_ram_type",$device->fields["type"])));
			} else {
				if (!empty($device->fields["type"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),73,9,utf8_decode('<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_ram_type",$device->fields["type"])));
				$pdf->addTextWrap($other_x+75,($start_tab-20)-(20*$i),28,9,utf8_decode($device->fields["frequence"]));
			}	
			break;
		case SND_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][7]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["type"])) $pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["common"][17].' :</i></b> '.$device->fields["type"]));
			break;
		case DRIVE_DEVICE : 
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][19]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["is_writer"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["profiles"][11].' :</i></b> '.getYesNo($device->fields["is_writer"])));
			else if (!empty($device->fields["speed"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["device_drive"][1].' :</i></b> '.$device->fields["speed"]));
			else if (!empty($device->fields["frequence"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["device_ram"][1].' :</i></b> '.$device->fields["frequence"]));
			break;
		case CONTROL_DEVICE :;
			$pdf->addTextWrap($nb_x,($start_tab-25)-(25*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-25)-(25*$i),73,9,utf8_decode($LANG["devices"][20]));
			$pdf->addTextWrap($design_x,($start_tab-25)-(25*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["interface"])) $pdf->addTextWrap($spec_x,($start_tab-20)-(20*$i),99,9,utf8_decode('<b><i>'.$LANG["common"][65].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_interface",$device->fields["interface"])));
			if (!empty($device->fields["raid"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["device_control"][0].' :</i></b> '.getYesNo($device->fields["raid"])));
			break;
		case PCI_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][21]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			break;
		case POWER_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][23]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["power"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["device_power"][0].' :</i></b> '.$device->fields["power"]));
			else if (!empty($device->fields["atx"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["device_power"][1].' :</i></b> '.getYesNo($device->fields["atx"])));
			break;
		case CASE_DEVICE :
			$pdf->addTextWrap($nb_x,($start_tab-20)-(20*$i),13,9,utf8_decode($val["quantity"].'x'));
			$pdf->addTextWrap($device_x,($start_tab-20)-(20*$i),73,9,utf8_decode($LANG["devices"][22]));
			$pdf->addTextWrap($design_x,($start_tab-20)-(20*$i),228,9,utf8_decode($device->fields["designation"]));
			if (!empty($device->fields["type"])) $pdf->addTextWrap($other_x,($start_tab-20)-(20*$i),103,9,utf8_decode('<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_case_type",$device->fields["type"])));
			break;
		}
	$i++;
	
	if(($start_tab-20)-(20*$i)<50){
		$pdf = plugin_pdf_newPage($pdf,$ID,$type);
		$i=0;
		$start_tab = 750;
		}
	}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;		
}

function plugin_pdf_licenses($tab,$width,$ID,$show_computers,$type){
	
	global $DB,$LANG,$LANGPDF;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$ci=new CommonItem();
	$query = "SELECT count(*) AS COUNT  FROM glpi_licenses WHERE (sID = '$ID')";
	$query_update = "SELECT count(glpi_licenses.ID) AS COUNT  FROM glpi_licenses, glpi_software WHERE (glpi_software.ID = glpi_licenses.sID AND glpi_software.update_software = '$ID' and glpi_software.is_update='1')";
	
	$i=0;
	
	if ($result = $DB->query($query)) {
		if ($DB->result($result,0,0)!=0) {
			$nb_licences=$DB->result($result, 0, "COUNT");
			$result_update = $DB->query($query_update);
			$nb_updates=$DB->result($result_update, 0, "COUNT");
			$installed = getInstalledLicence($ID);
			$tobuy=getLicenceToBuy($ID);
			$isfreeorglobal=isFreeSoftware($ID)||isGlobalSoftware($ID);
			
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),100,15);
			$pdf->filledRectangle(130,($start_tab-25)-(20*$i),90,15);
			$pdf->filledRectangle(225,($start_tab-25)-(20*$i),30,15);
			$pdf->filledRectangle(260,($start_tab-25)-(20*$i),80,15);
			$pdf->filledRectangle(345,($start_tab-25)-(20*$i),30,15);
			$pdf->filledRectangle(380,($start_tab-25)-(20*$i),30,15);
			$pdf->filledRectangle(415,($start_tab-25)-(20*$i),155,15);
			$pdf->restoreState();
			$pdf->addText(130,$start_tab,9,utf8_decode('<b>'.$nb_licences.' '.$LANG["software"][13].'     '.$nb_updates.' '.$LANG["software"][36].'     '.$installed.' '.$LANG["software"][19].'     '.$tobuy.' '.$LANG["software"][37].'</b>'));
			$pdf->addText(60,$start_tab-20,9,utf8_decode('<b>'.$LANG["software"][5].'</b>'));
			$pdf->addText(145,$start_tab-20,9,utf8_decode('<b>'.$LANG["common"][19].'</b>'));
			$pdf->addText(230,$start_tab-20,9,utf8_decode('<b>'.$LANG["common"][33].'</b>'));
			$pdf->addText(280,$start_tab-20,9,utf8_decode('<b>'.$LANG["software"][32].'</b>'));
			$pdf->addText(350,$start_tab-20,9,utf8_decode('<b>'.$LANG["software"][28].'</b>'));
			$pdf->addText(382,$start_tab-20,9,utf8_decode('<b>'.$LANG["software"][35].'</b>'));
			$pdf->addText(470,$start_tab-20,9,utf8_decode('<b>'.$LANG["software"][19].'</b>'));
			
			$i++;
			}
		else
			{
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
			$pdf->restoreState();
			$pdf->addText(240,$start_tab,9,'<b>'.utf8_decode($LANG["software"][14]).'</b>');
			}
		}
			
		$query = "SELECT count(ID) AS COUNT , version as VERSION, serial as SERIAL, expire as EXPIRE, oem as OEM, oem_computer as OEM_COMPUTER, buy as BUY  FROM glpi_licenses WHERE (sID = '$ID') GROUP BY version, serial, expire, oem, oem_computer, buy ORDER BY version, serial,oem, oem_computer";
			
		if ($result = $DB->query($query)) {			
			while ($data=$DB->fetch_array($result)) {
				$version=$data["VERSION"];
				$serial=$data["SERIAL"];
				$num_tot=$data["COUNT"];
				$expire=$data["EXPIRE"];
				$oem=$data["OEM"];
				$oem_computer=$data["OEM_COMPUTER"];
				$buy=$data["BUY"];
					
				$SEARCH_LICENCE="(glpi_licenses.sID = $ID AND glpi_licenses.serial = '".$serial."'  AND glpi_licenses.oem = '$oem' AND glpi_licenses.oem_computer = '$oem_computer'  AND glpi_licenses.buy = '$buy' ";
				if ($expire=="")
					$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL";
				else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '$expire'";
		
				if ($version=="")
					$SEARCH_LICENCE.=" AND glpi_licenses.version='')";
				else $SEARCH_LICENCE.=" AND glpi_licenses.version = '$version')";
		
				$today=date("Y-m-d"); 
				$expirer=0;
				if ($expire!=NULL&&$today>$expire)
					$expirer=1;
					
				$query_inst = "SELECT glpi_inst_software.ID AS ID, glpi_inst_software.license AS lID, glpi_computers.deleted as deleted, ";
				$query_inst .= " glpi_infocoms.ID as infocoms, glpi_licenses.comments AS COMMENT, ";
				$query_inst .= " glpi_computers.ID AS cID, glpi_computers.name AS cname FROM glpi_licenses";
				$query_inst .= " INNER JOIN glpi_inst_software ";
				$query_inst .= " ON ( glpi_inst_software.license = glpi_licenses.ID )";
				$query_inst .= " INNER JOIN glpi_computers ON (glpi_computers.deleted='0' AND glpi_computers.is_template='0' AND glpi_inst_software.cID= glpi_computers.ID) ";
				$query_inst .= " LEFT JOIN glpi_infocoms ON (glpi_infocoms.device_type='".LICENSE_TYPE."' AND glpi_infocoms.FK_device=glpi_licenses.ID) ";
				$query_inst .= " WHERE $SEARCH_LICENCE ORDER BY cname";
					
				$result_inst = $DB->query($query_inst);
				$num_inst=$DB->numrows($result_inst);
				
				$pdf->saveState();
				$pdf->setColor(0.95,0.95,0.95);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),100,15);
				$pdf->filledRectangle(130,($start_tab-25)-(20*$i),90,15);
				$pdf->filledRectangle(225,($start_tab-25)-(20*$i),30,15);
				$pdf->filledRectangle(260,($start_tab-25)-(20*$i),80,15);
				$pdf->filledRectangle(345,($start_tab-25)-(20*$i),30,15);
				$pdf->filledRectangle(380,($start_tab-25)-(20*$i),30,15);
				$pdf->filledRectangle(415,($start_tab-25)-(20*$i),155,15);
				$pdf->restoreState();
				
				$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($version));
				$pdf->addText(135,($start_tab-20)-(20*$i),9,utf8_decode($serial));
				$pdf->addText(235,($start_tab-20)-(20*$i),9,utf8_decode($num_tot));
				
				if ($expire==NULL)
					$pdf->addText(265,($start_tab-20)-(20*$i),9,utf8_decode($LANG["software"][26]));
				else{
					if ($expirer) 
						$pdf->addText(265,($start_tab-20)-(20*$i),9,utf8_decode($LANG["software"][27]));
					else 
						$pdf->addText(265,($start_tab-20)-(20*$i),9,utf8_decode($LANG["software"][25].' '.convDate($expire)));
					}
				
				if($oem)
					$pdf->addText(350,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][1]));
				else
					$pdf->addText(350,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][0]));
				
				if ($serial!="free"){
					if($buy)
						$pdf->addText(385,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][1]));
					else
						$pdf->addText(385,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][0]));
				}
				
				if (!$show_computers)
					$pdf->addText(420,($start_tab-20)-(20*$i),9,utf8_decode($LANG["software"][19].' '.$num_inst));
				else
					{
					while ($data_inst=$DB->fetch_array($result_inst))
						{
				
						$ci->getFromDB(COMPUTER_TYPE,$data_inst["cID"]);
						$name=$ci->getNameID();
						$computer = new Computer();
						$computer->getFromDB($data_inst["cID"]);
					
						$pdf->saveState();
						$pdf->setColor(0.95,0.95,0.95);
						$pdf->filledRectangle(415,($start_tab-25)-(20*$i),155,15);
						$pdf->restoreState();
						$pdf->addText(420,($start_tab-20)-(20*$i),9,utf8_decode($name.' ('.$computer->fields['serial'].')'));
						
						$i++;
		
						if(($start_tab-20)-(20*$i)<50){
							$pdf = plugin_pdf_newPage($pdf,$ID,$type);
							$i=0;
							$start_tab = 750;
							}
						}
						$i--;
					}
				$i++;
	
				if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
				}
			}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_software($tab,$width,$ID,$type){
	
	global $DB,$LANG,$LANGPDF;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$comp=new Computer();
	$comp->getFromDB($ID);
	$FK_entities=$comp->fields["FK_entities"];

	$query_cat = "SELECT 1 as TYPE, glpi_dropdown_software_category.name as category, glpi_software.category as category_id, glpi_software.name as softname, glpi_inst_software.license as license, glpi_inst_software.ID as ID,glpi_licenses.expire,glpi_software.deleted, glpi_licenses.sID, glpi_licenses.version, glpi_licenses.oem, glpi_licenses.oem_computer, glpi_licenses.serial, glpi_licenses.buy	FROM glpi_inst_software LEFT JOIN glpi_licenses ON ( glpi_inst_software.license = glpi_licenses.ID )
	LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) 
	LEFT JOIN glpi_dropdown_software_category ON (glpi_dropdown_software_category.ID = glpi_software.category)";

	$query_cat.=" WHERE glpi_inst_software.cID = '$ID' AND glpi_software.category > 0 "; 
    $query_nocat = "SELECT 2 as TYPE, glpi_dropdown_software_category.name as category, glpi_software.category as category_id, glpi_software.name as softname, glpi_inst_software.license as license, glpi_inst_software.ID as ID,glpi_licenses.expire,glpi_software.deleted, glpi_licenses.sID, glpi_licenses.version, glpi_licenses.oem, glpi_licenses.oem_computer, glpi_licenses.serial, glpi_licenses.buy  
        FROM glpi_inst_software LEFT JOIN glpi_licenses ON ( glpi_inst_software.license = glpi_licenses.ID ) 
        LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID)  
        LEFT JOIN glpi_dropdown_software_category ON (glpi_dropdown_software_category.ID = glpi_software.category)"; 
    $query_nocat.= " WHERE glpi_inst_software.cID = '$ID' AND (glpi_software.category <= 0 OR glpi_software.category IS NULL ) "; 
    $query="( $query_cat ) UNION ($query_nocat) ORDER BY TYPE, category, softname, version";

	$result = $DB->query($query);
	$i = 0;
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
	$pdf->restoreState();
	$pdf->addText(250,$start_tab,9,utf8_decode('<b>'.$LANG["software"][17].'</b>'));
	
	$cat=-1;
	
	if ($DB->numrows($result))
		while ($data=$DB->fetch_array($result)) {
			
			if($data["category_id"] != $cat)
				{
				$cat = $data["category_id"];
				$catname=$data["category"];
				
				if (!$cat)
					$catname=$LANG["softwarecategories"][3];
				
				$pdf->saveState();
				$pdf->setColor(0.8,0.8,0.8);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
				$pdf->restoreState();
				$pdf->addText(240,($start_tab-20)-(20*$i),9,utf8_decode('<b>'.$catname.'</b>'));
				
				$i++;
				
				$pdf->saveState();
				$pdf->setColor(0.8,0.8,0.8);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),385,15);
				$pdf->filledRectangle(415,($start_tab-25)-(20*$i),65,15);
				$pdf->filledRectangle(485,($start_tab-25)-(20*$i),40,15);
				$pdf->filledRectangle(530,($start_tab-25)-(20*$i),40,15);
				$pdf->restoreState();
				$pdf->addText(180,($start_tab-20)-(20*$i),9,utf8_decode('<b>'.$LANG["common"][16].'</b>'));
				$pdf->addText(425,($start_tab-20)-(20*$i),9,utf8_decode('<b>'.$LANG["financial"][98].'</b>'));
				$pdf->addText(493,($start_tab-20)-(20*$i),9,utf8_decode('<b>'.$LANG["software"][28].'</b>'));
				$pdf->addText(536,($start_tab-20)-(20*$i),9,utf8_decode('<b>'.$LANG["software"][35].'</b>'));
				
				$i++;
				}
			
			$sw = new Software();
			$sw->getFromDB($data['sID']);
			
			$pdf->saveState();
			$pdf->setColor(0.95,0.95,0.95);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),385,15);
			$pdf->filledRectangle(415,($start_tab-25)-(20*$i),65,15);
			$pdf->filledRectangle(485,($start_tab-25)-(20*$i),40,15);
			$pdf->filledRectangle(530,($start_tab-25)-(20*$i),40,15);
			$pdf->restoreState();
			
			$pdf->addText(27,($start_tab-20)-(20*$i),8,utf8_decode($sw->fields["name"].' ( '.$data["version"].' ) - '.$data['serial']));
			
			if($data['expire']==null)
				$pdf->addText(420,($start_tab-20)-(20*$i),9,utf8_decode($LANG["software"][26]));
			else{
				if($data['deleted'])
					$pdf->addText(420,($start_tab-20)-(20*$i),9,utf8_decode($LANG["software"][27]));
				else
					$pdf->addText(420,($start_tab-20)-(20*$i),9,utf8_decode($data["expire"]));
			}
			
			if($data['serial']!="free" && $data['serial']!="global")
				{
				if($data["oem"])
					$pdf->addText(495,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][1]));
				else
					$pdf->addText(495,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][0]));
				
				if($data["buy"])
					$pdf->addText(543,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][1]));
				else
					$pdf->addText(543,($start_tab-20)-(20*$i),9,utf8_decode($LANG["choice"][0]));
				}
			$i++;
	
			if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(250,$start_tab,9,'<b>'.utf8_decode($LANGPDF["software"][1]).'</b>');
		}
	$start_tab = ($start_tab-20)-(20*$i) - 20;
		
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_connection($tab,$width,$ID,$type){
	
	global $DB,$LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$items=array(PRINTER_TYPE=>$LANG["computers"][39],MONITOR_TYPE=>$LANG["computers"][40],PERIPHERAL_TYPE=>$LANG["computers"][46],PHONE_TYPE=>$LANG["computers"][55]);
	
	$ci=new CommonItem();
	$comp=new Computer();
	$info=new InfoCom();
	$comp->getFromDB($ID);
	
	$i=0;
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
	$pdf->restoreState();
	$pdf->addText(250,$start_tab,9,utf8_decode('<b>'.$LANG["connect"][0].' :</b>'));
	
	foreach ($items as $type=>$title){
		$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".$type."'";
		
		if ($result=$DB->query($query)) {
			$resultnum = $DB->numrows($result);
			if ($resultnum>0) {
				
				for ($j=0; $j < $resultnum; $j++, $i++) {
					$tID = $DB->result($result, $j, "end1");
					$connID = $DB->result($result, $j, "ID");
					$ci->getFromDB($type,$tID);
					$info->getFromDBforDevice($type,$tID) || $info->getEmpty();

					$pdf->saveState();
					$pdf->setColor(0.95,0.95,0.95);
					if ($ci->getField("otherserial")!=null || $info->fields["num_immo"]) {
						$pdf->filledRectangle(25,($start_tab-45)-(20*$i),$width-50, 35);
					} else {
						$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50, 15);
					}
					$pdf->restoreState();
					if ($j==0) {
						$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode('<b><i>'.$ci->getType().' :</i></b>'));						
					}

					$tempo=$ci->getName()." - ";
					if($ci->getField("serial")!=null) {
						$tempo .=$LANG["common"][19] . " : " .$ci->getField("serial")." - ";
					}
					$pdf->addText(120,($start_tab-20)-(20*$i),9,utf8_decode($tempo . plugin_pdf_getDropdownName("glpi_dropdown_state",$ci->getField('state'))));

					$tempo="";
					if($ci->getField("otherserial")!=null) {
						$tempo .=$LANG["common"][20] . " : " . $ci->getField("otherserial");
					}
					if ($info->fields["num_immo"]) {
						if ($tempo) $tempo .= " - ";
						$tempo .=$LANG["financial"][20] . " : " . $info->fields["num_immo"];
					}
					if ($tempo) {
						$i++;
						$pdf->addText(200,($start_tab-20)-(20*$i),9,utf8_decode($tempo));
					}
				}// each device	of current type
						
			} else { // No row	
					
				$pdf->saveState();
				$pdf->setColor(0.95,0.95,0.95);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
				$pdf->restoreState();

				switch ($type){
					case PRINTER_TYPE:
						$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($LANG["computers"][38]));
					break;
					case MONITOR_TYPE:
						$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($LANG["computers"][37]));
					break;
					case PERIPHERAL_TYPE:
						$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($LANG["computers"][47]));
					break;
					case PHONE_TYPE:
						$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($LANG["computers"][54]));
					break;
					}
				$i++;
			} // No row
		} // Result
			
		if(($start_tab-20)-(20*$i)<50){
			$pdf = plugin_pdf_newPage($pdf,$ID,$type);
			$i=0;
			$start_tab = 750;
		}
	} // each type
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
		
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
	
}

function plugin_pdf_port($tab,$width,$ID,$type){
	
	global $DB,$LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = ".$ID." AND device_type = ".COMPUTER_TYPE.") ORDER BY name, logical_number";
	
	$i=0;
	
	if ($result = $DB->query($query)) 
		{
		
		$nb_connect = $DB->numrows($result);
			
		if ($nb_connect!=0) 
			{
	
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
			$pdf->restoreState();
			
			$pdf->addText(250,$start_tab,9,utf8_decode('<b>'.$nb_connect.' '.$LANG["networking"][13].' :</b>'));
				
			while ($devid=$DB->fetch_row($result)) 
				{
				
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				
				for($j=0,$deb=0;$j<8;$j++,$deb++){
					
					if(($start_tab-20)-(20*($i+$j))<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					$deb=0;
					}
					
					if(!($nb_connect==1 && $j==7)){
					$pdf->saveState();
					if($j<7)
						$pdf->setColor(0.95,0.95,0.95);
					else
						$pdf->setColor(0.8,0.8,0.8);
					$pdf->filledRectangle(25,($start_tab-25)-(20*($deb+$i)),$width-50,15);
					$pdf->restoreState();
					}
					
					switch($j){
						case 0:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>#</i></b> '.$netport->fields["logical_number"].' <b><i>          '.$LANG["common"][16].' :</i></b> '.$netport->fields["name"]));
						break;
						case 1:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][51].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_netpoint",$netport->fields["netpoint"])));
						break;
						case 2:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][14].' / '.$LANG["networking"][15].' :</i></b> '.$netport->fields["ifaddr"].' / '.$netport->fields["ifmac"]));
						break;
						case 3:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][60].' / '.$LANG["networking"][61].' / '.$LANG["networking"][59].' :</i></b> '.$netport->fields["netmask"].' / '.$netport->fields["subnet"].' / '.$netport->fields["gateway"]));
						break;
						case 4:
						$query="SELECT * from glpi_networking_vlan WHERE FK_port='$ID'";
						$result2=$DB->query($query);
						if ($DB->numrows($result2)>0)
							while ($line=$DB->fetch_array($result2))
								$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][56].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_vlan",$line["FK_vlan"])));
						else
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][56].' :</i></b> '));
						break;
						case 5:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][65].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_iface",$netport->fields["iface"])));
						break;
						case 6:
						$contact = new Netport;
						$netport2 = new Netport;
					
						if ($contact->getContact($netport->fields["ID"]))
							{
							$netport2->getfromDB($contact->contact_id);
							$netport2->getDeviceData($netport2->fields["on_device"],$netport2->fields["device_type"]);
					
							if($netport2->device_name!=null)	
								$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][17].' :</i></b> '.$netport2->device_name));
							else
								$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][17].' :</i></b>'.$LANG["connect"][1]));
							}
						else
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["networking"][17].' :</i></b>'.$LANG["connect"][1]));
						break;
						case 7:
							$i+=$deb+1;
						break;
					}
				}
				}
				if($nb_connect==1)
					$i--;
				$start_tab = ($start_tab-20)-(20*$i) - 20;
			}
		}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(250,$start_tab,9,utf8_decode('<b>0 '.$LANG["networking"][37].'</b>'));		
		}
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_financial($tab,$width,$ID,$type){
	
	global $CFG_GLPI,$LANG,$LANGPDF;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$ic = new Infocom();
	$ci=new CommonItem();
	
	$i=0;
	
	if ($ci->getFromDB($type,$ID))
	if ($ic->getFromDBforDevice($type,$ID)){
	
	$length_tab = (($width-50)/2)-2.5;
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,2*$length_tab+5,15);
	$pdf->setColor(0.95,0.95,0.95);
	
	for($i=0;$i<11;$i++)
		{
		if($i<10)
			{
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$length_tab,15);
			$pdf->filledRectangle(25+$length_tab+5,($start_tab-25)-(20*$i),$length_tab,15);
			}
		else
			{
			$i+=2;
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),2*$length_tab+5,55);
			}
		}
	$pdf->restoreState();
	
	$pdf->addText(250,$start_tab,9,"<b>".utf8_decode($LANG["financial"][3])."</b>");
	
	$pdf->addText(30,$start_tab-20,9,utf8_decode("<b><i>".$LANG["financial"][26]." :</i></b> ".plugin_pdf_getDropdownName("glpi_enterprises",$ic->fields["FK_enterprise"])));
	$pdf->addText(30,$start_tab-40,9,utf8_decode("<b><i>".$LANG["financial"][18]." :</i></b> ".$ic->fields["num_commande"]));
	$pdf->addText(30,$start_tab-60,9,utf8_decode("<b><i>".$LANG["financial"][14]." :</i></b> ".convDate($ic->fields["buy_date"])));
	$pdf->addText(30,$start_tab-80,9,utf8_decode("<b><i>".$LANG["financial"][15]." :</i></b> ".$ic->fields["warranty_duration"]." mois <b><i> Expire le</i></b> ".getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"])));
	$pdf->addText(30,$start_tab-100,9,utf8_decode("<b><i>".$LANG["financial"][78]." :</i></b> ".formatNumber($ic->fields["warranty_value"])));
	$pdf->addText(30,$start_tab-120,9,utf8_decode("<b><i>".$LANG["rulesengine"][13]." :</i></b> ".formatNumber($ic->fields["value"])));
	$pdf->addText(30,$start_tab-140,9,utf8_decode("<b><i>".$LANG["financial"][20]." :</i></b> 	".$ic->fields["num_immo"]));
	$pdf->addText(30,$start_tab-160,9,utf8_decode("<b><i>".$LANG["financial"][23]." :</i></b> ".$ic->fields["amort_time"]." an(s)"));
	$pdf->addText(30,$start_tab-180,9,utf8_decode("<b><i>".$LANG["financial"][89]." :</i></b> ".showTco($ci->getField('ticket_tco'),$ic->fields["value"])));
	if($ic->fields["alert"]==0)
		$pdf->addText(30,$start_tab-200,9,utf8_decode("<b><i>".$LANG["setup"][247]." :</i></b> "));
	elseif($ic->fields["alert"]==4)
		$pdf->addText(30,$start_tab-200,9,utf8_decode("<b><i>".$LANG["setup"][247]." :</i></b> ".$LANG["financial"][80]));
	$pdf->addText(30,$start_tab-220,9,utf8_decode("<b><i>".$LANG["common"][25]." :</i></b> "));
			
	$y=$start_tab-220;
	$mytext=$ic->fields["comments"];
	while($mytext = $pdf->addTextWrap(105,$y,2*$length_tab-80,10,utf8_decode($mytext)))
		$y-=10;
	
	$pdf->addText($length_tab+35,$start_tab-20,9,utf8_decode("<b><i>".$LANG["financial"][82]." :</i></b> ".$ic->fields["facture"]));
	$pdf->addText($length_tab+35,$start_tab-40,9,utf8_decode("<b><i>".$LANG["financial"][19]." :</i></b> ".$ic->fields["bon_livraison"]));
	$pdf->addText($length_tab+35,$start_tab-60,9,utf8_decode("<b><i>".$LANG["financial"][76]." :</i></b> ".convDate($ic->fields["use_date"])));
	$pdf->addText($length_tab+35,$start_tab-80,9,utf8_decode("<b><i>".$LANG["financial"][87]." :</i></b> ".plugin_pdf_getDropdownName("glpi_dropdown_budget",$ic->fields["budget"]))); 
	$pdf->addText($length_tab+35,$start_tab-100,9,utf8_decode("<b><i>".$LANG["financial"][16]." :</i></b> ".$ic->fields["warranty_info"]));
	$pdf->addText($length_tab+35,$start_tab-120,9,utf8_decode("<b><i>".$LANG["financial"][81]." :</i></b> ".TableauAmort($ic->fields["amort_type"],$ic->fields["value"],$ic->fields["amort_time"],$ic->fields["amort_coeff"],$ic->fields["buy_date"],$ic->fields["use_date"],$CFG_GLPI["date_fiscale"],"n")));
	$pdf->addText($length_tab+35,$start_tab-140,9,utf8_decode("<b><i>".$LANG["financial"][22]." :</i></b> ".getAmortTypeName($ic->fields["amort_type"])));
	$pdf->addText($length_tab+35,$start_tab-160,9,utf8_decode("<b><i>".$LANG["financial"][77]." :</i></b> ".$ic->fields["amort_coeff"]));
	$pdf->addText($length_tab+35,$start_tab-180,9,utf8_decode("<b><i>".$LANG["financial"][90]." :</i></b> ".showTco($ci->getField('ticket_tco'),$ic->fields["value"],$ic->fields["buy_date"])));

	}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(245,$start_tab,9,utf8_decode("<b>".$LANGPDF["financial"][1]."</b>"));
		}
	

	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_contract($tab,$width,$ID,$type){
	
	global $DB,$CFG_GLPI,$LANG,$LANGPDF;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$ci=new CommonItem();
	$ci->getFromDB($type,$ID);

	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_device = ".$ID." AND glpi_contract_device.device_type = ".$type;

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	
	$i=$j=0;
	
	if($number>0){
		
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
	$pdf->filledRectangle(25,($start_tab-25)-(20*$i),100,15);
	$pdf->filledRectangle(130,($start_tab-25)-(20*$i),100,15);
	$pdf->filledRectangle(235,($start_tab-25)-(20*$i),100,15);
	$pdf->filledRectangle(340,($start_tab-25)-(20*$i),80,15);
	$pdf->filledRectangle(425,($start_tab-25)-(20*$i),60,15);
	$pdf->filledRectangle(490,($start_tab-25)-(20*$i),80,15);
	$pdf->restoreState();
	$pdf->addText(260,$start_tab,9,'<b>'.utf8_decode($LANG["financial"][66]).' :</b>');
	$pdf->addText(65,$start_tab-20,9,'<b>'.utf8_decode($LANG["common"][16]).'</b>');
	$pdf->addText(138,$start_tab-20,9,'<b>'.utf8_decode($LANG["financial"][4]).'</b>');
	$pdf->addText(255,$start_tab-20,9,'<b>'.utf8_decode($LANG["financial"][6]).'</b>');
	$pdf->addText(355,$start_tab-20,9,'<b>'.utf8_decode($LANG["financial"][26]).'</b>');
	$pdf->addText(428,$start_tab-20,9,'<b>'.utf8_decode($LANG["search"][8]).'</b>');
	$pdf->addText(515,$start_tab-20,9,'<b>'.utf8_decode($LANG["financial"][8]).'</b>');
	
	$i++;
		
	while ($j < $number) {
		$cID=$DB->result($result, $j, "FK_contract");
		$assocID=$DB->result($result, $j, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
		
		$pdf->saveState();
		$pdf->setColor(0.95,0.95,0.95);
		$pdf->filledRectangle(25,($start_tab-25)-(20*$i),100,15);
		$pdf->filledRectangle(130,($start_tab-25)-(20*$i),100,15);
		$pdf->filledRectangle(235,($start_tab-25)-(20*$i),100,15);
		$pdf->filledRectangle(340,($start_tab-25)-(20*$i),80,15);
		$pdf->filledRectangle(425,($start_tab-25)-(20*$i),60,15);
		$pdf->filledRectangle(490,($start_tab-25)-(20*$i),80,15);
		$pdf->restoreState();
		
		if (empty($con->fields["name"]))
			$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($con->fields["ID"]));
		else
			$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($con->fields["name"]));
		
		$pdf->addText(135,($start_tab-20)-(20*$i),9,utf8_decode($con->fields["num"]));
		$pdf->addText(240,($start_tab-20)-(20*$i),9,utf8_decode(plugin_pdf_getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"])));
		
		$temp = str_replace("<br>", "", getContractEnterprises($cID));
		
		if(strlen($temp)<14)
			$pdf->addText(345,($start_tab-20)-(20*$i),9,utf8_decode($temp));
		else
		{
			$temp=$pdf->addTextWrap(345,($start_tab-20)-(20*$i)+4,70,8,utf8_decode($temp));
			$pdf->addTextWrap(345,($start_tab-20)-(20*$i)-4,70,8,utf8_decode($temp));
		}
		
		$pdf->addText(430,($start_tab-20)-(20*$i),9,utf8_decode(convDate($con->fields["begin_date"])));
		
		if ($con->fields["begin_date"]!='' && $con->fields["begin_date"]!="0000-00-00")
			{
			$pdf->addText(515,($start_tab-20)-(20*$i)+4,7,utf8_decode($con->fields["duration"]." ".$LANG["financial"][57])); 
			$pdf->addText(505,($start_tab-20)-(20*$i)-4,7,"-> ".utf8_decode(getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]))); 
			}
		else
			$pdf->addText(510,($start_tab-20)-(20*$i),9,utf8_decode($con->fields["duration"]." ".$LANG["financial"][57])); 
		
		$i++;
		$j++;
		
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		}
	}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,$start_tab,9,utf8_decode('<b>'.$LANGPDF["financial"][2].'</b>'));
		}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
		
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_document($tab,$width,$ID,$type){
	
	global $DB,$LANG,$LANGPDF;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$query = "SELECT glpi_doc_device.ID as assocID, glpi_docs.* FROM glpi_doc_device "; 
	$query .= "LEFT JOIN glpi_docs ON (glpi_doc_device.FK_doc=glpi_docs.ID)"; 
	$query .= "WHERE glpi_doc_device.FK_device = ".$ID." AND glpi_doc_device.device_type = ".$type;
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	
	$i=0;
	
	if($number>0){
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
	$pdf->filledRectangle(25,($start_tab-25)-(20*$i),125,15);
	$pdf->filledRectangle(155,($start_tab-25)-(20*$i),120,15);
	$pdf->filledRectangle(280,($start_tab-25)-(20*$i),110,15);
	$pdf->filledRectangle(395,($start_tab-25)-(20*$i),100,15);
	$pdf->filledRectangle(500,($start_tab-25)-(20*$i),70,15);
	$pdf->restoreState();
	$pdf->addText(250,$start_tab,9,'<b>'.utf8_decode($LANG["document"][21]).' :</b>');
	$pdf->addText(80,$start_tab-20,9,'<b>'.utf8_decode($LANG["common"][16]).'</b>');
	$pdf->addText(195,$start_tab-20,9,'<b>'.utf8_decode($LANG["backup"][10]).'</b>');
	$pdf->addText(315,$start_tab-20,9,'<b>'.utf8_decode($LANG["document"][33]).'</b>');
	$pdf->addText(425,$start_tab-20,9,'<b>'.utf8_decode($LANG["document"][3]).'</b>');
	$pdf->addText(510,$start_tab-20,9,'<b>'.utf8_decode($LANG["document"][4]).'</b>');
	
	$i++;
		
	while ($data=$DB->fetch_assoc($result)) {
		
		$pdf->saveState();
		$pdf->setColor(0.95,0.95,0.95);
		$pdf->filledRectangle(25,($start_tab-25)-(20*$i),125,15);
		$pdf->filledRectangle(155,($start_tab-25)-(20*$i),120,15);
		$pdf->filledRectangle(280,($start_tab-25)-(20*$i),110,15);
		$pdf->filledRectangle(395,($start_tab-25)-(20*$i),100,15);
		$pdf->filledRectangle(500,($start_tab-25)-(20*$i),70,15);
		$pdf->restoreState();
		
		$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($data["name"]));
		$pdf->addText(160,($start_tab-20)-(20*$i),9,utf8_decode($data["filename"]));
		$pdf->addText(285,($start_tab-20)-(20*$i),9,utf8_decode($data["link"]));
		$pdf->addText(400,($start_tab-20)-(20*$i),9,utf8_decode(plugin_pdf_getDropdownName("glpi_dropdown_rubdocs",$data["rubrique"])));
		$pdf->addText(505,($start_tab-20)-(20*$i),9,utf8_decode($data["mime"]));
		
		$i++;
		
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
	}
	}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(250,$start_tab,9,'<b>'.utf8_decode($LANGPDF["document"][1]).'</b>');
		}
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_registry($tab,$width,$ID,$type){
	
	global $DB,$LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$REGISTRY_HIVE=array("HKEY_CLASSES_ROOT",
	"HKEY_CURRENT_USER",
	"HKEY_LOCAL_MACHINE",
	"HKEY_USERS",
	"HKEY_CURRENT_CONFIG",
	"HKEY_DYN_DATA");
	
	$query = "SELECT ID FROM glpi_registry WHERE computer_id='".$ID."'";
	
	$i=0;
	
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)!=0) {
			
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),130,15);
			$pdf->filledRectangle(160,($start_tab-25)-(20*$i),130,15);
			$pdf->filledRectangle(295,($start_tab-25)-(20*$i),140,15);
			$pdf->filledRectangle(440,($start_tab-25)-(20*$i),130,15);
			$pdf->restoreState();
			$pdf->addText(240,$start_tab,9,'<b>'.utf8_decode($DB->numrows($result)." ".$LANG["registry"][4]).' :</b>');
			$pdf->addText(65,$start_tab-20,9,'<b>'.utf8_decode($LANG["registry"][6]).'</b>');
			$pdf->addText(215,$start_tab-20,9,'<b>'.utf8_decode($LANG["registry"][1]).'</b>');
			$pdf->addText(345,$start_tab-20,9,'<b>'.utf8_decode($LANG["registry"][2]).'</b>');
			$pdf->addText(490,$start_tab-20,9,'<b>'.utf8_decode($LANG["registry"][3]).'</b>');
			
			$i++;
			
			while ($regid=$DB->fetch_row($result)) {
				$reg = new Registry;
				$reg->getfromDB(current($regid));
				
				$pdf->saveState();
				$pdf->setColor(0.95,0.95,0.95);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),130,15);
				$pdf->filledRectangle(160,($start_tab-25)-(20*$i),130,15);
				$pdf->filledRectangle(295,($start_tab-25)-(20*$i),140,15);
				$pdf->filledRectangle(440,($start_tab-25)-(20*$i),130,15);
				$pdf->restoreState();
													
				$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($reg->fields["registry_ocs_name"]));
				$pdf->addText(165,($start_tab-20)-(20*$i),9,utf8_decode($REGISTRY_HIVE[$reg->fields["registry_hive"]]));
				$pdf->addText(300,($start_tab-20)-(20*$i),9,utf8_decode($reg->fields["registry_path"]));
				$pdf->addText(445,($start_tab-20)-(20*$i),9,utf8_decode($reg->fields["registry_value"]));
				
				$i++;
		
				if(($start_tab-20)-(20*$i)<50){
						$pdf = plugin_pdf_newPage($pdf,$ID,$type);
						$i=0;
						$start_tab = 750;
						}
			}
		}
		else
			{
			if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
			$pdf->restoreState();
			$pdf->addText(230,$start_tab,9,'<b>'.utf8_decode($LANG["registry"][5]).'</b>');
			}
	}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_ticket($tab,$width,$ID,$type){
	
	global $DB,$CFG_GLPI, $LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$sort="glpi_tracking.date";
	$order=getTrackingOrderPrefs($_SESSION["glpiID"]);

	$where = "(status = 'new' OR status= 'assign' OR status='plan' OR status='waiting')";	

	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." WHERE $where and (computer = '$ID' and device_type= ".$type.") ORDER BY $sort $order";

	$result = $DB->query($query);

	$i = 0;
	
	$number = $DB->numrows($result);

	if ($number > 0)
	{
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,$start_tab,9,'<b>'.utf8_decode($number.' '.$LANG["job"][17].' '.$LANG["job"][16]).' :</b>');

		while ($data=$DB->fetch_assoc($result))
		{
			for($j=0,$deb=0;$j<9;$j++,$deb++){
					
					if(($start_tab-20)-(20*($i+$j))<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					$deb=0;
					}
					
					if(!($number==1 && $j==8)){
					$pdf->saveState();
					if($j<8)
						$pdf->setColor(0.95,0.95,0.95);
					else
						$pdf->setColor(0.8,0.8,0.8);
					$pdf->filledRectangle(25,($start_tab-25)-(20*($deb+$i)),$width-50,15);
					$pdf->restoreState();
					}
			
			switch($j){
						case 0:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["state"][0].' :</i></b>  ID'.$data["ID"].'     '.getStatusName($data["status"])));
						break;
						case 1:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][27].' :</i></b>       '.$LANG["joblist"][11].' : '.$data["date"]));
						break;
						case 2:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["joblist"][2].' :</i></b> '.getPriorityName($data["priority"])));
						break;
						case 3:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["job"][4].' :</i></b> '.getUserName($data["author"])));
						break;
						case 4:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["job"][5].' :</i></b> '.getUserName($data["assign"])));
						break;
						case 5:
							$ci=new CommonItem();
							$ci->getFromDB($data["device_type"],$data["computer"]);
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][1].' :</i></b> '.$ci->getType()));
						break;
						case 6:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][36].' :</i></b> '.$data["catname"]));
						break;
						case 7:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][57].' :</i></b> '.$data["name"]));
						break;
						case 8:
							$i+=$deb+1;
						break;
					}
				}
				if($number==1)
					$i--;
		}
	} 
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,$start_tab,9,'<b>'.utf8_decode($LANG["joblist"][8]).'</b>');
		}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
	
}

function plugin_pdf_oldticket($tab,$width,$ID,$type){
	
	global $DB,$CFG_GLPI, $LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$sort="glpi_tracking.date";
	$order=getTrackingOrderPrefs($_SESSION["glpiID"]);

	$where = "(status = 'old_done' OR status = 'old_notdone')";	
	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." WHERE $where and (device_type = ".$type." and computer = '$ID') ORDER BY $sort $order";

	$result = $DB->query($query);

	$i = 0;
	
	$number = $DB->numrows($result);

	if ($number > 0)
	{
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(240,$start_tab,9,'<b>'.utf8_decode($number.' '.$LANG["job"][18].' '.$LANG["job"][17].' '.$LANG["job"][16]).' :</b>');

		while ($data=$DB->fetch_assoc($result))
		{
			for($j=0,$deb=0;$j<9;$j++,$deb++){
					
					if(($start_tab-20)-(20*($i+$j))<50 && !($number==1 && $j==8)){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					$deb=0;
					}
					
					if(!($number==1 && $j==8)){
					$pdf->saveState();
					if($j<8)
						$pdf->setColor(0.95,0.95,0.95);
					else
						$pdf->setColor(0.8,0.8,0.8);
					$pdf->filledRectangle(25,($start_tab-25)-(20*($deb+$i)),$width-50,15);
					$pdf->restoreState();
					}
			
			switch($j){
						case 0:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["state"][0].' :</i></b>  ID'.$data["ID"].'     '.getStatusName($data["status"])));
						break;
						case 1:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][27].' :</i></b>       '.$LANG["joblist"][11].' : '.$data["date"]));
						break;
						case 2:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["joblist"][2].' :</i></b> '.getPriorityName($data["priority"])));
						break;
						case 3:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["job"][4].' :</i></b> '.getUserName($data["author"])));
						break;
						case 4:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["job"][5].' :</i></b> '.getUserName($data["assign"])));
						break;
						case 5:
							$ci=new CommonItem();
							$ci->getFromDB($data["device_type"],$data["computer"]);
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][1].' :</i></b> '.$ci->getType()));
						break;
						case 6:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][36].' :</i></b> '.$data["catname"]));
						break;
						case 7:
							$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,utf8_decode('<b><i>'.$LANG["common"][57].' :</i></b> '.$data["name"]));
						break;
						case 8:
							$i+=$deb+1;
						break;
					}
				}
				if($number==1)
					$i--;
		}
	} 
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(240,$start_tab,9,'<b>'.utf8_decode($LANG["joblist"][22]).'</b>');
		}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
	
}

function plugin_pdf_link($tab,$width,$ID,$type){
	
	global $DB,$LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$query="SELECT glpi_links.ID as ID, glpi_links.link as link, glpi_links.name as name , glpi_links.data as data from glpi_links INNER JOIN glpi_links_device ON glpi_links.ID= glpi_links_device.FK_links WHERE glpi_links_device.device_type=".$type." ORDER BY glpi_links.name";

	$result=$DB->query($query);
	
	$pdf->ezSetMargins(100,0,200,0);
	
	$i=0;
	
	$ci=new CommonItem;
	if ($DB->numrows($result)>0){
		
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(230,$start_tab,9,'<b>'.utf8_decode($LANG["title"][33]).'</b>');		
		
		while ($data=$DB->fetch_assoc($result)){

			$pdf->saveState();
			$pdf->setColor(0.95,0.95,0.95);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
			$pdf->restoreState();
			
			$name=$data["name"];
			if (empty($name))
				$name=$data["link"];

			$link=$data["link"];
			$file=trim($data["data"]);
			if (empty($file)){

				$ci->getFromDB($type,$ID);
				if (ereg("\[NAME\]",$link)){
					$link=ereg_replace("\[NAME\]",$ci->getName(),$link);
				}
				if (ereg("\[ID\]",$link)){
					$link=ereg_replace("\[ID\]",$ID,$link);
				}

				if (ereg("\[SERIAL\]",$link)){
					if ($tmp=$ci->getField('serial')){
						$link=ereg_replace("\[SERIAL\]",$tmp,$link);
					}
				}
				if (ereg("\[OTHERSERIAL\]",$link)){
					if ($tmp=$ci->getField('otherserial')){
						$link=ereg_replace("\[OTHERSERIAL\]",$tmp,$link);
					}
				}

				if (ereg("\[LOCATIONID\]",$link)){
					if ($tmp=$ci->getField('location')){
						$link=ereg_replace("\[LOCATIONID\]",$tmp,$link);
					}
				}

				if (ereg("\[LOCATION\]",$link)){
					if ($tmp=$ci->getField('location')){
						$link=ereg_replace("\[LOCATION\]",plugin_pdf_getDropdownName("glpi_dropdown_locations",$tmp),$link);
					}
				}
				if (ereg("\[NETWORK\]",$link)){
					if ($tmp=$ci->getField('network')){
						$link=ereg_replace("\[NETWORK\]",plugin_pdf_getDropdownName("glpi_dropdown_network",$tmp),$link);
					}
				}
				if (ereg("\[DOMAIN\]",$link)){
					if ($tmp=$ci->getField('domain'))
						$link=ereg_replace("\[DOMAIN\]",plugin_pdf_getDropdownName("glpi_dropdown_domain",$tmp),$link);
				}
				$ipmac=array();
				$j=0;
				if (ereg("\[IP\]",$link)||ereg("\[MAC\]",$link)){
					$query2 = "SELECT ifaddr,ifmac FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = ".$type.") ORDER BY logical_number";
					$result2=$DB->query($query2);
					if ($DB->numrows($result2)>0)
						while ($data2=$DB->fetch_array($result2)){
							$ipmac[$j]['ifaddr']=$data2["ifaddr"];
							$ipmac[$j]['ifmac']=$data2["ifmac"];
							$j++;
						}
				}

				if (ereg("\[IP\]",$link)||ereg("\[MAC\]",$link)){
					if (count($ipmac)>0){
						foreach ($ipmac as $key => $val){
							$tmplink=$link;
							$tmplink=ereg_replace("\[IP\]",$val['ifaddr'],$tmplink);
							$tmplink=ereg_replace("\[MAC\]",$val['ifmac'],$tmplink);
							$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($tmplink));
						}
					}
				} else 
					$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($name));
			} else {
				$link=$data['name'];		
				$ci->getFromDB($type,$ID);

				if (ereg("\[NAME\]",$link)){
					$link=ereg_replace("\[NAME\]",$ci->getName(),$link);
				}

				if (ereg("\[ID\]",$link)){
					$link=ereg_replace("\[ID\]",$_GET["ID"],$link);
				}
				$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode($name));
			}
		}
		$i++;
			
		if(($start_tab-20)-(20*$i)<50){
			$pdf = plugin_pdf_newPage($pdf,$ID,$type);
			$i=0;
			$start_tab = 750;
			}
	}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,$start_tab,9,'<b>'.utf8_decode($LANG["links"][7]).'</b>');
		}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
		
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_note($tab,$width,$ID,$type){
	
	global $LANGPDF,$LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$ci =new CommonItem;
	$ci->getfromDB ($type,$ID);
	
	$length = strlen($ci->getField('notes'));
	
	$i=0;
	
	if($length>0)
		{
			
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(280,$start_tab,9,'<b>'.utf8_decode($LANG["title"][37]).'</b>');
			
		$nbline = $length/140;
		$temp=utf8_decode($ci->getField('notes'));
		$i=$j=0;
		while($j<$nbline)
			{
			$pdf->saveState();
			$pdf->setColor(0.95,0.95,0.95);
			if($j==0)
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
			else
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,20);
			$pdf->restoreState();	
			
			$temp = $pdf->addTextWrap(40,($start_tab-20)-(20*$i),$width-80,9,$temp);
			$i++;
			$j++;
			
			if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
			}
		}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,$start_tab,9,'<b>'.utf8_decode($LANGPDF["note"][1]).'</b>');
		}
	
	$start_tab = ($start_tab-20)-(20*$i) - 20;
		
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_reservation($tab,$width,$ID,$type){
	
	global $DB,$LANG,$CFG_GLPI;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$resaID=0;
	
	$i=0;
	
	$pdf->ezSetMargins(200,0,200,0);

	if ($resaID=isReservable($type,$ID))
		{
		$ri=new ReservationItem;
		$ri->getFromDB($resaID);

		$now=$_SESSION["glpi_currenttime"];
		$query = "SELECT * FROM glpi_reservation_resa WHERE end > '".$now."' AND id_item='$resaID' ORDER BY begin";
		$result=$DB->query($query);
		
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(245,$start_tab,9,'<b>'.utf8_decode($LANG["reservation"][35]).'</b>');
		
		if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
		
		if ($DB->numrows($result)==0)
			{	
			$pdf->saveState();
			$pdf->setColor(0.95,0.95,0.95);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
			$pdf->restoreState();
			$pdf->addText(265,($start_tab-20)-(20*$i),9,utf8_decode($LANG["reservation"][37]));
			$i++;
			
			if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
			} 
		else 
			{
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),90,15);
			$pdf->filledRectangle(120,($start_tab-25)-(20*$i),90,15);
			$pdf->filledRectangle(215,($start_tab-25)-(20*$i),140,15);
			$pdf->filledRectangle(360,($start_tab-25)-(20*$i),210,15);
			$pdf->restoreState();

			$pdf->addText(45,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["search"][8]).'</b>');
			$pdf->addText(150,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["search"][9]).'</b>');
			$pdf->addText(280,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["reservation"][31]).'</b>');
			$pdf->addText(435,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["common"][25]).'</b>');
			
			$i++;
			
			if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
			
			while ($data=$DB->fetch_assoc($result))
				{
				$pdf->saveState();
				$pdf->setColor(0.95,0.95,0.95);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),90,15);
				$pdf->filledRectangle(120,($start_tab-25)-(20*$i),90,15);
				$pdf->filledRectangle(215,($start_tab-25)-(20*$i),140,15);
				$pdf->filledRectangle(360,($start_tab-25)-(20*$i),210,15);
				$pdf->restoreState();	
				
				$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode(convDateTime($data["begin"])));
				$pdf->addText(125,($start_tab-20)-(20*$i),9,utf8_decode($data["end"]));
				$pdf->addText(220,($start_tab-20)-(20*$i),9,utf8_decode($data["id_user"]));
				$pdf->addText(365,($start_tab-20)-(20*$i),9,utf8_decode($data["comment"]));
				
				$i++;
				
				if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
				}
			}
		
		$i++;
		
		if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
		}
		
		$query = "SELECT * FROM glpi_reservation_resa WHERE end <= '".$now."' AND id_item='$resaID' ORDER BY begin DESC";
		$result=$DB->query($query);

		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["reservation"][36]).'</b>');

		$i++;
		
		if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}

		if ($DB->numrows($result)==0)
			{	
			$pdf->saveState();
			$pdf->setColor(0.95,0.95,0.95);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),$width-50,15);
			$pdf->restoreState();
			$pdf->addText(265,$start_tab,9,utf8_decode($LANG["reservation"][37]));
			} 
		else 
			{
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,($start_tab-25)-(20*$i),90,15);
			$pdf->filledRectangle(120,($start_tab-25)-(20*$i),90,15);
			$pdf->filledRectangle(215,($start_tab-25)-(20*$i),140,15);
			$pdf->filledRectangle(360,($start_tab-25)-(20*$i),210,15);
			$pdf->restoreState();

			$pdf->addText(45,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["search"][8]).'</b>');
			$pdf->addText(150,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["search"][9]).'</b>');
			$pdf->addText(280,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["reservation"][31]).'</b>');
			$pdf->addText(435,($start_tab-20)-(20*$i),9,'<b>'.utf8_decode($LANG["common"][25]).'</b>');
			
			$i++;
			
			if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
			
			while ($data=$DB->fetch_assoc($result))
				{
				$pdf->saveState();
				$pdf->setColor(0.95,0.95,0.95);
				$pdf->filledRectangle(25,($start_tab-25)-(20*$i),90,15);
				$pdf->filledRectangle(120,($start_tab-25)-(20*$i),90,15);
				$pdf->filledRectangle(215,($start_tab-25)-(20*$i),140,15);
				$pdf->filledRectangle(360,($start_tab-25)-(20*$i),210,15);
				$pdf->restoreState();	
				
				$pdf->addText(30,($start_tab-20)-(20*$i),9,utf8_decode(convDateTime($data["begin"])));
				$pdf->addText(125,($start_tab-20)-(20*$i),9,utf8_decode($data["end"]));
				$pdf->addText(220,($start_tab-20)-(20*$i),9,utf8_decode($data["id_user"]));
				$pdf->addText(365,($start_tab-20)-(20*$i),9,utf8_decode($data["comment"]));
				
				$i++;
				
				if(($start_tab-20)-(20*$i)<50){
					$pdf = plugin_pdf_newPage($pdf,$ID,$type);
					$i=0;
					$start_tab = 750;
					}
				}
			}

		} 
	else
			{
			if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
			$pdf->saveState();
			$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
			$pdf->restoreState();
			$pdf->addText(250,$start_tab,9,'<b>'.utf8_decode($LANG["reservation"][34]).'</b>');
			}
		
	$start_tab = ($start_tab-20)-(20*$i) - 20;
		
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;	
}

function plugin_pdf_history($tab,$width,$ID,$type){
	
	global $DB,$LANG;
	
	$start_tab = $tab["start_tab"];
	$pdf = $tab["pdf"];
	
	$SEARCH_OPTION=getSearchOptions();
	
	$query="SELECT * FROM glpi_history WHERE FK_glpi_device='".$ID."' AND device_type='".$type."' ORDER BY  ID DESC;";
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	
	$pdf->saveState();
	$pdf->setColor(0.8,0.8,0.8);
	$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
	$pdf->restoreState();
	$pdf->addText(280,$start_tab,9,'<b>'.utf8_decode($LANG["title"][38]).'</b>');
	
	$i=0;
	
	if($number!=0){
	while ($data =$DB->fetch_array($result)){
		$field="";
		if($data["linked_action"]){
			switch ($data["linked_action"]){

				case HISTORY_ADD_DEVICE :
					$field = getDeviceTypeLabel($data["device_internal_type"]);
					$change = $LANG["devices"][25]." ".$data[ "new_value"];	
					break;

				case HISTORY_UPDATE_DEVICE :
					$field = getDeviceTypeLabel($data["device_internal_type"]);
					$change = getDeviceSpecifityLabel($data["device_internal_type"]).$data[ "old_value"].$data[ "new_value"];	
					break;

				case HISTORY_DELETE_DEVICE :
					$field = getDeviceTypeLabel($data["device_internal_type"]);
					$change = $LANG["devices"][26]." ".$data["old_value"];	
					break;
				case HISTORY_INSTALL_SOFTWARE :
					$field = $LANG["help"][31];
					$change = $LANG["software"][44]." ".$data["new_value"];	
					break;				
				case HISTORY_UNINSTALL_SOFTWARE :
					$field = $LANG["help"][31];
					$change = $LANG["software"][45]." ".$data["old_value"];	
					break;	
				case HISTORY_DISCONNECT_DEVICE:
					$ci = new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field = $ci->getType();
					$change = $LANG["central"][6]." ".$data["old_value"];	
					break;	
				case HISTORY_CONNECT_DEVICE:
					$ci = new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field = $ci->getType();
					$change = $LANG["log"][55]." ".$data["new_value"];	
					break;	
				case HISTORY_OCS_IMPORT:
					$ci = new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field = $ci->getType();
					$change = $LANG["ocsng"][7]." ".$LANG["ocsng"][45]." : ".$data["new_value"];	
					break;	
				case HISTORY_OCS_DELETE:
					$ci = new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field = $ci->getType();
					$change = $LANG["ocsng"][46]." ".$LANG["ocsng"][45]." : ".$data["old_value"];	
					break;	
				case HISTORY_OCS_LINK:
					$ci = new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field = $ci->getType();
					$change = $LANG["ocsng"][47]." ".$LANG["ocsng"][45]." : ".$data["new_value"];	
					break;	
				case HISTORY_OCS_IDCHANGED:
					$ci = new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field = $ci->getType();
					$change = $LANG["ocsng"][48].$data["old_value"].$data["new_value"];	
					break;	
			}
		}
		else{
			$fieldname="";
			foreach($SEARCH_OPTION[COMPUTER_TYPE] as $key2 => $val2)
				if($key2==$data["id_search_option"]){
					$field = $val2["name"];
					$fieldname = $val2["field"];
				}
				
			if ($fieldname=="comments")
				$change = $LANG["log"][64];
			else
				$change = $data[ "old_value"].$data[ "new_value"];
			}
		
		for($j=0,$deb=0;$j<6;$j++,$deb++){
					
			if(($start_tab-20)-(20*($i+$j))<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				$deb=0;
				}
					
			$pdf->saveState();
			if($j<5)
				$pdf->setColor(0.95,0.95,0.95);
			else
				$pdf->setColor(0.8,0.8,0.8);
			$pdf->filledRectangle(25,($start_tab-25)-(20*($deb+$i)),$width-50,15);
			$pdf->restoreState();
		
			switch($j){
						case 0:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,'<b><i>'.utf8_decode($LANG["common"][2].' : </i></b> '.$data["ID"]));
						break;
						case 1:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,'<b><i>'.utf8_decode($LANG["common"][27].' : </i></b> '.convDateTime($data["date_mod"])));
						break;
						case 2:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,'<b><i>'.utf8_decode($LANG["common"][34].' : </i></b> '.$data["user_name"]));
						break;
						case 3:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,'<b><i>'.utf8_decode($LANG["event"][18].' : </i></b> '.$field));
						break;
						case 4:
						$pdf->addText(27,($start_tab-20)-(20*($i+$deb)),9,'<b><i>'.utf8_decode($LANG["event"][19].' : </i></b> '.$change));
						break;
						case 5:
							$i+=$deb+1;
						break;
					}
				}	
	}
	}
	else
		{
		if(($start_tab-20)-(20*$i)<50){
				$pdf = plugin_pdf_newPage($pdf,$ID,$type);
				$i=0;
				$start_tab = 750;
				}
		$pdf->saveState();
		$pdf->setColor(0.8,0.8,0.8);
		$pdf->filledRectangle(25,$start_tab-5,$width-50,15);
		$pdf->restoreState();
		$pdf->addText(260,$start_tab,9,"<b>".utf8_decode($LANG["event"][20])."</b>");
		}
		
	$start_tab = ($start_tab-20)-(20*$i) - 20;
	
	$tab["start_tab"] = $start_tab;
	$tab["pdf"] = $pdf;
	
	return $tab;
}

function plugin_pdf_newPage($pdf,$ID,$type){
	$pdf->ezText("",1000);
	$pdf->ezText("",9);
	if($type!=-1)
		$pdf = plugin_pdf_add_header($pdf,$ID,$type);
	return $pdf;
}

function plugin_pdf_general($type,$tab_id,$tab){

$pdf= new Cezpdf('a4','portrait');
$width = $pdf->ez['pageWidth'];
$pdf->openHere('Fit');
$start_tab = 750;
$tab_pdf = array("pdf"=>$pdf,"start_tab"=>$start_tab);

$nb_id = count($tab_id);

$tab_pdf = plugin_pdf_background($tab_pdf,$width);

foreach($tab_id as $key => $ID)
	{
	switch($type){
		case COMPUTER_TYPE:
			
			$tab_pdf["pdf"] = plugin_pdf_add_header($tab_pdf["pdf"],$ID,COMPUTER_TYPE);
			$tab_pdf = plugin_pdf_config_computer($tab_pdf,$width,$ID);
			
			for($i=0;$i<count($tab);$i++)
			{
				switch($tab[$i]){
					case 0:
						$tab_pdf = plugin_pdf_financial($tab_pdf,$width,$ID,COMPUTER_TYPE);
						$tab_pdf = plugin_pdf_contract($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 1:
						$tab_pdf = plugin_pdf_connection($tab_pdf,$width,$ID,COMPUTER_TYPE);
						$tab_pdf = plugin_pdf_port($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 2:
						$tab_pdf = plugin_pdf_device($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 3:
						$tab_pdf = plugin_pdf_software($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 4:
						$tab_pdf = plugin_pdf_ticket($tab_pdf,$width,$ID,COMPUTER_TYPE);
						$tab_pdf = plugin_pdf_oldticket($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 5:
						$tab_pdf = plugin_pdf_document($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 6:
						$tab_pdf = plugin_pdf_registry($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 7:
						$tab_pdf = plugin_pdf_link($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 8:
						$tab_pdf = plugin_pdf_note($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 9:
						$tab_pdf = plugin_pdf_reservation($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					case 10:
						$tab_pdf = plugin_pdf_history($tab_pdf,$width,$ID,COMPUTER_TYPE);
					break;
					default:
					break;
				}
			}
		break;
		
		case SOFTWARE_TYPE:
		
			$tab_pdf["pdf"] = plugin_pdf_add_header($tab_pdf["pdf"],$ID,SOFTWARE_TYPE);
			$tab_pdf = plugin_pdf_config_software($tab_pdf,$width,$ID);
			
			for($i=0;$i<count($tab);$i++)
			{
				switch($tab[$i]){
					case 0:
						$tab_pdf = plugin_pdf_licenses($tab_pdf,$width,$ID,0,SOFTWARE_TYPE);
					break;
					case 1:
						$tab_pdf = plugin_pdf_licenses($tab_pdf,$width,$ID,1,SOFTWARE_TYPE);
					break;
					case 2:
						$tab_pdf = plugin_pdf_financial($tab_pdf,$width,$ID,SOFTWARE_TYPE);
						$tab_pdf = plugin_pdf_contract($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					case 3:
						$tab_pdf = plugin_pdf_document($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					case 4:
						$tab_pdf = plugin_pdf_ticket($tab_pdf,$width,$ID,SOFTWARE_TYPE);
						$tab_pdf = plugin_pdf_oldticket($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					case 5:
						$tab_pdf = plugin_pdf_link($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					case 6:
						$tab_pdf = plugin_pdf_note($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					case 7:
						$tab_pdf = plugin_pdf_reservation($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					case 8:
						$tab_pdf = plugin_pdf_history($tab_pdf,$width,$ID,SOFTWARE_TYPE);
					break;
					default:
					break;
				}
			}
		break;
		}
	if($nb_id!=$key+1)
		{
		$tab_pdf["pdf"] = plugin_pdf_newPage($tab_pdf["pdf"],$ID,-1);
		$tab_pdf["start_tab"] = 750;
		}
	}
	
$tab_pdf["pdf"]->ezStream();
}

/**
 * Print out an HTML checkbox
 * @param 
 */
function checkbox($myname,$label,$value,$checked=false)
{
	echo "<td><input type='checkbox' ".($checked==true?"checked='checked'":'')." name='$myname' value='$value'>".$label."</td>";
}

?>
