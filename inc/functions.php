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

/**
 * Display the Page header = type name, object name, entity name
 *
 * @param $pdf object for output (SimplePDF)
 * @param $ID of the item
 * @param $type of the item
 *
 * @return boolean : true if object found and readable
 */
function plugin_pdf_add_header($pdf,$ID,$item) {
   global $LANG;

   $entity = '';
   if ($item->getFromDB($ID) && $item->can($ID,'r')) {
      if (get_class($item)!='Ticket' && $item->fields['name']) {
         $name = $item->fields['name'];
      } else {
         $name = $LANG["common"][2].' '.$ID;
      }
      if (isMultiEntitiesMode() && isset($item->fields['entities_id'])) {
         $entity = ' ('.html_clean(Dropdown::getDropdownName('glpi_entities',
                                                             $item->fields['entities_id'])).')';
      }
      $pdf->setHeader($item->getTypeName()." - <b>$name</b>$entity");

      return true;
   }
   return false;
}


function plugin_pdf_main_ticket($pdf,$job,$private) {
   global $LANG,$CFG_GLPI, $PDF,$DB;

   $ID = $job->getField('id');
   if (!haveRight("show_all_ticket","1")
       && $job->fields["users_id"] != $_SESSION["glpiID"]
       && $job->fields["users_id_assign"] != $_SESSION["glpiID"]
       && !(haveRight("show_group_ticket",'1')
            && in_array($job->fields["groups_id"],$_SESSION["glpigroups"]))
       && !(haveRight("show_assign_ticket",'1')
            && in_array($job->fields["groups_id_assign"],$_SESSION["glpigroups"]))){
      return false;
   }

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.
            (empty($job->fields["name"])?$LANG['reminder'][15]:$name=$job->fields["name"]).'</b>');

   $author_name='';
   if ($job->fields["users_id"]) {
      $author = new User();
      $author->getFromDB($job->fields["users_id"]);
      $author_name = $author->getName();
   }

   $recipient_name='';
   if ($job->fields["users_id_recipient"]) {
      $recipient = new User();
      $recipient->getFromDB($job->fields["users_id_recipient"]);
      $recipient_name = $recipient->getName();
   }

   $assign_name='';
   if ($job->fields["users_id_assign"]) {
      $assign = new User();
      $assign->getFromDB($job->fields["users_id_assign"]);
      $assign_name = $assign->getName();
   }

   $serial_item = '';
   $location_item = '';
   if ($job->fields["itemtype"] && class_exists($job->fields["itemtype"])) {
      $item = new $job->fields["itemtype"]();
      if ($item->getFromDB($job->fields["items_id"])) {
         if (isset($item->fields["serial"])) {
            $serial_item =
               " <b><i>".$LANG['common'][19]."</i></b> : ".
                              html_clean($item->fields["serial"]);
         }
         if (isset($item->fields["locations_id"])) {
            $location_item =
               " <b><i>".$LANG['common'][15]."</i></b> : ".
                  html_clean(Dropdown::getDropdownName("glpi_locations",
                                                       $item->fields["locations_id"]));
         }
      }
   }

   if (count($_SESSION['glpiactiveentities'])>1) {
      $entity = " (".Dropdown::getDropdownName("glpi_entities",$job->fields["entities_id"]).")";
   } else {
      $entity = '';
   }
   //closedate
   if (!strstr($job->fields["status"],"old_")) {
      $closedate = "";
   } else {
      $closedate = $LANG['joblist'][12]." : ".convDateTime($job->fields["closedate"]);
   }

   $pdf->setColumnsSize(50,50);
   $pdf->displayLine("<b><i>".$LANG['joblist'][11]."</i></b> : ".convDateTime($job->fields["date"]).
                    " ".$LANG['job'][2]." ".$recipient_name,$closedate);

   $pdf->setColumnsSize(33,34,33);
   //row status / RequestType / realtime
   $pdf->displayLine(
      "<b><i>".$LANG['joblist'][0]."</i></b> : ".
            html_clean($job->getStatus($job->fields["status"])),
      "<b><i>".$LANG['job'][44]."</i></b> : ".
            html_clean(Dropdown::getDropdownName('glpi_requesttypes',
                                                 $job->fields['requesttypes_id'])),
      "<b><i>".$LANG['job'][20]."</i></b> : ".getRealtime($job->fields["realtime"]));

   //row3 (author / attribute / cost_time)
   $pdf->displayLine(
      "<b><i>".$LANG['job'][4]."</i></b> : ".html_clean($author_name),
      "<b><i>".$LANG['job'][5]."</i></b> :",
      "<b><i>".$LANG['job'][40]."</i></b> : ".formatNumber($job->fields["cost_time"]));

   //row4 (group / assign / cost_fixed)
   $pdf->displayLine(
      "<b><i>".$LANG['common'][35]."</i></b> : ".
            html_clean(Dropdown::getDropdownName("glpi_groups", $job->fields["groups_id"])),
      "<b><i>".$LANG['job'][6]."</i></b> : ".html_clean($assign_name),
      "<b><i>".$LANG['job'][41]."</i></b> : ".formatNumber($job->fields["cost_fixed"]));

   //row5 (priority / assign_ent / cost_material)
   $pdf->displayLine(
      "<b><i>".$LANG['joblist'][2]."</i></b> : ".
            html_clean(Ticket::getPriorityName($job->fields["priority"])),
      "<b><i>".$LANG['common'][35]."</i></b> : ".
            html_clean(Dropdown::getDropdownName("glpi_groups", $job->fields["groups_id_assign"])),
      "<b><i>".$LANG['job'][42]."</i></b> : ".formatNumber($job->fields["cost_material"]));

   //row6 (category / assign_ent / TotalCost)
   $pdf->displayLine(
      "<b><i>".$LANG['common'][36]."</i></b> : ".
            html_clean(Dropdown::getDropdownName("glpi_ticketcategories",
                                                 $job->fields["ticketcategories_id"])),
      "<b><i>".$LANG['financial'][26]."</i></b> : ".
            html_clean(Dropdown::getDropdownName("glpi_suppliers",
                                                 $job->fields["suppliers_id_assign"])),
      "<b><i>".$LANG['job'][43]."</i></b> : ".Ticket ::trackingTotalCost($job->fields["realtime"],
                                                                $job->fields["cost_time"],
                                                                $job->fields["cost_fixed"],
                                                                $job->fields["cost_material"]));

   $pdf->setColumnsSize(100);
   if ($job->fields["itemtype"] && class_exists($job->fields["itemtype"])) {
      $pdf->displayLine(
         "<b><i>".$LANG['common'][1]."</i></b> : ".html_clean($item->getTypeName())." ".
               html_clean($item->getNameID()).$serial_item . $location_item);
   } else {
      $pdf->displayLine("<b><i>".$LANG['common'][1]."</i></b>");
   }
   $pdf->displaySpace();

   //////////////followups///////////
   $pdf->displayTitle("<b>".$LANG['job'][37]."</b>");

   $RESTRICT = "";
   if (!$private) {
      // Don't show private'
      $RESTRICT=" AND `is_private` = '0' ";
   }
   if (!haveRight("show_full_ticket","1")) {
      // No right, only show connected user private one
      $RESTRICT=" AND (`is_private` = '0'
                       OR `users_id` ='".$_SESSION["glpiID"]."' ) ";
   }

   $query = "SELECT *
             FROM `glpi_ticketfollowups`
             WHERE `tickets_id` = '$ID'
             $RESTRICT
             ORDER BY `date` DESC";
   $result=$DB->query($query);

   if (!$DB->numrows($result)) {
      $pdf->displayLine($LANG['job'][12]);
   } else {
      while ($data=$DB->fetch_array($result)) {
         $pdf->setColumnsSize(15,15,15,55);
         $pdf->displayTitle("<b><i>".$LANG['common'][27]."</i></b>", // Date
                            "<b><i>".$LANG['common'][37]."</i></b>", // Author
                            "<b><i>".$LANG['job'][31]."</i></b>",    // Durée
                            "<b><i>".$LANG['job'][35]."</i></b>");   // Plan

         $realtime = '';
         $hour = floor($data["realtime"]);
         $minute = round(($data["realtime"]-$hour)*60,0);
         if ($hour) {
            $realtime = "$hour ".$LANG['job'][21];
         }
         if ($minute || !$hour) {
            $realtime .= " $minute ".$LANG['job'][22];
         }

         $query2 = "SELECT *
                    FROM `glpi_ticketplannings`
                    WHERE `ticketfollowups_id` = '".$data['id']."'";
         $result2=$DB->query($query2);

         if ($DB->numrows($result2)==0) {
            $planification=$LANG['job'][32];
         } else {
            $data2 = $DB->fetch_array($result2);
            $planification = Planning::getState($data2["state"])." - ".convDateTime($data2["begin"]).
                             " -> ".convDateTime($data2["end"])." - ".getUserName($data2["users_id"]);
         }

         $pdf->setColumnsSize(15,15,15,55);
         $pdf->displayLine(
         convDateTime($data["date"]),
         html_clean(getUserName($data["users_id"])),$realtime,$planification);
         $pdf->displayText("<b><i>".$LANG['joblist'][6]."</i></b>&nbsp;: ", $data["content"]);
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_main_computer($pdf,$computer) {
   global $LANG;

   $ID = $computer->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$computer->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.convDateTime($computer->fields['date_mod']);
   if(!empty($computer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$computer->fields['template_name'].')';
   } else if($computer->fields['is_ocs_import']) {
      $col2 = ' ('.$LANG['ocsng'][7].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$computer->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_states',$computer->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_locations', $computer->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_computertypes',
                                              $computer->fields['computertypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($computer->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $computer->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$computer->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_computermodels',
                                              $computer->fields['computermodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$computer->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$computer->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($computer->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$computer->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_groups',$computer->fields['groups_id'])),
      '<b><i>'.$LANG['setup'][88].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_networks', $computer->fields['networks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['setup'][89].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_domains', $computer->fields['domains_id'])),
      '<b><i>'.$LANG['computers'][53].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_operatingsystemservicepacks',
                                              $computer->fields['operatingsystemservicepacks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['computers'][9].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                              $computer->fields['operatingsystems_id'])),
      '<b><i>'.$LANG['computers'][52].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_operatingsystemversions',
                                              $computer->fields['operatingsystemversions_id'])));


   $pdf->displayLine(
      '<b><i>'.$LANG['computers'][11].' :</i></b> '.$computer->fields['os_licenseid'],
      '<b><i>'.$LANG['computers'][10].' :</i></b> '.$computer->fields['os_license_number']);

   if ($computer->fields['is_ocs_import']) {
      $col1 = '<b><i>'.$LANG['ocsng'][6].' '.$LANG['Menu'][33].' :</i></b> '.$LANG['choice'][1];
   } else {
      $col1 = '<b><i>'.$LANG['ocsng'][6].' '.$LANG['Menu'][33].' :</i></b> '.$LANG['choice'][0];
   }

   $pdf->displayLine($col1,'<b><i>'.$LANG['computers'][51].' :</i></b> '.
      html_clean(Dropdown::getDropdownName('glpi_autoupdatesystems',
                                           $computer->fields['autoupdatesystems_id'])));

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $computer->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_printer($pdf,$printer) {
   global $LANG;

   $ID = $printer->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$printer->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.convDateTime($printer->fields['date_mod']);
   if(!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$printer->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$printer->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_states', $printer->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_locations', $printer->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_printertypes',
                                              $printer->fields['printertypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($printer->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $printer->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$printer->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_printermodels',
                                              $printer->fields['printermodels_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][18].' :</i></b> '.$printer->fields['contact'],
      '<b><i>'.$LANG['common'][19].' :</i></b> '.$printer->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($printer->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$printer->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_groups', $printer->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
         ($printer->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine(
      '<b><i>'.$LANG['setup'][89].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_domains', $printer->fields['domains_id'])),
     '<b><i>'.$LANG['setup'][88].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_networks', $printer->fields['networks_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['devices'][6].' :</i></b> '.$printer->fields['memory_size'],
      '<b><i>'.$LANG['printers'][30].' :</i></b> '.$printer->fields['init_pages_counter']);

   $opts=array();
   if ($printer->fields['have_serial']) {
      $opts[] = $LANG['printers'][14];
   }
   if ($printer->fields['have_parallel']) {
      $opts[] = $LANG['printers'][15];
   }
   if ($printer->fields['have_usb']) {
   $opts[] = $LANG['printers'][27];
   }

   $pdf->displayLine('<b><i>'.$LANG['printers'][18].' : </i></b>'.implode(', ',$opts));


   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $printer->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_monitor($pdf,$item) {
   global $LANG;

   $ID = $item->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_monitortypes',
                                                 $item->fields['monitortypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                 $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_monitormodels',
                                                 $item->fields['monitormodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
            ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine( '<b><i>'.$LANG['monitors'][21].' :</i></b> '.$item->fields['size']);


   $opts = array();
   if ($item->fields['have_micro']) {
      $opts[] = $LANG['monitors'][14];
   }
   if ($item->fields['have_speaker']) {
      $opts[] = $LANG['monitors'][15];
   }
   if ($item->fields['have_subd']) {
      $opts[] = $LANG['monitors'][19];
   }
   if ($item->fields['have_bnc']) {
      $opts[] = $LANG['monitors'][20];
   }
   if ($item->fields['have_dvi']) {
      $opts[] = $LANG['monitors'][32];
   }
   if ($item->fields['have_pivot']) {
      $opts[] = $LANG['monitors'][33];
   }

   $pdf->displayLine(
      '<b><i>'.$LANG['monitors'][18].' : </i></b>'.implode(', ',$opts));

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_phone($pdf,$item) {
   global $LANG;

   $ID = $item->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_pĥonetypes', $item->fields['phonetypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                 $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_phonemodels',
                                                 $item->fields['phonemodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
            html_clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
            ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine(
      '<b><i>'.$LANG['phones'][18].' :</i></b> '.$item->fields['brand'],
      '<b><i>'.$LANG['phones'][36].' :</i></b> '.Dropdown::getYesNo($item->fields['phonepowersupplies_id']));

   $pdf->displayLine('<b><i>'.$LANG['setup'][71].' :</i></b> '.$item->fields['firmware'],
                     '<b><i>'.$LANG['phones'][40].' :</i></b> '.$item->fields['number_line']);

   $opts = array();
   if ($item->fields['have_headset']) {
      $opts[] = $LANG['phones'][38];
   }
   if ($item->fields['have_hp']) {
      $opts[] = $LANG['phones'][39];
   }

   $pdf->displayLine('<b><i>'.$LANG['monitors'][18].' : </i></b>'.implode(', ',$opts));

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_main_peripheral($pdf,$item) {
   global $LANG;

   $ID = $item->getField('id');

   $pdf->setColumnsSize(50,50);
   $col1 = '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>';
   $col2 = $LANG['common'][26].' : '.convDateTime($item->fields['date_mod']);

   if (!empty($printer->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$item->fields['template_name'].')';
   }
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$item->fields['name'],
      '<b><i>'.$LANG['state'][0].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_states', $item->fields['states_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_locations', $item->fields['locations_id'])),
      '<b><i>'.$LANG['common'][17].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_peripheraltypes',
                                              $item->fields['peripheraltypes_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($item->fields['users_id_tech']),
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $item->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][21].' :</i></b> '.$item->fields['contact_num'],
      '<b><i>'.$LANG['common'][22].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_peripheralmodels',
                                              $item->fields['peripheralmodels_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][18].' :</i></b> '.$item->fields['contact'],
                     '<b><i>'.$LANG['common'][19].' :</i></b> '.$item->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($item->fields['users_id']),
      '<b><i>'.$LANG['common'][20].' :</i></b> '.$item->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_groups', $item->fields['groups_id'])),
      '<b><i>'.$LANG['peripherals'][33].' :</i></b> '.
         ($item->fields['is_global']?$LANG['peripherals'][31]:$LANG['peripherals'][32]));

   $pdf->displayLine('<b><i>'.$LANG['phones'][18].' :</i></b> '.$item->fields['brand']);

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_cartridges($pdf, $p, $old=false) {
   global $DB,$CFG_GLPI, $LANG;

   $instID = $p->getField('id');

   if (!haveRight("cartridge","r")) {
      return false;
   }

   $dateout = "IS NULL ";
   if ($old) {
      $dateout = " IS NOT NULL ";
   }
   $query = "SELECT `glpi_cartridgeitems`.`id` AS tid,
                    `glpi_cartridgeitems`.`ref`,
                    `glpi_cartridgeitems`.`name`,
                    `glpi_cartridges`.`id`,
                    `glpi_cartridges`.`pages`,
                    `glpi_cartridges`.`date_use`,
                    `glpi_cartridges`.`date_out`,
                    `glpi_cartridges`.`date_in`
             FROM `glpi_cartridges`, `glpi_cartridgeitems`
             WHERE `glpi_cartridges`.`date_out` $dateout
                   AND `glpi_cartridges`.`printers_id` = '$instID'
                   AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
             ORDER BY `glpi_cartridges`.`date_out` ASC,
                      `glpi_cartridges`.`date_use` DESC,
                      `glpi_cartridges`.`date_in`";

   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = 0;
   $pages=$p->fields['init_pages_counter'];

   $pdf->setColumnsSize(100);
   $pdf->displayTitle("<b>".($old ? $LANG['cartridges'][35] : $LANG['cartridges'][33] )."</b>");

   if (!$number) {
      $pdf->displayLine($LANG['search'][15]);
   } else {
      $pdf->setColumnsSize(25,13,12,12,12,26);
      $pdf->displayTitle('<b><i>'.$LANG['cartridges'][12],
                                  $LANG['consumables'][23],
                                  $LANG['cartridges'][24],
                                  $LANG['consumables'][26],
                                  $LANG['search'][9],
                                  $LANG['cartridges'][39].'</b></i>');

      $stock_time = 0;
      $use_time = 0;
      $pages_printed = 0;
      $nb_pages_printed = 0;
      while ($data=$DB->fetch_array($result)) {
         $date_in  = convDate($data["date_in"]);
         $date_use = convDate($data["date_use"]);
         $date_out = convDate($data["date_out"]);

         $col1 = $data["name"]." - ".$data["ref"];
         $col2 = Cartridge::getStatus($data["date_use"], $data["date_out"]);
         $col6 = '';

         $tmp_dbeg = explode("-",$data["date_in"]);
         $tmp_dend = explode("-",$data["date_use"]);

         $stock_time_tmp = mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
                           - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
         $stock_time += $stock_time_tmp;

         if ($old) {
            $tmp_dbeg = explode("-",$data["date_use"]);
            $tmp_dend = explode("-",$data["date_out"]);

            $use_time_tmp = mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
                            - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
            $use_time += $use_time_tmp;

            $col6 = $data['pages'];

            if ($pages < $data['pages']) {
               $pages_printed += $data['pages'] - $pages;
               $nb_pages_printed++;
               $col6 .= " (". ($data['pages']-$pages)." ".$LANG['printers'][31].")";
               $pages = $data['pages'];
            }
         }
         $pdf->displayLine($col1, $col2, $date_in, $date_use, $date_out, $col6);
      } // Each cartridge
   }

   if ($old) {
      if ($number > 0) {
         if ($nb_pages_printed == 0) {
            $nb_pages_printed = 1;
         }

         $pdf->setColumnsSize(33,33,34);
         $pdf->displayTitle(
            "<b><i>".$LANG['cartridges'][40]." :</i></b> ".
               round($stock_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57],
            "<b><i>".$LANG['cartridges'][41]." :</i></b> ".
               round($use_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57],
            "<b><i>".$LANG['cartridges'][42]." :</i></b> ".round($pages_printed/$nb_pages_printed));
      }
      $pdf->displaySpace();
   }
}


function plugin_pdf_financial($pdf,$item) {
   global $CFG_GLPI,$LANG;

   $ID = $item->getField('id');

   if (!haveRight("infocom","r")) {
      return false;
   }

   $ic = new Infocom();

   $pdf->setColumnsSize(100);
   if ($ic->getFromDBforDevice(get_class($item),$ID)) {
      $pdf->displayTitle("<b>".$LANG["financial"][3]."</b>");

      $pdf->setColumnsSize(50,50);
      $pdf->displayLine(
         "<b><i>".$LANG["financial"][26]." :</i></b> ".
            html_clean(Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"])),
         "<b><i>".$LANG["financial"][82]." :</i></b> ".$ic->fields["bill"]);

      $pdf->displayLine("<b><i>".$LANG["financial"][18]." :</i></b> ".$ic->fields["order_number"],
                        "<b><i>".$LANG["financial"][19]." :</i></b> ".$ic->fields["delivery_number"]);

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][14]." :</i></b> ".convDate($ic->fields["buy_date"]),
         "<b><i>".$LANG["financial"][76]." :</i></b> ".convDate($ic->fields["use_date"]));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][15]." :</i></b> ".
               $ic->fields["warranty_duration"]." mois <b><i>Expire le</i></b> ".
               getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]),
         "<b><i>".$LANG["financial"][87]." :</i></b> ".
               html_clean(Dropdown::getDropdownName("glpi_budgets", $ic->fields["budgets_id"])));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][78]." :</i></b> ".formatNumber($ic->fields["warranty_value"]),
         "<b><i>".$LANG["financial"][16]." :</i></b> ".$ic->fields["warranty_info"]);

      $pdf->displayLine(
         "<b><i>".$LANG["rulesengine"][13]." :</i></b> ".formatNumber($ic->fields["value"]),
         "<b><i>".$LANG["financial"][81]." :</i></b> ".Infocom::Amort($ic->fields["sink_type"],
                                                                      $ic->fields["value"],
                                                                      $ic->fields["sink_time"],
                                                                      $ic->fields["sink_coeff"],
                                                                      $ic->fields["buy_date"],
                                                                      $ic->fields["use_date"],
                                                                      $CFG_GLPI['date_tax'],"n"));

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][20]." :</i></b> ".$ic->fields["immo_number"],
         "<b><i>".$LANG["financial"][22]." :</i></b> ".
               Infocom::getAmortTypeName($ic->fields["sink_type"]));

      $pdf->displayLine("<b><i>".$LANG["financial"][23]." :</i></b> ".$ic->fields["sink_time"]." ".
                                 $LANG['financial'][9],
                        "<b><i>".$LANG["financial"][77]." :</i></b> ".$ic->fields["sink_coeff"]);

      $pdf->displayLine(
         "<b><i>".$LANG["financial"][89]." :</i></b> ".Infocom::showTco($item->getField('ticket_tco'),
                                                                        $ic->fields["value"]),
         "<b><i>".$LANG["financial"][90]." :</i></b> ".Infocom::showTco($item->getField('ticket_tco'),
                                                                        $ic->fields["value"],
                                                                        $ic->fields["buy_date"]));

      $pdf->setColumnsSize(100);
      $col1 = "<b><i>".$LANG["setup"][247]." :</i></b> ";
      if ($ic->fields["alert"] == 0) {
         $col1 .= $LANG['choice'][0];
      } else if ($ic->fields["alert"] == 4) {
         $col1 .= $LANG["financial"][80];
      }
      $pdf->displayLine($col1);
      $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $ic->fields["comment"]);
   } else {
      $pdf->displayTitle("<b>".$LANG['plugin_pdf']['financial'][1]."</b>");
   }

   $pdf->displaySpace();
}


function plugin_pdf_main_software($pdf,$software) {
   global $LANG;

   $ID = $software->getField('id');

   $col1 = '<b>'.$LANG['common'][2].' '.$software->fields['id'].')</b>';
   $col2 = '<b>'.$LANG['common'][26].' : '.convDateTime($software->fields['date_mod']).'</b>';

   if (!empty($software->fields['template_name'])) {
      $col2 .= ' ('.$LANG['common'][13].' : '.$software->fields['template_name'].')';
   }

   $pdf->setColumnsSize(50,50);
   $pdf->displayTitle($col1, $col2);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].' :</i></b> '.$software->fields['name'],
      '<b><i>'.$LANG['common'][36].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_softwarecategories',
                                              $software->fields['softwarecategories_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][15].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_locations', $software->fields['locations_id'])),
      '<b><i>'.$LANG['software'][3].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_operatingsystems',
                                              $software->fields['operatingsystems_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][10].' :</i></b> '.getUserName($software->fields['users_id_tech']),
      '<b><i>'.$LANG['software'][46].' :</i></b> ' .
         ($software->fields['is_helpdesk_visible']?$LANG['choice'][1]:$LANG['choice'][0]));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][5].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                              $software->fields['manufacturers_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][34].' :</i></b> '.getUserName($software->fields['users_id']),
      '<b><i>'.$LANG['common'][35].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_groups', $software->fields['groups_id'])));

   if ($software->fields['softwares_id']>0) {
      $col2 = '<b><i> '.$LANG['pager'][2].' </i></b> '.
               html_clean(Dropdown::getDropdownName('glpi_softwares',
                                                    $software->fields['softwares_id']));
   } else {
      $col2 = '';
   }

   $pdf->displayLine(
      '<b><i>'.$LANG['software'][29].' :</i></b> '.
         ($software->fields['is_update']?$LANG['choice'][1]:$LANG['choice'][0]), $col2);

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $software->fields['comment']);

   $pdf->displaySpace();
}


function plugin_pdf_device($pdf,$computer) {
   global $LANG;

   $computer->getFromDBwithDevices($computer->getField('id'));

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG["title"][30].'</b>');

   $pdf->setColumnsSize(3,14,44,19,20);

   foreach ($computer->devices as $key => $val) {
      $device = new Device($val["devType"]);
      $device->getFromDB($val["devID"]);

      switch ($device->devtype) {
      case HDD_DEVICE :
         if (!empty($device->fields["rpm"])) {
            $col5 = '<b><i>'.$LANG["device_hdd"][0].' :</i></b> '.$device->fields["rpm"];
         } else if (!empty($device->fields["interfacetypes_id"])) {
            $col5 = '<b><i>'.$LANG["common"][65].' :</i></b> '.
                     html_clean(Dropdown::getDropdownName("glpi_interfacetypes",
                                                          $device->fields["interfacetypes_id"]));
         } else if (!empty($device->fields["cache"])) {
            $col5 = '<b><i>'.$LANG["device_hdd"][1].' :</i></b> '.$device->fields["cache"];
         } else {
            $col5 = '';
         }
         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][1], $device->fields["designation"],
                           '<b><i>'.$LANG["device_hdd"][4].' :</i></b> '.$val["specificity"], $col5);
         break;

      case GFX_DEVICE :
         $col4 = (empty($device->fields["interfacetype_id"]) ? '' : '<b><i>'.
                  $LANG["device_gfxcard"][0].' :</i></b> '.$device->fields["interfacetype_id"]);
         $col5 = (empty($device->fields["specif_default"]) ? '' :  '<b><i>'.
                  $LANG["common"][65].' :</i></b> '.$device->fields["specif_default"]);

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][2], $device->fields["designation"],
                           $col4, $col5);
         break;

      case NETWORK_DEVICE :
         $col4 = (empty($device->fields["bandwidth"]) ? '' : '<b><i>'.
                  $LANG["device_iface"][0].' :</i></b> '.$device->fields["bandwidth"]);

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][3], $device->fields["designation"],
                           $col4,'<b><i>'.$LANG["networking"][15].' :</i></b> '.$val["specificity"]
                           );
         break;

      case MOBOARD_DEVICE :
         $col4 = (empty($device->fields["chipset"]) ? '' : '<b><i>'.
                  $LANG["device_moboard"][0].' :</i></b> '.$device->fields["chipset"]);
         $col5 = '';

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][5], $device->fields["designation"],
                           $col4, $col5);
         break;

      case PROCESSOR_DEVICE :
         $col5 = '';
         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][4], $device->fields["designation"],
                           '<b><i>'.$LANG["device_ram"][1].' :</i></b> '.$val["specificity"], $col5);
         break;

      case RAM_DEVICE :
         $col4 = (empty($device->fields["type"]) ? '' : '<b><i>'.
                  $LANG["common"][17].' :</i></b> '.
                  html_clean(Dropdown::getDropdownName("glpi_devicememories",
                                                       $device->fields["type"]))) .
                                 (empty($device->fields["frequence"]) ? '' : '<b><i>'.
                                  $LANG['device_ram'][1].' :</i></b> '.$device->fields["frequence"]);

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][6], $device->fields["designation"],
                           $col4, '<b><i>'.$LANG["monitors"][21].' :</i></b> '.$val["specificity"]);
         break;

      case SND_DEVICE :
         $col4 = (empty($device->fields["type"]) ? '' : '<b><i>'.$LANG["common"][17].' :</i></b> '.
                  $device->fields["type"]);
         $col5 = '';

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][7], $device->fields["designation"],
                           $col4, $col5);
         break;

      case DRIVE_DEVICE :
         if (!empty($device->fields["is_writer"])) {
            $col4 = '<b><i>'.$LANG["profiles"][11].' :</i></b> '.
                    Dropdown::getYesNo($device->fields["is_writer"]);
         } else if (!empty($device->fields["speed"])) {
            $col4 = '<b><i>'.$LANG["device_drive"][1].' :</i></b> '.$device->fields["speed"];
         } else if (!empty($device->fields["frequence"])) {
            $col4 = '<b><i>'.$LANG["device_ram"][1].' :</i></b> '.$device->fields["specif_default"];
         } else {
            $col4 = '';
         }
         $col5 = '';

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][19],
                           $device->fields["designation"], $col4, $col5);
         break;

      case CONTROL_DEVICE :
         $col4 = (empty($device->fields["interfacetypes_id"]) ? '' : '<b><i>'.
                  $LANG["common"][65].' :</i></b> '.
                  html_clean(Dropdown::getDropdownName("glpi_interfacetypes",
                                                       $device->fields["interfacetypes_id"])));
         $col5 = (empty($device->fields["is_raid"]) ? '' : '<b><i>'.
                  $LANG["device_control"][0].' :</i></b> '.Dropdown::getYesNo($device->fields["is_raid"]));

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][20],
                           $device->fields["designation"], $col4, $col5);
         break;

      case PCI_DEVICE :
         $col4 = '';
         $col5 = '';
         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][21],
                           $device->fields["designation"], $col4, $col5);
         break;

      case POWER_DEVICE :
         $col4 = (empty($device->fields["power"]) ? '' : '<b><i>'.
                  $LANG["device_power"][0].' :</i></b> '.$device->fields["power"]);
         $col5 = (empty($device->fields["is_atx"]) ? '' : '<b><i>'.
                  $LANG["device_power"][1].' :</i></b> '.Dropdown::getYesNo($device->fields["is_atx"]));

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][23],
                           $device->fields["designation"], $col4, $col5);
         break;

      case CASE_DEVICE :
         $col4 = (empty($device->fields["type"]) ? '' : '<b><i>'.
                  $LANG["common"][17].' :</i></b> '.
                  html_clean(Dropdown::getDropdownName("glpi_devicecasetypes",
                                                       $device->fields["type"])));
         $col5 = '';

         $pdf->displayLine($val["quantity"].'x', $LANG["devices"][22],
                           $device->fields["designation"], $col4, $col5);
         break;
      }
   } // each device

   $pdf->displaySpace();
}


