<?php
/**
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
 @copyright Copyright (c) 2009-2022 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/

class PluginPdfPreference extends CommonDBTM {

   static $rightname = "plugin_pdf";


   static function showPreferences() {
      global $PLUGIN_HOOKS;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);
      $pref   = new self();
      $dbu    = new DbUtils();

      echo "<div class='center' id='pdf_type'>";
      foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $plug) {
         if (!($item = $dbu->getItemForItemtype($type))) {
            continue;
         }
         if ($item->canView()) {
            $pref->menu($item, $target);
         }
      }
      echo "</div>";
   }


   /**
    * @param $num
    * @param $label
    * @param $checked   (false by default)
   **/
   function checkbox($num,$label,$checked=false) {

       echo "<td width='20%'><input type='checkbox' ".($checked==true?"checked='checked'":'').
             " name='item[$num]' value='1'>&nbsp;".$label."</td>";
    }


    /**
     * @param $item
     * @param $action
    **/
   function menu($item, $action) {
      global $DB, $PLUGIN_HOOKS;

      $type = $item->getType();

      // $ID set if current object, not set from preference
      if (isset($item->fields['id'])) {
         $ID = $item->fields['id'];
      } else {
         $ID = 0;
         $item->getEmpty();
      }

      if (!isset($PLUGIN_HOOKS['plugin_pdf'][$type])
          || !class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {
         return;
      }
      $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
      $options = $itempdf->defineAllTabsPDF();

      $formid="plugin_pdf_${type}_".mt_rand();
      echo "<form name='".$formid."' id='".$formid."' action='$action' method='post' ".
             ($ID ? "target='_blank'" : "")."><table class='tab_cadre_fixe'>";

      $landscape = false;
      $values    = [];

      foreach ($DB->request($this->getTable(),
                            ['SELECT' => 'tabref',
                             'WHERE'  => ['users_id' => $_SESSION['glpiID'],
                                          'itemtype' => $type]]) AS $data) {
         if ($data["tabref"] == 'landscape') {
            $landscape = true;
         } else {
            $values[$data["tabref"]] = $data["tabref"];
         }
      }
      // Always export, at least, main part.
      if (!count($values) && isset($options[$type.'$main'])) {
         $values[$type.'$main'] = 1;
      }

      echo "<tr><th colspan='6'>".sprintf(__('%1$s: %2$s'),
                                          __('Choose the tables to print in pdf', 'pdf'),
                                          $item->getTypeName());
      echo "</th></tr>";

      $i = 0;
      foreach ($options as $num => $title) {
         if (!$i) {
            echo "<tr class='tab_bg_1'>";
         }
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $title = "$title ($num)";
         }
         $this->checkbox($num, $title, (isset($values[$num]) ? true : false));
         if ($i == 4) {
            echo "</tr>";
            $i = 0;
         } else {
            $i++;
         }
      }
      if ($i) {
         while ($i <= 4) {
            echo "<td width='20%'>&nbsp;</td>";
            $i++;
         }
         echo "</tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='2' class='left'>";
      echo "<a onclick=\"if (markCheckboxes('".$formid."') ) return false;\" href='".
           $_SERVER['PHP_SELF']."?select=all'>".__('Check all')."</a> / ";
      echo "<a onclick=\"if (unMarkCheckboxes('".$formid."') ) return false;\" href='".
           $_SERVER['PHP_SELF']."?select=none'>".__('Uncheck all')."</a></td>";

      echo "<td colspan='4' class='center'>";
      echo Html::hidden('plugin_pdf_inventory_type', ['value' => $type]);
      echo Html::hidden('indice', ['value' => count($options)]);

      if ($ID) {
        echo __('Display (number of items)')."&nbsp;";
        Dropdown::showListLimit();
      }
      echo "<select name='page'>\n";
      echo "<option value='0'>".__('Portrait', 'pdf')."</option>\n"; // Portrait
      echo "<option value='1'".($landscape?"selected='selected'":'').">".__('Landscape', 'pdf').
           "</option>\n"; // Paysage
      echo "</select>&nbsp;&nbsp;&nbsp;&nbsp;\n";

      if ($ID) {
         echo Html::hidden('itemID', ['value' => $ID]);
         echo Html::submit(_sx('button','Print', 'pdf'), ['name' => 'generate',
                                                          'class' => 'btn btn-primary']);
      } else {
         echo Html::submit(_sx('button', 'Save'), ['name'  => 'plugin_pdf_user_preferences_save',
                                                   'class' => 'btn btn-primary',
                                                   'icon'  => 'ti ti-device-floppy']);
      }
      echo "</td></tr></table>";
      Html::closeForm();
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (($item->getType() == 'Preference')) {
         return __('Print to pdf', 'pdf');
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Preference') {
         self::showPreferences();
      }
      return true;
   }


   static function install(Migration $mig) {
      global $DB;

      $table = 'glpi_plugin_pdf_preferences';
      if (!$DB->tableExists('glpi_plugin_pdf_preference')
          && !$DB->tableExists($table)) {
         $default_charset   = DBConnection::getDefaultCharset();
         $default_collation = DBConnection::getDefaultCollation();
         $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

         $query = "CREATE TABLE `". $table."`(
                  `id` int $default_key_sign NOT NULL AUTO_INCREMENT,
                  `users_id` int $default_key_sign NOT NULL COMMENT 'RELATION to glpi_users (id)',
                  `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant',
                  `tabref` VARCHAR(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name',
                  PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET= {$default_charset}
                 COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC";
         $DB->queryOrDie($query, $DB->error());

      } else {
          if ($DB->tableExists('glpi_plugin_pdf_preference')) {
            $mig->renameTable('glpi_plugin_pdf_preference', 'glpi_plugin_pdf_preferences');
         }
         // 0.6.0
         if ($DB->fieldExists($table,'user_id')) {
            $mig->changeField($table, 'user_id', 'users_id',
                              "int {$default_key_sign} NOT NULL DEFAULT '0'",
                              ['comment' => 'RELATION to glpi_users (id)']);
         }
         // 0.6.1
         if ($DB->fieldExists($table,'FK_users')) {
            $mig>changeField($table, 'FK_users', 'users_id',
                             "int {$default_key_sign} NOT NULL DEFAULT '0'",
                             ['comment' => 'RELATION to glpi_users (id)']);
         }
         // 0.6.0
         if ($DB->fieldExists($table,'cat')) {
            $mig->changeField($table, 'cat', 'itemtype', 'VARCHAR(100) NOT NULL',
                              ['comment' => 'see define.php *_TYPE constant']);
         }
         // 0.6.1
         if ($DB->fieldExists($table,'device_type')) {
            $mig->changeField($table, 'device_type', 'itemtype', 'VARCHAR(100) NOT NULL',
                              ['comment' => 'see define.php *_TYPE constant']);
         }
         // 0.6.0
         if ($DB->fieldExists($table,'table_num')) {
            $mig->changeField($table, 'table_num', 'tabref', 'string',
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
   }

   static function uninstall(Migration $mig) {
      $mig->dropTable('glpi_plugin_pdf_preferences');
   }
}
