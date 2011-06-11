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

// Original Author of file: BALPE Dévi
// Purpose of file:
// ----------------------------------------------------------------------

class PluginPdfPreference extends CommonDBTM {


   function showPreferences($target) {
      global $LANG, $DB, $CFG_GLPI, $PLUGIN_HOOKS;

      echo "<div class='center' id='pdf_type'>";
      foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $plug) {
         if (!class_exists($type)) {
            continue;
         }
         $item = new $type();
         if ($item->canView()) {
            $this->menu($item, $target);
         }
      }
      echo "</div>";
   }


    function checkbox($num,$label,$checked=false) {

       echo "<td width='20%'><input type='checkbox' ".($checked==true?"checked='checked'":'').
             " name='item[$num]' value='1'>".$label."</td>";
    }


   function menu($item, $action) {
      global $LANG, $DB, $PLUGIN_HOOKS;

      $type = get_class($item);

      // $ID set if current object, not set from preference
      $ID = (isset($item->fields['id']) ? $item->fields['id'] : 0);

       if (!isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
          return;
       }

       // Main options
       $options = doOneHook($PLUGIN_HOOKS['plugin_pdf'][$type], "prefPDF", $item);
       if (!is_array($options)) {
          return;
       }

       // Plugin options
       if (isset($PLUGIN_HOOKS["headings"]) && is_array($PLUGIN_HOOKS["headings"])) {
          foreach ($PLUGIN_HOOKS["headings"] as $plug => $funcname) {
             if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
             }

             if (is_callable($funcname)
                 && isset($PLUGIN_HOOKS["headings_actionpdf"][$plug])
                 && is_callable($funcaction=$PLUGIN_HOOKS["headings_actionpdf"][$plug])) {

                $title = call_user_func($funcname,$item,'');
                $calls = call_user_func($funcaction,$item,'');

                if (is_array($title) && count($title)) {
                   foreach ($title as $key => $val) {
                      $opt = $plug."_".$key;
                      if (isset($calls[$key]) && is_callable($calls[$key])) {
                         $options[$opt]=$val;
                      }
                   }
                }
             }
          }
       }

       echo "<form name='plugin_pdf_$type' id='plugin_pdf_$type' action='$action' method='post' ".
             ($ID ? "target='_blank'" : "")."><table class='tab_cadre_fixe'>";

       $landscape = false;
       $values = array();
       $sql = "SELECT `tabref`
               FROM `".$this->getTable()."`
               WHERE `users_ID` = '" . $_SESSION['glpiID'] . "'
                     AND `itemtype` = '$type'";

       foreach ($DB->request($sql) AS $data) {
          if ($data["tabref"]=='landscape') {
             $landscape = true;
          } else {
             $values[$data["tabref"]] = $data["tabref"];
          }
       }

       echo "<tr><th colspan='6'>" . $LANG['plugin_pdf']['title'][2]. "&nbsp;: ".
               $item->getTypeName() ."</th></tr>";

       $i = 0;
       foreach ($options as $num => $title) {
          if (!$i) {
             echo "<tr class='tab_bg_1'>";
          }
          $this->checkbox($num,$title,(isset($values[$num])?true:false));
          if ($i==4) {
             echo "</tr>";
             $i = 0;
          } else {
             $i++;
          }
       }
       if ($i) {
          while ($i<=4) {
             echo "<td width='20%'>&nbsp;</td>";
             $i++;
          }
          echo "</tr>";
       }

       echo "<tr class='tab_bg_2'><td colspan='2' class='left'>";
       echo "<a onclick=\"if (markCheckboxes('plugin_pdf_$type') ) return false;\" href='".
             $_SERVER['PHP_SELF']."?select=all'>".$LANG['buttons'][18]."</a> / ";
       echo "<a onclick=\"if (unMarkCheckboxes('plugin_pdf_$type') ) return false;\" href='".
             $_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a></td>";

       echo "<td colspan='4' class='center'>";
       echo "<input type='hidden' name='plugin_pdf_inventory_type' value='$type'>";
       echo "<input type='hidden' name='indice' value='".count($options)."'>";

       echo "<select name='page'>\n";
       echo "<option value='0'>".$LANG['common'][69]."</option>\n"; // Portrait
       echo "<option value='1'".($landscape?"selected='selected'":'').">".$LANG['common'][68].
            "</option>\n"; // Paysage
       echo "</select>&nbsp;&nbsp;&nbsp;&nbsp;\n";

       if ($ID) {
          echo "<input type='hidden' name='itemID' value='$ID'>";
          echo "<input type='submit' value='" . $LANG['plugin_pdf']['button'][1] .
                "' name='generate' class='submit'></td></tr>";
       } else {
          echo "<input type='submit' value='" . $LANG['plugin_pdf']['button'][2] .
                "' name='plugin_pdf_user_preferences_save' class='submit'></td></tr>";
       }
       echo "</table></form>";
    }

}

?>