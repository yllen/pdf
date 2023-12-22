<?php
/**
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
 @copyright Copyright (c) 2018-2022 PDF plugin team
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

      $dbu = new DbUtils();

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
         foreach ($soluce as $row) {
            if ($row['solutiontypes_id']) {
               $title = Toolbox::stripTags(Dropdown::getDropdownName('glpi_solutiontypes',
                                                                     $row['solutiontypes_id']));
            } else {
               $title = __('Solution');
            }
            $sol = Toolbox::stripTags(Glpi\Toolbox\Sanitizer::unsanitize(html_entity_decode($row['content'],
                                                                         ENT_QUOTES, "UTF-8")));

            if ($row['status'] == 3) {
               $text = __('Soluce approved on ', 'pdf');
            } else if ($row['status'] == 4) {
               $text = __('Soluce refused on ', 'pdf');
            } else {
               $text = $textapprove = '';
            }
            if (isset($row['date_approval']) || !empty($row["users_id_approval"])) {
               $textapprove = "<br /><br /><br /><i>".
                               sprintf(__('%1$s %2$s'), $text,
                                       Html::convDateTime($row['date_approval']))."&nbsp;".
                               sprintf(__('%1$s %2$s'), __('By'),
                                       Toolbox::stripTags($dbu->getUserName($row["users_id_approval"])))
                               ."</i>";
            }
            $pdf->displayText("<b><i>".sprintf(__('%1$s: %2$s'), $title."</i></b>", ''), $sol.
                              $textapprove);
         }
      }

      $pdf->displaySpace();
   }
}