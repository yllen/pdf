<?php
/**
 * @version $Id: config.form.php 217 2017-09-29 07:37:36Z orthagh $
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Behaviors plugin for GLPI.

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
 @copyright Copyright (c) 2019 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

// No autoload when plugin is not activated
require_once('../inc/config.class.php');

$config = new PluginPdfConfig();
if (isset($_POST["update"])) {
   $config->check($_POST['id'], UPDATE);

   $config->update($_POST);

   Html::back();
}
Html::redirect($CFG_GLPI["root_doc"]."/front/config.form.php?forcetab=".
               urlencode('PluginPdfConfig$1'));
