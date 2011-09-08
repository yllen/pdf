<?php

/*
 * @version $Id$
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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

abstract class PluginPdfCommon {

   static protected $othertabs = array();

   protected $obj= NULL;

   abstract function __construct();

   static function registerStandardTab($typeform, $typetab) {

      if (isset(self::$othertabs[$typeform])) {
         self::$othertabs[$typeform][] = $typetab;
      } else {
         self::$othertabs[$typeform] = array($typetab);
      }
   }


   /**
    * Add standard define tab
    *
    * @param $itemtype  itemtype link to the tab
    * @param $ong       array defined tab array
    * @param $options   array of options (for withtemplate)
    *
    *  @return nothing (set the tab array)
   **/
   function addStandardTab($itemtype, &$ong, $options) {
      global $LANG;

      $withtemplate = 0;
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      if (!is_integer($itemtype) && class_exists($itemtype)) {
         $obj = new $itemtype();
         if (method_exists($obj, "getTabNameForPdf")) {
            $titles = $obj->getTabNameForPdf($this, $withtemplate);
            if (!is_array($titles)) {
               $titles = array(1 => $titles);
            }

            foreach ($titles as $key => $val) {
               if (!empty($val)) {
                  $ong[$itemtype.'####'.$key] = $val;
               }
            }
         }
      }
   }


   function defineAllTabs($options=array()) {

      $onglets  = $this->obj->defineTabs();
      unset($onglets['empty']);

      // Add plugins TAB
      if (isset(self::$othertabs[$this->obj->getType()]) && !$this->obj->isNewItem()) {
         foreach(self::$othertabs[$this->obj->getType()] as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
         }
      }

      return $onglets;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      return $LANG['plugin_pdf']['title'][1];
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      $pref = new PluginPdfPreference;
      $pref->menu($item,$CFG_GLPI['root_doc']."/plugins/pdf/front/export.php");

      return true;
   }


   function addHeader($ID) {
      global $LANG;

      $entity = '';
      if ($this->obj->getFromDB($ID) && $this->obj->can($ID,'r')) {
         if ($this->obj->getType()!='Ticket'
             && $this->obj->getType()!='KnowbaseItem'
             && $this->obj->isField('name')) {
            $name = $this->obj->getField('name');
         } else {
            $name = $LANG["common"][2].' '.$ID;
         }
         if (Session::isMultiEntitiesMode() && isset($this->obj->fields['entities_id'])) {
            $entity = ' ('.Html::clean(Dropdown::getDropdownName('glpi_entities',
                                                                $this->obj->fields['entities_id'])).')';
         }
         $this->pdf->setHeader($this->obj->getTypeName()." - <b>$name</b>$entity");

         return true;
      }
      return false;
   }


   function generatePDF($tab_id, $tab, $page=0, $render=true) {
      Toolbox::logDebug("generatePDF", $tab);

      $this->pdf = new PluginPdfSimplePDF('a4', ($page ? 'landscape' : 'portrait'));

      foreach ($tab_id as $key => $id) {
         if ($this->addHeader($id)) {
            $this->pdf->newPage();
         } else {
            // Object not found or no right to read
            continue;
         }
      }
      if($render) {
         $this->pdf->render();
      } else {
         return $this->pdf->output();
      }
   }
}