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

include_once (GLPI_ROOT . '/plugins/pdf/inc/functions.php');

/**
 * Hook : options for one type
 *
 * @param $type of item
 *
 * @return array of string which describe the options
 */
function plugin_pdf_prefPDF($item) {
   global $LANG;

   $tabs = array();
   switch (get_class($item)) {
      case 'Computer' :
         $tabs = $item->defineTabs(1,'');
         if (isset($tabs[13])) {
            unset($tabs[13]); // OCSNG
         }
      break;

      case 'Printer' :
      case 'Monitor' :
      case 'Phone' :
      case 'Peripheral' :
         $tabs = $item->defineTabs(1,'');
         break;

      case 'Software' :
         $tabs = $item->defineTabs(1,'');
         if (isset($tabs[21])) {
            unset($tabs[21]); // Merge
         }
         break;

      case 'SoftwareLicense' :
         $tabs = $item->defineTabs(1,'');
         if (isset($tabs[1])) {
            unset($tabs[1]); // Main : TODO
         }
         break;

      case 'SoftwareVersion' :
         $tabs = $item->defineTabs(1,'');
         if (isset($tabs[1])) {
            unset($tabs[1]); // Main : TODO
         }
         break;

      case 'Ticket' :
         return array('private' => $LANG['common'][77], // Privé
                      5         => $LANG['Menu'][27]);  // Documents
   }
   return $tabs;
}


/**
 * Hook to generate a PDF for a type
 *
 * @param $type of item
 * @param $tab_id array of ID
 * @param $tab of option to be printed
 * @param $page boolean true for landscape
 */
function plugin_pdf_generatePDF($item, $tab_id, $tab, $page=0) {
   plugin_pdf_general($item, $tab_id, $tab, $page);
}


function plugin_pdf_get_headings($item,$withtemplate) {
   global $LANG, $PLUGIN_HOOKS;

   $type = get_class($item);
   if ($type == 'Preference') {
      return array(1 => $LANG['plugin_pdf']['title'][1]);

   } else if ($type == 'Profile') {
      if ($item->fields['interface']!='helpdesk') {
         return array(1 => $LANG['plugin_pdf']['title'][1]);
      }

   } else if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
      if ($item->getField('id') && !$withtemplate) {
         return array( 1 => $LANG['plugin_pdf']['title'][1]);
      }
   }
   return false;
}


function plugin_pdf_headings_actions($item) {
   global $PLUGIN_HOOKS;

   $type = get_class($item);
   switch ($type) {
      case 'Profile' :
      case 'Preference' :
         return array(1 => "plugin_pdf_headings");

      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
            return array(1 => "plugin_pdf_headings");
         }
   }
   return false;
}


// action heading
function plugin_pdf_headings($item,$withtemplate=0) {
   global $CFG_GLPI,$PLUGIN_HOOKS;

   $pref = new PluginPdfPreference;
   $type = get_class($item);

   switch ($type) {
      case 'Profile' :
         $prof =  new PluginPdfProfile();
         $ID = $item->getField('id');
         if ($prof->GetfromDB($ID) || $prof->createProfile($item)) {
            $prof->showForm($CFG_GLPI["root_doc"]."/plugins/pdf/front/profile.php",$ID);
         }
         break;

      case 'Preference' :
         $pref->showForm($CFG_GLPI['root_doc']."/plugins/pdf/front/preference.form.php");
         break;

      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
            $pref->menu($item,$CFG_GLPI['root_doc']."/plugins/pdf/front/export.php");
         }
   }
}


function plugin_pdf_MassiveActions($type) {
   global $LANG,$PLUGIN_HOOKS;

   switch ($type) {
      case 'Profile' :
         return array("plugin_pdf_allow"=>$LANG['plugin_pdf']['title'][1]);

      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
            return array("plugin_pdf_DoIt"=>$LANG['plugin_pdf']['title'][1]);
         }
   }
   return array();
}


function plugin_pdf_MassiveActionsDisplay($type,$action) {
   global $LANG,$PLUGIN_HOOKS;

   switch ($type) {
      case 'Profile' :
         switch ($action) {
            case "plugin_pdf_allow":
               dropdownYesNo('use');
               echo "<input type='submit' name='massiveaction' class='submit' value='".
                     $LANG['buttons'][2]."'>";
               break;
         }
         break;

      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$type]) && $action=='plugin_pdf_DoIt') {
            echo "<input type='submit' name='massiveaction' class='submit' value='".
                  $LANG['buttons'][2]."'>";
         }
   }
   return "";
}