function plugin_pdf_versions($pdf,$item){
   global $DB,$LANG;

   $sID = $item->getField('id');

   $query = "SELECT `glpi_softwareversions`.*,
                    `glpi_states`.`name` AS sname
             FROM `glpi_softwareversions`
             LEFT JOIN `glpi_states` ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
             WHERE (`softwares_id` = '$sID')
             ORDER BY `name`";

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG['software'][5].'</b>');

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         $pdf->setColumnsSize(20,20,15,45);
         $pdf->displayTitle('<b><i>'.$LANG['software'][5].'</i></b>',
                            '<b><i>'.$LANG['state'][0].'</i></b>',
                            '<b><i>'.$LANG['software'][19].'</i></b>',
                            '<b><i>'.$LANG['common'][25].'</i></b>');
         $pdf->setColumnsAlign('left','left','right','left');

         for ($tot=$nb=0 ; $data=$DB->fetch_assoc($result) ; $tot+=$nb) {
            $nb = Computer_SoftwareVersion::countForVersion($data['id']);
            $pdf->displayLine((empty($data['name'])?"(".$data['id'].")":$data['name']),
                              $data['sname'], $nb,
                              str_replace(array("\r","\n")," ",$data['comment']));
         }
         $pdf->setColumnsAlign('left','right','right','left');
         $pdf->displayTitle('',"<b>".$LANG['common'][33]." : </b>",$tot, '');
      } else {
         $pdf->displayLine($LANG['search'][15]);
      }
   } else {
      $pdf->displayLine($LANG['search'][15]."!");
   }
   $pdf->displaySpace();
}


