<?php

/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// Original Author of file: Balpe Dévi / Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}

include_once (GLPI_ROOT . "/inc/includes.php");

//Save user preferences
if (isset($_POST['plugin_pdf_user_preferences_save'])
    && isset($_POST["plugin_pdf_inventory_type"])) {

   $DB->query("DELETE
               FROM `glpi_plugin_pdf_preferences`
               WHERE `users_id` ='" . $_SESSION["glpiID"] . "'
                     AND `itemtype`='" . $_POST["plugin_pdf_inventory_type"]."'");

   if (isset($_POST['item'])) {
      foreach ($_POST['item'] as $key => $val) {
         $DB->query("INSERT INTO
                     `glpi_plugin_pdf_preferences` (`id` ,`users_id` ,`itemtype` ,`tabref`)
                     VALUES (NULL , '".$_SESSION["glpiID"]."',
                             '".$_POST["plugin_pdf_inventory_type"]."', '$key')");
      }
   }
   if (isset($_POST["page"]) && $_POST["page"]) {
      $DB->query("INSERT INTO
                  `glpi_plugin_pdf_preferences` (`id` ,`users_id` ,`itemtype` ,`tabref`)
                  VALUES (NULL , '".$_SESSION["glpiID"]."',
                          '".$_POST["plugin_pdf_inventory_type"]."', 'landscape')");
   }
   glpi_header($_SERVER['HTTP_REFERER']);
}

?>