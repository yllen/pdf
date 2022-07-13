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


class PluginPdfSoftwareVersion extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new SoftwareVersion());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, SoftwareVersion $version) {

      $ID = $version->getField('id');

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b><i>'.sprintf(__('%1$s: %2$s'), __('ID')."</i>", $ID."</b>"));

      $pdf->setColumnsSize(50,50);

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Name').'</i></b>', $version->fields['name']),
         '<b><i>'.sprintf(__('%1$s: %2$s'), _n('Software', 'Software', 2).'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwares',
                                                                       $version->fields['softwares_id']))));

      $pdf->displayLine(
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Status').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_states',
                                                                       $version->fields['states_id']))),
         '<b><i>'.sprintf(__('%1$s: %2$s'), __('Operating system').'</i></b>',
                          Toolbox::stripTags(Dropdown::getDropdownName('glpi_operatingsystems',
                                                                       $version->fields['operatingsystems_id']))));

      $pdf->setColumnsSize(100);
      PluginPdfCommon::mainLine($pdf, $version, 'comment');
      $pdf->displaySpace();
   }


   static function pdfForSoftware(PluginPdfSimplePDF $pdf, Software $item){
      global $DB;

      $sID = $item->getField('id');

      $query = ['FIELDS'    => ['glpi_softwareversions.*',
                                'glpi_states.name AS sname',
                                'glpi_operatingsystems.name AS osname'],
                'FROM'      => 'glpi_softwareversions',
                'LEFT JOIN' => ['glpi_states'
                                => ['FKEY' => ['glpi_states'           => 'id',
                                               'glpi_softwareversions' => 'states_id']],
                                'glpi_operatingsystems'
                                => ['FKEY' => ['glpi_operatingsystems' => 'id',
                                               'glpi_softwareversions' => 'operatingsystems_id']]],
                'WHERE'      => ['softwares_id' => $sID],
                'ORDER'      => 'name'];

      $result = $DB->request($query);
      $number = count($result);

      $pdf->setColumnsSize(100);
      $title = '<b>'.SoftwareVersion::getTypeName($number).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(13,13,30,14,30);
         $pdf->displayTitle('<b><i>'.$title.'</i></b>',
                            '<b><i>'.__('Status').'</i></b>',
                            '<b><i>'.__('Operating system').'</i></b>',
                            '<b><i>'._n('Installation', 'Installations', 2).'</i></b>',
                            '<b><i>'.__('Comments').'</i></b>');
         $pdf->setColumnsAlign('left','left','left','right','left');

         $tot = 0;
         foreach ($result as $data) {
            $nb = Item_SoftwareVersion::countForVersion($data['id']);
            $pdf->displayLine((empty($data['name'])?"(".$data['id'].")":$data['name']),
                              $data['sname'], $data['osname'], $nb,
                              str_replace(["\r","\n"]," ",$data['comment']));
            $tot+=$nb;
         }
         $pdf->setColumnsAlign('left','right','left', 'right','left');
         $pdf->displayTitle('','',"<b>".sprintf(__('%1$s: %2$s'), __('Total')."</b>", ''),$tot, '');
      }
      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'Item_SoftwareVersion$1' :
            PluginPdfItem_SoftwareVersion::pdfForVersionByEntity($pdf, $item);
            break;

         case 'Item_SoftwareVersion$2' :
            PluginPdfItem_SoftwareVersion::pdfForSoftware($pdf, $item);
            break;

         default :
            return false;
      }
      return true;
   }
}