function plugin_pdf_main_license($pdf,$license, $main=true) {
   global $DB,$LANG;

   $ID = $license->getField('id');

   $pdf->setColumnsSize(100);
   $entity = '';
   if (isMultiEntitiesMode() && !$main) {
      $entity = ' ('.html_clean(Dropdown::getDropdownName('glpi_entities',
                                                          $license->fields['entities_id'])).')';
   }
   $pdf->displayTitle('<b><i>'.$LANG['common'][2]."</i> : $ID</b>$entity");

   $pdf->setColumnsSize(50,50);

   $pdf->displayLine(
      '<b><i>'.$LANG['help'][31].'</i></b>: '.
         html_clean(Dropdown::getDropdownName('glpi_softwares', $license->fields['softwares_id'])),
      '<b><i>'.$LANG['common'][17].'</i></b>: '.
         html_clean(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                              $license->fields['softwarelicensetypes_id'])));

   $pdf->displayLine('<b><i>'.$LANG['common'][16].'</i></b>: '.$license->fields['name'],
                     '<b><i>'.$LANG['common'][19].'</i></b>: '.$license->fields['serial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['software'][1].'</i></b>: '.
         html_clean(Dropdown::getDropdownName('glpi_softwareversions',
                                              $license->fields['softwareversions_id_buy'])),
      '<b><i>'.$LANG['common'][20].'</i></b>: '.$license->fields['otherserial']);

   $pdf->displayLine(
      '<b><i>'.$LANG['software'][2].'</i></b>: '.
         html_clean(Dropdown::getDropdownName('glpi_softwareversions',
                                              $license->fields['softwareversions_id_use'])),
      '<b><i>'.$LANG['tracking'][29].'</i></b>: '.
         ($license->fields['number']>0?$license->fields['number']:$LANG['software'][4]));

   $pdf->displayLine(
      '<b><i>'.$LANG['help'][25].'</i></b>: '.
         ($license->fields['computers_id']?html_clean(Dropdown::getDropdownName('glpi_computers',
                                                            $license->fields['computers_id'])):''),
      '<b><i>'.$LANG['software'][32].'</i></b>: '.convDate($license->fields['expire']));

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $license->fields['comment']);

   if ($main) {
   $pdf->displaySpace();
   }
}