function plugin_pdf_MassiveActionsProcess($data){

   switch ($data["action"]) {
      case "plugin_pdf_DoIt" :
         foreach ($data['item'] as $key => $val) {
            if ($val) {
               $tab_id[]=$key;
            }
         }
         $_SESSION["plugin_pdf"]["type"] = $data["itemtype"];
         $_SESSION["plugin_pdf"]["tab_id"] = serialize($tab_id);
         echo "<script type='text/javascript'>
               location.href='../plugins/pdf/front/export.massive.php'</script>";
         break;

      case "plugin_pdf_allow" :
         $profglpi = new Profile();
         $prof = new PluginPdfProfile();
         foreach ($data['item'] as $key => $val) {
            if ($profglpi->getFromDB($key) && $profglpi->fields['interface']!='helpdesk') {
               if ($prof->getFromDB($key)) {
                  $prof->update(array('id'  => $key,
                                      'use' => $data['use']));
               } else if ($data['use']) {
                  $prof->add(array('id' => $key,
                                   'use' => $data['use']));
               }
            }
         }
         break;
   }
}


function plugin_pdf_pre_item_purge($item) {

   switch (get_class($item)) {
      case 'Profile' :
         // Manipulate data if needed
         $PluginPdfProfile = new PluginPdfProfile;
         $PluginPdfProfile->cleanProfiles($item->getField("id"));
         break;
   }
   return $item;
}


function plugin_pdf_install() {
   global $DB;

   if (!TableExists('glpi_plugin_pdf_profiles')) {
      $query= "CREATE TABLE IF NOT EXISTS
               `glpi_plugin_pdf_profiles` (
                  `id` int(11) NOT NULL,
                  `profile` varchar(255) default NULL,
                  `use` tinyint(1) default 0,
                  PRIMARY KEY (`id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die($DB->error());
   } else {
      if (FieldExists('glpi_plugin_pdf_profiles','ID')) { //< 0.7.0
         $query= "ALTER TABLE `glpi_plugin_pdf_profiles`
                  CHANGE `ID` `id` INT( 11 ) NOT NULL AUTO_INCREMENT";
         $DB->query($query) or die($DB->error());
      }
   }

   if (!TableExists('glpi_plugin_pdf_preference')) {
      $query= "CREATE TABLE IF NOT EXISTS
               `glpi_plugin_pdf_preferences` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `users_id` int(11) NOT NULL COMMENT 'RELATION to glpi_users (id)',
                  `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant',
                  `tabref` varchar(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name',
                  PRIMARY KEY (`id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die($DB->error());
   } else {
      $DB->query("RENAME TABLE `glpi_plugin_pdf_preference` TO `glpi_plugin_pdf_preferences`");
      $query = "ALTER TABLE `glpi_plugin_pdf_preferences` ";
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','user_id')) {
         $query .= " CHANGE `user_id` `users_id` INT(11) NOT NULL COMMENT 'RELATION to glpi_users (id)',";
      }
      if (FieldExists('glpi_plugin_pdf_preferences','cat')) {
         $query .= " CHANGE `cat` `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant',";
      }
      if (FieldExists('glpi_plugin_pdf_preferences','table_num')) {
         $query .= " CHANGE `table_num` `tabref` VARCHAR(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name'";
      }

      // 0.6.1
      if (FieldExists('glpi_plugin_pdf_preferences','FK_users')) {
         $query .= " CHANGE `FK_users` `users_id` INT(11) NOT NULL COMMENT 'RELATION to glpi_users (id)',";
      }
      if (FieldExists('glpi_plugin_pdf_preferences','device_type')) {
         $query .= " CHANGE `device_type` `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant'";
      }
      $DB->query($query) or die($DB->error());
   }

   // Give right to current Profile
   include_once (GLPI_ROOT . '/plugins/pdf/inc/profile.class.php');
   $prof =  new PluginPdfProfile();
   $prof->add(array('id'   => $_SESSION['glpiactiveprofile']['id'],
                    'use'  => 1));
   return true;
}


function plugin_pdf_uninstall() {
   global $DB;

   $query = "DROP TABLE IF EXISTS `glpi_plugin_pdf_preference`";
   $DB->query($query) or die($DB->error());

   $query = "DROP TABLE IF EXISTS `glpi_plugin_pdf_preferences`";
   $DB->query($query) or die($DB->error());

   $query = "DROP TABLE IF EXISTS `glpi_plugin_pdf_profiles`";
   $DB->query($query) or die($DB->error());

   return true;
}

?>