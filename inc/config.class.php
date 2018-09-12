<?php
/**
 * @version $Id: config.class.php 194 2016-10-25 10:02:30Z tsmr $
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Behaviors plugin for GLPI.

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
 @copyright Copyright (c) 2018 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
--------------------------------------------------------------------------
*/

class PluginPdfConfig extends CommonDBTM {

   static private $_instance = NULL;
   static $rightname         = 'config';


   static function canCreate() {
      return Session::haveRight('config', UPDATE);
   }


   static function canView() {
      return Session::haveRight('config', READ);
   }


   static function getTypeName($nb=0) {
      return __('Setup');
   }


   function getName($with_comment=0) {
      return __('Print to pdf', 'pdf');
   }


   /**
    * Singleton for the unique config record
    */
   static function getInstance() {

      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }


   static function install(Migration $mig) {
      global $DB;

      $table = 'glpi_plugin_pdf_configs';
      if (!$DB->tableExists($table)) { //not installed

         $query = "CREATE TABLE `". $table."`(
                     `id` int(11) NOT NULL,
                     `currency`  VARCHAR(15) NULL,
                     `date_mod` datetime default NULL,
                     PRIMARY KEY  (`id`)
                   ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($query, 'Error in creating glpi_plugin_pdf_configs'.
                                 "<br>".$DB->error());

         $query = "INSERT INTO `$table`
                         (id, currency)
                   VALUES (1, 'EUR')";
         $DB->queryOrDie($query, 'Error during update glpi_plugin_pdf_configs'.
                                 "<br>" . $DB->error());
      }

   }


   static function uninstall(Migration $mig) {
      $mig->dropTable('glpi_plugin_pdf_configs');
   }


