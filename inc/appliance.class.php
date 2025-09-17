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

class PluginPdfAppliance extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    /**
     * @param $obj (defult NULL)
     **/
    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new Appliance());
    }

    /**
     * Define tabs to display
     *
     * @see CommonGLPI final defineAllTabs()
    **/
    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);

        //   unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
        return $onglets;
    }

    /**
     * show Tab content
     *
     * @param $pdf                  instance of plugin PDF
     * @param $item        string   CommonGLPI object
     * @param $tab         string   CommonGLPI
     *
     * @return bool
    **/
    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        if ($item instanceof Appliance) {
            switch ($tab) {
                case 'Appliance_Item$1':
                    $plugin = new Plugin();
                    if (
                        $plugin->isActivated('appliances')
                        && class_exists('PluginAppliancesAppliance_Item')
                    ) {
                        PluginAppliancesAppliance_Item::pdfForAppliance($pdf, $item);
                    } else {
                        self::pdfForAppliance($pdf, $item);
                    }
                    break;

                case 'PluginAppliancesOptvalue$1':
                    if (class_exists('PluginAppliancesOptvalue')) {
                        PluginAppliancesOptvalue::pdfForAppliance($pdf, $item);
                    }
                    break;

                default:
                    return false;
            }
        }

        return true;
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, Appliance $item)
    {
        $dbu = new DbUtils();

        PluginPdfCommon::mainTitle($pdf, $item);

        $pdf->displayLine(
            sprintf(__('%1$s: %2$s'), '<b><i>' . __('Name') . '</i></b>', $item->fields['name']),
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . _n('Status', 'Statuses', 1) . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_states',
                    $item->fields['states_id'],
                )),
            ),
        );

        $pdf->displayLine(
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Associable to a ticket') . '</i></b>',
                Dropdown::getYesNo($item->fields['is_helpdesk_visible']),
            ),
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Location') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_locations',
                    $item->fields['locations_id'],
                )),
            ),
        );

        $pdf->displayLine(
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Type') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_appliancetypes',
                    $item->fields['appliancetypes_id'],
                )),
            ),
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Technician in charge of the hardware') . '</i></b>',
                getUserName($item->fields['users_id_tech']),
            ),
        );

        $pdf->displayLine(
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Manufacturer') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_suppliers',
                    $item->fields['manufacturers_id'],
                )),
            ),
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Group in charge of the hardware') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_groups',
                    $item->fields['groups_id_tech'],
                )),
            ),
        );

        $pdf->displayLine(
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Serial number') . '</i></b>',
                $item->fields['serial'],
            ),
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Inventory number') . '</i></b>',
                $item->fields['otherserial'],
            ),
        );

        $pdf->displayLine(
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('User') . '</i></b>',
                getUserName($item->fields['users_id']),
            ),
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Group') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_groups',
                    $item->fields['groups_id'],
                )),
            ),
        );

        $pdf->displayLine(
            sprintf(
                __('%1$s: %2$s'),
                '<b><i>' . __('Environment', 'appliances') . '</i></b>',
                Toolbox::stripTags(Dropdown::getDropdownName(
                    'glpi_applianceenvironments',
                    $item->fields['applianceenvironments_id'],
                )),
            ),
        );

        $pdf->displayText(
            sprintf(__('%1$s: %2$s'), '<b><i>' . __('Comments') . '</i></b>', $item->fields['comment']),
        );

        $pdf->displaySpace();
    }

    public static function pdfForAppliance(PluginPdfSimplePDF $pdf, Appliance $appli)
    {
        /** @var DBmysql $DB */
        global $DB;

        $instID = $appli->fields['id'];

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . _n('Associated item', 'Associated items', 2) . '</b>');

        $result = $DB->request('SELECT DISTINCT `itemtype`
                              FROM `glpi_appliances_items`
                              WHERE `appliances_id` = ' . $instID);
        $number = count($result);

        if (Session::isMultiEntitiesMode()) {
            $pdf->setColumnsSize(12, 27, 25, 18, 18);
            $pdf->displayTitle(
                '<b><i>' . __('Type'),
                __('Name'),
                __('Entity'),
                __('Serial number'),
                __('Inventory number') . '</i></b>',
            );
        } else {
            $pdf->setColumnsSize(25, 31, 22, 22);
            $pdf->displayTitle(
                '<b><i>' . __('Type'),
                __('Name'),
                __('Serial number'),
                __('Inventory number') . '</i></b>',
            );
        }

        if (!$number) {
            $pdf->displayLine(__('No item found'));
        } else {
            $dbu = new DbUtils();
            foreach ($result as $id => $row) {
                $type = $row['itemtype'];
                if (!($item = $dbu->getItemForItemtype($type))) {
                    continue;
                }

                if ($item->canView()) {
                    $column = 'name';
                    if ($type == 'Ticket') {
                        $column = 'id';
                    }
                    if ($type == 'KnowbaseItem') {
                        $column = 'question';
                    }

                    $query = ['FIELDS' => [$item->getTable() . '.*',
                        'glpi_entities.id AS entity',
                        'glpi_appliances_items_relations.id AS IDD'],
                        'FROM'      => 'glpi_appliances_items',
                        'LEFT JOIN' => [$item->getTable()
                                        => ['FKEY' => [$item->getTable() => 'id',
                                            'glpi_appliances_items'      => 'items_id'],
                                            ['glpi_appliances_items.itemtype' => $type]],
                            'glpi_appliances_items_relations'
                             => ['FKEY' => ['glpi_appliances_items_relations' => 'appliances_items_id',
                                 'glpi_appliances_items'                      => 'id']],
                            'glpi_entities'
                             => ['FKEY' => ['glpi_entities' => 'id',
                                 $item->getTable()          => 'entities_id']]],
                        'WHERE' => ['glpi_appliances_items.appliances_id' => $instID]
                                      + getEntitiesRestrictCriteria($item->getTable())];

                    if ($item->maybeTemplate()) {
                        $query['WHERE'][$item->getTable() . '.is_template'] = 0;
                    }
                    $query['ORDER'] = ['glpi_entities.completename', $item->getTable() . '.' . $column];

                    $result_linked = $DB->request($query);
                    if (count($result_linked)) {
                        foreach ($result_linked as $id => $data) {
                            if (!$item->getFromDB($data['id'])) {
                                continue;
                            }

                            if ($type == 'Ticket') {
                                $data['name'] = sprintf(__('%1$s %2$s'), __('Ticket'), $data['id']);
                            }
                            if ($type == 'KnowbaseItem') {
                                $data['name'] = $data['question'];
                            }
                            $name = $data['name'];
                            if (empty($data['name'])) {
                                $name = sprintf(__('%1$s (%2$s)'), $name, $data['id']);
                            }

                            if (Session::isMultiEntitiesMode()) {
                                $pdf->setColumnsSize(12, 27, 25, 18, 18);
                                $pdf->displayLine(
                                    $item->getTypeName(1),
                                    $name,
                                    Dropdown::getDropdownName(
                                        'glpi_entities',
                                        $data['entities_id'],
                                    ),
                                    (isset($data['serial']) ? $data['serial'] : '-'),
                                    (isset($data['otherserial']) ? $data['otherserial'] : '-'),
                                );
                            } else {
                                $pdf->setColumnsSize(25, 31, 22, 22);
                                $pdf->displayTitle(
                                    $item->getTypeName(1),
                                    $name,
                                    (isset($data['serial']) ? $data['serial'] : '-'),
                                    (isset($data['otherserial']) ? $data['otherserial'] : '-'),
                                );
                            }

                            if (!empty($data['IDD'])) {
                                self::showList_relation($pdf, $data['IDD']);
                            }
                        }
                    }
                }
            }
        }
        $pdf->displaySpace();
    }

    public static function showList_relation($pdf, $relID)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $relation = new Appliance_Item_Relation();
        $relation->getFromDB($relID);

        $item = $relation->fields['itemtype'];

        $objtype = new $item();

        // selects all the attached relations
        $tablename = $dbu->getTableForItemType($item);
        $title     = $objtype->getTypeName();

        $field = 'name AS dispname';
        if ($item == 'Location') {
            $field = 'completename AS dispname';
        }

        $sql_loc = ['SELECT' => ['glpi_appliances_items_relations.*', $field],
            'FROM'           => $tablename,
            'LEFT JOIN'      => ['glpi_appliances_items_relations'
                             => ['FKEY' => [$tablename             => 'id',
                                 'glpi_appliances_items_relations' => 'items_id']]],
            'WHERE' => ['glpi_appliances_items_relations.id' => $relID]];

        $result_loc = $DB->request($sql_loc);

        $opts = [];
        foreach ($result_loc as $res) {
            $opts[] = $res['dispname'];
        }
        $pdf->setColumnsSize(100);
        $pdf->displayLine(sprintf(
            __('%1$s: %2$s'),
            '<b><i>' . __('Relations') . "&nbsp;$title </i> </b>",
            implode(', ', $opts),
        ));
    }

    /**
     * Show for PDF the optional value for a device / applicatif
     *
     * @param $pdf            object for the output
     * @param $ID             of the relation
     * @param $appliancesID   ID of the applicatif
     **/
    public static function showList_PDF($pdf, $ID, $appliancesID)
    {
        /** @var DBmysql $DB */
        global $DB;

        $result_app_opt = $DB->request(['FIELDS' => ['id', 'champ', 'ddefault', 'vvalues'],
            'FROM'                               => 'glpi_plugin_appliances_optvalues',
            'WHERE'                              => ['plugin_appliances_appliances_id' => $appliancesID],
            'ORDER'                              => 'vvalues']);
        $number_champs = count($result_app_opt);

        if (!$number_champs) {
            return;
        }

        $opts = [];
        for ($i = 1 ; $i <= $number_champs ; $i++) {
            if ($data_opt = $result_app_opt->current()) {
                $query_val = $DB->request(['SELECT' => 'vvalue',
                    'FROM'                          => 'glpi_plugin_appliances_optvalues_items',
                    'WHERE'                         => ['plugin_appliances_optvalues_id' => $data_opt['id'],
                        'items_id'                                                       => $ID]]);
                $data_val = $query_val->current();
                $vvalue   = ($data_val ? $data_val['vvalue'] : '');
                if (empty($vvalue) && !empty($data_opt['ddefault'])) {
                    $vvalue = $data_opt['ddefault'];
                }
                $opts[] = $data_opt['champ'] . ($vvalue ? '=' . $vvalue : '');
            }
        } // For

        $pdf->setColumnsSize(100);
        $pdf->displayLine(sprintf(
            __('%1$s: %2$s'),
            '<b><i>' . __('User fields', 'appliances') . '</i></b>',
            implode(', ', $opts),
        ));
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbu = new DbUtils();

        $ID       = $item->getField('id');
        $itemtype = get_class($item);

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . __('Associated appliances', 'appliances') . '</b>');

        $query = ['FIELDS' => ['glpi_plugin_appliances_appliances_items.id AS entID',
            'glpi_plugin_appliances_appliances.*'],
            'FROM'      => 'glpi_plugin_appliances_appliances_items',
            'LEFT JOIN' => ['glpi_plugin_appliances_appliances'
                  => ['FKEY' => ['glpi_plugin_appliances_appliances'
                        => 'id',
                      'glpi_plugin_appliances_appliances_items'
                            => 'plugins_appliances_appliances_id']],
                'glpi_entities'
                      => ['FKEY' => ['glpi_entities' => 'id',
                          'glpi_plugin_appliances_appliances'
                                => 'entities_id']]],
            'WHERE' => ['glpi_plugin_appliances_appliances_items.items_id' => $ID,
                'glpi_plugin_appliances_appliances_items.itemtype'         => $itemtype]
            + getEntitiesRestrictCriteria(
                'glpi_plugin_appliances_appliances',
                'entities_id',
                $item->getEntityID(),
                true,
            )];
        $result = $DB->request($query);
        $number = count($result);

        if (!$number) {
            $pdf->displayLine(__('No item found'));
        } else {
            if (Session::isMultiEntitiesMode()) {
                $pdf->setColumnsSize(30, 30, 20, 20);
                $pdf->displayTitle('<b><i>' . __('Name'), __('Entity'), __('Group'), __('Type') . '</i></b>');
            } else {
                $pdf->setColumnsSize(50, 25, 25);
                $pdf->displayTitle('<b><i>' . __('Name'), __('Group'), __('Type') . '</i></b>');
            }

            while ($data = $result->current()) {
                $appliancesID = $data['id'];
                if (Session::isMultiEntitiesMode()) {
                    $pdf->setColumnsSize(30, 30, 20, 20);
                    $pdf->displayLine(
                        $data['name'],
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_entities',
                            $data['entities_id'],
                        )),
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_groups',
                            $data['groups_id'],
                        )),
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_plugin_appliances_appliancetypes',
                            $data['plugin_appliances_appliancetypes_id'],
                        )),
                    );
                } else {
                    $pdf->setColumnsSize(50, 25, 25);
                    $pdf->displayLine(
                        $data['name'],
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_groups',
                            $data['groups_id'],
                        )),
                        Toolbox::stripTags(Dropdown::getDropdownName(
                            'glpi_plugin_appliances_appliancetypes',
                            $data['plugin_appliances_appliancetypes_id'],
                        )),
                    );
                }
                if (class_exists('PluginAppliancesRelation')) {
                    PluginAppliancesRelation::showList_PDF($pdf, $data['relationtype'], $data['entID']);
                }
                if (class_exists('PluginAppliancesOptvalue_Item')) {
                    PluginAppliancesOptvalue_Item::showList_PDF($pdf, $ID, $appliancesID);
                }
                $result->next();
            }
        }
        $pdf->displaySpace();
    }
}