function plugin_pdf_main_version($pdf,$version) {
   global $DB,$LANG;

   $ID = $version->getField('id');

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b><i>'.$LANG['common'][2]."</i> : $ID</b>");

   $pdf->setColumnsSize(50,50);

   $pdf->displayLine(
      '<b><i>'.$LANG['common'][16].'</i></b>: '.$version->fields['name'],
      '<b><i>'.$LANG['help'][31].'</i></b>: '.
         html_clean(Dropdown::getDropdownName('glpi_softwares', $version->fields['softwares_id'])));

   $pdf->displayLine(
      '<b><i>'.$LANG["state"][0].' :</i></b> '.
         html_clean(Dropdown::getDropdownName('glpi_states', $version->fields['states_id'])),
      '');

   $pdf->setColumnsSize(100);
   $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $version->fields['comment']);
   $pdf->displaySpace();
}


function plugin_pdf_licenses($pdf,$software,$infocom) {
   global $DB,$LANG;

   $sID = $software->getField('id');
   $license = new SoftwareLicense;

   $query = "SELECT `id`
             FROM `glpi_softwarelicenses`
             WHERE `softwares_id` = '$sID' " .
             getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true) . "
             ORDER BY `name`";

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG['software'][11].'</b>');

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)) {
         for ($tot=0 ; $data=$DB->fetch_assoc($result) ; ) {
            if ($license->getFromDB($data['id'])) {
               plugin_pdf_main_license($pdf,$license,false);
               if ($infocom) {
                  plugin_pdf_financial($pdf,$data['id'],'SoftwareLicense');
               }
            }
         }
      } else {
         $pdf->displayLine($LANG['search'][15]);
      }
   } else {
      $pdf->displayLine($LANG['search'][15]."!");
   }
   $pdf->displaySpace();
}


