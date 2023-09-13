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

class PluginPdfCustomfield {

    static function renderCustomFields(&$pdf, &$item, &$tab){
        global $DB;

        $ID = $item->getField('id');
        $type = get_class($item);

        if ($result = $DB->request([
                'SELECT' => '*',
                'FROM' => 'glpi_plugin_fields_' . strtolower(get_class($item)) . substr($tab, strpos($tab, '$') + 1).'s',
                'WHERE' => [
                'items_id' => $ID,
                'itemtype' => $type]])) {

            $pdf->setColumnsSize(100);

            $nb_connect = count($result);

            $labels = $DB->request([
                'SELECT' => ['name', 'label', 'type'],
                'FROM' => 'glpi_plugin_fields_fields'
            ]);

            $dictionary = [];
            $dropdowns = [];
            $types = [];

            foreach ($labels as $row) {
                $dictionary[$row['name']] = $row['label'];
                $types[$row['name']] = $row['type'];
                if ($row['type'] == 'dropdown'){
                        $dropdowns[$row['name']] = 'plugin_fields_'.$row['name'].'dropdowns_id';
                }
            }

            $columnCounter = 0;
            $firstColumn = "";
            $secondColumn = "";

            foreach ($result as $field) {
                $keys = array_keys($field);
                foreach ($keys as $key) {
                if (isset($dictionary[$key])) {

                    $displayValue = $field[$key];

                    // If, then use locale
                    if($types[$key] == 'yesno') {
                            $displayValue = ($field[$key] == '1') ? __('Yes') : __('No');
                    }

                    // If dropdown get item and class 
                    if($types[$key] == 'dropdown') {
                            $field_name = $dropdowns[$key];
                            $fieldClass = 'PluginFields'.ucfirst($key).'Dropdown';
                            $subitem = new $fieldClass();
                    if (isset($field[$field_name]) && $field[$field_name] > 0){
                        $subitem->getFromDB($field[$field_name]);
                        $displayValue = $subitem->fields['name'];
                    }
                    }

                    // Order always by two columns

                    if ($columnCounter == 0) {
                        $firstColumn = '<b><i>'.sprintf(__('%1$s: %2$s'), $dictionary[$key].'</i></b>', $displayValue);
                        $columnCounter++;
                    } else {
                        $secondColumn = '<b><i>'.sprintf(__('%1$s: %2$s'), $dictionary[$key].'</i></b>', $displayValue);
                        $pdf->setColumnsSize(50,50);

                        $pdf->displayLine($firstColumn, $secondColumn);

                        $columnCounter = 0;
                        $firstColumn = "";
                        $secondColumn = "";
                    }
                }
                }
            }

            // If a column is missing, then print
            if ($firstColumn != "") {
                $pdf->setColumnsSize(100);
                $pdf->displayLine($firstColumn);
            }

            return true;
        }

    }

    static function getTabsFromFields($type){
        global $DB;

        $dbUtils = new DbUtils();
        $entityRestrict = $dbUtils->getEntitiesRestrictCriteria(PluginFieldsContainer::getTable(), "", "", true, false);
        if (count($entityRestrict)) {
            $entityRestrict = [$entityRestrict];
        }

        $itemtypes = [$type];
        $itemtypesCriteria = [];
        foreach ($itemtypes as $itemtype) {
            $itemtypesCriteria[] = [
                'itemtypes' => ['LIKE', '%\"'.$itemtype.'\"%']
            ];
        }
        $request = [
            'SELECT' => ['id', 'name', 'label'],
            'FROM'   => PluginFieldsContainer::getTable(),
            'WHERE'  => [
                'AND' => [
                    'OR' => $itemtypesCriteria,
                    'type'      => 'tab',
                    'is_active' => true,
                    ]
                    + $entityRestrict,
            ]
        ];

        return $DB->request($request);
    }

}
