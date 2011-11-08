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

class PluginPdfContract_Item extends PluginPdfCommon {

   function __construct(Contract_Item $obj=NULL) {

      $this->obj = ($obj ? $obj : new Contract_Item());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB,$CFG_GLPI,$LANG;

      if (!Session::haveRight("contract","r")) {
         return false;
      }

      $type = $item->getType();
      $ID = $item->getField('id');
      $con = new Contract();

      $query = "SELECT *
                FROM `glpi_contracts_items`
                WHERE `glpi_contracts_items`.`items_id` = '$ID'
                      AND `glpi_contracts_items`.`itemtype` = '$type'";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = $j = 0;

      $pdf->setColumnsSize(100);
      if ($number > 0) {
         $pdf->displayTitle('<b>'.$LANG["financial"][66].'</b>');

         $pdf->setColumnsSize(19,19,19,16,11,16);
         $pdf->displayTitle($LANG["common"][16], $LANG["financial"][4], $LANG["financial"][6],
                            $LANG["financial"][26], $LANG["search"][8], $LANG["financial"][8]);

         $i++;

         while ($j < $number) {
            $cID = $DB->result($result, $j, "contracts_id");
            $assocID = $DB->result($result, $j, "id");

            if ($con->getFromDB($cID)) {
               $pdf->displayLine(
                  (empty($con->fields["name"]) ? "(".$con->fields["id"].")" : $con->fields["name"]),
                  $con->fields["num"],
                  Html::clean(Dropdown::getDropdownName("glpi_contracttypes",
                                                       $con->fields["contracttypes_id"])),
                  str_replace("<br>", " ", $con->getSuppliersNames()),
                  Html::convDate($con->fields["begin_date"]),
                  $con->fields["duration"]." ".$LANG["financial"][57]);
            }
            $j++;
         }
      } else {
         $pdf->displayTitle("<b>".$LANG['plugin_pdf']['financial'][2]."</b>");
      }
      $pdf->displaySpace();
   }
}