function plugin_pdf_installations($pdf,$item){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);
   $crit = ($type=='Software' ? 'softwares_id' : 'id');

   $query = "SELECT `glpi_computers_softwareversions`.*,
                    `glpi_computers`.`name` AS compname,
                    `glpi_computers`.`id` AS cID,
                    `glpi_computers`.`serial`,
                    `glpi_computers`.`otherserial`,
                    `glpi_softwareversions`.`name` AS version,
                    `glpi_softwareversions`.`id`,
                    `glpi_softwareversions`.`softwares_id`,
                    `glpi_softwareversions`.`name`,
                    `glpi_entities`.`completename`,
                    `glpi_locations`.`completename` AS location,
                    `glpi_softwarelicenses`.`name` AS lname,
                    `glpi_softwarelicenses`.`id`
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id` = `glpi_softwareversions`.`id`)
                INNER JOIN `glpi_computers`
                     ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                LEFT JOIN `glpi_entities`
                     ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                LEFT JOIN `glpi_locations`
                     ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                LEFT JOIN `glpi_softwarelicenses`
                     ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwareversions`.`softwares_id`
                         AND `glpi_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                WHERE (`glpi_softwareversions`.`$crit` = '$ID') " .
                       getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                       AND `glpi_computers`.`is_deleted` = '0'
                       AND `glpi_computers`.`is_template` = '0'
                ORDER BY version, compname";

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG['software'][19].'</b>');

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         $pdf->setColumnsSize(14,16,15,15,22,18);
         $pdf->displayTitle('<b><i>'.$LANG['software'][5],  // vername
                                     $LANG['common'][16],   // compname
                                     $LANG['common'][19],   // serial
                                     $LANG['common'][20],   // asset
                                     $LANG['common'][15],   // location
                                     $LANG['software'][11].'</i></b>'); // licname

         while ($data = $DB->fetch_assoc($result)) {
            $compname = $data['compname'];
            if (empty($compname) || $_SESSION['glpiID']) {
               $compname .= " (".$data['cID'].")";
            }
            $pdf->displayLine($data['version'], $compname,$data['serial'], $data['otherserial'],
                              $data['location'], $data['lname']);
         }

      } else {
         $pdf->displayLine($LANG['search'][15]);
      }

   } else {
      $pdf->displayLine($LANG['search'][15]."!");
   }
   $pdf->displaySpace();
}


function plugin_pdf_software($pdf,$comp){
   global $DB,$LANG;

   $ID = $comp->getField('id');
   $entities_id = $comp->fields["entities_id"];

   $query = " `glpi_softwarecategories`.`name` AS category,
              `glpi_softwares`.`softwarecategories_id` AS category_id,
              `glpi_softwares`.`name` AS softname,
              `glpi_computers_softwareversions`.`id` AS ID,
              `glpi_softwares`.`is_deleted`,
              `glpi_states`.`name` AS state,
              `glpi_softwareversions`.`softwares_id` AS sID,
              `glpi_softwareversions`.`name` AS version,
              `glpi_softwarelicenses`.`computers_id`,
              `glpi_softwarelicenses`.`softwarelicensetypes_id` AS lictype
             FROM `glpi_computers_softwareversions`
             LEFT JOIN `glpi_softwareversions`
               ON (`glpi_computers_softwareversions`.`softwareversions_id` = `glpi_softwareversions`.`id`)
             LEFT JOIN `glpi_states`
               ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
             LEFT JOIN `glpi_softwarelicenses`
               ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwarelicenses`.`softwares_id`
                   AND `glpi_softwarelicenses`.`computers_id` = '$ID')
             LEFT JOIN `glpi_softwares`
               ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
             LEFT JOIN `glpi_softwarecategories`
               ON (`glpi_softwarecategories`.`id` = `glpi_softwares`.`softwarecategories_id`)
             WHERE `glpi_computers_softwareversions`.`computers_id` = '$ID'";

   $query_cat = "SELECT 1 as TYPE,
                 $query
                  AND `glpi_softwares`.`softwarecategories_id` > '0' ";

   $query_nocat = "SELECT 2 as TYPE,
                   $query
                    AND (`glpi_softwares`.`softwarecategories_id` <= '0'
                         OR `glpi_softwares`.`softwarecategories_id` IS NULL )";

   $sql = "( $query_cat )
           UNION
           ($query_nocat)
           ORDER BY TYPE, category, softname, version";

   $DB->query("SET SESSION group_concat_max_len = 9999999;");
   $result = $DB->query($sql);

   $pdf->setColumnsSize(100);

   if ($DB->numrows($result)) {
      $pdf->displayTitle('<b>'.$LANG["software"][17].'</b>');

      $cat = -1;
      while ($data=$DB->fetch_array($result)) {
         if ($data["category_id"] != $cat) {
            $cat = $data["category_id"];
            $catname = ($cat ? $data["category"] : $LANG["softwarecategories"][3]);

            $pdf->setColumnsSize(100);
            $pdf->displayTitle('<b>'.$catname.'</b>');

            $pdf->setColumnsSize(59,13,13,15);
            $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
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
            ($data['computers_id'] == $ID ? html_clean(Dropdown::getDropdownName("glpi_softwarelicensetypes",
                                                                                 $data["lictype"])) : ''));
      } // Each soft

   } else {
      $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['software'][1].'</b>');
   }
   $pdf->displaySpace();
}


