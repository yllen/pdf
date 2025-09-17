<?php

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

class PluginPdfItem_Device extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new Item_Devices());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $devtypes = Item_Devices::getDeviceTypes();

        $ID = $item->getField('id');
        if (!$item->can($ID, READ)) {
            return false;
        }

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . Toolbox::ucfirst(_n('Component', 'Components', 2)) . '</b>');

        $pdf->setColumnsSize(3, 14, 42, 41);

        $vide = true;
        foreach ($devtypes as $itemtype) {
            $devicetypes   = new $itemtype();
            $specificities = $devicetypes->getSpecificities();
            $specif_fields = array_keys($specificities);
            $specif_text   = implode(',', $specif_fields);

            if (!empty($specif_text)) {
                $specif_text = ' ,' . $specif_text . ' ';
            }
            $associated_type = str_replace('Item_', '', $itemtype);
            $linktable       = $dbu->getTableForItemType($itemtype);
            $fk              = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($associated_type));

            $query = 'SELECT count(*) AS NB, `id`, `' . $fk . '`' . $specif_text . '
                   FROM `' . $linktable . "`
                   WHERE `items_id` = '" . $ID . "'
                         AND `itemtype` = '" . $item->getType() . "'
                   GROUP BY `" . $fk . '`' . $specif_text;



            $device     = new $associated_type();
            $itemdevice = new $itemtype();
            foreach ($DB->request($query) as $data) {
                $itemdevice->getFromDB($data['id']);
                if ($device->getFromDB($data[$fk])) {
                    $spec = $device->getAdditionalFields();
                    $col4 = '';
                    if (count($spec)) {
                        $colspan = (60 / count($spec));
                        foreach ($spec as $i => $label) {
                            $toto  = substr($label['name'], 0, strpos($label['name'], '_'));
                            $value = '';
                            if (isset($itemdevice->fields[$toto]) && !empty($itemdevice->fields[$toto])) {
                                $value = $itemdevice->fields[$toto];
                            }
                            if (isset($device->fields[$label['name']])
                                && !empty($device->fields[$label['name']])) {
                                if (($label['type'] == 'dropdownValue')
                                    && ($device->fields[$label['name']] != 0)) {
                                    if (empty($value)) {
                                        $table = getTableNameForForeignKeyField($label['name']);
                                        $value = Dropdown::getDropdownName(
                                            $table,
                                            $device->fields[$label['name']],
                                        );
                                    }
                                    $col4 .= '<b><i>' . sprintf(
                                        __('%1$s: %2$s'),
                                        $label['label'] . '</i></b>',
                                        Toolbox::stripTags($value) . ' ',
                                    );
                                } else {
                                    if (empty($value)) {
                                        $value = $device->fields[$label['name']];
                                    }
                                    if ($label['type'] == 'bool') {
                                        if ($value == 1) {
                                            $value = __('Yes');
                                        } else {
                                            $value = __('No');
                                        }
                                    }
                                    if (isset($label['unit'])) {
                                        $labelname = '<b><i>' . sprintf(
                                            __('%1$s (%2$s)'),
                                            $label['label'],
                                            $label['unit'],
                                        ) . '</i></b>';
                                    } else {
                                        $labelname = $label['label'];
                                    }
                                    $col4 .= '<b><i>' . sprintf(__('%1$s: %2$s'), $labelname . '</i></b>', $value . ' ');
                                }
                            } elseif (isset($device->fields[$label['name'] . '_default'])
                                       && !empty($device->fields[$label['name'] . '_default'])) {
                                $col4 .= '<b><i>' . sprintf(
                                    __('%1$s: %2$s'),
                                    $label['label'] . '</i></b>',
                                    $device->fields[$label['name'] . '_default'] . ' ',
                                );
                            }
                        }
                    }
                    $pdf->displayLine($data['NB'], $device->getTypeName(), $device->getName(), $col4);
                    $vide = false;
                }
            }
        }
        if ($vide) {
            $pdf->setColumnsSize(100);
            $pdf->setColumnsAlign('center');
            $pdf->displayLine(__('No item to display'));
        }

        $pdf->displaySpace();
    }
}
