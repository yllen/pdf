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

class PluginPdfChangeValidation extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new ChangeValidation());
    }

    public static function pdfForChange(PluginPdfSimplePDF $pdf, Change $change)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . __('Approvals for the change', 'pdf') . '</b>');

        if (!Session::haveRightsOr(
            'changevalidation',
            array_merge(
                CommonITILValidation::getCreateRights(),
                CommonITILValidation::getValidateRights(),
                CommonITILValidation::getPurgeRights(),
            ),
        )) {
            return false;
        }
        $ID = $change->getField('id');

        $result = $DB->request(
            'glpi_changevalidations',
            ['WHERE'    => ['changes_id' => $change->getField('id')],
                'ORDER' => 'submission_date DESC'],
        );
        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . ChangeValidation::getTypeName(2) . '</b>';
        if (!$number) {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
        } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(10, 10, 15, 20, 10, 15, 20);
            $pdf->displayTitle(
                _x('item', 'State'),
                __('Request date'),
                __('Approval requester'),
                __('Request comments'),
                __('Approval status'),
                __('Approver'),
                __('Approval comments'),
            );

            foreach ($result as $row) {
                $pdf->displayLine(
                    TicketValidation::getStatus($row['status']),
                    Html::convDateTime($row['submission_date']),
                    $dbu->getUserName($row['users_id']),
                    trim($row['comment_submission']),
                    Html::convDateTime($row['validation_date']),
                    $dbu->getUserName($row['users_id_validate']),
                    trim($row['comment_validation']),
                );
            }
        }
        $pdf->displaySpace();
    }
}
