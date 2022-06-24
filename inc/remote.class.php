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


class PluginPdfRemote  {

   static $rightname = "plugin_pdf";


   static function methodGetTabs($params, $protocol) {
      global $PLUGIN_HOOKS;

      if (isset ($params['help'])) {
         return ['help'   => 'bool,optional',
                 'type'   => 'string'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['type'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                     '', 'type');
      }
      $type = $params['type'];

      if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])
          && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {

         $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
         return $itempdf->defineAllTabs();
      }

      return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_FAILED);
   }


   static function methodGetPdf($params, $protocol) {
      global $PLUGIN_HOOKS;

      if (isset ($params['help'])) {
            return ['help'      => 'bool,optional',
                    'type'      => 'string',
                    'id'        => 'integer',
                    'landscape' => 'bool,optional',
                    'tabs'      => 'string,optional',
                    'alltabs'   => 'bool,optional'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }
      if (!isset($params['type'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                     '', 'type');
      }
      $dbu = new DbUtils();
      $type = $params['type'];
      if (!$item = $dbu->getItemForItemtype($type)) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                                                     'type');
      }
      if (!isset($params['id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                     '', 'id');
      }
      if (!is_numeric($params['id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                                                     'id');
      }
      $id = intval($params['id']);

      $landscape = (isset($params['landscape']) ? intval($params['landscape']) : false);

      if (!$item->can($id, READ)) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }

      if (isset($params['tabs'])) {
         if (isset($params['alltabs'])) {
            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER,
                                                        '', 'tabs+alltabs');
         }
         $tabs = explode(',', $params['tabs']);
      } else {
         $tabs = [$type.'$main'];
      }
      if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])  && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {
         $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
         if (isset($params['alltabs'])) {
            $tabs = $itempdf->defineAllTabs();
            $tabs = array_keys($tabs);
         }
         $out = $itempdf->generatePDF([$id], $tabs, $landscape, false);
         return ['name'   => "$type-$id.pdf",
                 'base64' => base64_encode($out)];
      }
      return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_FAILED);
   }
}
