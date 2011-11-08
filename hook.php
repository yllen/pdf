<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

 https://forge.indepnet.net/projects/pdf
 -------------------------------------------------------------------------

 LICENSE

 This file is part of pdf.

 pdf is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 pdf is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with pdf. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------


function plugin_pdf_postinit() {
   global $CFG_GLPI, $PLUGIN_HOOKS;

   foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $typepdf) {
      CommonGLPI::registerStandardTab($type, $typepdf);
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


function plugin_pdf_MassiveActionsDisplay($options=array()) {
   global $LANG,$PLUGIN_HOOKS;

   switch ($options['itemtype']) {
      case 'Profile' :
         switch ($options['action']) {
            case "plugin_pdf_allow":
               Dropdown::showYesNo('use');
               echo "<input type='submit' name='massiveaction' class='submit' value='".
                     $LANG['buttons'][2]."'>";
               break;
         }
         break;

      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$options['itemtype']]) && $options['action']=='plugin_pdf_DoIt') {
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

      $changes = array();
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','user_id')) {
         $changes[] = "CHANGE `user_id` `users_id` INT(11) NOT NULL COMMENT 'RELATION to glpi_users (id)'";
      }
      // 0.6.1
      if (FieldExists('glpi_plugin_pdf_preferences','FK_users')) {
         $changes[] = "CHANGE `FK_users` `users_id` INT(11) NOT NULL COMMENT 'RELATION to glpi_users (id)'";
      }
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','cat')) {
         $changes[] = "CHANGE `cat` `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant'";
      }
      // 0.6.1
      if (FieldExists('glpi_plugin_pdf_preferences','device_type')) {
         $changes[] = "CHANGE `device_type` `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant'";
      }
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','table_num')) {
         $changes[] = "CHANGE `table_num` `tabref` VARCHAR(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name'";
      }
      if (count($changes)) {
         $query = "ALTER TABLE `glpi_plugin_pdf_preferences` ".implode(', ',$changes);
         $DB->query($query) or die($DB->error());
      }
   }

   // Give right to current Profile
   include_once (GLPI_ROOT . '/plugins/pdf/inc/profile.class.php');
   $prof =  new PluginPdfProfile();
   $prof->add(array('id'      => $_SESSION['glpiactiveprofile']['id'],
                    'profile' => $_SESSION['glpiactiveprofile']['name'],
                    'use'     => 1));
   return true;
}


function plugin_pdf_uninstall() {
   global $DB;

   $tables = array ("glpi_plugin_pdf_preference",
                    "glpi_plugin_pdf_preferences",
                    "glpi_plugin_pdf_profiles");

   foreach ($tables as $table) {
      $query = "DROP TABLE IF EXISTS `$table`;";
      $DB->query($query) or die($DB->error());
   }

   return true;
}

?>