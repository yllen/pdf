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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

class PluginPdfProfile extends CommonDBTM {


   function getSearchOptions() {
      global $LANG;

      $tab = array();

      $tab['common'] = $LANG['plugin_pdf']['title'][1];

      $tab['table']     = $this->getTable();
      $tab['field']     = 'use';
      $tab['linkfield'] = 'id';
      $tab['datatype']  = 'bool';

      return $tab;
   }


   function createProfile($profile) {

      return $this->add(array('id'      => $profile->getField('id'),
                              'profile' => $profile->getField('name')));
   }


   //if profile deleted
   static function cleanProfiles(Profile $prof) {
      $plugprof = new self();
      $plugprof->delete(array('id'=>$prof->getField("id")));
   }


   function showForm($ID, $options=array()) {
      global $LANG,$DB;

      $target = $this->getFormURL();
      if (isset($options['target'])) {
        $target = $options['target'];
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $canedit = $this->can($ID,'w');

      echo "<form action='".$target."' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='center b'>".
            $LANG['plugin_pdf']['title'][1]. " - " .$this->fields["profile"]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['plugin_pdf']['title'][1]."&nbsp;:</td><td>";
      Dropdown::showYesNo("use",(isset($this->fields["use"])?$this->fields["use"]:''));
      echo "</td></tr>\n";

      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>";
         echo "<input type='hidden' name='id' value=$ID>";
         echo "<input type='submit' name='update_user_profile' value='".$LANG["buttons"][7].
               "' class='submit'>&nbsp;";
         echo "</td></tr>\n";
      }
      echo "</table>";
      Html::closeForm();
   }


   static function changeprofile() {

      $tmp = new self();
       if ($tmp->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
          $_SESSION["glpi_plugin_pdf_profile"] = $tmp->fields;
       } else {
          unset($_SESSION["glpi_plugin_pdf_profile"]);
       }
   }

   function canView() {
      return Session::haveRight('profile','r');
   }

   function canCreate() {
      return Session::haveRight('profile','w');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if ($item->getType() == 'Profile') {
         return $LANG['plugin_pdf']['title'][1];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Profile') {
         $prof =  new self();
         $ID = $item->getField('id');
         if (!$prof->GetfromDB($ID)) {
            $prof->createProfile($item);
         }
         $prof->showForm($ID);
      }
      return true;
   }
}
?>