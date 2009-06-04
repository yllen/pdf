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

function plugin_pdf_checkbox($num,$label,$checked=false) {
	echo "<td width='20%'><input type='checkbox' ".
		($checked==true?"checked='checked'":'').
		" name='item[$num]' value='1'>".$label."</td>";
}

function plugin_pdf_menu($type, $action, $ID) {
	global $LANG, $DB, $PLUGIN_HOOKS;

	if (!isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
		return;
	} 
	$options = doOneHook($PLUGIN_HOOKS['plugin_pdf'][$type], "prefPDF", $type);
	if (!is_array($options)) {
		return;
	}
	
	echo "<form name='plugin_pdf_$type' id='plugin_pdf_$type' action='$action' method='post' " . 
		($ID>0 ? "target='_blank'" : "")."><table class='tab_cadre_fixe'>";

	$values = array();
	$sql = "select table_num from glpi_plugin_pdf_preference WHERE user_id =" . $_SESSION['glpiID'] . " and cat='$type'";
	foreach ($DB->request($sql) AS $data) {
		$values[$data["table_num"]] = $data["table_num"]; 		
	}
	
	$ci = new CommonItem();
	$ci->setType($type);
	echo "<tr><th colspan='6'>" . $LANG['plugin_pdf']["title"][2]. " : ".$ci->getType() . "</th></tr>";
	
	$i=0;
	foreach ($options as $num => $title) {
		if (!$i) {
			echo "<tr class='tab_bg_1'>";					
		}
		plugin_pdf_checkbox($num,$title,(isset($values[$num])?true:false));
		if ($i==4) {
			echo "</tr>";
			$i=0;
		} else {
			$i++;			
		}
	}
	if ($i) {
		while ($i<=4) {
			echo "<td width='20%'>&nbsp;</td>"; 
			$i++;
		}
		echo "</tr>";
	}
	
	echo "<tr class='tab_bg_2'><td colspan='2' align='left'>";
	echo "<a onclick=\"if (   markCheckboxes('plugin_pdf_$type') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG['buttons'][18]."</a> / ";
	echo "<a onclick=\"if ( unMarkCheckboxes('plugin_pdf_$type') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a></td>";

	echo "<td colspan='4' align='center'>";
	echo "<input type='hidden' name='plugin_pdf_inventory_type' value='$type'>";
	echo "<input type='hidden' name='indice' value='".count($options)."'>";
	
	if ($ID>0) {
		echo "<input type='hidden' name='itemID' value='$ID'>";
		echo "<select name='page'>\n";
		echo "<option value='0'>".$LANG['common'][69]."</option>\n"; // Portrait
		echo "<option value='1'>".$LANG['common'][68]."</option>\n"; // Paysage
		echo "</select>&nbsp;&nbsp;&nbsp;&nbsp;\n";	
		echo "<input type='submit' value='" . $LANG['plugin_pdf']["button"][1] . "' name='generate' class='submit'></td></tr>";			
	} else {
		echo "<input type='submit' value='" . $LANG['plugin_pdf']["button"][2] . "' name='plugin_pdf_user_preferences_save' class='submit'></td></tr>";
	}
	echo "</table></form>";
	
}

function plugin_pdf_getDropdownName($table,$id,$withcomment=0){
	
	$name = getDropdownName($table,$id,$withcomment);
	
	if($name=="&nbsp;")
		$name="";
	
	return $name; 
}

function plugin_pdf_add_header($pdf,$ID,$type){
	
	global $LANG;
	
	$entity = '';
	
	$ci = new CommonItem();
	if ($ci->getFromDB($type, $ID) && $ci->obj->fields['name']) {
		if (isMultiEntitiesMode() && isset($ci->obj->fields['FK_entities'])) {
			$entity = ' ('.plugin_pdf_getDropdownName('glpi_entities',$ci->obj->fields['FK_entities']).')'; 
		}
		$name = $ci->obj->fields['name'];
	} else {
		$name = $LANG["common"][2].' '.$ID;
	}
	$pdf->setHeader("<b>$name</b>$entity");
}

