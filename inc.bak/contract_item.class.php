<?php
/**
 * @version $Id: contract_item.class.php 558 2020-09-03 08:40:26Z yllen $
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
 @copyright Copyright (c) 2009-2019 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfContract_Item extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Contract_Item());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $type = $item->getType();
      $ID   = $item->getField('id');
      $con  = new Contract();
      $dbu  = new DbUtils();

     $query = ['SELECT'    =>  'glpi_contracts_items.*',
               'FROM'      => ['glpi_contracts_items', 'glpi_contracts'],
               'LEFT JOIN' => ['glpi_entities'
                               => ['FKEY' => ['glpi_contracts' => 'entities_id',
                                              'glpi_entities'  => 'id']]],
               'WHERE'    => ['glpi_contracts.id'              => '`glpi_contracts_items`.`contracts_id`',
                              'glpi_contracts_items.items_id'  => $ID ,
                              'glpi_contracts_items.itemtype'  => $type]
                              + $dbu->getEntitiesRestrictCriteria('glpi_contracts','','',true),
               'ORDER'    => 'glpi_contracts.name'];

      $result = $DB->request($query);
      $number = count($result);
      $i = $j = 0;

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Associated contract', 'Associated contracts', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, $number));

         $pdf->setColumnsSize(19,19,15,10,16,11,10);
         $pdf->displayTitle(__('Name'), __('Entity'), _x('phone', 'Number'), __('Contract type'),
                            __('Supplier'), __('Start date'), __('Initial contract period'));

         $i++;

         while ($j < $number) {
            $row     = $result->next();
            $cID     = $row['contracts_id'];
            $assocID = $row['id'];

            if ($con->getFromDB($cID)) {
               $textduration = '';
               if ($con->fields['duration'] > 0) {
                  $textduration = sprintf(__('Valid to %s'),
                                          Infocom::getWarrantyExpir($con->fields["begin_date"],
                                                                    $con->fields["duration"]));
               }
               $pdf->displayLine(
                  (empty($con->fields["name"]) ? "(".$con->fields["id"].")" : $con->fields["name"]),
                  Dropdown::getDropdownName("glpi_entities", $con->fields["entities_id"]),
                  $con->fields["num"],
                  Html::clean(Dropdown::getDropdownName("glpi_contracttypes",
                                                       $con->fields["contracttypes_id"])),
                  str_replace("<br>", " ", $con->getSuppliersNames()),
                  Html::convDate($con->fields["begin_date"]),
                  sprintf(__('%1$s - %2$s'),
                          sprintf(_n('%d month', '%d months', $con->fields["duration"]),
                                  $con->fields["duration"]),
                          $textduration));
            }
            $j++;
         }
      }
      $pdf->displaySpace();
   }
}