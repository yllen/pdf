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
 @copyright Copyright (c) 2009-2022 PDF plugin team
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

      $type       = $item->getType();
      $ID         = $item->getField('id');
      $itemtable  = getTableForItemType($type);
      $con        = new Contract();
      $dbu        = new DbUtils();

     $query = ['SELECT'    => ['glpi_contracts_items.*', 'glpi_contracts.*'],
               'FROM'      => 'glpi_contracts_items',
               'LEFT JOIN' => ['glpi_contracts'
                               => ['FKEY' => ['glpi_contracts' => 'id',
                                             'glpi_contracts_items' => 'contracts_id']]],
               'WHERE'    => ['glpi_contracts_items.items_id' => $ID ,
                              'glpi_contracts_items.itemtype' => $type]
                              + $dbu->getEntitiesRestrictCriteria('glpi_contracts','','',true),
               'ORDER'    => 'glpi_contracts.name'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Associated contract', 'Associated contracts', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, $number));

         $pdf->setColumnsSize(19,19,15,10,16,11,10);
         $pdf->displayTitle(__('Name'), __('Entity'), _x('phone', 'Number'), __('Contract type'),
                            __('Supplier'), __('Start date'), __('Initial contract period'));

         foreach ($result as $row) {
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
                  Toolbox::stripTags(Dropdown::getDropdownName("glpi_contracttypes",
                                                               $con->fields["contracttypes_id"])),
                  str_replace("<br>", " ", $con->getSuppliersNames()),
                  Html::convDate($con->fields["begin_date"]),
                  sprintf(__('%1$s - %2$s'),
                          sprintf(_n('%d month', '%d months', $con->fields["duration"]),
                                  $con->fields["duration"]),
                          $textduration));
            }
         }
      }
      $pdf->displaySpace();
   }


   static function pdfForContract(PluginPdfSimplePDF $pdf, Contract $contract) {
      global $DB;

      $instID = $contract->fields['id'];

      if (!$contract->can($instID, READ)) {
         return false;
      }

      $types_iterator = Contract_Item::getDistinctTypes($instID);
      $number = count($types_iterator);

      $data    = [];
      $totalnb = 0;
      $used    = [];
      foreach ($types_iterator as $type_row) {
         $itemtype = $type_row['itemtype'];
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $itemtype_2 = null;
            $itemtable_2 = null;

            $params = ['SELECT' => [$itemtable . '.*',
                                    Contract_Item::getTable() . '.id AS linkid',
                                    'glpi_entities.id AS entity'],
                       'FROM'   => 'glpi_contracts_items',
                       'WHERE'  => ['glpi_contracts_items.itemtype'     => $itemtype,
                                    'glpi_contracts_items.contracts_id' => $instID]];

            if ($item instanceof Item_Devices) {
               $itemtype_2  = $itemtype::$itemtype_2;
               $itemtable_2 = $itemtype_2::getTable();
               $namefield   = 'name_device';
               $params['SELECT'][] = $itemtable_2 . '.designation AS ' . $namefield;
            } else {
               $namefield = $item->getNameField();
               $namefield = "$itemtable.$namefield";
            }

            $params['LEFT JOIN'][$itemtable] = ['FKEY' => [$itemtable                 => 'id',
                                                           Contract_Item::getTable()  => 'items_id']];

            if ($itemtype != 'Entity') {
               $params['LEFT JOIN']['glpi_entities'] = ['FKEY' => [$itemtable        => 'entities_id',
                                                                   'glpi_entities'   => 'id']];
            }

            if ($item instanceof Item_Devices) {
               $id_2  = $itemtype_2::getIndexName();
               $fid_2 = $itemtype::$items_id_2;

               $params['LEFT JOIN'][$itemtable_2] = ['FKEY' => [$itemtable     => $fid_2,
                                                                $itemtable_2   => $id_2]];
            }

            if ($item->maybeTemplate()) {
               $params['WHERE'][] = [$itemtable . '.is_template' => 0];
            }
            $params['WHERE'] += getEntitiesRestrictCriteria($itemtable, '', '', $item->maybeRecursive());
            $params['ORDER'] = "glpi_entities.completename, $namefield";

            $iterator = $DB->request($params);
            $nb       = count($iterator);

            if ($nb > 0) {
               $data[$itemtype] = [];
               foreach ($iterator as $objdata) {
                  $data[$itemtype][$objdata['id']] = $objdata;
                  $used[$itemtype][$objdata['id']] = $objdata['id'];
               }
            }
            $totalnb += $nb;
         }
      }

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Item', 'Items', $number).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, $number);
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(15,18,29,15,15,8);
         $pdf->displayTitle("<b><i>".__('Type')."</i></b>", "<b><i>".__('Name')."</i></b>",
                            "<b><i>".__('Entity')."</i></b>",  "<b><i>".__('Serial number')."</i></b>",
                            "<b><i>".__('Inventory number')."</i></b>", "<b><i>".__('Status')."</i></b>");

         $totalnb = 0;
         foreach ($data as $itemtype => $datas) {

            if (isset($datas['longlist'])) {
               $pdf->displayLine($datas['name'], $datas['link']);
            } else {
               $prem = true;
               $nb   = count($datas);
               foreach ($datas as $objdata) {
                  $item = new $itemtype();
                  if ($item instanceof Item_Devices) {
                     $name = $objdata["name_device"];
                  } else {
                     $name = $objdata["name"];
                  }
                  if ($_SESSION["glpiis_ids_visible"]
                        || empty($data["name"])) {
                     $name = sprintf(__('%1$s (%2$s)'), $name, $objdata["id"]);
                  }

                  if ($prem) {
                     $typename = $item->getTypeName($nb);
                     $pdf->displayLine(Toolbox::stripTags(sprintf(__('%1$s: %2$s'), $typename, $nb)),
                                       Toolbox::stripTags($name),
                                       Dropdown::getDropdownName("glpi_entities", $objdata['entity']),
                                       Toolbox::stripTags((isset($objdata["serial"])
                                                           ? "".$objdata["serial"]."" :"-")),
                                       Toolbox::stripTags((isset($objdata["otherserial"])
                                                           ? "".$objdata["otherserial"]."" :"-")),
                                       (isset($objdata['states_id']) ? Dropdown::getDropdownName("glpi_states", $objdata['states_id'])
                                                                     : ''));
                     $prem = false;
                  } else {
                     $pdf->displayLine('',
                                       Toolbox::stripTags($name),
                                       Dropdown::getDropdownName("glpi_entities", $objdata['entity']),
                                       Toolbox::stripTags((isset($objdata["serial"])
                                                           ? "".$objdata["serial"]."" :"-")),
                                       Toolbox::stripTags((isset($objdata["otherserial"])
                                                           ? "".$objdata["otherserial"]."" :"-")),
                                       (isset($objdata['states_id']) ? Dropdown::getDropdownName("glpi_states", $objdata['states_id'])
                                                                     : ''));
                  }
               }
            }
         }
      }
   }
}