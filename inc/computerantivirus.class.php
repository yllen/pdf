<?php
/**
 * @version $Id $
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
 @copyright Copyright (c) 2009-2018 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfComputerAntivirus extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ComputerAntivirus());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $item) {
      global $DB;

      $ID = $item->getField('id');

      $result = $DB->request('glpi_computerantiviruses', ['computers_id' => $ID,
                                                          'is_deleted'   => 0]);
      $pdf->setColumnsSize(100);
      $title = "<b>".__('Antivirus')."</b>";

      if (!count($result)) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, count($result));
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(25,20,15,15,5,5,15);
         $pdf->displayTitle(__('Name'), __('Manufacturer'), __('Antivirus version'),
                            __('Signature database version'), __('Active'),__('Up to date'),
                            __('Expiration date'));

         $antivirus = new ComputerAntivirus();
         foreach($result as $data) {
            $pdf->displayLine($data['name'],
                              Html::clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                                    $data['manufacturers_id'])),
                              $data['antivirus_version'],
                              $data['signature_version'],
                              Dropdown::getYesNo($data['is_active']),
                              Dropdown::getYesNo($data['is_uptodate']),
                              Html::clean(Html::convDate($data['date_expiration'])));
         }
      }

   }
}