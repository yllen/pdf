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


class PluginPdfProfile extends Profile {

   static $rightname = 'profile';


   function getSearchOptions() {

      $tab = array();

      $tab['common'] = __('Print to pdf', 'pdf');

      $tab['table']     = $this->getTable();
      $tab['field']     = 'use';
      $tab['linkfield'] = 'id';
      $tab['datatype']  = 'bool';

      return $tab;
   }


   function showForm($ID, $options=array()) {
      global $DB;

      $profile      = new Profile();

      if ($canedit = Session::haveRightsOr(self::$rightname, array(CREATE, UPDATE, PURGE))) {
         echo "<form action='".$profile->getFormURL()."' method='post'>";
      }
      $profile->getFromDB($ID);

      $real_right = ProfileRight::getProfileRights($ID, array('plugin_pdf'));
      $checked = 0;
      if (isset($real_right)
          && ($real_right['plugin_pdf'] == 1)) {
         $checked = 1;
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='center b'>".__('Print to pdf', 'pdf');
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Print to pdf', 'pdf')."</td><td>";
      Html::showCheckbox(array('name'    => '_plugin_pdf',
                               'checked' => $checked));
      echo "</td></tr></table>\n";

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', array('value' => $ID));
         echo Html::submit(_sx('button', 'Update'), array('name' => 'update'));
         echo "</div>\n";
         Html::closeForm();
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == 'Profile') {
         return __('Print to pdf', 'pdf');
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Profile') {
         $prof =  new self();
         $ID = $item->getField('id');
         $prof->showForm($ID);
      }
      return true;
   }

}