function plugin_pdf_config_computer($pdf,$ID) {
	global $LANG;
	
	$computer=new Computer();
	$computer->getFromDB($ID);
	
	$pdf->setColumnsSize(50,50);
	$col1 = '<b>'.$LANG["common"][2].' '.$computer->fields['ID'].'</b>';
	if(!empty($computer->fields['tplname']))
		$col2 = $LANG["common"][26].' : '.convDateTime($computer->fields["date_mod"]).' ('.$LANG["common"][13].' : '.$computer->fields['tplname'].')';
	else if($computer->fields['ocs_import'])
		$col2 = $LANG["common"][26].' : '.convDateTime($computer->fields["date_mod"]).' ('.$LANG["ocsng"][7].')';
	else
		$col2 = $LANG["common"][26].' : '.convDateTime($computer->fields["date_mod"]);
	$pdf->displayTitle($col1, $col2);

	$pdf->displayLine(
		'<b><i>'.$LANG["common"][16].' :</i></b> '.$computer->fields['name'],
		'<b><i>'.$LANG["common"][18].' :</i></b> '.$computer->fields['contact']);
	$pdf->displayLine(
		'<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName('glpi_type_computers',$computer->fields['type']),
		'<b><i>'.$LANG["common"][21].' :</i></b> '.$computer->fields['contact_num']);
	$pdf->displayLine(
		'<b><i>'.$LANG["common"][22].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_model',$computer->fields['model']),
		'<b><i>'.$LANG["common"][34].' :</i></b> '.getUserName($computer->fields['FK_users']));
	$pdf->displayLine(
		'<b><i>'.$LANG["common"][5].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_manufacturer',$computer->fields['FK_glpi_enterprise']),
		'<b><i>'.$LANG["common"][35].' :</i></b> '.plugin_pdf_getDropdownName('glpi_groups',$computer->fields['FK_groups']));
	$pdf->displayLine(
		'<b><i>'.$LANG["computers"][9].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os',$computer->fields['os']),
		'<b><i>'.$LANG["common"][10].' :</i></b> '.getUserName($computer->fields['tech_num']));
	$pdf->displayLine(
		'<b><i>'.$LANG["computers"][52].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os_version',$computer->fields['os_version']),
		'<b><i>'.$LANG["setup"][88].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_network',$computer->fields['network']));
	$pdf->displayLine(
		'<b><i>'.$LANG["computers"][53].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_os_sp',$computer->fields['os_sp']),
		'<b><i>'.$LANG["setup"][89].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_domain',$computer->fields['domain']));
	$pdf->displayLine(
		'<b><i>'.$LANG["computers"][10].' :</i></b> '.$computer->fields['os_license_number'],
		'<b><i>'.$LANG["common"][19].' :</i></b> '.$computer->fields['serial']);
	$pdf->displayLine(
		'<b><i>'.$LANG["computers"][11].' :</i></b> '.$computer->fields['os_license_id'],
		'<b><i>'.$LANG["common"][20].' :</i></b> '.$computer->fields['otherserial']);
	if($computer->fields['ocs_import'])
		$col1 = '<b><i>'.$LANG["ocsng"][6].' '.$LANG["Menu"][33].' :</i></b> '.$LANG["choice"][1];
	else
		$col1 = '<b><i>'.$LANG["ocsng"][6].' '.$LANG["Menu"][33].' :</i></b> '.$LANG["choice"][0];
	$pdf->displayLine($col1,
		'<b><i>'.$LANG["state"][0].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_state',$computer->fields['state']));
	$pdf->displayLine('',
		'<b><i>'.$LANG["computers"][51].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_auto_update',$computer->fields['auto_update']));

	$pdf->setColumnsSize(100);
	$pdf->displayLine(
		'<b><i>'.$LANG["common"][15].' :</i></b> '.plugin_pdf_getDropdownName('glpi_dropdown_locations',$computer->fields['location']));
		
	$pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $computer->fields['comments']);
	
	$pdf->displaySpace();
}

function plugin_pdf_financial($pdf,$ID,$type){
	
	global $CFG_GLPI,$LANG;
	
	$ic = new Infocom();
	$ci = new CommonItem();

	$pdf->setColumnsSize(100);
	if ($ci->getFromDB($type,$ID) && $ic->getFromDBforDevice($type,$ID)) {
		$pdf->displayTitle("<b>".$LANG["financial"][3]."</b>");
		
		$pdf->setColumnsSize(50,50);
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][26]." :</i></b> ".plugin_pdf_getDropdownName("glpi_enterprises",$ic->fields["FK_enterprise"]),
			"<b><i>".$LANG["financial"][82]." :</i></b> ".$ic->fields["facture"]);
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][18]." :</i></b> ".$ic->fields["num_commande"],
			"<b><i>".$LANG["financial"][19]." :</i></b> ".$ic->fields["bon_livraison"]);
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][14]." :</i></b> ".convDate($ic->fields["buy_date"]),
			"<b><i>".$LANG["financial"][76]." :</i></b> ".convDate($ic->fields["use_date"]));
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][15]." :</i></b> ".$ic->fields["warranty_duration"]." mois <b><i> Expire le</i></b> ".getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]),
			"<b><i>".$LANG["financial"][87]." :</i></b> ".plugin_pdf_getDropdownName("glpi_dropdown_budget",$ic->fields["budget"])); 
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][78]." :</i></b> ".formatNumber($ic->fields["warranty_value"]),
			"<b><i>".$LANG["financial"][16]." :</i></b> ".$ic->fields["warranty_info"]);
		$pdf->displayLine(
			"<b><i>".$LANG["rulesengine"][13]." :</i></b> ".formatNumber($ic->fields["value"]),
			"<b><i>".$LANG["financial"][81]." :</i></b> ".TableauAmort($ic->fields["amort_type"],$ic->fields["value"],$ic->fields["amort_time"],$ic->fields["amort_coeff"],$ic->fields["buy_date"],$ic->fields["use_date"],$CFG_GLPI["date_fiscale"],"n"));
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][20]." :</i></b> 	".$ic->fields["num_immo"],
			"<b><i>".$LANG["financial"][22]." :</i></b> ".getAmortTypeName($ic->fields["amort_type"]));
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][23]." :</i></b> ".$ic->fields["amort_time"]." ".$LANG['financial'][9],
			"<b><i>".$LANG["financial"][77]." :</i></b> ".$ic->fields["amort_coeff"]);
		$pdf->displayLine(
			"<b><i>".$LANG["financial"][89]." :</i></b> ".showTco($ci->getField('ticket_tco'),$ic->fields["value"]),
			"<b><i>".$LANG["financial"][90]." :</i></b> ".showTco($ci->getField('ticket_tco'),$ic->fields["value"],$ic->fields["buy_date"]));

		$pdf->setColumnsSize(100);
		$col1 = "<b><i>".$LANG["setup"][247]." :</i></b> ";
		if($ic->fields["alert"]==0)
			$col1 .= $LANG['choice'][0];
		else if($ic->fields["alert"]==4)
			$col1 .= $LANG["financial"][80];
		$pdf->displayLine($col1);
	
		$pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $ic->fields["comments"]);
	} else {
		$pdf->displayTitle("<b>".$LANG['plugin_pdf']["financial"][1]."</b>");
	}

	$pdf->displaySpace();
}

