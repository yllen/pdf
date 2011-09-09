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

   protected $obj= NULL;

   /**
    * Constructor, should intialize $this->obj property
   **/
   abstract function __construct(CommonGLPI $obj=NULL);

   /**
    * Add standard define tab
    *
    * @param $itemtype  itemtype link to the tab
    * @param $ong       array defined tab array
    * @param $options   array of options (for withtemplate)
    *
    *  @return nothing (set the tab array)
   **/
   final function addStandardTab($itemtype, &$ong, $options) {
      global $LANG;

      $withtemplate = 0;
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      if (!is_integer($itemtype)
          && !preg_match('/^PluginPdf/', $itemtype)
          && class_exists($itemtype)) {
         $obj = new $itemtype();
         if (method_exists($itemtype, "displayTabContentForPDF")) {
            $titles = $obj->getTabNameForItem($this->obj, $withtemplate);
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


   /**
    * Get the list of the printable tab for the object
    * Can be overriden to remove some unwanted tab
    *
    * @param $options Array of options
    *
    */
   function defineAllTabs($options=array()) {
      global $LANG;

      $onglets  = array_merge(
         array('_main_' => $this->obj->getTypeName(1)),
         $this->obj->defineTabs());

      $othertabs = CommonGLPI::getOtherTabs($this->obj->getType());

      unset($onglets['empty']);

      // Add plugins TAB
      foreach($othertabs as $typetab) {
         $this->addStandardTab($typetab, $onglets, $options);
      }

      return $onglets;
   }


   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param $item         CommonDBTM object for which the tab need to be displayed
    * @param $withtemplate boolean is a template object ?
    *
    *  @return string tab name
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      return $LANG['plugin_pdf']['title'][1];
   }


   /**
    * export Tab content
    *
    * @since version 0.83
    *
    * @param $pdf   PluginPdfSimplePDF object for output
    * @param $item  CommonGLPI object for which the tab need to be displayed
    * @param $tab   string tab number
    *
    * @return true
   **/
   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {
      return false;
   }


   /**
    * show Tab content
    *
    * @since version 0.83
    *
    * @param $item         CommonGLPI object for which the tab need to be displayed
    * @param $tabnum       integer tab number
    * @param $withtemplate boolean is a template object ?
    *
    * @return true
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      $pref = new PluginPdfPreference;
      $pref->menu($item,$CFG_GLPI['root_doc']."/plugins/pdf/front/export.php");

      return true;
   }


   /**
    * Read the object and create header for all pages
    *
    * @param $ID integer, ID of the object to print
   **/
   private function addHeader($ID) {
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


   /**
    * Generate the PDF for some object
    *
    * @param $tab_id  Array   of ID of object to print
    * @param $tabs    Array   of name of tab to print
    * @param $page    Integer 1 for landscape, 0 for portrait
    * @param $render  Boolean send result if true,  return result if false
    *
    * @return pdf output if $render is false
   **/
   final function generatePDF($tab_id, $tabs, $page=0, $render=true) {

      $this->pdf = new PluginPdfSimplePDF('a4', ($page ? 'landscape' : 'portrait'));

      foreach ($tab_id as $key => $id) {
         if ($this->addHeader($id)) {
            $this->pdf->newPage();
         } else {
            // Object not found or no right to read
            continue;
         }

         foreach ($tabs as $tab) {
            if (!$this->displayTabContentForPDF($this->pdf, $this->obj, $tab)) {

            }
         }
      }
      if($render) {
         $this->pdf->render();
      } else {
         return $this->pdf->output();
      }
   }
}