<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2013 by the pdf Development Team.

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
    * @return nothing (set the tab array)
   **/
   final function addStandardTab($itemtype, &$ong, $options) {

      $withtemplate = 0;
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      if (!is_integer($itemtype)
          && ($obj = getItemForItemtype($itemtype))) {

         if (method_exists($itemtype, "displayTabContentForPDF")
             && !($obj instanceof PluginPdfCommon)) {

            $titles = $obj->getTabNameForItem($this->obj, $withtemplate);
            if (!is_array($titles)) {
               $titles = array(1 => $titles);
            }

            foreach ($titles as $key => $val) {
               if (!empty($val)) {
                  $ong[$itemtype.'$'.$key] = $val;
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
   **/
   function defineAllTabs($options=array()) {

      $onglets  = array_merge(array('_main_' => $this->obj->getTypeName(1)),
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

      if(!isset($withtemplate) || empty($withtemplate)) {
         return __('Print to pdf', 'pdf');
      }
   }


   /**
    * export Tab content - specific content for this type
    * is run first, before displayCommonTabForPDF.
    *
    * @since version 0.83
    *
    * @param $pdf          PluginPdfSimplePDF object for output
    * @param $item         CommonGLPI object for which the tab need to be displayed
    * @param $tab   string tab number
    *
    * @return true if display done (else will search for another handler)
   **/
   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {
      return false;
   }


   /**
    * export Tab content - classic content use by various object
    *
    * @since version 0.83
    *
    * @param $pdf          PluginPdfSimplePDF object for output
    * @param $item         CommonGLPI object for which the tab need to be displayed
    * @param $tab   string tab number
    *
    * @return true if display done (else will search for another handler)
   **/
   static final function displayCommonTabForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            static::pdfMain($pdf, $item);
            break;

         case 'Note' :
            self::pdfNote($pdf, $item);
            break;

         case 'Document$1' :
            PluginPdfDocument::pdfForItem($pdf, $item);
            break;

         case 'NetworkPort$1' :
            PluginPdfNetworkPort::pdfForItem($pdf, $item);
            break;

         case 'Infocom$1' :
            PluginPdfInfocom::pdfForItem($pdf, $item);
            break;

         case 'Contract_Item$1' :
            PluginPdfContract_Item::pdfForItem($pdf, $item);
            break;

         case 'Ticket$1' :
            PluginPdfTicket::pdfForItem($pdf, $item);
            break;

         case 'Link$1' :
            PluginPdfLink::pdfForItem($pdf, $item);
            break;

         case 'Reservation$1' :
            PluginPdfReservation::pdfForItem($pdf, $item);
            break;

         case 'Log$1' :
            PluginPdfLog::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
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
      $pref->menu($item, $CFG_GLPI['root_doc']."/plugins/pdf/front/export.php");

      return true;
   }


   /**
    * Read the object and create header for all pages
    *
    * @param $ID integer, ID of the object to print
   **/
   private function addHeader($ID) {

      $entity = '';
      if ($this->obj->getFromDB($ID) && $this->obj->can($ID,'r')) {
         if ($this->obj->getType()!='Ticket'
             && $this->obj->getType()!='KnowbaseItem'
             && $this->obj->isField('name')) {
            $name = $this->obj->getField('name');
         } else {
            $name = sprintf(__('%1$s %2$s'), __('ID'), $ID);
         }
         if (Session::isMultiEntitiesMode() && $this->obj->isEntityAssign()) {
            $entity = ' ('.Html::clean(Dropdown::getDropdownName('glpi_entities',
                                                                $this->obj->getEntityID())).')';
         }
         $this->pdf->setHeader(sprintf(__('%1$s - %2$s'), $this->obj->getTypeName(),
                                       sprintf(__('%1$s %2$s'), '<b>'.$name.'</b>', $entity)));

         return true;
      }
      return false;
   }


   static function pdfNote(PluginPdfSimplePDF $pdf, CommonDBTM $item) {

      $ID   = $item->getField('id');
      $note = trim($item->getField('notepad'));

      $pdf->setColumnsSize(100);
      if (Toolbox::strlen($note) > 0) {
         $pdf->displayTitle('<b>'.__('Notes').'</b>');
         $pdf->displayText('', $note, 5);
      } else {
         $pdf->displayTitle('<b>'.__('No note found', 'pdf').'</b>');
      }
      $pdf->displaySpace();
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
            if (!$this->displayTabContentForPDF($this->pdf, $this->obj, $tab)
                && !$this->displayCommonTabForPDF($this->pdf, $this->obj, $tab)) {

               $data     = explode('$',$tab);
               $itemtype = $data[0];
               // Default set
               $tabnum   = (isset($data[1]) ? $data[1] : 1);

               if (!is_integer($itemtype)
                   && ($itemtype != 'empty')
                   && method_exists($itemtype, "displayTabContentForPdf")
                   && ($obj = getItemForItemtype($itemtype))) {
                  if ($obj->displayTabContentForPdf($this->pdf, $this->obj, $tabnum)) {
                     continue;
                  }
               }
               Toolbox::logInFile('php-errors',
                                  sprintf(__("PDF: don't know how to display %s tab").'\n', $tab));
            }
         }
      }
      if($render) {
         $this->pdf->render();
      } else {
         return $this->pdf->output();
      }
   }


   static function mainTitle(PluginPdfSimplePDF $pdf, $item) {

      $pdf->setColumnsSize(50,50);

      $col1 = '<b>'.sprintf(__('%1$s %2$s'),__('ID'), $item->fields['id']).'</b>';
      $col2 = sprintf(__('%1$s: %2$s'), __('Last update'),
                      Html::convDateTime($item->fields['date_mod']));
      if (!empty($printer->fields['template_name'])) {
         $col2 = sprintf(__('%1$s (%2$s)'), $col2,
                         sprintf(__('%1$s: %2$s'), __('Template name'),
                                 $item->fields['template_name']));
      }
      return $pdf->displayTitle($col1, $col2);
   }


   static function mainLine(PluginPdfSimplePDF $pdf, $item, $field) {

      $type = Toolbox::strtolower($item->getType());
      switch($field) {
         case 'name-status' :
            return $pdf->displayLine(
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>',
                                      $item->fields['name']),
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Status').'</i></b>',
                                      Html::clean(Dropdown::getDropdownName('glpi_states',
                                                                            $item->fields['states_id']))));

         case 'location-type' :
            return $pdf->displayLine(
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Location').'</i></b>',
                                      Html::clean(Dropdown::getDropdownName('glpi_locations',
                                                                            $item->fields['locations_id']))),
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Type').'</i></b>',
                                      Html::clean(Dropdown::getDropdownName('glpi_'.$type.'types',
                                                                            $item->fields[$type.'types_id']))));

         case 'tech-manufacturer' :
            return $pdf->displayLine(
                     '<b><i>'.sprintf(__('%1$s: %2$s'),
                                      __('Technician in charge of the hardware').'</i></b>',
                                      getUserName($item->fields['users_id_tech'])),
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Manufacturer').'</i></b>',
                                      Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                                            $item->fields['manufacturers_id']))));
         case 'group-model' :
            return $pdf->displayLine(
                     '<b><i>'.sprintf(__('%1$s: %2$s'),
                                      __('Group in charge of the hardware').'</i></b>',
                                      Html::clean(Dropdown::getDropdownName('glpi_groups',
                                                                            $item->fields['groups_id_tech']))),
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Model').'</i></b>',
                                      Html::clean(Dropdown::getDropdownName('glpi_'.$type.'models',
                                                                            $item->fields[$type.'models_id']))));

         case 'usernum-serial' :
            return $pdf->displayLine(
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Alternate username number').'</i></b>',
                                      $item->fields['contact_num']),
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Serial number').'</i></b>',
                                      $item->fields['serial']));

         case 'user-otherserial' :
            return $pdf->displayLine(
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Alternate username').'</i></b>',
                                      $item->fields['contact']),
                     '<b><i>'.sprintf(__('%1$s: %2$s'), __('Inventory number').'</i></b>',
                                      $item->fields['otherserial']));

         case 'comment' :
            return $pdf->displayText('<b><i>'.sprintf(__('%1$s: %2$s'), __('Comments').'</i></b>',
                                                      $item->fields['comment']));

       default :
        return;
      }
   }

}