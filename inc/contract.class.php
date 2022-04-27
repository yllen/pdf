<?php
/**
 * @version $Id: setup.php 378 2014-06-08 15:12:45Z yllen $
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
 @authors   Nelly Mahu-Lasson
 @copyright Copyright (c) 2020-2021 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfContract extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Contract());
   }

   static function getFields(){
      return ['name' => 'Name',
              'type' => 'Contract type',
              'num' => 'Number',
              'status' => 'Status',
              'begin_date' => 'Start date',
              'duration' => 'Initial contract period',
              'notice' => 'Notice',
              'accounting_number' => 'Account number',
              'periodicity' => 'Contract renewal period',
              'billing' => 'Invoice period',
              'renewal' => 'Renewal',
              'max_links_allowed' => 'Max number of items',
              'alert' => 'Email alarms',
              'comments' => 'Comments'];
   }

   function defineAllTabsPDF($options=[]) {

      $onglets = parent::defineAllTabsPDF($options);
      unset($onglets['Contract_Supplier$1']);
      return $onglets;
   }

   static function defineField($pdf, Contract $contract, $field){
      if(isset(parent::getFields()[$field])){
         return PluginPdfCommon::mainField($pdf, $contract, $field);
      } else {
         switch($field) {
            case 'num':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), _x('phone', 'Number').'</i></b>',
                                       $contract->fields['num']);
            case 'begin_date':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Start date').'</i></b>',
                                       Html::convDate($contract->fields['begin_date']));
            case 'duration':
               $textduration = "";
               if (!empty($contract->fields["begin_date"])) {
                  $textduration = sprintf(__('%1$s %2$s'), '   -> ',
                                          Infocom::getWarrantyExpir($contract->fields['begin_date'],
                                                                  $contract->fields['duration']));
               }
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Initial contract period').'</i></b>',
                                       sprintf(_n('%d month', '%d months', $contract->fields['duration']),
                                             $contract->fields['duration']) .$textduration);
            case 'notice':
               $textduration = "";
               if (!empty($contract->fields["begin_date"]) && ($contract->fields["notice"] > 0)) {
                  $textduration = sprintf(__('%1$s %2$s'), '   -> ',
                                          Infocom::getWarrantyExpir($contract->fields["begin_date"],
                                                                    $contract->fields["duration"],
                                                                    $contract->fields["notice"]));
               }
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Notice').'</i></b>',
                                       sprintf(_n('%d month', '%d months', $contract->fields["notice"]),
                                             $contract->fields["notice"]).$textduration);
            case 'accounting_number':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Account number').'</i></b>',
                                       $contract->fields["accounting_number"]);
            case 'periodicity':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Contract renewal period').'</i></b>',
                                       sprintf(_n('%d month', '%d months', $contract->fields["periodicity"]),
                                             $contract->fields["periodicity"]));
            case 'billing':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Invoice period').'</i></b>',
                                       sprintf(_n('%d month', '%d months', $contract->fields["billing"]),
                                             $contract->fields["billing"]));
            case 'renewal':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Renewal').'</i></b>',
                                       Contract::getContractRenewalName($contract->fields["renewal"]));
            case 'max_links_allowed':
               return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Max number of items').'</i></b>',
                                       $contract->fields["max_links_allowed"]);
            case 'alert':
               if (Entity::getUsedConfig("use_contracts_alert", $contract->fields["entities_id"])) {
                  return '<b><i>'.sprintf(__('%1$s: %2$s'), __('Email alarms').'</i></b>',
                                          ($contract->fields['alert'] > 0) ? $contract->fields['alert'] : '');
               }
         }
      }
   }

   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'ContractCost$1' :
            PluginPdfContract::pdfCost($pdf, $item);
            break;

         case 'Contract_Item$1' :
            PluginPdfContract_Item::pdfForContract($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }


   static function pdfCost(PluginPdfSimplePDF $pdf, Contract $contract) {
      global $DB;

      $ID = $contract->getField('id');

      $result = $DB->request(['FROM'   => 'glpi_contractcosts',
                              'WHERE'  => ['contracts_id' => $ID],
                              'ORDER'  => 'begin_date']);

      $number = count($result);

      if (!$number) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), '<b>'.ContractCost::getTypeName(2).'</b>',
                                    __('No item to display')));
      } else {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle('<b>'.ContractCost::getTypeName($number).'</b>');

         $pdf->setColumnsSize(20,20,20,20,20);
         $pdf->setColumnsAlign('left','center','center','left', 'right');

         $pdf->displayTitle("<b><i>".__('Name')."</i></b>",
                            "<b><i>".__('Begin date')."</i></b>",
                            "<b><i>".__('End date')."</i></b>",
                            "<b><i>".Budget::getTypeName(1)."</i></b>",
                            "<b><i>"._n('Cost', 'Costs', 1)."</i></b>");

         $total = 0;
         while ($data = $result->next()) {
            $pdf->displayLine($data['name'],
                              Html::convDate($data['begin_date']),
                              Html::convDate($data['end_date']),
                              Toolbox::stripTags(Dropdown::getDropdownName('glpi_budgets',
                                                                    $data['budgets_id'])),
                              PluginPdfConfig::formatNumber($data['cost']));
            $total += $data['cost'];
         }

         $pdf->setColumnsSize(81,19);
         $pdf->setColumnsAlign('right','right');
         $pdf->displayLine('<b>'.__('Total cost').'</b>',
                           '<b>'.PluginPdfConfig::formatNumber($total).'</b>');
      }
   }
}