/*
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
	$pdf->addText(30,$start_tab-60,9,utf8_decode('<b><i>'.$LANG["common"][34].' :</i></b> '.getUserName($software->fields["FK_users"])));
	$pdf->addText(30,$start_tab-80,9,utf8_decode('<b><i>'.$LANG["common"][10].' :</i></b> '.getUserName($software->fields["tech_num"])));
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
*/
function plugin_pdf_device($pdf,$ID,$type){
	
	global $LANG;
	
	$computer=new Computer();
	$computer->getFromDBwithDevices($ID);

	
	$pdf->setColumnsSize(100);
	$pdf->displayTitle('<b>'.$LANG["title"][30].'</b>');

	$pdf->setColumnsSize(3,14,44,20,19);
	
	foreach($computer->devices as $key => $val) {
		$device = new Device($val["devType"]);
		$device->getFromDB($val["devID"]);
		
		switch($device->devtype) {
		case HDD_DEVICE :
			if (!empty($device->fields["rpm"]))	$col5='<b><i>'.$LANG["device_hdd"][0].' :</i></b> '.$device->fields["rpm"];
			else if (!empty($device->fields["interface"])) $col5='<b><i>'.$LANG["common"][65].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_interface",$device->fields["interface"]);
			else if (!empty($device->fields["cache"])) $col5='<b><i>'.$LANG["device_hdd"][1].' :</i></b> '.$device->fields["cache"];
			else $col5='';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][1],
				$device->fields["designation"],
				'<b><i>'.$LANG["device_hdd"][4].' :</i></b> '.$val["specificity"],
				$col5);
			break;
		case GFX_DEVICE :
			$col4 = (empty($device->fields["ram"]) ? '' : '<b><i>'.$LANG["device_gfxcard"][0].' :</i></b> '.$device->fields["ram"]);
			$col5 = (empty($device->fields["interface"]) ? '' :  '<b><i>'.$LANG["common"][65].' :</i></b> '.$device->fields["interface"]);
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][2],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case NETWORK_DEVICE :
			$col5 = (empty($device->fields["bandwidth"]) ? '' : '<b><i>'.$LANG["device_iface"][0].' :</i></b> '.$device->fields["bandwidth"]);
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][3],
				$device->fields["designation"],
				'<b><i>'.$LANG["networking"][15].' :</i></b> '.$val["specificity"],
				$col5);
			break;
		case MOBOARD_DEVICE :
			$col4 = (empty($device->fields["chipset"]) ? '' : '<b><i>'.$LANG["device_moboard"][0].' :</i></b> '.$device->fields["chipset"]);
			$col5 = '';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][5],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case PROCESSOR_DEVICE :
			$col5 = '';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][4],
				$device->fields["designation"],
				'<b><i>'.$LANG["device_ram"][1].' :</i></b> '.$val["specificity"],
				$col5);
			break;
		case RAM_DEVICE :
			$col5 = (empty($device->fields["type"]) ? '' : '<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_ram_type",$device->fields["type"])) .
					(empty($device->fields["frequence"]) ? '' : $device->fields["frequence"]);
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][6],
				$device->fields["designation"],
				'<b><i>'.$LANG["monitors"][21].' :</i></b> '.$val["specificity"],
				$col5);
			break;
		case SND_DEVICE :
			$col4 = (empty($device->fields["type"]) ? '' : '<b><i>'.$LANG["common"][17].' :</i></b> '.$device->fields["type"]);
			$col5 = '';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][7],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case DRIVE_DEVICE : 
			if (!empty($device->fields["is_writer"])) $col4 = '<b><i>'.$LANG["profiles"][11].' :</i></b> '.getYesNo($device->fields["is_writer"]);
			else if (!empty($device->fields["speed"])) $col4 = '<b><i>'.$LANG["device_drive"][1].' :</i></b> '.$device->fields["speed"];
			else if (!empty($device->fields["frequence"])) $col4 = '<b><i>'.$LANG["device_ram"][1].' :</i></b> '.$device->fields["frequence"];
			else $col4 = '';
			$col5 = '';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][19],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case CONTROL_DEVICE :;
			$col4 = (empty($device->fields["interface"]) ? '' : '<b><i>'.$LANG["common"][65].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_interface",$device->fields["interface"]));
			$col5 = (empty($device->fields["raid"]) ? '' : '<b><i>'.$LANG["device_control"][0].' :</i></b> '.getYesNo($device->fields["raid"]));
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][20],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case PCI_DEVICE :
			$col4 = '';
			$col5 = '';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][21],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case POWER_DEVICE :
			$col4 = (empty($device->fields["power"]) ? '' : '<b><i>'.$LANG["device_power"][0].' :</i></b> '.$device->fields["power"]);
			$col5 = (empty($device->fields["atx"]) ? '' : '<b><i>'.$LANG["device_power"][1].' :</i></b> '.getYesNo($device->fields["atx"]));
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][23],
				$device->fields["designation"],
				$col4, $col5);
			break;
		case CASE_DEVICE :
			$col4 = (empty($device->fields["type"]) ? '' : '<b><i>'.$LANG["common"][17].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_case_type",$device->fields["type"]));
			$col5 = '';
			$pdf->displayLine($val["quantity"].'x',
				$LANG["devices"][22],
				$device->fields["designation"],
				$col4, $col5);
			break;
		}
	} // each device
	
	$pdf->displaySpace();
}
/*
function plugin_pdf_licenses($tab,$width,$ID,$show_computers,$type){
	
	global $DB,$LANG;
	
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
*/
function plugin_pdf_software($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$comp=new Computer();
	$comp->getFromDB($ID);
	$FK_entities=$comp->fields["FK_entities"];

	$query_cat = "SELECT 1 as TYPE, glpi_dropdown_software_category.name as category, glpi_software.category as category_id, 
		glpi_software.name as softname, glpi_inst_software.ID as ID, glpi_software.deleted, glpi_dropdown_state.name AS state,
		glpi_softwareversions.sID, glpi_softwareversions.name AS version,glpi_softwarelicenses.FK_computers AS FK_computers,glpi_softwarelicenses.type AS lictype
		FROM glpi_inst_software 
		LEFT JOIN glpi_softwareversions ON ( glpi_inst_software.vID = glpi_softwareversions.ID )
		LEFT JOIN glpi_dropdown_state ON ( glpi_dropdown_state.ID = glpi_softwareversions.state )
		LEFT JOIN glpi_softwarelicenses ON ( glpi_softwareversions.sID = glpi_softwarelicenses.sID AND glpi_softwarelicenses.FK_computers = '$ID')
		LEFT JOIN glpi_software ON (glpi_softwareversions.sID = glpi_software.ID) 
		LEFT JOIN glpi_dropdown_software_category ON (glpi_dropdown_software_category.ID = glpi_software.category)";
	$query_cat .= " WHERE glpi_inst_software.cID = '$ID' AND glpi_software.category > 0";

	$query_nocat = "SELECT 2 as TYPE, glpi_dropdown_software_category.name as category, glpi_software.category as category_id,
		glpi_software.name as softname, glpi_inst_software.ID as ID, glpi_software.deleted, glpi_dropdown_state.name AS state,
		glpi_softwareversions.sID, glpi_softwareversions.name AS version,glpi_softwarelicenses.FK_computers AS FK_computers,glpi_softwarelicenses.type AS lictype
	    FROM glpi_inst_software 
		LEFT JOIN glpi_softwareversions ON ( glpi_inst_software.vID = glpi_softwareversions.ID ) 
		LEFT JOIN glpi_dropdown_state ON ( glpi_dropdown_state.ID = glpi_softwareversions.state )
		LEFT JOIN glpi_softwarelicenses ON ( glpi_softwareversions.sID = glpi_softwarelicenses.sID AND glpi_softwarelicenses.FK_computers = '$ID')
	    LEFT JOIN glpi_software ON (glpi_softwareversions.sID = glpi_software.ID)  
	    LEFT JOIN glpi_dropdown_software_category ON (glpi_dropdown_software_category.ID = glpi_software.category)";
	$query_nocat .= " WHERE glpi_inst_software.cID = '$ID' AND (glpi_software.category <= 0 OR glpi_software.category IS NULL )";

	$query = "( $query_cat ) UNION ($query_nocat) ORDER BY TYPE, category, softname, version";

	$DB->query("SET SESSION group_concat_max_len = 9999999;");
	$result = $DB->query($query);

	$pdf->setColumnsSize(100);
	
	if ($DB->numrows($result)) {

		$pdf->displayTitle('<b>'.$LANG["software"][17].'</b>');

		$cat=-1;
		while ($data=$DB->fetch_array($result)) {
			
			if($data["category_id"] != $cat) {
				$cat = $data["category_id"];
				$catname = ($cat ? $data["category"] : $LANG["softwarecategories"][3]);
				
				$pdf->setColumnsSize(100);
				$pdf->displayTitle('<b>'.$catname.'</b>');
				
				$pdf->setColumnsSize(59,13,13,15);
				$pdf->displayTitle(
					'<b>'.$LANG["common"][16].'</b>',
					'<b>'.$LANG['state'][0].'</b>',
					'<b>'.$LANG['software'][5].'</b>',
					'<b>'.$LANG['software'][30].'</b>');				
			}
			
			$sw = new Software();
			$sw->getFromDB($data['sID']);
			
			$pdf->displayLine(
				$data['softname'],
				$data['state'],
				$data['version'],
				($data["FK_computers"]==$ID ? plugin_pdf_getDropdownName("glpi_dropdown_licensetypes",$data["lictype"]) : ''));
		} // Each soft
	} else	{
		$pdf->displayTitle('<b>'.$LANG['plugin_pdf']["software"][1].'</b>');
	}
	$pdf->displaySpace();
}

