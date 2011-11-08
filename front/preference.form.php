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
   Html::back();
}

?>