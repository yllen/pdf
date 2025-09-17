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

class PluginPdfITILSolution extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new ITILSolution());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $pdf->setColumnsSize(100);

        $soluce = $DB->request(
            'glpi_itilsolutions',
            ['itemtype'    => $item->getType(),
                'items_id' => $item->fields['id']],
        );

        $number = count($soluce);

        $title = '<b>' . __('Solution') . '</b>';
        if (!$number) {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
        } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
            $pdf->displayTitle($title);
            foreach ($soluce as $row) {
                if ($row['solutiontypes_id']) {
                    $title = Toolbox::stripTags(Dropdown::getDropdownName(
                        'glpi_solutiontypes',
                        $row['solutiontypes_id'],
                    ));
                } else {
                    $title = __('Solution');
                }
                $sol = Glpi\Toolbox\Sanitizer::unsanitize(Html::entity_decode_deep($row['content']));
                $sol = preg_replace('#data:image/[^;]+;base64,#', '@', $sol);

                preg_match_all('/<img [^>]*src=[\'"]([^\'"]*docid=([0-9]*))[^>]*>/', $sol, $res, PREG_SET_ORDER);

                foreach ($res as $img) {
                    $docimg = new Document();
                    $docimg->getFromDB((int) $img[2]);

                    $path = '<img src="file://' . GLPI_DOC_DIR . '/' . $docimg->fields['filepath'] . '"/>';
                    $sol = str_replace($img[0], $path, $sol);
                }

                $text = $textapprove = '';
                if ($row['status'] == 3) {
                    $text = __('Soluce approved on ', 'pdf');
                } elseif ($row['status'] == 4) {
                    $text = __('Soluce refused on ', 'pdf');
                }
                if (isset($row['date_approval']) || !empty($row['users_id_approval'])) {
                    $textapprove = '<br /><br /><br /><i>' .
                                    sprintf(
                                        __('%1$s %2$s'),
                                        $text,
                                        Html::convDateTime($row['date_approval']),
                                    ) . '&nbsp;' .
                                    sprintf(
                                        __('%1$s %2$s'),
                                        __('By'),
                                        Toolbox::stripTags($dbu->getUserName($row['users_id_approval'])),
                                    )
                                    . '</i>';
                }
                $pdf->displayText('<b><i>' . sprintf(__('%1$s: %2$s'), $title . '</i></b>', ''), $sol . $textapprove);
            }
        }

        $pdf->displaySpace();
    }
}