function plugin_pdf_connection($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$items=array(PRINTER_TYPE=>$LANG["computers"][39],MONITOR_TYPE=>$LANG["computers"][40],PERIPHERAL_TYPE=>$LANG["computers"][46],PHONE_TYPE=>$LANG["computers"][55]);
	
	$ci=new CommonItem();
	$comp=new Computer();
	$info=new InfoCom();
	$comp->getFromDB($ID);
	
	$pdf->setColumnsSize(100);
	$pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');
	
	foreach ($items as $type=>$title){
		$query = "SELECT * from glpi_connect_wire WHERE end2='$ID' AND type='".$type."'";
		
		if ($result=$DB->query($query)) {
			$resultnum = $DB->numrows($result);
			if ($resultnum>0) {
				
				for ($j=0; $j < $resultnum; $j++) {
					$tID = $DB->result($result, $j, "end1");
					$connID = $DB->result($result, $j, "ID");
					$ci->getFromDB($type,$tID);
					$info->getFromDBforDevice($type,$tID) || $info->getEmpty();


					$line1 = $ci->getName()." - ";
					if($ci->getField("serial")!=null) {
						$line1 .= $LANG["common"][19] . " : " .$ci->getField("serial")." - ";
					}
					$line1 .= plugin_pdf_getDropdownName("glpi_dropdown_state",$ci->getField('state'));

					$line2 = "";
					if($ci->getField("otherserial")!=null) {
						$line2 = $LANG["common"][20] . " : " . $ci->getField("otherserial");
					}
					if ($info->fields["num_immo"]) {
						if ($line2) $line2 .= " - ";
						$line2 .= $LANG["financial"][20] . " : " . $info->fields["num_immo"];
					}
					if ($line2) {
						$pdf->displayText('<b>'.$ci->getType().'</b>', $line1 . "\n" . $line2, 2);
					} else {
						$pdf->displayText('<b>'.$ci->getType().'</b>', $line1, 1);
					}
				}// each device	of current type
						
			} else { // No row	
					
				switch ($type){
					case PRINTER_TYPE:
						$pdf->displayLine($LANG["computers"][38]);
					break;
					case MONITOR_TYPE:
						$pdf->displayLine($LANG["computers"][37]);
					break;
					case PERIPHERAL_TYPE:
						$pdf->displayLine($LANG["computers"][47]);
					break;
					case PHONE_TYPE:
						$pdf->displayLine($LANG["computers"][54]);
					break;
					}
			} // No row
		} // Result
			
	} // each type
	
	$pdf->displaySpace();	
}

function plugin_pdf_port($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = ".$ID." AND device_type = ".COMPUTER_TYPE.") ORDER BY name, logical_number";
	
	$pdf->setColumnsSize(100);
	if ($result = $DB->query($query)) {
		$nb_connect = $DB->numrows($result);
			
		if (!$nb_connect) {
			$pdf->displayTitle('<b>0 '.$LANG["networking"][37].'</b>');
			
		} else { 
			$pdf->displayTitle('<b>'.$nb_connect.' '.$LANG["networking"][13].' :</b>');
				
			while ($devid=$DB->fetch_row($result)) {
				
				$netport = new Netport;
				$netport->getfromDB(current($devid));
				
				$pdf->displayLine('<b><i>#</i></b> '.$netport->fields["logical_number"].' <b><i>          '.$LANG["common"][16].' :</i></b> '.$netport->fields["name"]);
				$pdf->displayLine('<b><i>'.$LANG["networking"][51].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_netpoint",$netport->fields["netpoint"]));
				$pdf->displayLine('<b><i>'.$LANG["networking"][14].' / '.$LANG["networking"][15].' :</i></b> '.$netport->fields["ifaddr"].' / '.$netport->fields["ifmac"]);
				$pdf->displayLine('<b><i>'.$LANG["networking"][60].' / '.$LANG["networking"][61].' / '.$LANG["networking"][59].' :</i></b> '.$netport->fields["netmask"].' / '.$netport->fields["subnet"].' / '.$netport->fields["gateway"]);
				
				$query="SELECT * from glpi_networking_vlan WHERE FK_port='$ID'";
				$result2=$DB->query($query);
				if ($DB->numrows($result2)>0) {
					$line = '<b><i>'.$LANG["networking"][56].' :</i></b>';
					while ($line=$DB->fetch_array($result2)) {
						$line .= ' ' . plugin_pdf_getDropdownName("glpi_dropdown_vlan",$line["FK_vlan"]);
					}
					$pdf->displayLine($line);
				}
				$pdf->displayLine('<b><i>'.$LANG["common"][65].' :</i></b> '.plugin_pdf_getDropdownName("glpi_dropdown_iface",$netport->fields["iface"]));


				$contact = new Netport;
				$netport2 = new Netport;
			
				$line = '<b><i>'.$LANG["networking"][17].' :</i></b> ';
				if ($contact->getContact($netport->fields["ID"]))
					{
					$netport2->getfromDB($contact->contact_id);
					$netport2->getDeviceData($netport2->fields["on_device"],$netport2->fields["device_type"]);
			
					$line .= ($netport2->device_name ? $netport2->device_name : $LANG["connect"][1]);
				} else {
					$line .= $LANG["connect"][1];
				}
				$pdf->displayLine($line);
			} // each port
		} // Found
	} // Query
	
	$pdf->displaySpace();	
}

