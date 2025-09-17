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

class PluginPdfItem_Disk extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new Item_Disk());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID = $item->getField('id');

        $result = $DB->request(
            'glpi_items_disks',
            ['SELECT'       => ['glpi_filesystems.name', 'glpi_items_disks.*'],
                'LEFT JOIN' => ['glpi_filesystems'
                                => ['FKEY' => ['glpi_items_disks' => 'filesystems_id',
                                    'glpi_filesystems'            => 'id']]],
                'WHERE' => ['items_id' => $ID,
                    'itemtype'         => $item->getType(),
                    'is_deleted'       => 0]],
        );

        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . _n('Volume', 'Volumes', $number) . '</b>';

        if (!$number) {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'] . ' / ' . $number);
            } else {
                $title = sprintf(__('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(21, 21, 20, 9, 9, 9, 11);
            $pdf->displayTitle(
                '<b>' . __('Name'),
                __('Partition'),
                __('Mount point'),
                __('File system'),
                __('Global size'),
                __('Free size'),
                __('Free percentage') . '</b>',
            );

            $pdf->setColumnsAlign('left', 'left', 'left', 'left', 'center', 'right', 'right');

            foreach ($result as $data) {
                $percent = 0;
                if ($data['totalsize'] > 0) {
                    $percent = round(100 * $data['freesize'] / $data['totalsize']);
                }
                $pdf->displayLine(
                    '<b>' . $data['name'] . '</b>',
                    $data['device'],
                    $data['mountpoint'],
                    $data['name'],
                    sprintf(
                        __('%s Mio'),
                        Toolbox::stripTags(Html::formatNumber(
                            $data['totalsize'],
                            false,
                            0,
                        )),
                    ),
                    sprintf(
                        __('%s Mio'),
                        Toolbox::stripTags(Html::formatNumber(
                            $data['freesize'],
                            false,
                            0,
                        )),
                    ),
                    sprintf(
                        __('%s %s'),
                        Toolbox::stripTags(Html::formatNumber($percent, false, 0)),
                        '%',
                    ),
                );
            }
        }
        $pdf->displaySpace();
    }
}
