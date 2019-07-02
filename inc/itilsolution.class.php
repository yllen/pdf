<?php
/**
 * @version $Id:  yllen $
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
 @copyright Copyright (c) 2018-2019 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfITILSolution extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ITILSolution());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $pdf->setColumnsSize(100);

      $soluce = $DB->request('glpi_itilsolutions',
                             ['itemtype'   => $item->getType(),
                              'items_id'   => $item->fields['id']]);

      $number = count($soluce);

      $title = '<b>'.__('Solution').'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, $number);
         $pdf->displayTitle($title);
         while ($row = $soluce->next()) {
            if ($row['solutiontypes_id']) {
               $title = Html::clean(Dropdown::getDropdownName('glpi_solutiontypes',
                                                              $row['solutiontypes_id']));
            } else {
               $title = __('Solution');
            }
            $sol = Html::clean(Toolbox::unclean_cross_side_scripting_deep(
                                                      html_entity_decode($row['content'],
                                                                         ENT_QUOTES, "UTF-8")));

            if ($row['status'] == 3) {
               $text = __('Soluce approved on ', 'pdf');
            } else if ($row['status'] == 4) {
               $text = __('Soluce refused on ', 'pdf');
            }
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'), $title."</i></b>", ''), $sol.
                              "<br /><br /><br /><i>".sprintf(__('%1$s %2$s'), $text,
                                                              $row['date_approval'])."</i>");
         }
      }

      $pdf->displaySpace();
   }
}