function plugin_pdf_contract($pdf,$ID,$type){
	
	global $DB,$CFG_GLPI,$LANG;
	
	$ci = new CommonItem();
	$con = new Contract;

	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_device = ".$ID." AND glpi_contract_device.device_type = ".$type;

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	
	$i=$j=0;
	
	$pdf->setColumnsSize(100);
	if($ci->getFromDB($type,$ID) && $number>0) {
		
		$pdf->displayTitle($LANG["financial"][66]);

		$pdf->setColumnsSize(19,19,19,16,11,16);
		$pdf->displayTitle(
			$LANG["common"][16],
			$LANG["financial"][4],
			$LANG["financial"][6],
			$LANG["financial"][26],
			$LANG["search"][8],
			$LANG["financial"][8]
			);
	
		$i++;
		
		while ($j < $number) {
			$cID=$DB->result($result, $j, "FK_contract");
			$assocID=$DB->result($result, $j, "ID");
		
			if ($con->getFromDB($cID)) {
				$pdf->displayLine(
					(empty($con->fields["name"]) ? "(".$con->fields["ID"].")" : $con->fields["name"]),
					$con->fields["num"],
					plugin_pdf_getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"]),
					str_replace("<br>", " ", getContractEnterprises($cID)),
					convDate($con->fields["begin_date"]),
					$con->fields["duration"]." ".$LANG["financial"][57]
					);					
			}
			$j++;
		}
	} else {
		$pdf->displayTitle("<b>".$LANG['plugin_pdf']["financial"][2]."</b>");		
	}	
	
	$pdf->displaySpace();
}
function plugin_pdf_document($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$query = "SELECT glpi_doc_device.ID as assocID, glpi_docs.* FROM glpi_doc_device "; 
	$query .= "LEFT JOIN glpi_docs ON (glpi_doc_device.FK_doc=glpi_docs.ID)"; 
	$query .= "WHERE glpi_doc_device.FK_device = ".$ID." AND glpi_doc_device.device_type = ".$type;
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	
	$pdf->setColumnsSize(100);
	if (!$number) {
		$pdf->displayTitle('<b>'.$LANG['plugin_pdf']["document"][1].'</b>');
		
	} else {
		$pdf->displayTitle(
			'<b>'.$LANG["document"][21].' :</b>');

		$pdf->setColumnsSize(32,15,21,19,13);
		$pdf->displayTitle(
			'<b>'.$LANG["common"][16].'</b>',
			'<b>'.$LANG["document"][2].'</b>',
			'<b>'.$LANG["document"][33].'</b>',
			'<b>'.$LANG["document"][3].'</b>',
			'<b>'.$LANG["document"][4].'</b>');
			
		while ($data=$DB->fetch_assoc($result)) {
			
			$pdf->displayLine(
				$data["name"],
				basename($data["filename"]),
				$data["link"],
				plugin_pdf_getDropdownName("glpi_dropdown_rubdocs",$data["rubrique"]),
				$data["mime"]);
		}
	}
	$pdf->displaySpace();
}

function plugin_pdf_registry($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$REGISTRY_HIVE=array("HKEY_CLASSES_ROOT",
	"HKEY_CURRENT_USER",
	"HKEY_LOCAL_MACHINE",
	"HKEY_USERS",
	"HKEY_CURRENT_CONFIG",
	"HKEY_DYN_DATA");
	
	$query = "SELECT ID FROM glpi_registry WHERE computer_id='".$ID."'";
	
	$pdf->setColumnsSize(100);
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)>0) {
			$pdf->displayTitle('<b>'.$DB->numrows($result)." ".$LANG["registry"][4].'</b>');
	
			$pdf->setColumnsSize(25,25,25,25);
			$pdf->displayTitle(
				'<b>'.$LANG["registry"][6].'</b>',
				'<b>'.$LANG["registry"][1].'</b>',
				'<b>'.$LANG["registry"][2].'</b>',
				'<b>'.$LANG["registry"][3].'</b>');
			
			$reg = new Registry;
			
			while ($regid=$DB->fetch_row($result)) {
				if ($reg->getfromDB(current($regid))) {
					$pdf->displayLine(
						$reg->fields["registry_ocs_name"],
						$REGISTRY_HIVE[$reg->fields["registry_hive"]],
						$reg->fields["registry_path"],
						$reg->fields["registry_value"]);
				}
			}
		} else {
			$pdf->displayTitle('<b>'.$LANG["registry"][5].'</b>');
		}
	}

	$pdf->displaySpace();
}