function plugin_pdf_computer_connection ($pdf,$comp){
   global $DB,$LANG;

   $ID = $comp->getField('id');

   $items = array('Printer'    => $LANG["computers"][39],
                  'Monitor'    => $LANG["computers"][40],
                  'Peripheral' => $LANG["computers"][46],
                  'Phone'      => $LANG["computers"][55]);

   $info = new InfoCom();

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');

   foreach ($items as $type => $title) {
      if (!class_exists($type)) {
         continue;
      }
      $item = new $type();
      if (!$item->canView()) {
         continue;
      }
      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$ID'
                      AND `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         $resultnum = $DB->numrows($result);
         if ($resultnum > 0) {
            for ($j=0 ; $j < $resultnum ; $j++) {
               $tID = $DB->result($result, $j, "items_id");
               $connID = $DB->result($result, $j, "id");
               $item->getFromDB($tID);
               $info->getFromDBforDevice($type,$tID) || $info->getEmpty();

               $line1 = $item->getTypeName()." - ";
               if ($item->getField("serial") != null) {
                  $line1 .= $LANG["common"][19] . " : " .$item->getField("serial")." - ";
               }
               $line1 .= html_clean(Dropdown::getDropdownName("glpi_states",
                                                              $item->getField('states_id')));

               $line2 = "";
               if ($item->getField("otherserial") != null) {
                  $line2 = $LANG["common"][20] . " : " . $item->getField("otherserial");
               }
               if ($info->fields["immo_number"]) {
                  if ($line2) {
                     $line2 .= " - ";
                  }
                  $line2 .= $LANG["financial"][20] . " : " . $info->fields["immo_number"];
               }
               if ($line2) {
                  $pdf->displayText('<b>'.$item->getTypeName().'</b>', $line1 . "\n" . $line2, 2);
               } else {
                  $pdf->displayText('<b>'.$item->getTypeName().'</b>', $line1, 1);
               }
            }// each device	of current type

         } else { // No row
            switch ($type) {
               case 'Printer' :
                  $pdf->displayLine($LANG["computers"][38]);
                  break;

               case 'Monitor' :
                  $pdf->displayLine($LANG["computers"][37]);
                  break;

               case 'Peripheral' :
                  $pdf->displayLine($LANG["computers"][47]);
                  break;

               case 'Phone' :
                  $pdf->displayLine($LANG["computers"][54]);
                  break;
            }
         } // No row
      } // Result
   } // each type
   $pdf->displaySpace();
}


function plugin_pdf_device_connection($pdf,$comp){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($comp);

   $info = new InfoCom();

   $pdf->setColumnsSize(100);
   $pdf->displayTitle('<b>'.$LANG["connect"][0].' :</b>');

   $query = "SELECT *
             FROM `glpi_computers_items`
             WHERE `computers_id` = '$ID'
                   AND `itemtype` = '$type'";

   if ($result = $DB->query($query)) {
      $resultnum = $DB->numrows($result);
      if ($resultnum > 0) {
         for ($j=0 ; $j < $resultnum ; $j++) {
            $tID = $DB->result($result, $j, "items_id");
            $connID = $DB->result($result, $j, "id");
            $comp->getFromDB($tID);
            $info->getFromDBforDevice('Computer',$tID) || $info->getEmpty();

            $line1 = ($comp->fields['name']?$comp->fields['name']:"(".$comp->fields['id'].")")." - ";
            if ($comp->fields['serial']) {
               $line1 .= $LANG["common"][19] . " : " .$comp->fields['serial']." - ";
            }
            $line1 .= html_clean(Dropdown::getDropdownName("glpi_states",$comp->fields['states_id']));

            $line2 = "";
            if ($comp->fields['otherserial']) {
               $line2 .= $LANG["common"][20] . " : " .$comp->fields['otherserial']." - ";
            }
            if ($info->fields['immo_number']) {
               if ($line2) {
                  $line2 .= " - ";
               }
               $line2 .= $LANG["financial"][20] . " : " . $info->fields['immo_number'];
            }
            if ($line2) {
               $pdf->displayText('<b>'.$LANG['help'][25].'</b>', $line1 . "\n" . $line2, 2);
            } else {
               $pdf->displayText('<b>'.$LANG['help'][25].'</b>', $line1, 1);
            }
         }// each device	of current type

      } else { // No row
         $pdf->displayLine($LANG['connect'][1]);
      } // No row
   } // Result
   $pdf->displaySpace();
}


function plugin_pdf_port($pdf,$item){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   $query = "SELECT `id`
             FROM `glpi_networkports`
             WHERE `items_id` = '$ID'
                   AND `itemtype` = '$type'
             ORDER BY `name`, `logical_number`";

   $pdf->setColumnsSize(100);
   if ($result = $DB->query($query)) {
      $nb_connect = $DB->numrows($result);
      if (!$nb_connect) {
         $pdf->displayTitle('<b>0 '.$LANG["networking"][37].'</b>');
      } else {
         $pdf->displayTitle('<b>'.$nb_connect.' '.$LANG["networking"][13].' :</b>');

         while ($devid=$DB->fetch_row($result)) {
            $netport = new NetworkPort;
            $netport->getfromDB(current($devid));

            $pdf->displayLine('<b><i># </i></b> '.$netport->fields["logical_number"].'<b><i>'.
                              $LANG["common"][16].' :</i></b> '.$netport->fields["name"]);

            $pdf->displayLine('<b><i>'.$LANG["networking"][51].' :</i></b> '.
                              html_clean(Dropdown::getDropdownName("glpi_netpoints",
                                                                   $netport->fields["netpoints_id"])));

            $pdf->displayLine('<b><i>'.$LANG["networking"][14].' / '.
                              $LANG["networking"][15].' :</i></b> '.$netport->fields["ip"].' / '.
                              $netport->fields["mac"]);

            $pdf->displayLine('<b><i>'.$LANG["networking"][60].' / '.$LANG["networking"][61].' / '.
                              $LANG["networking"][59].' :</i></b> '.$netport->fields["netmask"].' / '.
                              $netport->fields["subnet"].' / '.$netport->fields["gateway"]);

            $query = "SELECT *
                      FROM `glpi_networkports_vlans`
                      WHERE `networkports_id` = '$ID'";

            $result2 = $DB->query($query);
            if ($DB->numrows($result2) > 0) {
               $line = '<b><i>'.$LANG["networking"][56].' :</i></b>';

               while ($line=$DB->fetch_array($result2)) {
                  $line .= ' ' . html_clean(Dropdown::getDropdownName("glpi_networkports_vlans",
                                                                      $line["vlans_id"]));
               }
               $pdf->displayLine($line);
            }

            $pdf->displayLine(
               '<b><i>'.$LANG["common"][65].' :</i></b> '.
                  html_clean(Dropdown::getDropdownName("glpi_networkinterfaces",
                                                       $netport->fields["networkinterfaces_id"])));

            $contact = new NetworkPort;
            $netport2 = new NetworkPort;

            $line = '<b><i>'.$LANG["networking"][17].' :</i></b> ';
            if ($contact->getContact($netport->fields["id"])) {
               $netport2->getfromDB($contact->contact_id);
               $netport2->getDeviceData($netport2->fields['items_id'],$netport2->fields['itemtype']);

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


function plugin_pdf_contract ($pdf,$item){
   global $DB,$CFG_GLPI,$LANG;

   if (!haveRight("contract","r")) {
      return false;
   }

   $type = get_class($item);
   $ID = $item->getField('id');
   $con = new Contract();

   $query = "SELECT *
             FROM `glpi_contracts_items`
             WHERE `glpi_contracts_items`.`items_id` = '$ID'
                   AND `glpi_contracts_items`.`itemtype` = '$type'";

   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = $j = 0;

   $pdf->setColumnsSize(100);
   if ($number > 0) {
      $pdf->displayTitle($LANG["financial"][66]);

      $pdf->setColumnsSize(19,19,19,16,11,16);
      $pdf->displayTitle($LANG["common"][16], $LANG["financial"][4], $LANG["financial"][6],
                         $LANG["financial"][26], $LANG["search"][8], $LANG["financial"][8]);

      $i++;

      while ($j < $number) {
         $cID = $DB->result($result, $j, "contracts_id");
         $assocID = $DB->result($result, $j, "id");

         if ($con->getFromDB($cID)) {
            $pdf->displayLine(
               (empty($con->fields["name"]) ? "(".$con->fields["id"].")" : $con->fields["name"]),
               $con->fields["num"],
               html_clean(Dropdown::getDropdownName("glpi_contracttypes",
                                                    $con->fields["contracttypes_id"])),
               str_replace("<br>", " ", $con->getSuppliersNames()),
               convDate($con->fields["begin_date"]),
               $con->fields["duration"]." ".$LANG["financial"][57]);
         }
         $j++;
      }
   } else {
      $pdf->displayTitle("<b>".$LANG['plugin_pdf']['financial'][2]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_document($pdf,$item){
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!haveRight("document","r")) {
      return false;
   }

   $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                    `glpi_documents`.*
             FROM `glpi_documents_items`
             LEFT JOIN `glpi_documents`
                  ON (`glpi_documents_items`.`documents_id` = `glpi_documents`.`id`)
             WHERE `glpi_documents_items`.`items_id` = '$ID'
                   AND `glpi_documents_items`.`itemtype` = '$type'";

   $result = $DB->query($query);
   $number = $DB->numrows($result);

   $pdf->setColumnsSize(100);
   if (!$number) {
      $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['document'][1].'</b>');
   } else {
      $pdf->displayTitle('<b>'.$LANG["document"][21].' :</b>');

      $pdf->setColumnsSize(32,15,21,19,13);
      $pdf->displayTitle('<b>'.$LANG["common"][16].'</b>',
                         '<b>'.$LANG["document"][2].'</b>',
                         '<b>'.$LANG["document"][33].'</b>',
                         '<b>'.$LANG["document"][3].'</b>',
                         '<b>'.$LANG["document"][4].'</b>');

      while ($data = $DB->fetch_assoc($result)) {
         $pdf->displayLine($data["name"], basename($data["filename"]), $data["link"],
                           html_clean(Dropdown::getDropdownName("glpi_documentcategories",
                                                                $data["documentcategories_id"])),
                           $data["mime"]);
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_registry($pdf,$item) {
   global $DB,$LANG;

   $ID = $item->getField('id');

   $REGISTRY_HIVE = array("HKEY_CLASSES_ROOT",
                          "HKEY_CURRENT_USER",
                          "HKEY_LOCAL_MACHINE",
                          "HKEY_USERS",
                          "HKEY_CURRENT_CONFIG",
                          "HKEY_DYN_DATA");

   $query = "SELECT `id`
             FROM `glpi_registrykeys`
             WHERE `computers_id` = '$ID'";

   $pdf->setColumnsSize(100);
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         $pdf->displayTitle('<b>'.$DB->numrows($result)." ".$LANG["registry"][4].'</b>');

         $pdf->setColumnsSize(25,25,25,25);
         $pdf->displayTitle('<b>'.$LANG["registry"][6].'</b>',
                            '<b>'.$LANG["registry"][1].'</b>',
                            '<b>'.$LANG["registry"][2].'</b>',
                            '<b>'.$LANG["registry"][3].'</b>');

         $reg = new RegistryKey;

         while ($regid = $DB->fetch_row($result)) {
            if ($reg->getfromDB(current($regid))) {
               $pdf->displayLine($reg->fields['ocs_name'],
                                 $REGISTRY_HIVE[$reg->fields['hive']],
                                 $reg->fields['path'],
                                 $reg->fields['value']);
            }
         }

      } else {
         $pdf->displayTitle('<b>'.$LANG["registry"][5].'</b>');
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_ticket($pdf,$item) {
   global $DB,$CFG_GLPI, $LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!haveRight("show_all_ticket","1")) {
      return;
   }

   $query = "SELECT ".getCommonSelectForTrackingSearch()."
             FROM glpi_tickets ".
             getCommonLeftJoinForTrackingSearch()."
             WHERE (`status` = 'new'
                    OR `status` = 'assign'
                    OR `status` = 'plan'
                    OR `status` = 'waiting')
                   AND (`items_id` = '$ID'
                        AND `itemtype` = '$type')
             ORDER BY `glpi_tickets`.`date`";

   $result = $DB->query($query);
   $number = $DB->numrows($result);

   $pdf->setColumnsSize(100);
   if (!$number) {
      $pdf->displayTitle('<b>'.$LANG['joblist'][24] . " - " . $LANG["joblist"][8].'</b>');
   } else {
      $pdf->displayTitle('<b>'.$LANG['joblist'][24]." - $number ".$LANG["job"][8].'</b>');

      while ($data = $DB->fetch_assoc($result)) {
         $pdf->displayLine('<b><i>'.$LANG["state"][0].' :</i></b> ID'.$data["id"].'     '.
                           Ticket::getStatus($data["status"]));

         $pdf->displayLine('<b><i>'.$LANG["common"][27].' :</i></b>'.$LANG["joblist"][11].' : '.
                           $data["date"]);

         $pdf->displayLine('<b><i>'.$LANG["joblist"][2].' :</i></b> '.
                           Ticket::getPriorityName($data["priority"]));

         $pdf->displayLine('<b><i>'.$LANG["job"][4].' :</i></b> '.getUserName($data["users_id"]));

         $pdf->displayLine('<b><i>'.$LANG["job"][5].' :</i></b> '.
                           getUserName($data["users_id_assign"]));

         $pdf->displayLine('<b><i>'.$LANG["common"][36].' :</i></b> '.$data["catname"]);

         $pdf->displayLine('<b><i>'.$LANG["common"][57].' :</i></b> '.$data["name"]);
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_oldticket($pdf,$item) {
   global $DB,$CFG_GLPI, $LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!haveRight("show_all_ticket","1")) {
      return;
   }

   $query = "SELECT ".getCommonSelectForTrackingSearch()."
             FROM glpi_tickets ".
             getCommonLeftJoinForTrackingSearch()."
             WHERE (`status` = 'solved'
                    OR `status` = 'closed')
                   AND (`itemtype` = '$type'
                   AND `items_id` = '$ID')
             ORDER BY `glpi_tickets`.`date`";

   $result = $DB->query($query);
   $number = $DB->numrows($result);

   $pdf->setColumnsSize(100);
   if (!$number) {
      $pdf->displayTitle('<b>'.$LANG['joblist'][25] . " - " . $LANG["joblist"][8].'</b>');
   } else {
      $pdf->displayTitle('<b>'.$LANG['joblist'][25]." - $number ".$LANG["job"][8].'</b>');

      while ($data = $DB->fetch_assoc($result)) {
         $pdf->displayLine('<b><i>'.$LANG["state"][0].' :</i></b> ID'.$data["id"].'     '.
                           Ticket::getStatus($data["status"]));

         $pdf->displayLine('<b><i>'.$LANG["common"][27].' :</i></b>'.$LANG["joblist"][11].' : '.
                           $data["date"]);

         $pdf->displayLine('<b><i>'.$LANG["joblist"][2].' :</i></b> '.
                           Ticket::getPriorityName($data["priority"]));

         $pdf->displayLine('<b><i>'.$LANG["job"][4].' :</i></b> '.getUserName($data["users_id"]));

         $pdf->displayLine('<b><i>'.$LANG["job"][5].' :</i></b> '.
                           getUserName($data["users_id_assign"]));

         $pdf->displayLine('<b><i>'.$LANG["common"][36].' :</i></b> '.$data["catname"]);

         $pdf->displayLine('<b><i>'.$LANG["common"][57].' :</i></b> '.$data["name"]);
      }
   }
   $pdf->displaySpace();
}


function plugin_pdf_link($pdf,$item) {
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!haveRight("link","r")) {
      return false;
   }

   $query = "SELECT `glpi_links`.`id` AS ID, `glpi_links`.`link`, `glpi_links`.`name`,
                    `glpi_links`.`data`
             FROM `glpi_links`
             INNER JOIN `glpi_links_itemtypes`
                  ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
             WHERE `glpi_links_itemtypes`.`itemtype` = '$type'
             ORDER BY `glpi_links`.`name`";

   $result=$DB->query($query);

   $pdf->setColumnsSize(100);
   if ($DB->numrows($result) > 0) {
      $pdf->displayTitle('<b>'.$LANG["title"][33].'</b>');

      while ($data = $DB->fetch_assoc($result)) {
         $name = $data["name"];
         if (empty($name)) {
            $name = $data["link"];
         }
         $link = $data["link"];
         $file = trim($data["data"]);
         if (empty($file)) {
            if (strpos("[NAME]",$link)) {
               $link = str_replace("[NAME]",$item->getName(),$link);
            }
            if (strpos("[ID]",$link)) {
               $link = str_replace("[ID]",$ID,$link);
            }

            if (strpos("[SERIAL]",$link)) {
               if ($tmp = $item->getField('serial')){
                  $link = str_replace("[SERIAL]",$tmp,$link);
               }
            }

            if (strpos("[OTHERSERIAL]",$link)) {
               if ($tmp = $item->getField('otherserial')) {
                  $link = str_replace("[OTHERSERIAL]",$tmp,$link);
               }
            }

            if (strpos("[LOCATIONID]",$link)) {
               if ($tmp = $item->getField('locations_id')){
                  $link = str_replace("[LOCATIONID]",$tmp,$link);
               }
            }

            if (strpos("[LOCATION]",$link)) {
               if ($tmp = $item->getField('locations_id')){
                  $link = str_replace("[LOCATION]",
                                      html_clean(Dropdown::getDropdownName("glpi_locations",$tmp)),
                                      $link);
               }
            }

            if (strpos("[NETWORK]",$link)) {
               if ($tmp = $item->getField('networks_id')){
                  $link = str_replace("[NETWORK]",
                                      html_clean(Dropdown::getDropdownName("glpi_networks",$tmp)),
                                      $link);
               }
            }

            if (strpos("[DOMAIN]",$link)) {
               if ($tmp = $item->getField('domains_id'))
                  $link = str_replace("[DOMAIN]",
                                      html_clean(Dropdown::getDropdownName("glpi_domains",$tmp)),
                                      $link);
            }
            $ipmac = array();
            $j = 0;
            if (strstr($link,"[IP]") || strstr($link,"[MAC]")) {
               $query2 = "SELECT `ip`, `mac`
                          FROM `glpi_networkports`
                          WHERE `items_id` = '".$item->fields['id']."'
                                AND `itemtype` = '$type'
                          ORDER BY logical_number";

               $result2 = $DB->query($query2);
               if ($DB->numrows($result2) > 0) {
                  while ($data2 = $DB->fetch_array($result2)) {
                     $ipmac[$j]['ip'] = $data2["ip"];
                     $ipmac[$j]['mac'] = $data2["mac"];
                     $j++;
                  }
               }
               if (count($ipmac) > 0) { // One link per network address
                  foreach ($ipmac as $key => $val) {
                     $tmplink = $link;
                     $tmplink = str_replace("[IP]",$val['ip'],$tmplink);
                     $tmplink = str_replace("[MAC]",$val['mac'],$tmplink);
                     $pdf->displayLink("$name - $tmplink", $tmplink);
                  }
               }

            } else { // Single link (not network info)
               $pdf->displayLink("$name - $link", $link);
            }
         } else { // Generated File
            $ci->getFromDB($type,$ID);

            // Manage Filename
            if (strstr($link,"[NAME]")){
               $link = str_replace("[NAME]",$ci->getName(),$link);
            }

            if (strstr($link,"[LOGIN]")) {
               if (isset($_SESSION["glpiname"])){
                  $link = str_replace("[LOGIN]",$_SESSION["glpiname"],$link);
               }
            }

            if (strstr($link,"[ID]")) {
               $link = str_replace("[ID]",$_GET["ID"],$link);
            }
            $pdf->displayLine("$name - $link");
         }
      } // Each link
   } else {
      $pdf->displayTitle('<b>'.$LANG["links"][7].'</b>');
   }
   $pdf->displaySpace();
}


function plugin_pdf_volume($pdf,$item) {
   global $DB, $LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   $query = "SELECT `glpi_filesystems`.`name` AS fsname, `glpi_computerdisks`.*
             FROM `glpi_computerdisks`
             LEFT JOIN `glpi_filesystems`
               ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
             WHERE (`computers_id` = '$ID')";

   $result=$DB->query($query);

   $pdf->setColumnsSize(100);
   if ($DB->numrows($result) > 0) {
      $pdf->displayTitle("<b>".$LANG['computers'][8]."</b>");

      $pdf->setColumnsSize(22,23,22,11,11,11);
      $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                         '<b>'.$LANG['computers'][6].'</b>',
                         '<b>'.$LANG['computers'][5].'</b>',
                         '<b>'.$LANG['common'][17].'</b>',
                         '<b>'.$LANG['computers'][3].'</b>',
                         '<b>'.$LANG['computers'][2].'</b>');

      $pdf->setColumnsAlign('left','left','left','center','right','right');

      while ($data = $DB->fetch_assoc($result)) {
         $pdf->displayLine('<b>'.utf8_decode(empty($data['name'])?$data['ID']:$data['name']).'</b>',
                           $data['device'],
                           $data['mountpoint'],
                           Dropdown::getDropdownName('glpi_filesystems',$data["filesystems_id"]),
                           formatNumber($data['totalsize'], false, 0)." ".$LANG['common'][82],
                           formatNumber($data['freesize'], false, 0)." ".$LANG['common'][82]);
      }
   } else {
      $pdf->displayTitle("<b>".$LANG['computers'][8] . " - " . $LANG['search'][15]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_note($pdf,$item) {
   global $LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   $note = trim($item->getField('notepad'));

   $pdf->setColumnsSize(100);
   if (utf8_strlen($note) > 0) {
      $pdf->displayTitle('<b>'.$LANG["title"][37].'</b>');
      $pdf->displayText('', $note, 5);
   } else {
      $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['note'][1].'</b>');
   }
   $pdf->displaySpace();
}


function plugin_pdf_reservation($pdf,$item) {
   global $DB,$LANG,$CFG_GLPI;

   $ID = $item->getField('id');
   $type = get_class($item);

   if (!haveRight("reservation_central","r")) {
      return;
   }

   $user = new User();
   $ri = new ReservationItem;
   $pdf->setColumnsSize(100);
   if ($ri->getFromDBbyItem($type,$ID)) {
      $now = $_SESSION["glpi_currenttime"];
      $query = "SELECT *
                FROM `glpi_reservationitems`
                INNER JOIN `glpi_reservations`
                     ON (`glpi_reservations`.`reservationitems_id` = `glpi_reservationitems`.`id`)
                WHERE `end` > '".$now."'
                      AND `glpi_reservationitems`.`items_id` = '$ID'
                ORDER BY `begin`";

      $result = $DB->query($query);

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG["reservation"][35]."</b>");

      if (!$DB->numrows($result)) {
         $pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
      } else {
         $pdf->setColumnsSize(14,14,26,46);
         $pdf->displayTitle('<i>'.$LANG["search"][8].'</i>',
                            '<i>'.$LANG["search"][9].'</i>',
                            '<i>'.$LANG["reservation"][31].'</i>',
                            '<i>'.$LANG["common"][25].'</i>');

         while ($data = $DB->fetch_assoc($result)) {
            if ($user->getFromDB($data["users_id"])) {
               $name = formatUserName($user->fields["id"], $user->fields["name"],
                                      $user->fields["realname"], $user->fields["firstname"]);
            } else {
               $name = "(".$data["users_id"].")";
            }
            $pdf->displayLine(convDateTime($data["begin"]), convDateTime($data["end"]),
                              $name, str_replace(array("\r","\n")," ",$data["comment"]));
         }
      }

      $query = "SELECT *
                FROM `glpi_reservationitems`
                INNER JOIN `glpi_reservations`
                     ON (`glpi_reservations`.`reservationitems_id` = `glpi_reservationitems`.`id`)
                WHERE `end` <= '".$now."'
                      AND `glpi_reservationitems`.`items_id` = '$ID'
                ORDER BY `begin`
                DESC";

      $result = $DB->query($query);

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".$LANG["reservation"][36]."</b>");

      if (!$DB->numrows($result)) {
         $pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
      } else {
         $pdf->setColumnsSize(14,14,26,46);
         $pdf->displayTitle('<i>'.$LANG["search"][8].'</i>',
                            '<i>'.$LANG["search"][9].'</i>',
                            '<i>'.$LANG["reservation"][31].'</i>',
                            '<i>'.$LANG["common"][25].'</i>');

         while ($data = $DB->fetch_assoc($result)) {
            if ($user->getFromDB($data["users_id"])) {
               $name = formatUserName($user->fields["id"], $user->fields["name"],
                                      $user->fields["realname"], $user->fields["firstname"]);
            } else {
               $name = "(".$data["users_id"].")";
            }
            $pdf->displayLine(convDateTime($data["begin"]), convDateTime($data["end"]),$name,
                                           str_replace(array("\r","\n")," ",$data["comment"]));
         }
      }

   } else { // Not isReservable
      $pdf->displayTitle("<b>".$LANG["reservation"][34]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_history($pdf,$item) {
   global $DB,$LANG;

   $ID = $item->getField('id');
   $type = get_class($item);

   $query="SELECT *
           FROM `glpi_logs`
           WHERE `items_id`= '$ID'
                 AND `itemtype` = '$type'
           ORDER BY `id` DESC";

   $result = $DB->query($query);
   $number = $DB->numrows($result);

   $pdf->setColumnsSize(100);
   if ($number > 0) {
      $pdf->displayTitle("<b>".$LANG["title"][38]."</b>");

      $pdf->setColumnsSize(14,15,20,51);
      $pdf->displayTitle('<b><i>'.$LANG["common"][27].'</i></b>',
                         '<b><i>'.$LANG["common"][34].'</i></b>',
                         '<b><i>'.$LANG["event"][18].'</i></b>',
                         '<b><i>'.$LANG["event"][19].'</i></b>');

      while ($data = $DB->fetch_array($result)) {
         $field = "";
         if ($data["linked_action"]) {
            switch ($data["linked_action"]) {
               case HISTORY_DELETE_ITEM :
                  $change = $LANG['log'][22];
                  break;

               case HISTORY_RESTORE_ITEM :
                  $change = $LANG['log'][23];
                  break;

               case HISTORY_ADD_DEVICE :
                  $field = getDictDeviceLabel($data["itemtype_link"]);
                  $change = $LANG["devices"][25]." ".$data[ "new_value"];
                  break;

               case HISTORY_UPDATE_DEVICE :
                  $field = getDictDeviceLabel($data["itemtype_link"]);
                  $change = getDeviceSpecifityLabel($data["itemtype_link"]).$data[ "old_value"].
                                                    " --> ".$data[ "new_value"];
                  break;

               case HISTORY_DELETE_DEVICE :
                  $field = getDictDeviceLabel($data["itemtype_link"]);
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

               case HISTORY_DISCONNECT_DEVICE :
                  $field = NOT_AVAILABLE;
                  if (class_exists($data["itemtype_link"])) {
                     $item = new $data["itemtype_link"]();
                     $field = $item->getTypeName();
                  }
                  $change = $LANG['log'][26]." ".$data["old_value"];
                  break;

               case HISTORY_CONNECT_DEVICE :
                  $field = NOT_AVAILABLE;
                  if (class_exists($data["itemtype_link"])) {
                     $item = new $data["itemtype_link"]();
                     $field = $item->getTypeName();
                  }
                  $change = $LANG["log"][27]." ".$data["new_value"];

               case HISTORY_OCS_IMPORT :
                  if (haveRight("view_ocsng","r")) {
                     $change = $LANG["ocsng"][7]." ".$LANG["ocsng"][45]." : ".$data["new_value"];
                  } else {
                     $display_history = false;
                  }
                  break;

               case HISTORY_OCS_DELETE :
                  if (haveRight("view_ocsng","r")) {
                     $change = $LANG["ocsng"][46]." ".$LANG["ocsng"][45]." : ".$data["old_value"];
                     $change.= "&nbsp;"."\"".$data["old_value"]."\"";
                  } else {
                     $display_history = false;
                  }
                  break;

               case HISTORY_OCS_LINK:
                  if (haveRight("view_ocsng","r")) {
                     $field = NOT_AVAILABLE;
                     if (class_exists($data["itemtype_link"])) {
                        $item = new $data["itemtype_link"]();
                        $field = $item->getTypeName();
                     }
                     $change = $LANG["ocsng"][47]." ".$LANG["ocsng"][45]." : ".$data["new_value"];
                  } else {
                     $display_history = false;
                  }
                  break;

               case HISTORY_OCS_IDCHANGED:
                  if (haveRight("view_ocsng","r")) {
                     $change = $LANG["ocsng"][48].' : "'.$data["old_value"].
                               '" --> "'.$data["new_value"].'"';
                  } else {
                     $display_history = false;
                  }
                  break;

               case HISTORY_LOG_SIMPLE_MESSAGE :
                  $change = $data["new_value"];
                  break;

               case HISTORY_ADD_RELATION :
                  $field = NOT_AVAILABLE;
                  if (class_exists($data["itemtype_link"])) {
                     $item = new $data["itemtype_link"]();
                     $field = $item->getTypeName();
                  }
                  $change = $LANG['log'][32]." : ".$data["new_value"]."\"";
                  break;

               case HISTORY_DEL_RELATION :
                  $field = NOT_AVAILABLE;
                  if (class_exists($data["itemtype_link"])) {
                     $item = new $data["itemtype_link"]();
                     $field = $item->getTypeName();
                  }
                  $change = $LANG['log'][33]." : ".$data["old_value"]."\"";
                  break;
            }

         } else { // Not a linked_action
            $fieldname = "";
            foreach(Search::getOptions($type) as $key2 => $val2) {
               if ($key2 == $data["id_search_option"]) {
                  $field = $val2["name"];
                  $fieldname = $val2["field"];
               }
            }
            switch ($fieldname) {
               case "comment" :
                  $change = $LANG["log"][64];
                  break;

               case "notepad" :
                  $change =$LANG['log'][67];
                  break;

               default :
                  $change = str_replace("&nbsp;"," ",$data["old_value"])." --> ".
                            str_replace("&nbsp;"," ",$data["new_value"]);
            }
         }
         $pdf->displayLine(convDateTime($data["date_mod"]), $data["user_name"], $field, $change);
      } // Each log
   } else {
      $pdf->displayTitle("<b>".$LANG["event"][20]."</b>");
   }
   $pdf->displaySpace();
}


function plugin_pdf_pluginhook($onglet,$pdf,$item) {
   global $PLUGIN_HOOKS;

   if (preg_match("/^(.*)_([0-9]*)$/",$onglet,$split)) {
      $plug = $split[1];
      $ID_onglet = $split[2];

      if (isset($PLUGIN_HOOKS["headings_actionpdf"][$plug])) {
         if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
            include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
         }

         $function = $PLUGIN_HOOKS["headings_actionpdf"][$plug];
         if (is_callable($function)) {
            $actions = call_user_func($function, $item);

            if (isset($actions[$ID_onglet]) && is_callable($actions[$ID_onglet])) {
               call_user_func($actions[$ID_onglet], $pdf, $item);
               return true;
            }
         }
      }
   }
}


function plugin_pdf_general($item, $tab_id, $tab, $page=0) {

   $pdf = new PluginPdfSimplePDF('a4', ($page ? 'landscape' : 'portrait'));

   $nb_id = count($tab_id);

   foreach ($tab_id as $key => $id) {
      if (plugin_pdf_add_header($pdf,$id,$item)) {
         $pdf->newPage();
      } else {
         // Object not found or no right to read
         continue;
      }

      switch (get_class($item)) {
         case 'Computer' :
            plugin_pdf_main_computer($pdf,$item);
            foreach ($tab as $i) {
               switch($i) { // See Computer::defineTabs();
                  case 4 :
                     plugin_pdf_financial($pdf,$item);
                     plugin_pdf_contract ($pdf,$item);
                     break;

                  case 3 :
                     plugin_pdf_computer_connection($pdf,$item);
                     plugin_pdf_port($pdf,$item);
                     break;

                  case 1 :
                     plugin_pdf_device($pdf,$item);
                     break;

                  case 2 :
                     plugin_pdf_software($pdf,$item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf,$item);
                     plugin_pdf_oldticket($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 14 :
                     plugin_pdf_registry($pdf,$item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf,$item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf,$item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  case 20 :
                     plugin_pdf_volume($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'Printer' :
            plugin_pdf_main_printer($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) {  // See Printer::defineTabs();
                  case 1 :
                     plugin_pdf_cartridges($pdf,$item,false);
                     plugin_pdf_cartridges($pdf,$item,true);
                     break;

                  case 3 :
                     plugin_pdf_device_connection($pdf,$item);
                     plugin_pdf_port($pdf,$item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf,$item);
                     plugin_pdf_contract ($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf,$item);
                     plugin_pdf_oldticket($pdf,$item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf,$item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf,$item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'Monitor' :
            plugin_pdf_main_monitor($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) { // See Monitor::defineTabs();
                  case 1 :
                     plugin_pdf_device_connection($pdf,$item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf,$item);
                     plugin_pdf_contract ($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf,$item);
                     plugin_pdf_oldticket($pdf,$item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf,$item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf,$item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'Peripheral' :
            plugin_pdf_main_peripheral($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) { // See Peripheral::defineTabs();
                  case 1 :
                     plugin_pdf_device_connection($pdf,$item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf,$item);
                     plugin_pdf_contract ($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf,$item);
                     plugin_pdf_oldticket($pdf,$item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf,$item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf,$item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'Phone' :
            plugin_pdf_main_phone($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) { // See Phone::defineTabs();
                  case 1 :
                     plugin_pdf_device_connection($pdf,$item);
                     break;

                  case 4 :
                     plugin_pdf_financial($pdf,$item);
                     plugin_pdf_contract ($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf,$item);
                     plugin_pdf_oldticket($pdf,$item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf,$item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf,$item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'SoftwareLicense' :
            plugin_pdf_main_license($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) { // See SoftwareLicense::defineTabs();
                  case 4 :
                     plugin_pdf_financial($pdf,$item);
                     plugin_pdf_contract($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'SoftwareVersion' :
            plugin_pdf_main_version($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) { // See SoftwareVersion::defineTabs();
                  case 2 :
                     plugin_pdf_installations($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'Software' :
            plugin_pdf_main_software($pdf,$item);
            foreach ($tab as $i) {
               switch ($i) { // See Software::defineTabs();
                  case 1 :
                     plugin_pdf_versions($pdf,$item);
                     plugin_pdf_licenses($pdf,$item,in_array(2,$tab));
                     break;

                  case 2 :
                     plugin_pdf_installations($pdf,$item);
                     break;

                  case 4 :
                     // only template - plugin_pdf_financial($pdf,$ID,SOFTWARE_TYPE);
                     plugin_pdf_contract($pdf,$item);
                     break;

                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  case 6 :
                     plugin_pdf_ticket($pdf,$item);
                     plugin_pdf_oldticket($pdf,$item);
                     break;

                  case 7 :
                     plugin_pdf_link($pdf,$item);
                     break;

                  case 10 :
                     plugin_pdf_note($pdf,$item);
                     break;

                  case 11 :
                     plugin_pdf_reservation($pdf,$item);
                     break;

                  case 12 :
                     plugin_pdf_history($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;

         case 'Ticket' :
            plugin_pdf_main_ticket($pdf,$item,in_array('private',$tab));
            foreach ($tab as $i) {
               switch ($i) { // Value not from Job::defineTabs but from plugin_pdf_prefPDF
                  case 5 :
                     plugin_pdf_document($pdf,$item);
                     break;

                  default :
                     plugin_pdf_pluginhook($i,$pdf,$item);
               }
            }
            break;
      } // Switch type
   } // Each ID
   $pdf->render();
}

?>