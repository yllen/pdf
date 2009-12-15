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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

class PluginPdfProfile extends CommonDBTM {

   public $table = 'glpi_plugin_pdf_profiles';
   public $type  = 'PluginPdfProfile';


   function getSearchOptions() {
      global $LANG;

      $tab = array();

      $tab['common'] = $LANG['pulse2'][1];

      $tab['table']     = 'glpi_plugin_pdf_profiles';
      $tab['field']     = 'use';
      $tab['linkfield'] = 'id';$LANG['plugin_pdf']['title'][1];
      $tab['datatype']  = 'bool';

      return $tab;
   }


   function createProfile($profile) {

      return $this->add(array('id'      => $profile->getField('id'),
                              'profile' => $profile->getField('name')));
   }


   //if profile deleted
   function cleanProfiles($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_plugin_pdf_profiles`
                WHERE `id` = '$ID' ";
      $DB->query($query);
   }


   function showForm($target,$ID) {
      global $LANG,$DB;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $canedit = $this->can($ID,'w');

      echo "<form action='$target' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='center b'>".
            $LANG['plugin_pdf']['title'][1]. " - " .$this->fields["profile"]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['plugin_pdf']['title'][1]."&nbsp;:</td><td>";
      dropdownYesNo("use",(isset($this->fields["use"])?$this->fields["use"]:''));
      echo "</td></tr>\n";

      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>";
         echo "<input type='hidden' name='id' value=$ID>";
         echo "<input type='submit' name='update_user_profile' value='".$LANG["buttons"][7].
               "' class='submit'>&nbsp;";
         echo "</td></tr>\n";
      }
      echo "</table></form>";
   }


   static function changeprofile() {

      $tmp = new self();
       if ($tmp->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
          $_SESSION["glpi_plugin_pdf_profile"] = $tmp->fields;
       } else {
          unset($_SESSION["glpi_plugin_pdf_profile"]);
       }
   }

   static function canView() {
      return haveRight('profile','r');
   }

   static function canCreate() {
      return haveRight('profile','w');
   }
}
?>