   static function showConfigForm($item) {
      global $PDF_DEVICES;

      $config = self::getInstance();

      $config->showFormHeader();


      echo "<tr class='tab_bg_1'>";
      echo "<td>".__("Choose your international currency", "pdf")."</td><td>";
      foreach ($PDF_DEVICES as $option => $value) {
         $options[$option] = $option ." - ". $value[0]. " (".$value[1].")";
      }
      Dropdown::showFromArray("currency", $options,
                              ['value' => $config->fields['currency']]);
      echo "</td></tr>\n";

      $config->showFormButtons(['candel'=>false]);

      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='Config') {
            return self::getName();
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Config') {
         self::showConfigForm($item);
      }
      return true;
   }


   static function currency() {

      //   name, symbole, currency, uniqUE
      return ['AED' => [__('UAE Dirham', 'pdf'), 'د.إ', false],
              'AFN' => [__('Afghani', 'pdf'), 'Af'],
              'ALL' => [__('Lek', 'pdf'), 'L',  false],
              'AMD' => [__('Armenian Dram', 'pdf'), 'Դ'],
              'AOA' => [__('Kwanza', 'pdf'), 'Kz'],
              'ARS' => [__('Argentine Peso', 'pdf'), '$', false],
              'AUD' => [__('Australian Dollar', 'pdf'), '$', false],
              'AWG' => [__('Aruban Guilder/Florin', 'pdf'), 'ƒ'],
              'AZN' => [__('Azerbaijanian Manat', 'pdf'), 'ман'],
              'BAM' => [__('Konvertibilna Marka', 'pdf'), 'КМ'],
              'BBD' => [__('Barbados Dollar', 'pdf'), '$', false],
              'BDT' => [__('Taka', 'pdf'), '৳'],
              'BGN' => [__('Bulgarian Lev', 'pdf'), 'лв'],
              'BHD' => [__('Bahraini Dinar', 'pdf'), 'ب.د'],
              'BIF' => [__('Burundi Franc', 'pdf'), '₣', false],
              'BMD' => [__('Bermudian Dollar', 'pdf'), '$', false],
              'BND' => [__('Brunei Dollar', 'pdf'), '$', false],
              'BOB' => [__('Boliviano', 'pdf'), 'Bs.'],
              'BRL' => [__('Brazilian Real', 'pdf'), 'R$'],
              'BSD' => [__('Bahamian Dollar', 'pdf'), '$', false],
              'BTN' => [__('Ngultrum', 'pdf'), ''],
              'BWP' => [__('Pula', 'pdf'), 'P', false],
              'BYR' => [__('Belarussian Ruble', 'pdf'), 'Br.'],
              'BZD' => [__('Belize Dollar', 'pdf'), '$', false],
              'CAD' => [__('Canadian Dollar', 'pdf'), '$', false],
              'CDF' => [__('Congolese Franc', 'pdf'), 'F', false],
              'CHF' => [__('Swiss Franc', 'pdf'), 'F', false],
              'CLP' => [__('Chilean Peso', 'pdf'), '$', false],
              'CNY' => [__('Yuan', 'pdf'), '¥'],
              'COP' => [__('Colombian Peso', 'pdf'), '$', false],
              'CRC' => [__('Costa Rican Colon', 'pdf'), '₡'],
              'CUP' => [__('Cuban Peso', 'pdf'), '$', false],
              'CVE' => [__('Cape Verde Escudo', 'pdf'), '$', false],
              'CZK' => [__('Czech Koruna', 'pdf'), 'Kč'],
              'DJF' => [__('Djibouti Franc', 'pdf'), '₣', false],
              'DKK' => [__('Danish Krone', 'pdf'), 'kr', false],
              'DOP' => [__('Dominican Peso', 'pdf'), '$', false],
              'DZD' => [__('Algerian Dinar', 'pdf'), 'د.ج'],
              'EGP' => [__('Egyptian Pound', 'pdf'), '£', false],
              'ERN' => [__('Nakfa', 'pdf'), 'Nfk'],
              'ETB' => [__('Ethiopian Birr', 'pdf'), ''],
              'EUR' => [__('Euro', 'pdf'), '€'],
              'FJD' => [__('Fiji Dollar', 'pdf'), '$', false],
              'FKP' => [__('Falkland Islands Pound', 'pdf'), '£', false],
              'GBP' => [__('Pound Sterling', 'pdf'), '£', false],
              'GEL' => [__('Lari', 'pdf'), 'ლ'],
              'GHS' => [__('Cedi', 'pdf'), '₵'],
              'GIP' => [__('Gibraltar Pound', 'pdf'), '£', false],
              'GMD' => [__('Dalasi', 'pdf'), 'D'],
              'GNF' => [__('Guinea Franc', 'pdf'), '₣', false],
              'GTQ' => [__('Quetzal', 'pdf'), 'Q'],
              'HKD' => [__('Hong Kong Dollar', 'pdf'), '$', false],
              'HNL' => [__('Lempira', 'pdf'), 'L', false],
              'HRK' => [__('Croatian Kuna', 'pdf'), 'Kn'],
              'HTG' => [__('Gourde', 'pdf'), 'G'],
              'HUF' => [__('Forint', 'pdf'), 'Ft'],
              'IDR' => [__('Rupiah', 'pdf'), 'Rp'],
              'ILS' => [__('New Israeli Shekel', 'pdf'), '₪'],
              'INR' => [__('Indian Rupee', 'pdf'), '₨', false],
              'IQD' => [__('Iraqi Dinar', 'pdf'), 'ع.د'],
              'IRR' => [__('Iranian Rial', 'pdf'), '﷼'],
              'ISK' => [__('Iceland Krona', 'pdf'), 'Kr', false],
              'JMD' => [__('Jamaican Dollar', 'pdf'), '$', false],
              'JOD' => [__('Jordanian Dinar', 'pdf'), 'د.ا', false],
              'JPY' => [__('Yen', 'pdf'), '¥'],
              'KES' => [__('Kenyan Shilling', 'pdf'), 'Sh', false],
              'KGS' => [__('Som', 'pdf'), ''],
              'KHR' => [__('Riel', 'pdf'), '៛'],
              'KPW' => [__('North Korean Won', 'pdf'), '₩', false],
              'KRW' => [__('South Korean Won', 'pdf'),  '₩', false],
              'KWD' => [__('Kuwaiti Dinar', 'pdf'), 'د.ك'],
              'KYD' => [__('Cayman Islands Dollar', 'pdf'), '$', false],
              'KZT' => [__('Tenge', 'pdf'), '〒'],
              'LAK' => [__('Kip', 'pdf'), '₭'],
              'LBP' => [__('Lebanese Pound', 'pdf'), '£L'],
              'LKR' => [__('Sri Lanka Rupee', 'pdf'), 'Rs'],
              'LRD' => [__('Liberian Dollar', 'pdf'), '$', false],
              'LSL' => [__('Loti', 'pdf'), 'L', false],
              'LYD' => [__('Libyan Dinar', 'pdf'), 'ل.د'],
              'MAD' => [__('Moroccan Dirham', 'pdf'), 'د.م.'],
              'MDL' => [__('Moldavian Leu', 'pdf'), 'L', false],
              'MGA' => [__('Malagasy Ariary', 'pdf'), ''],
              'MKD' => [__('Denar', 'pdf'), 'ден'],
              'MMK' => [__('Kyat', 'pdf'), 'K', false],
              'MNT' => [__('Tugrik', 'pdf'), '₮'],
              'MOP' => [__('Pataca', 'pdf'), 'P', false],
              'MRO' => [__('Ouguiya', 'pdf'), 'UM'],
              'MUR' => [__('Mauritius Rupee', 'pdf'), '₨', false],
              'MVR' => [__('Rufiyaa', 'pdf'), 'ރ.'],
              'MWK' => [__('Kwacha', 'pdf'), 'MK'],
              'MXN' => [__('Mexican Peso', 'pdf'), '$', false],
              'MYR' => [__('Malaysian Ringgit', 'pdf'), 'RM'],
              'MZN' => [__('Metical', 'pdf'), 'MTn'],
              'NAD' => [__('Namibia Dollar', 'pdf'), '$', false],
              'NGN' => [__('Naira', 'pdf'), '₦'],
              'NIO' => [__('Cordoba Oro', 'pdf'), 'C$'],
              'NOK' => [__('Norwegian Krone', 'pdf'), 'kr', false],
              'NPR' => [__('Nepalese Rupee', 'pdf'), '₨', false],
              'NZD' => [__('New Zealand Dollar', 'pdf'), '$', false],
              'OMR' => [__('Rial Omani', 'pdf'), 'ر.ع.'],
              'PAB' => [__('Balboa', 'pdf'), 'B/.'],
              'PEN' => [__('Nuevo Sol', 'pdf'), 'S/.'],
              'PGK' => [__('Kina', 'pdf'), 'K', false],
              'PHP' => [__('Philippine Peso', 'pdf'), '₱'],
              'PKR' => [__('Pakistan Rupee', 'pdf'), '₨', false],
              'PLN' => [__('PZloty', 'pdf'), 'zł'],
              'PYG' => [__('Guarani', 'pdf'), '₲'],
              'QAR' => [__('Qatari Rial', 'pdf'), 'ر.ق'],
              'RON' => [__('Leu', 'pdf'), 'L', false],
              'RSD' => [__('Serbian Dinar', 'pdf'), 'din'],
              'RUB' =>[__('Russian Ruble', 'pdf'), 'р.'],
              'RWF' => [__('Rwanda Franc', 'pdf'), 'F', false],
              'SAR' => [__('Saudi Riyal', 'pdf'), 'ر.س '],
              'SBD' => [__('Solomon Islands Dollar', 'pdf'), '$', false],
              'SCR' => [__('Seychelles Rupee', 'pdf'), '₨', false],
              'SDG' => [__('Sudanese', 'pdf'), '£', false],
              'SEK' => [__('Swedish Krona', 'pdf'), 'kr', false],
              'SGD' => [__('Singapore Dollar', 'pdf'), '$', false],
              'SHP' => [__('Saint Helena Pound', 'pdf'), '£', false],
              'SLL' => [__('leone', 'pdf'), 'Le'],
              'SOS' => [__('Somali Shilling', 'pdf'), 'Sh', false],
              'SRD' => [__('Suriname Dollar', 'pdf'), '$', false],
              'STD' => [__('Dobra', 'pdf'), 'Db'],
              'SYP' => [__('Syrian Pound', 'pdf'), 'ل.س'],
              'SZL' => [__('Lilangeni', 'pdf'), 'L', false],
              'THB' => [__('Baht', 'pdf'), '฿'],
              'TJS' => [__('Somoni', 'pdf'), 'ЅМ'],
              'TMT' => [__('Manat', 'pdf'), 'm'],
              'TND' => [__('Tunisian Dinar', 'pdf'), 'د.ت'],
              'TOP' => [__('Pa’anga', 'pdf'), 'T$'],
              'TRY' => [__('Turkish Lira', 'pdf'), '₤', false],
              'TTD' => [__('Trinidad and Tobago Dollar', 'pdf'), '$', false],
              'TWD' => [__('Taiwan Dollar', 'pdf'), '$', false],
              'TZS' => [__('Tanzanian Shilling', 'pdf'), 'Sh', false],
              'UAH' => [__('Hryvnia', 'pdf'), '₴'],
              'UGX' => [__('Uganda Shilling', 'pdf'), 'Sh', false],
              'USD' => [__('US Dollar', 'pdf'), '$', false],
              'UYU' => [__('Peso Uruguayo', 'pdf'), '$', false],
              'UZS' => [__('Uzbekistan Sum', 'pdf'), ''],
              'VEF' => [__('Bolivar Fuerte', 'pdf'), 'Bs F'],
              'VND' => [__('Dong', 'pdf'), '₫'],
              'VUV' => [__('Vatu', 'pdf'), 'Vt'],
              'WST' => [__('Tala', 'pdf'), 'T'],
              'XAF' => [__('CFA Franc BCEAO', 'pdf'), '₣', false],
              'XCD' => [__('East Caribbean Dollar', 'pdf'), '$', false],
              'XPF' => [__('CFP Franc', 'pdf'), '₣', false],
              'YER' => [__('Yemeni Rial', 'pdf'), '﷼'],
              'ZAR' => [__('Rand', 'pdf'), 'R'],
              'ZMW' => [__('Zambian Kwacha', 'pdf'), 'ZK'],
              'ZWL' => [__('Zimbabwe Dollar', 'pdf'), '$', false]];
   }


   static function formatNumber($value) {
      global $PDF_DEVICES;


      $config = new Config();
      foreach ($config->find("`context` = 'core' AND `name` = 'language'") as $row) {
         $language = $row['value'];
      }
      $user = new User();
      $user->getFromDB($_SESSION['glpiID']);
      if (!empty($user->fields['language'])) {
         $language = $user->fields['language'];
      }
      $currency = PluginPdfConfig::getInstance();

      $fmt = numfmt_create($language, NumberFormatter::CURRENCY );
      $val = numfmt_format_currency($fmt, $value, $currency->getField('currency'));
      foreach ($PDF_DEVICES as $option => $value) {
         if ($currency->fields['currency'] == $option) {
            $sym = $value[1];
            return  preg_replace("/$option/", $sym, $val);
         }
      }
   }


   static function currencyName() {
      global $PDF_DEVICES;

      $config = self::getInstance();
      $name = '';
      foreach ($PDF_DEVICES as $option => $value) {
         if ($config->getField('currency') == $option) {
            if (isset($value[2])) {
               return $value[0];
            }
         }
      }
   }
}