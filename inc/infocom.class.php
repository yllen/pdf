<?php
/**
 * @version $Id$
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
 @copyright Copyright (c) 2009-2020 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfInfocom extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Infocom());
   }

   static function getFields(){
      return [
         'order_date' => 'Order date',
         'buy_date' => 'Date of purchase',
         'delivery_date' => 'Delivery date',
         'use_date' => 'Startup date',
         'inventory_date' => 'Date of last physical inventory',
         'decommission_date' => 'Decommission date',
         'supplier' => 'Supplier',
         'budget' => 'Budget',
         'order_number' => 'Order number',
         'immo_number' => 'Immobilization number',
         'bill' => 'Invoice number',
         'delivery_number' => 'Delivery form',
         'value' => 'Value',
         'warranty_value' => 'Warranty extension value',
         'acc_value' => 'Account net value',
         'sink_time' => 'Amortization duration',
         'sink_type' => 'Amortization type',
         'sink_coeff' => 'Amortization coefficient',
         'tco' => 'TCO (value + tracking cost)',
         'monthly_tco' => 'Monthly TCO',
         'glpi_businesscriticities' => 'Business criticity',
         'comments' => 'Comments',
         'warranty_date' => 'Start date of warranty',
         'warranty_duration' => 'Warranty duration',
         'warranty_info' => 'Warranty information',
         'warranty_alarm' => 'Alarms'
      ];
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item, $fields){
      global $CFG_GLPI, $PDF_DEVICES;

      $ID = $item->getField('id');

      if (!Session::haveRight("infocom", READ)) {
         return false;
      }

      $ic = new Infocom();

      $pdf->setColumnsSize(100);
      $title = '<b>'.__('Financial and administratives information').'</b>';
      $lifecyclefields = [];
      $financialfields = [];
      $warrantyfields  = [];

      if (!$ic->getFromDBforDevice(get_class($item),$ID)) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $currency = PluginPdfConfig::getInstance();

         foreach ($PDF_DEVICES as $option => $value) {
            if ($currency->fields['currency'] == $option) {
               $sym = $value[1];
            }
         }

         if (empty($fields)){
            $fields = array_keys(static::getFields());
         }

         foreach($fields as $num){
            $print = static::getFields()[$num];
            switch($num){
               //Asset lifecycle fields
               case 'order_date':
               case 'buy_date':
               case 'delivery_date':
               case 'use_date':
               case 'inventory_date':
               case 'decommission_date':
                  $lifecyclefields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __($print)."</i></b>",
                                                  Html::convDate($ic->fields[$num]));
                  break;

               //Financial and administrative information
               case 'supplier':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Supplier')."</i></b>",
                                   Html::clean(Dropdown::getDropdownName("glpi_suppliers",
                                                                         $ic->fields["suppliers_id"])));
                  break;
               case 'budget':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Budget')."</i></b>",
                                   Html::clean(Dropdown::getDropdownName("glpi_budgets",
                                                                         $ic->fields["budgets_id"])));
                  break;
               case 'order_number':
               case 'immo_number':
               case 'bill':
               case 'delivery_number':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __($print)."</i></b>",
                                                      $ic->fields[$num]);
                  break;
               case 'value':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), _x('price', 'Value')."</i></b>",
                                                      PluginPdfConfig::formatNumber($ic->fields["value"]));
                  break;
               case 'warranty_value':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Warranty extension value')."</i></b>",
                                                      PluginPdfConfig::formatNumber($ic->fields["warranty_value"]));
                  break;
               case 'acc_value':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Account net value')."</i></b>",
                                                      Infocom::Amort($ic->fields["sink_type"], $ic->fields["value"],
                                                                     $ic->fields["sink_time"], $ic->fields["sink_coeff"],
                                                                     $ic->fields["warranty_date"], $ic->fields["use_date"],
                                                                     $CFG_GLPI['date_tax'],"n"));
                  break;
               case 'sink_time':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Amortization duration')."</i></b>",
                                          sprintf(__('%1$s (%2$s)'),
                                                sprintf(_n('%d year', '%d years', $ic->fields["sink_time"]),
                                                      $ic->fields["sink_time"]),
                                                Infocom::getAmortTypeName($ic->fields["sink_type"])));
                  break;
               case 'sink_type':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Amortization type')."</i></b>",
                                                        Infocom::getAmortTypeName($ic->fields["sink_type"]));
                  break;
               case 'sink_coeff':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Amortization coefficient')."</i></b>",
                                                        $ic->fields["sink_coeff"]);
                  break;
               case 'tco':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('TCO (value + tracking cost)')."</i></b>",
                                                        sprintf(__('%1$s %2$s'),
                                                                Html::clean(Infocom::showTco($item->getField('ticket_tco'),
                                                                                             $ic->fields["value"])), $sym));
                  break;
               case 'monthly_tco':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Monthly TCO')."</i></b>",
                                             sprintf(__('%1$s %2$s'),
                                                      Html::clean(Infocom::showTco($item->getField('ticket_tco'),
                                                                                    $ic->fields["value"],
                                                                                    $ic->fields["buy_date"])), $sym));
                  break;
               case 'glpi_businesscriticities':
                  $financialfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Business criticity')."</i></b>",
                                                        Dropdown::getDropdownName('glpi_businesscriticities',
                                                                                 $ic->fields['businesscriticities_id']));
                  break;
                                                                                 
               //Waranty Information
               case 'warranty_date':
                  $warrantyfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Start date of warranty')."</i></b>",
                                                       Html::convDate($ic->fields["warranty_date"]));
                  break;
               case 'warranty_duration':
                  $warrantyfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Warranty duration')."</i></b>",
                                                      sprintf(__('%1$s - %2$s'),
                                                            sprintf(_n('%d month', '%d months',
                                                                        $ic->fields["warranty_duration"]),
                                                                     $ic->fields["warranty_duration"]),
                                                            sprintf(__('Valid to %s'),
                                                                     Infocom::getWarrantyExpir($ic->fields["buy_date"],
                                                                                                $ic->fields["warranty_duration"]))));
                  break;
               case 'warranty_info':
                  $warrantyfields[] = "<b><i>".sprintf(__('%1$s: %2$s'), __('Warranty information')."</i></b>",
                                                       $ic->fields["warranty_info"]);
                  break;
               case 'warranty_alarm':
                  $col1 = "<b><i>".__('Alarms on financial and administrative information')."</i></b>";
                  if ($ic->fields["alert"] == 0) {
                     $col1 = sprintf(__('%1$s: %2$s'), $col1, __('No'));
                  } else if ($ic->fields["alert"] == 4) {
                     $col1 = sprintf(__('%1$s: %2$s'), $col1, __('Warranty expiration date'));
                  }
                  $warrantyfields[] = $col1;
                  break;
            }
         }

         if (!empty($lifecyclefields)){
            $pdf->displayTitle("<b>".__('Asset lifecycle')."</b>");
            PluginPdfCommon::displayLines($pdf, $lifecyclefields);
         }
         if (!empty($financialfields)){
            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".__($title)."</b>");
            PluginPdfCommon::displayLines($pdf, $financialfields);
            if (isset(static::getFields()['comments'])){
               PluginPdfCommon::mainLine($pdf, $ic, 'comment');
            }
         }
         if (!empty($warrantyfields)){
            $pdf->setColumnsSize(100);
            $pdf->displayTitle("<b>".__('Warranty information')."</b>");
            PluginPdfCommon::displayLines($pdf, $warrantyfields);
         }
      }

      $pdf->displaySpace();
   }
}