function plugin_pdf_ticket($pdf,$ID,$type){
	
	global $DB,$CFG_GLPI, $LANG;
	
	$sort="glpi_tracking.date";
	$order=getTrackingOrderPrefs($_SESSION["glpiID"]);

	$where = "(status = 'new' OR status= 'assign' OR status='plan' OR status='waiting')";	

	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." WHERE $where and (computer = '$ID' and device_type= ".$type.") ORDER BY $sort $order";

	$result = $DB->query($query);
	$number = $DB->numrows($result);

	$pdf->setColumnsSize(100);
	if (!$number) {
		$pdf->displayTitle('<b>'.$LANG['joblist'][24] . " - " . $LANG["joblist"][8].'</b>');
		
	} else	{
		$pdf->displayTitle('<b>'.$LANG['joblist'][24]." - $number ".$LANG["job"][8].'</b>');

		while ($data=$DB->fetch_assoc($result))	{
			$pdf->displayLine('<b><i>'.$LANG["state"][0].' :</i></b>  ID'.$data["ID"].'     '.getStatusName($data["status"]));
			$pdf->displayLine('<b><i>'.$LANG["common"][27].' :</i></b>       '.$LANG["joblist"][11].' : '.$data["date"]);
			$pdf->displayLine('<b><i>'.$LANG["joblist"][2].' :</i></b> '.getPriorityName($data["priority"]));
			$pdf->displayLine('<b><i>'.$LANG["job"][4].' :</i></b> '.getUserName($data["author"]));
			$pdf->displayLine('<b><i>'.$LANG["job"][5].' :</i></b> '.getUserName($data["assign"]));
			$pdf->displayLine('<b><i>'.$LANG["common"][36].' :</i></b> '.$data["catname"]);
			$pdf->displayLine('<b><i>'.$LANG["common"][57].' :</i></b> '.$data["name"]);
		}
	} 

	$pdf->displaySpace();
}

function plugin_pdf_oldticket($pdf,$ID,$type){
	
	global $DB,$CFG_GLPI, $LANG;
	
	$sort="glpi_tracking.date";
	$order=getTrackingOrderPrefs($_SESSION["glpiID"]);

	$where = "(status = 'old_done' OR status = 'old_notdone')";	
	$query = "SELECT ".getCommonSelectForTrackingSearch()." FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." WHERE $where and (device_type = ".$type." and computer = '$ID') ORDER BY $sort $order";

	$result = $DB->query($query);
	$number = $DB->numrows($result);

	$pdf->setColumnsSize(100);
	if (!$number) {
		$pdf->displayTitle('<b>'.$LANG['joblist'][25] . " - " . $LANG["joblist"][8].'</b>');
		
	} else	{
		$pdf->displayTitle('<b>'.$LANG['joblist'][25]." - $number ".$LANG["job"][8].'</b>');

		while ($data=$DB->fetch_assoc($result))	{
			$pdf->displayLine('<b><i>'.$LANG["state"][0].' :</i></b>  ID'.$data["ID"].'     '.getStatusName($data["status"]));
			$pdf->displayLine('<b><i>'.$LANG["common"][27].' :</i></b>       '.$LANG["joblist"][11].' : '.$data["date"]);
			$pdf->displayLine('<b><i>'.$LANG["joblist"][2].' :</i></b> '.getPriorityName($data["priority"]));
			$pdf->displayLine('<b><i>'.$LANG["job"][4].' :</i></b> '.getUserName($data["author"]));
			$pdf->displayLine('<b><i>'.$LANG["job"][5].' :</i></b> '.getUserName($data["assign"]));
			$pdf->displayLine('<b><i>'.$LANG["common"][36].' :</i></b> '.$data["catname"]);
			$pdf->displayLine('<b><i>'.$LANG["common"][57].' :</i></b> '.$data["name"]);
		}
	} 
	
	$pdf->displaySpace();
}

function plugin_pdf_link($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$query="SELECT glpi_links.ID as ID, glpi_links.link as link, glpi_links.name as name , glpi_links.data as data from glpi_links INNER JOIN glpi_links_device ON glpi_links.ID= glpi_links_device.FK_links WHERE glpi_links_device.device_type=".$type." ORDER BY glpi_links.name";

	$result=$DB->query($query);
	
	$ci=new CommonItem;

	$pdf->setColumnsSize(100);
	if ($DB->numrows($result)>0){
		
		$pdf->displayTitle('<b>'.$LANG["title"][33].'</b>');
		
		//$pdf->setColumnsSize(25,75);
		while ($data=$DB->fetch_assoc($result)){
			
			$name=$data["name"];
			if (empty($name))
				$name=$data["link"];

			$link=$data["link"];
			$file=trim($data["data"]);
			$ci->getFromDB($type,$ID);
			if (empty($file)){

				if (strpos("[NAME]",$link)){
					$link=str_replace("[NAME]",$ci->getName(),$link);
				}
				if (strpos("[ID]",$link)){
					$link=str_replace("[ID]",$ID,$link);
				}

				if (strpos("[SERIAL]",$link)){
					if ($tmp=$ci->getField('serial')){
						$link=str_replace("[SERIAL]",$tmp,$link);
					}
				}
				if (strpos("[OTHERSERIAL]",$link)){
					if ($tmp=$ci->getField('otherserial')){
						$link=str_replace("[OTHERSERIAL]",$tmp,$link);
					}
				}

				if (strpos("[LOCATIONID]",$link)){
					if ($tmp=$ci->getField('location')){
						$link=str_replace("[LOCATIONID]",$tmp,$link);
					}
				}

				if (strpos("[LOCATION]",$link)){
					if ($tmp=$ci->getField('location')){
						$link=str_replace("[LOCATION]",plugin_pdf_getDropdownName("glpi_dropdown_locations",$tmp),$link);
					}
				}
				if (strpos("[NETWORK]",$link)){
					if ($tmp=$ci->getField('network')){
						$link=str_replace("[NETWORK]",plugin_pdf_getDropdownName("glpi_dropdown_network",$tmp),$link);
					}
				}
				if (strpos("[DOMAIN]",$link)){
					if ($tmp=$ci->getField('domain'))
						$link=str_replace("[DOMAIN]",plugin_pdf_getDropdownName("glpi_dropdown_domain",$tmp),$link);
				}
				$ipmac=array();
				$j=0;
				if (strstr($link,"[IP]")||strstr($link,"[MAC]")){
					$query2 = "SELECT ifaddr,ifmac FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = ".$type.") ORDER BY logical_number";
					$result2=$DB->query($query2);
					if ($DB->numrows($result2)>0)
						while ($data2=$DB->fetch_array($result2)){
							$ipmac[$j]['ifaddr']=$data2["ifaddr"];
							$ipmac[$j]['ifmac']=$data2["ifmac"];
							$j++;
						}
					if (count($ipmac)>0){ // One link per network address
						foreach ($ipmac as $key => $val){
							$tmplink=$link;
							$tmplink=str_replace("[IP]",$val['ifaddr'],$tmplink);
							$tmplink=str_replace("[MAC]",$val['ifmac'],$tmplink);
							$pdf->displayLink("$name - $tmplink", $tmplink);						
						}
					}
				} else { // Single link (not network info)
					$pdf->displayLink("$name - $link", $link);						
				} 
			} else { // Generated File
				//$link=$data['name'];		
				$ci->getFromDB($type,$ID);

				// Manage Filename
				if (strstr($link,"[NAME]")){
					$link=str_replace("[NAME]",$ci->getName(),$link);
				}

				if (strstr($link,"[LOGIN]")){
					if (isset($_SESSION["glpiname"])){
						$link=str_replace("[LOGIN]",$_SESSION["glpiname"],$link);
					}
				}

				if (strstr($link,"[ID]")){
					$link=str_replace("[ID]",$_GET["ID"],$link);
				}
				$pdf->displayLine("$name - $link");		
			}
		} // Each link
	} else {
		$pdf->displayTitle('<b>'.$LANG["links"][7].'</b>');
	}
	
	$pdf->displaySpace();
}

