<?php
/**
 * @version $Id$
 -------------------------------------------------------------------------
 LICENSE

 This file is part of PDF plugin for GLPI.

 PDF is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 PDF is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @package   pdf
 @authors   Nelly Mahu-Lasson, Remi Collet
 @copyright Copyright (c) 2009-2016 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
 */

function plugin_pdf_postinit() {
   global $CFG_GLPI, $PLUGIN_HOOKS;

   foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $typepdf) {
      CommonGLPI::registerStandardTab($type, $typepdf);
   }
}


function plugin_pdf_MassiveActions($type) {
   global $PLUGIN_HOOKS;

   switch ($type) {
      default :
         if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
            return array('PluginPdfCommon'.MassiveAction::CLASS_ACTION_SEPARATOR.'DoIt' => __('Print to pdf', 'pdf'));
         }
   }
   return array();
}


function plugin_pdf_install() {
   global $DB;

   $migration = new Migration('1.2');
   //new install
   if (!TableExists('glpi_plugin_pdf_profiles')
       && !TableExists('glpi_plugin_pdf_preferences')) {
      include_once(GLPI_ROOT."/plugins/pdf/inc/profile.class.php");
      PluginPdfProfile::install($migration);

   } else {
      if (TableExists('glpi_plugin_pdf_profiles')
          && FieldExists('glpi_plugin_pdf_profiles','ID')) { //< 0.7.0
         $migration->changeField('glpi_plugin_pdf_profiles', 'ID', 'id', 'autoincrement');
      }
      // -- SINCE 0.85 --
      //Add new rights in glpi_profilerights table
      $profileRight = new ProfileRight();
      $query = "SELECT *
                FROM `glpi_plugin_pdf_profiles`
                WHERE `use` = 1";

      foreach ($DB->request($query) as $data) {
         $right['profiles_id']   = $data['id'];
         $right['name']          = "plugin_pdf";
         $right['rights']        = $data['use'];

         $profileRight->add($right);
      }
      $DB->query("DROP TABLE `glpi_plugin_pdf_profiles`");

   }

   if (!TableExists('glpi_plugin_pdf_preference')
       && !TableExists('glpi_plugin_pdf_preferences')) {
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
      if (TableExists('glpi_plugin_pdf_preference')) {
         $migration->renameTable('glpi_plugin_pdf_preference', 'glpi_plugin_pdf_preferences');
      }
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','user_id')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'user_id', 'users_id', 'integer',
                                 array('comment' => 'RELATION to glpi_users (id)'));
      }
      // 0.6.1
      if (FieldExists('glpi_plugin_pdf_preferences','FK_users')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'FK_users', 'users_id', 'integer',
                                 array('comment' => 'RELATION to glpi_users (id)'));
      }
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','cat')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'cat', 'itemtype',
                                 'VARCHAR(100) NOT NULL',
                                 array('comment' => 'see define.php *_TYPE constant'));
      }
      // 0.6.1
      if (FieldExists('glpi_plugin_pdf_preferences','device_type')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'device_type', 'itemtype',
                                 'VARCHAR(100) NOT NULL',
                                 array('comment' => 'see define.php *_TYPE constant'));
      }
      // 0.6.0
      if (FieldExists('glpi_plugin_pdf_preferences','table_num')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'table_num', 'tabref',
                                 'string',
                                 array('comment' => 'ref of tab to display, or plugname_#, or option name'));
      }
      //0.85
      if (isset($main)) {
         $query = "UPDATE `glpi_plugin_pdf_preferences`
                   SET `tabref`= CONCAT(`itemtype`,'$main')
                   WHERE `tabref`='_main_'";
         $DB->queryOrDie($query, "update tabref for main");
      }

      $migration->executeMigration();
   }

   return true;
}


function plugin_pdf_uninstall() {
   global $DB;

   $tables = array ("glpi_plugin_pdf_preference",
                    "glpi_plugin_pdf_profiles",
                    "glpi_plugin_pdf_preferences");

   $migration = new Migration('0.85');
   foreach ($tables as $table) {
      $migration->dropTable($table);
   }

   //Delete rights associated with the plugin
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'plugin_pdf'";
   $DB->queryOrDie($query, $DB->error());

   return true;
}


/**
 * @since version 1.0.2
**/
function plugin_pdf_registerMethods() {
   global $WEBSERVICES_METHOD;

   $WEBSERVICES_METHOD['pdf.getTabs']  = array('PluginPdfRemote', 'methodGetTabs');
   $WEBSERVICES_METHOD['pdf.getPdf']   = array('PluginPdfRemote', 'methodGetPdf');
}

