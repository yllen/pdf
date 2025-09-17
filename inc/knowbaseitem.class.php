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

class PluginPdfKnowbaseItem extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new KnowbaseItem());
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);
        unset($onglets['KnowbaseItem$3']); // tab for edition
        unset($onglets['KnowbaseItem_Item$1']);
        unset($onglets['KnowbaseItemTranslation$1']);
        unset($onglets['KnowbaseItem_Revision$1']);

        return $onglets;
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, KnowbaseItem $item)
    {
        $dbu = new DbUtils();

        $ID = $item->getField('id');

        if (!Session::haveRightsOr(
            'knowbase',
            [READ, KnowbaseItem::READFAQ, KnowbaseItem::KNOWBASEADMIN],
        )) {
            return false;
        }

        $knowbaseitemcategories_id = $item->getField('knowbaseitemcategories_id');
        $fullcategoryname
        = Toolbox::stripTags($dbu->getTreeValueCompleteName(
            'glpi_knowbaseitemcategories',
            $knowbaseitemcategories_id,
        ));

        $question
        = Toolbox::stripTags(Glpi\Toolbox\Sanitizer::unsanitize(html_entity_decode(
            $item->getField('name'),
            ENT_QUOTES,
            'UTF-8',
        )));

        $answer = Glpi\Toolbox\Sanitizer::unsanitize(Html::entity_decode_deep($item->getField('answer')));
        $answer = preg_replace('#data:image/[^;]+;base64,#', '@', $answer);

        preg_match_all('/<img [^>]*src=[\'"]([^\'"]*docid=([0-9]*))[^>]*>/', $answer, $res, PREG_SET_ORDER);

        foreach ($res as $img) {
            $docimg = new Document();
            $docimg->getFromDB((int) $img[2]);
            $width = null;

            if (preg_match('/\bwidth=["\']?(\d+)/i', $img[0], $match_width)) {
                $width = $match_width[1];
            }

            $path    = '<img src="file://' . GLPI_DOC_DIR . '/' . $docimg->fields['filepath'] . '" width="' . $width . '"/>';
            $answer = str_replace($img[0], $path, $answer);
        }

        $pdf->setColumnsSize(100);

        if (Toolbox::strlen($fullcategoryname) > 0) {
            $pdf->displayTitle('<b>' . __('Category name') . '</b>');
            $pdf->displayLine($fullcategoryname);
        }

        if (Toolbox::strlen($question) > 0) {
            $pdf->displayTitle('<b>' . __('Subject') . '</b>');
            $pdf->displayText('', $question, 5);
        } else {
            $pdf->displayTitle('<b>' . __('No question found', 'pdf') . '</b>');
        }

        if (Toolbox::strlen($answer) > 0) {
            $pdf->displayTitle('<b>' . __('Content') . '</b>');
            $pdf->displayText('', $answer, 5);
        } else {
            $pdf->displayTitle('<b>' . __('No answer found', 'pdf') . '</b>');
        }

        $pdf->setColumnsSize(50, 15, 15, 10, 10);
        $pdf->displayTitle(
            __('Writer'),
            __('Creation date'),
            __('Last update'),
            __('FAQ'),
            _n('View', 'Views', 2),
        );
        $pdf->displayLine(
            $dbu->getUserName($item->fields['users_id']),
            Html::convDateTime($item->fields['date']),
            Html::convDateTime($item->fields['date_mod']),
            Dropdown::getYesNo($item->fields['is_faq']),
            $item->fields['view'],
        );

        $pdf->displaySpace();
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof KnowbaseItem) {
            switch ($tab) {
                case 'KnowbaseItem$1':
                    self::pdfMain($pdf, $item);
                    break;

                case 'KnowbaseItem$2':
                    self::pdfCible($pdf, $item);
                    break;

                default:
                    return false;
            }
        }

        return true;
    }

    /**
     * @since version 0.85
    **/
    public static function pdfCible(PluginPdfSimplePDF $pdf, KnowbaseItem $item)
    {
        $dbu = new DbUtils();

        $ID = $item->getField('id');

        if (!Session::haveRightsOr(
            'knowbase',
            [READ, KnowbaseItem::READFAQ, KnowbaseItem::KNOWBASEADMIN],
        )) {
            return false;
        }

        $users    = KnowbaseItem_User::getUsers($ID);
        $entities = Entity_KnowbaseItem::getEntities($ID);
        $groups   = Group_KnowbaseItem::getGroups($ID);
        $profiles = KnowbaseItem_Profile::getProfiles($ID);

        $nb = $item->countVisibilities();
        if ($nb) {
            $pdf->setColumnsSize(100);
            $pdf->displayTitle(_n('Target', 'Targets', $nb));

            $pdf->setColumnsSize(30, 70);
            $pdf->displayTitle(__('Type'), _n('Recipient', 'Recipients', 2));

            $recursive = '';
            if (count($entities)) {
                foreach ($entities as $key => $val) {
                    foreach ($val as $data) {
                        if ($data['is_recursive']) {
                            $recursive = '(' . __('R') . ')';
                        }
                        $pdf->displayLine(
                            __('Entity'),
                            sprintf(
                                __('%1s %2s'),
                                Dropdown::getDropdownName(
                                    'glpi_entities',
                                    $data['entities_id'],
                                ),
                                $recursive,
                            ),
                        );
                    }
                }
            }

            if (count($profiles)) {
                foreach ($profiles as $key => $val) {
                    foreach ($val as $data) {
                        if ($data['is_recursive']) {
                            $recursive = '(' . __('R') . ')';
                        }
                        $names = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id']);
                        $profilename = '';
                        if ($data['entities_id'] >= 0) {
                            $profilename = sprintf(
                                __('%1$s / %2$s'),
                                $names,
                                Dropdown::getDropdownName(
                                    'glpi_entities',
                                    $data['entities_id'],
                                ),
                            );
                            if ($data['is_recursive']) {
                                $profilename = sprintf(__('%1$s %2$s'), $profilename, $recursive);
                            }
                        }
                        $pdf->displayLine(__('Profile'), $profilename);
                    }
                }
            }

            if (count($groups)) {
                foreach ($groups as $key => $val) {
                    foreach ($val as $data) {
                        if ($data['is_recursive']) {
                            $recursive = '(' . __('R') . ')';
                        }
                        $names = Dropdown::getDropdownName('glpi_groups', $data['groups_id']);
                        $groupname = '';
                        if ($data['entities_id'] >= 0) {
                            $groupname = sprintf(
                                __('%1$s / %2$s'),
                                $names,
                                Dropdown::getDropdownName(
                                    'glpi_entities',
                                    $data['entities_id'],
                                ),
                            );
                            if ($data['is_recursive']) {
                                $groupname = sprintf(__('%1$s %2$s'), $groupname, $recursive);
                            }
                        }
                        $pdf->displayLine(__('Group'), $groupname);
                    }
                }
            }

            if (count($users)) {
                foreach ($users as $key => $val) {
                    foreach ($val as $data) {
                        $pdf->displayLine(__('User'), $dbu->getUserName($data['users_id']));
                    }
                }
            }
        }
    }
}