function plugin_pdf_volume($pdf,$ID,$type){
	
	global $DB, $LANG;
	
	$query = "SELECT glpi_dropdown_filesystems.name as fsname, glpi_computerdisks.* 
		FROM glpi_computerdisks
		LEFT JOIN glpi_dropdown_filesystems ON (glpi_computerdisks.FK_filesystems = glpi_dropdown_filesystems.ID)
		WHERE (FK_computers = '$ID')";

	$result=$DB->query($query);

	$pdf->setColumnsSize(100);
	if ($DB->numrows($result)>0){
		
		$pdf->displayTitle("<b>".$LANG['computers'][8]."</b>");

		$pdf->setColumnsSize(22,23,22,11,11,11);
		$pdf->displayTitle(
			'<b>'.$LANG['common'][16].'</b>',
			'<b>'.$LANG['computers'][6].'</b>',
			'<b>'.$LANG['computers'][5].'</b>',
			'<b>'.$LANG['common'][17].'</b>',
			'<b>'.$LANG['computers'][3].'</b>',
			'<b>'.$LANG['computers'][2].'</b>'
			);
		
		$pdf->setColumnsAlign('left','left','left','center','right','right');
		
		while ($data=$DB->fetch_assoc($result)){

		$pdf->displayLine(		
			'<b>'.utf8_decode(empty($data['name'])?$data['ID']:$data['name']).'</b>',
			$data['device'],
			$data['mountpoint'],
			$data['fsname'],
			formatNumber($data['totalsize'], false, 0)." ".$LANG['common'][82],
			formatNumber($data['freesize'], false, 0)." ".$LANG['common'][82]
			);
		}
	} else {
		$pdf->displayTitle("<b>".$LANG['computers'][8] . " - " . $LANG['search'][15]."</b>");
	}

	$pdf->displaySpace();
}

function plugin_pdf_note($pdf,$ID,$type){
	
	global $LANG;
	
	$ci =new CommonItem;
	$ci->getfromDB ($type,$ID);
	
	$note = trim($ci->getField('notes'));
	
	$pdf->setColumnsSize(100);
	if(utf8_strlen($note)>0)
		{
		$pdf->displayTitle('<b>'.$LANG["title"][37].'</b>');
		$pdf->displayText('', $note, 10);		
	} else {
		$pdf->displayTitle('<b>'.$LANG['plugin_pdf']["note"][1].'</b>');
	}
		
	$pdf->displaySpace();
}

function plugin_pdf_reservation($pdf,$ID,$type){
	
	global $DB,$LANG,$CFG_GLPI;
	
	$resaID=0;
	$user = new User();	
	
	$pdf->setColumnsSize(100);
	if ($resaID=isReservable($type,$ID)) {

		$now=$_SESSION["glpi_currenttime"];
		$query = "SELECT * FROM glpi_reservation_resa WHERE end > '".$now."' AND id_item='$resaID' ORDER BY begin";
		$result=$DB->query($query);
		
		$pdf->setColumnsSize(100);
		$pdf->displayTitle("<b>".$LANG["reservation"][35]."</b>");
				
		if (!$DB->numrows($result)) {	
			$pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
			
		} else {
			$pdf->setColumnsSize(14,14,26,46);						
			$pdf->displayTitle(
				'<i>'.$LANG["search"][8].'</i>',
				'<i>'.$LANG["search"][9].'</i>',
				'<i>'.$LANG["reservation"][31].'</i>',
				'<i>'.$LANG["common"][25].'</i>');
						
			while ($data=$DB->fetch_assoc($result))	{
				if ($user->getFromDB($data["id_user"])) {
					$name = formatUserName($user->fields["ID"],$user->fields["name"],$user->fields["realname"],$user->fields["firstname"]);					
				} else {
					$name = "(".$data["id_user"].")";
				}
				$pdf->displayLine(convDateTime($data["begin"]),	convDateTime($data["end"]),
					$name, str_replace(array("\r","\n")," ",$data["comment"]));								
			}
		}
		
		$query = "SELECT * FROM glpi_reservation_resa WHERE end <= '".$now."' AND id_item='$resaID' ORDER BY begin DESC";
		$result=$DB->query($query);

		$pdf->setColumnsSize(100);
		$pdf->displayTitle("<b>".$LANG["reservation"][36]."</b>");

		if (!$DB->numrows($result))	{	
			$pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
		} else	{
			$pdf->setColumnsSize(14,14,26,46);	
			$pdf->displayTitle(
				'<i>'.$LANG["search"][8].'</i>',
				'<i>'.$LANG["search"][9].'</i>',
				'<i>'.$LANG["reservation"][31].'</i>',
				'<i>'.$LANG["common"][25].'</i>');
			
			while ($data=$DB->fetch_assoc($result))	{
				if ($user->getFromDB($data["id_user"])) {
					$name = formatUserName($user->fields["ID"],$user->fields["name"],$user->fields["realname"],$user->fields["firstname"]);					
				} else {
					$name = "(".$data["id_user"].")";
				}
				$pdf->displayLine(convDateTime($data["begin"]),	convDateTime($data["end"]),
					$name, str_replace(array("\r","\n")," ",$data["comment"]));								
			}
		}

	} else { // Not isReservable
		$pdf->displayTitle("<b>".$LANG["reservation"][34]."</b>");
	}
		
	$pdf->displaySpace();
}

