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

function plugin_init_pdf() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['pdf'] = true;

   Plugin::registerClass('PluginPdfProfile',    array('addtabon' => 'Profile'));
   if (Session::haveRight('plugin_pdf', READ)) {
      Plugin::registerClass('PluginPdfPreference', array('addtabon' => 'Preference'));
   }

   if (Session::getLoginUserID()
       && Session::haveRight('plugin_pdf', READ)) {
      $PLUGIN_HOOKS['use_massive_action']['pdf'] = 1;
   }

   $plugin = new Plugin();
   if ($plugin->isActivated("datainjection")) {
      $PLUGIN_HOOKS['menu_entry']['pdf'] = 'front/preference.form.php';
   }

      // Define the type for which we know how to generate PDF :
      $PLUGIN_HOOKS['plugin_pdf']['Computer']         = 'PluginPdfComputer';
      $PLUGIN_HOOKS['plugin_pdf']['Group']            = 'PluginPdfGroup';
      $PLUGIN_HOOKS['plugin_pdf']['KnowbaseItem']     = 'PluginPdfKnowbaseItem';
      $PLUGIN_HOOKS['plugin_pdf']['Monitor']          = 'PluginPdfMonitor';
      $PLUGIN_HOOKS['plugin_pdf']['NetworkEquipment'] = 'PluginPdfNetworkEquipment';
      $PLUGIN_HOOKS['plugin_pdf']['Peripheral']       = 'PluginPdfPeripheral';
      $PLUGIN_HOOKS['plugin_pdf']['Phone']            = 'PluginPdfPhone';
      $PLUGIN_HOOKS['plugin_pdf']['Printer']          = 'PluginPdfPrinter';
      $PLUGIN_HOOKS['plugin_pdf']['Software']         = 'PluginPdfSoftware';
      $PLUGIN_HOOKS['plugin_pdf']['SoftwareLicense']  = 'PluginPdfSoftwareLicense';
      $PLUGIN_HOOKS['plugin_pdf']['SoftwareVersion']  = 'PluginPdfSoftwareVersion';
      $PLUGIN_HOOKS['plugin_pdf']['Ticket']           = 'PluginPdfTicket';
      $PLUGIN_HOOKS['plugin_pdf']['Problem']          = 'PluginPdfProblem';
      $PLUGIN_HOOKS['plugin_pdf']['Change']           = 'PluginPdfChange';

      // End init, when all types are registered by all plugins
      $PLUGIN_HOOKS['post_init']['pdf'] = 'plugin_pdf_postinit';

      // Integration with WebService plugin
      $PLUGIN_HOOKS['webservices']['pdf'] = 'plugin_pdf_registerMethods';
}


function plugin_version_pdf() {

   return array('name'           => __('Print to pdf', 'pdf'),
                'version'        => '1.0.2',
                'author'         => 'Remi Collet, Nelly Mahu-Lasson',
                'license'        => 'GPLv3+',
                'homepage'       => 'https://forge.indepnet.net/projects/pdf',
                'minGlpiVersion' => '0.85.3');
}


function plugin_pdf_check_prerequisites(){

   if (version_compare(GLPI_VERSION,'0.85.3','lt') || version_compare(GLPI_VERSION,'9.1','ge')) {
      echo "This plugin requires GLPI >= 0.85.3";
      return false;
   }
   return true;
}


function plugin_pdf_check_config(){
   return true;
}
