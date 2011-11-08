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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfInfocom extends PluginPdfCommon {

   function __construct(Infocom $obj=NULL) {

      $this->obj = ($obj ? $obj : new Infocom());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $CFG_GLPI,$LANG;

      $ID = $item->getField('id');

      if (!Session::haveRight("infocom","r")) {
         return false;
      }

      $ic = new Infocom();

      $pdf->setColumnsSize(100);
      if ($ic->getFromDBforDevice(get_class($item),$ID)) {
         $pdf->displayTitle("<b>".$LANG["financial"][3]."</b>");

         $pdf->setColumnsSize(50,50);

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][26]." :</i></b> ".
               Html::clean(Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"])),
            "<b><i>".$LANG["financial"][87]." :</i></b> ".
               Html::clean(Dropdown::getDropdownName("glpi_budgets", $ic->fields["budgets_id"])));

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][18]." :</i></b> ".$ic->fields["order_number"],
            "<b><i>".$LANG["financial"][28]." :</i></b> ".Html::convDate($ic->fields["order_date"]));

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][20]." :</i></b> ".$ic->fields["immo_number"],
            "<b><i>".$LANG["financial"][14]." :</i></b> ".Html::convDate($ic->fields["buy_date"]));

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][82]." :</i></b> ".$ic->fields["bill"],
            "<b><i>".$LANG["financial"][27]." :</i></b> ".Html::convDate($ic->fields["delivery_date"]));

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][19]." :</i></b> ".$ic->fields["delivery_number"],
            "<b><i>".$LANG["financial"][76]." :</i></b> ".Html::convDate($ic->fields["use_date"]));

         $pdf->displayLine(
            "<b><i>".$LANG["rulesengine"][13]." :</i></b> ".Html::clean(Html::formatNumber($ic->fields["value"])),
            "<b><i>".$LANG["financial"][114]." :</i></b> ".Html::convDate($ic->fields["inventory_date"]));

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][78]." :</i></b> ".Html::clean(Html::formatNumber($ic->fields["warranty_value"])),
            "<b><i>".$LANG["financial"][23]." :</i></b> ".$ic->fields["sink_time"]." ". $LANG['financial'][9].
               ' ('.Infocom::getAmortTypeName($ic->fields["sink_type"]).')');

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][81]." :</i></b> ".
               Infocom::Amort($ic->fields["sink_type"], $ic->fields["value"], $ic->fields["sink_time"],
                              $ic->fields["sink_coeff"], $ic->fields["buy_date"], $ic->fields["use_date"],
                              $CFG_GLPI['date_tax'],"n"),
            "<b><i>".$LANG["financial"][77]." :</i></b> ".$ic->fields["sink_coeff"]);

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][89]." :</i></b> ".
               Html::clean(Infocom::showTco($item->getField('ticket_tco'), $ic->fields["value"])),
            "<b><i>".$LANG["financial"][90]." :</i></b> ".
               Html::clean(Infocom::showTco($item->getField('ticket_tco'), $ic->fields["value"], $ic->fields["buy_date"])));

         $pdf->displayText('<b><i>'.$LANG["common"][25].' :</i></b>', $ic->fields["comment"], 1);

         $pdf->setColumnsSize(100);
         $pdf->displayTitle("<b>".$LANG["financial"][7]."</b>");

         $pdf->setColumnsSize(50,50);

         $pdf->displayLine(
            "<b><i>".$LANG["financial"][29]." :</i></b> ".Html::convDate($ic->fields["warranty_date"]),
            "<b><i>".$LANG["financial"][15]." :</i></b> ".$ic->fields["warranty_duration"].' '.$LANG['financial'][57].
                  ', '.$LANG['financial'][88].Infocom::getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]));

         $col1 = "<b><i>".$LANG["setup"][247]." :</i></b> ";
         if ($ic->fields["alert"] == 0) {
            $col1 .= $LANG['choice'][0];
         } else if ($ic->fields["alert"] == 4) {
            $col1 .= $LANG["financial"][80];
         }
         $pdf->displayLine(
            "<b><i>".$LANG["financial"][16]." :</i></b> ".$ic->fields["warranty_info"],
            $col1);
      } else {
         $pdf->displayTitle("<b>".$LANG['plugin_pdf']['financial'][1]."</b>");
      }

      $pdf->displaySpace();
   }
}