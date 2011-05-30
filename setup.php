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

function plugin_init_pdf() {
   global $PLUGIN_HOOKS,$LANG;

   // Params : plugin name - string type - number - attributes
   Plugin::registerClass('PluginPdfProfile');

   Plugin::registerClass('PluginPdfPreference');

   $PLUGIN_HOOKS['change_profile']['pdf'] = array('PluginPdfProfile','changeprofile');

   if (isset($_SESSION["glpi_plugin_pdf_profile"])
       && $_SESSION["glpi_plugin_pdf_profile"]["use"]) {

      $PLUGIN_HOOKS['use_massive_action']['pdf']   = 1;
      $PLUGIN_HOOKS['headings']['pdf']             = 'plugin_pdf_get_headings';
      $PLUGIN_HOOKS['headings_action']['pdf']      = 'plugin_pdf_headings_actions';

      $PLUGIN_HOOKS['pre_item_purge']['pdf'] = array('Profile' => array('PluginPdfProfile','cleanProfiles'));
   }

   // Define the type for which we know how to generate PDF, need :
   // - plugin_pdf_prefPDF($type)
   // - plugin_pdf_generatePDF($type, $tab_id, $tab, $page=0)
   $PLUGIN_HOOKS['plugin_pdf']['Computer']        = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['Software']        = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['SoftwareLicense'] = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['SoftwareVersion'] = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['Printer']         = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['Monitor']         = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['Phone']           = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['Peripheral']      = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['Ticket']          = 'pdf';
   $PLUGIN_HOOKS['plugin_pdf']['KnowbaseItem']    = 'pdf';
}


function plugin_version_pdf() {
   global $LANG;

   return array('name'           => $LANG['plugin_pdf']['title'][1],
                'version'        => '0.8.0',
                'author'         => 'Dévi Balpe, Remi Collet, Nelly Lasson, Walid Nouh',
                'homepage'       => 'https://forge.indepnet.net/wiki/pdf/',
                'minGlpiVersion' => '0.80');
}


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_pdf_check_prerequisites(){

   if (version_compare(GLPI_VERSION,'0.80','lt') || version_compare(GLPI_VERSION,'0.81','ge')) {
      echo "This plugin requires GLPI 0.80";
   }
   return true;
}


// Config process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_pdf_check_config(){
   return TableExists("glpi_plugin_pdf_profiles");
}

?>