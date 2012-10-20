<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2012 by the pdf Development Team.

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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfKnowbaseItem extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new KnowbaseItem());
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['KnowbaseItem$1']);
      return $onglets;
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, KnowbaseItem $item){
      global $DB,$LANG;

      $ID = $item->getField('id');

      if (!Session::haveRight('knowbase', 'r') || !Session::haveRight('faq', 'r')) {
         return false;
      }

      $knowbaseitemcategories_id = $item->getField('knowbaseitemcategories_id');
      $fullcategoryname = Html::clean(getTreeValueCompleteName("glpi_knowbaseitemcategories",
                                                   $knowbaseitemcategories_id));

      $question = Html::clean(Toolbox::unclean_cross_side_scripting_deep(
                  html_entity_decode($item->getField('name'),
                                          ENT_QUOTES, "UTF-8")));

      $answer = Html::clean(Toolbox::unclean_cross_side_scripting_deep(
                  html_entity_decode($item->getField('answer'),
                                          ENT_QUOTES, "UTF-8")));


      $pdf->setColumnsSize(100);

      if (Toolbox::strlen($fullcategoryname) > 0) {
         $pdf->displayTitle('<b>'.$LANG['common'][36].'</b>');
         $pdf->displayLine($fullcategoryname);
      }

      if (Toolbox::strlen($question) > 0) {
         $pdf->displayTitle('<b>'.$LANG['knowbase'][14].'</b>');
         $pdf->displayText('', $question, 5);
      } else {
         $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['knowbase'][1].'</b>');
      }

      if (Toolbox::strlen($answer) > 0) {
         $pdf->displayTitle('<b>'.$LANG['knowbase'][15].'</b>');
         $pdf->displayText('', $answer, 5);
      } else {
         $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['knowbase'][2].'</b>');
      }

      $pdf->setColumnsSize(50,15,15,10,10);
      $pdf->displayTitle($LANG['common'][37],   // Author
                         $LANG['common'][27],   // Created
                         $LANG['common'][26],   // Updated TODO : find a shorter locale
                         $LANG['Menu'][20],     // FAQ
                         $LANG['knowbase'][26]);   // # of Views
      $pdf->displayLine(getUserName($item->fields["users_id"]),
                        Html::convDateTime($item->fields["date"]),
                        Html::convDateTime($item->fields["date_mod"]),
                        Dropdown::getYesNo($item->fields["is_faq"]),
                        $item->fields["view"]);

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

         case 'Document$1' :
            PluginPdfDocument::pdfForItem($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}