function plugin_pdf_history($pdf,$ID,$type){
	
	global $DB,$LANG;
	
	$SEARCH_OPTION=getSearchOptions();
	
	$query="SELECT * FROM glpi_history WHERE FK_glpi_device='".$ID."' AND device_type='".$type."' ORDER BY  ID DESC;";
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	
	$pdf->setColumnsSize(100);
	$pdf->displayTitle("<b>".$LANG["title"][38]."</b>");
	
	
	if ($number>0) {
		//$pdf->setColumnsSize(9,14,15,15,47);
		$pdf->setColumnsSize(14,15,20,51);
		$pdf->displayTitle(
			//'<b><i>'.$LANG["common"][2].'</i></b>',
			'<b><i>'.$LANG["common"][27].'</i></b>',
			'<b><i>'.$LANG["common"][34].'</i></b>',
			'<b><i>'.$LANG["event"][18].'</i></b>',
			'<b><i>'.$LANG["event"][19].'</i></b>');

		while ($data =$DB->fetch_array($result)){
			$field="";
			if($data["linked_action"]){
				switch ($data["linked_action"]){
	
					case HISTORY_ADD_DEVICE :
						$field = getDictDeviceLabel($data["device_internal_type"]);
						$change = $LANG["devices"][25]." ".$data[ "new_value"];	
						break;
	
					case HISTORY_UPDATE_DEVICE :
						$field = getDictDeviceLabel($data["device_internal_type"]);
						$change = getDeviceSpecifityLabel($data["device_internal_type"]).$data[ "old_value"].$data[ "new_value"];	
						break;
	
					case HISTORY_DELETE_DEVICE :
						$field = getDictDeviceLabel($data["device_internal_type"]);
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
			} else { // Not a linked_action
				$fieldname="";
				foreach($SEARCH_OPTION[COMPUTER_TYPE] as $key2 => $val2)
					if($key2==$data["id_search_option"]){
						$field = $val2["name"];
						$fieldname = $val2["field"];
					}
					
				if ($fieldname=="comments")
					$change = $LANG["log"][64];
				else
					$change = str_replace("&nbsp;"," ",$data["old_value"])." > ".str_replace("&nbsp;"," ",$data["new_value"]);
			}
			
			$pdf->displayLine(
				//$data["ID"],
				convDateTime($data["date_mod"]),
				$data["user_name"],
				$field,
				$change);
		} // Each log
	} else {
		$pdf->displayTitle("<b>".$LANG["event"][20]."</b>");
	}

	$pdf->displaySpace();
}

function plugin_pdf_general($type, $tab_id, $tab, $page=0) {

$pdf = new simplePDF('a4', ($page ? 'landscape' : 'portrait'));

$nb_id = count($tab_id);

foreach($tab_id as $key => $ID)
	{
	switch($type){
		case COMPUTER_TYPE:
			
			plugin_pdf_add_header($pdf,$ID,COMPUTER_TYPE);
			$pdf->newPage();
			plugin_pdf_config_computer($pdf,$ID);
			
			for($i=0;$i<count($tab);$i++)
			{
				switch($tab[$i]){
					case 0:
						plugin_pdf_financial($pdf,$ID,COMPUTER_TYPE);
						plugin_pdf_contract ($pdf,$ID,COMPUTER_TYPE);
						break;
					case 1:
						plugin_pdf_connection($pdf,$ID,COMPUTER_TYPE);
						plugin_pdf_port($pdf,$ID,COMPUTER_TYPE);
						break;
					case 2:
						plugin_pdf_device($pdf,$ID,COMPUTER_TYPE);
						break;
					case 3:
						plugin_pdf_software($pdf,$ID,COMPUTER_TYPE);
						break;
					case 4:
						plugin_pdf_ticket($pdf,$ID,COMPUTER_TYPE);
						plugin_pdf_oldticket($pdf,$ID,COMPUTER_TYPE);
						break;
					case 5:
						plugin_pdf_document($pdf,$ID,COMPUTER_TYPE);
						break;
					case 6:
						plugin_pdf_registry($pdf,$ID,COMPUTER_TYPE);
						break;
					case 7:
						plugin_pdf_link($pdf,$ID,COMPUTER_TYPE);
						break;
					case 8:
						plugin_pdf_note($pdf,$ID,COMPUTER_TYPE);
						break;
					case 9:
						plugin_pdf_reservation($pdf,$ID,COMPUTER_TYPE);
						break;
					case 10:
						plugin_pdf_history($pdf,$ID,COMPUTER_TYPE);
						break;
					case 11:
						plugin_pdf_volume($pdf,$ID,COMPUTER_TYPE);
						break;
				}
			}
		break;
		
		case SOFTWARE_TYPE:
		
			plugin_pdf_add_header($pdf,$ID,SOFTWARE_TYPE);
			$pdf->newPage();
			// plugin_pdf_config_software($pdf,$ID);
			
			for($i=0;$i<count($tab);$i++)
			{
				switch($tab[$i]){
					case 0:
						//plugin_pdf_licenses($pdf,$ID,0,SOFTWARE_TYPE);
						break;
					case 1:
						//plugin_pdf_licenses($pdf,$ID,1,SOFTWARE_TYPE);
						break;
					case 2:
						plugin_pdf_financial($pdf,$ID,SOFTWARE_TYPE);
						plugin_pdf_contract($pdf,$ID,SOFTWARE_TYPE);
						break;
					case 3:
						plugin_pdf_document($pdf,$ID,SOFTWARE_TYPE);
						break;
					case 4:
						plugin_pdf_ticket($pdf,$ID,SOFTWARE_TYPE);
						plugin_pdf_oldticket($pdf,$ID,SOFTWARE_TYPE);
						break;
					case 5:
						plugin_pdf_link($pdf,$ID,SOFTWARE_TYPE);
						break;
					case 6:
						plugin_pdf_note($pdf,$ID,SOFTWARE_TYPE);
						break;
					case 7:
						plugin_pdf_reservation($pdf,$ID,SOFTWARE_TYPE);
						break;
					case 8:
						plugin_pdf_history($pdf,$ID,SOFTWARE_TYPE);
						break;
				}
			}
		break;
		}
	}
$pdf->render();	
}

?>
