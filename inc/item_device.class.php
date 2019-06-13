<?php
/**
 * @version $Id:
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


class PluginPdfItem_Device extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_Device());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, $item) {
         global $DB;

      $dbu      = new DbUtils();

      $devtypes = Item_Devices::getDeviceTypes();

      $ID = $item->getField('id');
      if (!$item->can($ID, READ)) {
         return false;
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.Toolbox::ucfirst(_n('Component', 'Components', 2)).'</b>');

      $pdf->setColumnsSize(3,14,42,41);

      foreach ($devtypes as $itemtype) {

         $devicetypes   = new $itemtype();
         $specificities = $devicetypes->getSpecificities();
         $specif_fields = array_keys($specificities);
         $specif_text   = implode(',',$specif_fields);

         if (!empty($specif_text)) {
            $specif_text=" ,".$specif_text." ";
         }
         $associated_type  = str_replace('Item_', '', $itemtype);
         $linktable        = $dbu->getTableForItemType($itemtype);
         $fk               = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($associated_type));

         $query = "SELECT count(*) AS NB, `id`, `".$fk."`".$specif_text."
                   FROM `".$linktable."`
                   WHERE `items_id` = '".$ID."'
                   AND `itemtype` = '".$item->getType()."'
                   GROUP BY `".$fk."`".$specif_text;

         $device = new $associated_type();
         $itemdevice = new $itemtype();
         foreach ($DB->request($query) as $data) {
            $itemdevice->getFromDB($data['id']);
            if ($device->getFromDB($data[$fk])) {
               $spec = $device->getAdditionalFields();
               $col4 = '';
               if (count($spec)) {
                  $colspan = (60/count($spec));
                  foreach ($spec as $i => $label) {
                     $toto = substr($label['name'], 0, strpos($label['name'], '_'));
                     $value = '';
                     if (isset($itemdevice->fields[$toto]) && !empty($itemdevice->fields[$toto])) {
                        $value = $itemdevice->fields[$toto];
                     }
                     if (isset($device->fields[$label["name"]])
                         && !empty($device->fields[$label["name"]])) {

                        if (($label["type"] == "dropdownValue")
                            && ($device->fields[$label["name"]] != 0)) {
                           if (!isset($value) || empty($value)) {
                              $table = getTableNameForForeignKeyField($label["name"]);
                              $value = Dropdown::getDropdownName($table,
                                                                 $device->fields[$label["name"]]);
                            }
                            $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $label["label"].'</i></b>',
                                                     Html::clean($value)." ");
                        } else {
                           if (!isset($value) || empty($value)) {
                              $value = $device->fields[$label["name"]];
                           }
                           if ($label["type"] == "bool") {
                               if ($value == 1) {
                                  $value = __('Yes');
                               } else {
                                  $value = __('No');
                               }
                           }
                           if (isset($label["unit"])) {
                              $labelname = '<b><i>'.sprintf(__('%1$s (%2$s)'), $label["label"],
                                                            $label["unit"]).'</i></b>';
                           } else {
                              $labelname = $label["label"];
                           }
                           $col4 .= sprintf(__('%1$s: %2$s'), $labelname, $value." ");
                        }
                     } else if (isset($device->fields[$label["name"]."_default"])
                                && !empty($device->fields[$label["name"]."_default"])) {
                        $col4 .= '<b><i>'.sprintf(__('%1$s: %2$s'), $label["label"].'</i></b>',
                                                  $device->fields[$label["name"]."_default"]." ");
                     }
                  }
               }
               $pdf->displayLine($data['NB'], $device->getTypeName(), $device->getName(), $col4);
            }
         }
      }

      $pdf->displaySpace();
   }
}