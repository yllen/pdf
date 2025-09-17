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

class PluginPdfDomain_Item extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new Domain_Item());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID = $item->getField('id');

        $query = ['SELECT' => ['glpi_domains.*',
            'glpi_domains_items.domainrelations_id'],
            'FROM'       => 'glpi_domains',
            'INNER JOIN' => ['glpi_domains_items'
                             => ['FKEY' => ['glpi_domains' => 'id',
                                 'glpi_domains_items'      => 'domains_id']]],
            'WHERE' => ['glpi_domains_items.itemtype' => $item->getType(),
                'glpi_domains_items.items_id'         => $ID],
            'ORDER' => 'glpi_domains.name'];

        $result = $DB->request($query);
        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . Domain::getTypeName($number) . '</b>';

        if (!$number) {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'] . ' / ' . $number);
            } else {
                $title = sprintf(__('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(17, 15, 10, 10, 8, 8, 16, 16);
            $pdf->displayTitle(
                __('Name'),
                __('Entity'),
                __('Group in charge'),
                __('Technician in charge'),
                __('Type'),
                __('Domain relation'),
                __('Creation date'),
                __('Expiration date'),
            );

            foreach ($result as $data) {
                $pdf->displayLine(
                    $data['name'],
                    Dropdown::getDropdownName('glpi_entities', $data['entities_id']),
                    Dropdown::getDropdownName('glpi_groups', $data['groups_id_tech']),
                    getUserName($data['users_id_tech']),
                    Dropdown::getDropdownName('glpi_domaintypes', $data['domaintypes_id']),
                    Dropdown::getDropdownName('glpi_domainrelations', $data['domainrelations_id']),
                    Html::convDate($data['date_creation']),
                    Html::convDate($data['date_expiration']),
                );
            }
        }
        $pdf->displaySpace();
    }
}
