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
 @copyright Copyright (c) 2009-2021 PDF plugin team
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
            return ['PluginPdfCommon'.MassiveAction::CLASS_ACTION_SEPARATOR.'DoIt'
                     => __('Print to pdf', 'pdf')];
         }
   }
   return [];
}


function plugin_pdf_install() {
   global $DB;

   $migration = new Migration('1.7.0');

   //new install
   if (!$DB->tableExists('glpi_plugin_pdf_profiles')
       && !$DB->tableExists('glpi_plugin_pdf_preferences')) {
      include_once(Plugin::getPhpDir('pdf')."inc/profile.class.php");
      PluginPdfProfile::install($migration);

   } else {
      if ($DB->tableExists('glpi_plugin_pdf_profiles')
          && $DB->fieldExists('glpi_plugin_pdf_profiles','ID')) { //< 0.7.0
         $migration->changeField('glpi_plugin_pdf_profiles', 'ID', 'id', 'autoincrement');
      }
      // -- SINCE 0.85 --
      //Add new rights in glpi_profilerights table
      $profileRight = new ProfileRight();

      if ($DB->tableExists('glpi_plugin_pdf_profiles')) {
         foreach ($DB->request('glpi_plugin_pdf_profiles', ['use' => 1]) as $data) {
            $right['profiles_id']   = $data['id'];
            $right['name']          = "plugin_pdf";
            $right['rights']        = $data['use'];

            $profileRight->add($right);
         }
         $migration->dropTable('glpi_plugin_pdf_profiles');
      }

   }

   if (!$DB->tableExists('glpi_plugin_pdf_preference')
       && !$DB->tableExists('glpi_plugin_pdf_preferences')) {

      $default_charset   = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

      $query= "CREATE TABLE IF NOT EXISTS
               `glpi_plugin_pdf_preferences` (
                  `id` int $default_key_sign NOT NULL AUTO_INCREMENT,
                  `users_id` int $default_key_sign NOT NULL COMMENT 'RELATION to glpi_users (id)',
                  `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant',
                  `tabref` varchar(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name',
                  PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
      $DB->queryOrDie($query, $DB->error());
   } else {
      if ($DB->tableExists('glpi_plugin_pdf_preference')) {
         $migration->renameTable('glpi_plugin_pdf_preference', 'glpi_plugin_pdf_preferences');
      }
      // 0.6.0
      if ($DB->fieldExists('glpi_plugin_pdf_preferences','user_id')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'user_id', 'users_id', "int {$default_key_sign} NOT NULL DEFAULT '0'",
                                 ['comment' => 'RELATION to glpi_users (id)']);
      }
      // 0.6.1
      if ($DB->fieldExists('glpi_plugin_pdf_preferences','FK_users')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'FK_users', 'users_id', "int {$default_key_sign} NOT NULL DEFAULT '0'",
                                 ['comment' => 'RELATION to glpi_users (id)']);
      }
      // 0.6.0
      if ($DB->fieldExists('glpi_plugin_pdf_preferences','cat')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'cat', 'itemtype',
                                 'VARCHAR(100) NOT NULL',
                                 ['comment' => 'see define.php *_TYPE constant']);
      }
      // 0.6.1
      if ($DB->fieldExists('glpi_plugin_pdf_preferences','device_type')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'device_type', 'itemtype',
                                 'VARCHAR(100) NOT NULL',
                                 ['comment' => 'see define.php *_TYPE constant']);
      }
      // 0.6.0
      if ($DB->fieldExists('glpi_plugin_pdf_preferences','table_num')) {
         $migration->changeField('glpi_plugin_pdf_preferences', 'table_num', 'tabref',
                                 'string',
                                 ['comment' => 'ref of tab to display, or plugname_#, or option name']);
      }
      //0.85
      if (isset($main)) {
         $query = "UPDATE `glpi_plugin_pdf_preferences`
                   SET `tabref`= CONCAT(`itemtype`,'$main')
                   WHERE `tabref`='_main_'";
         $DB->queryOrDie($query, "update tabref for main");
      }
   }

   if (!$DB->tableExists('glpi_plugin_pdf_configs')) {
      include_once(Plugin::getPhpDir('pdf')."inc/config.class.php");
      PluginPdfConfig::install($migration);
   }

      $migration->executeMigration();

   return true;
}


function plugin_pdf_uninstall() {
   global $DB;

   $migration = new Migration('1.7.0');

   $tables = ["glpi_plugin_pdf_preference",
              "glpi_plugin_pdf_profiles",
              "glpi_plugin_pdf_preferences"];

   foreach ($tables as $table) {
      $migration->dropTable($table);
   }

   //Delete rights associated with the plugin
   $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'plugin_pdf'";
   $DB->queryOrDie($query, $DB->error());

   if ($DB->tableExists('glpi_plugin_pdf_configs')) {
      include_once(Plugin::getPhpDir('pdf')."inc/config.class.php");
      PluginPdfConfig::uninstall($migration);
   }

   $migration->executeMigration();

   return true;
}


/**
 * @since version 1.0.2
**/
function plugin_pdf_registerMethods() {
   global $WEBSERVICES_METHOD;

   $WEBSERVICES_METHOD['pdf.getTabs']  = ['PluginPdfRemote', 'methodGetTabs'];
   $WEBSERVICES_METHOD['pdf.getPdf']   = ['PluginPdfRemote', 'methodGetPdf'];
}

