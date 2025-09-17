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

class PluginPdfItilFollowup extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new ITILFollowup());
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item, $private)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $ID   = $item->getField('id');
        $type = $item->getType();

        //////////////followups///////////

        $query = ['FROM' => 'glpi_itilfollowups',
            'WHERE'      => ['items_id' => $ID,
                'itemtype'              => $type],
            'ORDER' => 'date DESC'];

        if (!$private) {
            // Don't show private'
            $query['WHERE']['is_private'] = 0;
        } elseif (!Session::haveRight('followup', ITILFollowup::SEEPRIVATE)) {
            // No right, only show connected user private one
            $query['WHERE']['OR'] = ['is_private' => 0,
                'users_id'                        => Session::getLoginUserID()];
        }

        $result = $DB->request($query);

        $number = count($result);

        $pdf->setColumnsSize(100);
        $title = '<b>' . ITILFollowup::getTypeName(2) . '</b>';

        if (!$number) {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
        } else {
            if ($number > $_SESSION['glpilist_limit']) {
                $title = sprintf(__('%1$s (%2$s)'), $title, $_SESSION['glpilist_limit'] . '/' . $number);
            } else {
                $title = sprintf(__('%1$s: %2$s'), $title, $number);
            }
            $pdf->displayTitle($title);

            $pdf->setColumnsSize(44, 14, 42);
            $pdf->displayTitle('<b><i>' . __('Source of followup', 'pdf') . '</i></b>', // Source
                '<b><i>' . __('Date') . '</i></b>', // Date
                '<b><i>' . __('Requester') . '</i></b>'); // Author

            foreach ($result as $data) {
                if ($data['requesttypes_id']) {
                    $lib = Dropdown::getDropdownName('glpi_requesttypes', $data['requesttypes_id']);
                } else {
                    $lib = '';
                }
                if ($data['is_private']) {
                    $lib = sprintf(__('%1$s (%2$s)'), $lib, __('Private'));
                }
                $pdf->displayLine(
                    Toolbox::stripTags($lib),
                    Html::convDateTime($data['date']),
                    Toolbox::stripTags($dbu->getUserName($data['users_id'])),
                );


                $content = Glpi\Toolbox\Sanitizer::unsanitize(Html::entity_decode_deep($data['content']));
                $content = preg_replace('#data:image/[^;]+;base64,#', '@', $content);

                preg_match_all('/<img [^>]*src=[\'"]([^\'"]*docid=([0-9]*))[^>]*>/', $content, $res, PREG_SET_ORDER);

                foreach ($res as $img) {
                    $docimg = new Document();
                    $docimg->getFromDB((int) $img[2]);

                    $path    = '<img src="file://' . GLPI_DOC_DIR . '/' . $docimg->fields['filepath'] . '"/>';
                    $content = str_replace($img[0], $path, $content);
                }

                $pdf->displayText(
                    '<b><i>' . sprintf(__('%1$s: %2$s') . '</i></b>', __('Description'), ''),
                    $content,
                    1,
                );
            }
        }
        $pdf->displaySpace();
    }
}
