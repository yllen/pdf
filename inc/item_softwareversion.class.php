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
 @authors   Nelly Mahu-Lasson
 @copyright Copyright (c) 2020-2022 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfItem_SoftwareVersion extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_SoftwareVersion());
   }


   static function pdfForSoftware(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB, $CFG_GLPI;

      $dbu = new DbUtils();

      $ID   = $item->getField('id');
      $type = $item->getType();
      $crit = ($type=='Software' ? 'softwares_id' : 'id');

      if ($crit == "softwares_id") {
         $number = Item_SoftwareVersion::countForSoftware($ID);
         $sort = "`entity` ASC, `version`, `itemname`";
      } else {
         $number = Item_SoftwareVersion::countForVersion($ID);
         $sort = "`entity` ASC, `itemname`";
      }

      $item_version_table = 'glpi_items_softwareversions';
      foreach ($CFG_GLPI['software_types'] as $itemtype) {
         $canshowitems[$itemtype] = $itemtype::canView();
         $itemtable = $itemtype::getTable();
         $query = ['SELECT' => [$item_version_table . '.*',
                                'glpi_softwareversions.name AS version',
                                'glpi_softwareversions.softwares_id AS sID',
                                'glpi_softwareversions.id AS vID',
                                "{$itemtable}.name AS itemname",
                                "{$itemtable}.id AS iID",
                     new QueryExpression($DB->quoteValue($itemtype)." AS ".$DB::quoteName('item_type')),
                               ],
                   'FROM'   => $item_version_table,
                   'INNER JOIN' => ['glpi_softwareversions'
                                    => ['FKEY' => [$item_version_table     => 'softwareversions_id',
                                                   'glpi_softwareversions' => 'id']]
                                   ],
                   'LEFT JOIN' => [$itemtable
                                   => ['FKEY' => [$item_version_table  => 'items_id',
                                                  $itemtable           => 'id',
                                                  ['AND'
                                                    => [$item_version_table.'.itemtype'  => $itemtype
                                                  ]]]]],
                   'WHERE'     => ["glpi_softwareversions.$crit"             => $ID,
                                   'glpi_items_softwareversions.is_deleted'  => 0
                                  ]
                  ];

         if ($DB->fieldExists($itemtable, 'serial')) {
            $query['SELECT'][] = $itemtable.'.serial';
         } else {
            $query['SELECT'][] = new QueryExpression(
                                    $DB->quoteValue('')." AS ".$DB->quoteName($itemtable.".serial"));
         }

         if ($DB->fieldExists($itemtable, 'otherserial')) {
            $query['SELECT'][] = $itemtable.'.otherserial';
         } else {
            $query['SELECT'][] = new QueryExpression(
                                 $DB->quoteValue('')." AS ".$DB->quoteName($itemtable.".otherserial"));
         }

         if ($DB->fieldExists($itemtable, 'users_id')) {
            $query['SELECT'][] = 'glpi_users.name AS username';
            $query['SELECT'][] = 'glpi_users.id AS userid';
            $query['SELECT'][] = 'glpi_users.realname AS userrealname';
            $query['SELECT'][] = 'glpi_users.firstname AS userfirstname';
            $query['LEFT JOIN']['glpi_users'] = ['FKEY' => [$itemtable     => 'users_id',
                                                            'glpi_users'   => 'id']
                                                ];
         } else {
            $query['SELECT'][] = new QueryExpression(
                                 $DB->quoteValue('')." AS ".$DB->quoteName($itemtable.".username"));
            $query['SELECT'][] = new QueryExpression(
                                 $DB->quoteValue('-1')." AS ".$DB->quoteName($itemtable.".userid"));
            $query['SELECT'][] = new QueryExpression(
                                 $DB->quoteValue('')." AS ".$DB->quoteName($itemtable.".userrealname"));
            $query['SELECT'][] = new QueryExpression(
                                 $DB->quoteValue('')." AS ".$DB->quoteName($itemtable.".userfirstname"));
         }

         if ($DB->fieldExists($itemtable, 'entities_id')) {
            $query['SELECT'][] = 'glpi_entities.completename AS entity';
            $query['LEFT JOIN']['glpi_entities'] = ['FKEY' => [$itemtable      => 'entities_id',
                                                               'glpi_entities' => 'id']
                                                   ];
            $query['WHERE'] += getEntitiesRestrictCriteria($itemtable, '', '', true);
         } else {
            $query['SELECT'][] = new QueryExpression(
                                             $DB->quoteValue('')." AS ".$DB->quoteName('entity'));
         }

         if ($DB->fieldExists($itemtable, 'locations_id')) {
            $query['SELECT'][] = 'glpi_locations.completename AS location';
            $query['LEFT JOIN']['glpi_locations'] = ['FKEY' => [$itemtable       => 'locations_id',
                                                                'glpi_locations' => 'id']
                                                    ];
         } else {
            $query['SELECT'][] = new QueryExpression(
                                             $DB->quoteValue('')." AS ".$DB->quoteName('location'));
         }
         if ($DB->fieldExists($itemtable, 'states_id')) {
            $query['SELECT'][] = 'glpi_states.name AS state';
            $query['LEFT JOIN']['glpi_states'] = ['FKEY' => [$itemtable    => 'states_id',
                                                             'glpi_states' => 'id']
                                                 ];
         } else {
            $query['SELECT'][] = new QueryExpression(
                                                $DB->quoteValue('')." AS ".$DB->quoteName('state'));
         }

         if ($DB->fieldExists($itemtable, 'groups_id')) {
            $query['SELECT'][] = 'glpi_groups.name AS groupe';
            $query['LEFT JOIN']['glpi_groups'] = ['FKEY' => [$itemtable    => 'groups_id',
                                                             'glpi_groups' => 'id']
                                                 ];
         } else {
            $query['SELECT'][] = new QueryExpression(
                                             $DB->quoteValue('')." AS ".$DB->quoteName('groupe'));
         }

         if ($DB->fieldExists($itemtable, 'is_deleted')) {
            $query['WHERE']["{$itemtable}.is_deleted"] = 0;
         }

         if ($DB->fieldExists($itemtable, 'is_template')) {
            $query['WHERE']["{$itemtable}.is_template"] = 0;
         }

         $queries[] = $query;
      }
      $union = new QueryUnion($queries, true);
      $criteria = ['SELECT' => [],
                   'FROM'   => $union,
                   'ORDER'  => "$sort ASC",
                   'LIMIT'  => $_SESSION['glpilist_limit']
                  ];

      $iterator = $DB->request($criteria);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('Installation', 'Installations', $number).'</b>';
      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $pdf->setColumnsSize(8,8,12,10,10,12,8,10,12,12);
         $pdf->displayTitle('<b><i>'._n('Version', 'Versions', 2), __('Name'), __('Serial number'),
                            __('Inventory number'), __('Location'), __('Status'), __('Group'),
                            __('User'), _n('License', 'Licenses', 2),
                            __('Type').'</i></b>');

         foreach ($iterator as $data) {
            $itemname = $data['itemname'];
            if (empty($itemname) || $_SESSION['glpiis_ids_visible']) {
               $itemname = sprintf(__('%1$s (%2$s)'), $itemname, $data['iID']);
            }
            $lics = Item_SoftwareLicense::GetLicenseForInstallation($data['item_type'],
                                                                    $data['iID'], $data['vID']);

            $tmp = [];
            if (count($lics)) {
               foreach ($lics as $lic) {
                  $licname = $lic['name'];
                  if (!empty($lic['type'])) {
                     $licname = sprintf(__('%1$s (%2$s)'), $licname, $lic['type']);
                  }
                  $tmp[] = $licname;
               }
            }
            $linkUser = User::canView();
            $pdf->displayLine($data['version'], $data['item_type'], $itemname,$data['serial'], $data['otherserial'],
                              $data['location'], $data['state'], $data['groupe'],
                              formatUserName($data['userid'], $data['username'], $data['userrealname'],
                                             $data['userfirstname'], $linkUser), implode(', ', $tmp));
         }
      }
      $pdf->displaySpace();
   }


   static function pdfForVersionByEntity(PluginPdfSimplePDF $pdf, SoftwareVersion $version) {
      global $DB;

      $dbu = new DbUtils();

      $softwareversions_id = $version->getField('id');

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.sprintf(__('%1$s: %2$s'),
                                       Dropdown::getDropdownName('glpi_softwares',
                                                                 $version->getField('softwares_id')),
                                       $version->getField('name'))."</b>");
      $pdf->setColumnsSize(75,25);
      $pdf->setColumnsAlign('left', 'right');

      $pdf->displayTitle('<b>'.__('Entity'), _n('Installation', 'Installations', 2).'</b>');

      $lig = $tot = 0;
      if (in_array(0, $_SESSION["glpiactiveentities"])) {
         $nb = Item_SoftwareVersion::countForVersion($softwareversions_id,0);
         if ($nb > 0) {
            $pdf->displayLine(__('Root entity'), $nb);
            $tot += $nb;
            $lig++;
         }
      }
      $sql = ['SELECT'  => ['id', 'completename'],
              'FROM'    => 'glpi_entities',
              'WHERE'   => $dbu->getEntitiesRestrictRequest('glpi_entities'),
              'ORDER'   => 'completename'];

      foreach ($DB->request($sql) as $ID => $data) {
         $nb = Item_SoftwareVersion::countForVersion($softwareversions_id,$ID);
         if ($nb > 0) {
            $pdf->displayLine($data["completename"], $nb);
            $tot += $nb;
            $lig++;
         }
      }

      if ($tot > 0) {
         if ($lig > 1) {
            $pdf->displayLine(__('Total'), $tot);
         }
      } else {
         $pdf->setColumnsSize(100);
         $pdf->setColumnsAlign('center');
         $pdf->displayLine(__('No item to display'));
      }
      $pdf->displaySpace();
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, $item){
      global $DB;

      $ID = $item->getField('id');

      // From Item_SoftwareVersion::showForComputer();
      $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_items_softwareversions`.`id`,
                       `glpi_states`.`name` AS state,
                       `glpi_softwareversions`.`id` AS verid,
                       `glpi_softwareversions`.`softwares_id`,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_softwares`.`is_valid` AS softvalid,
                       `glpi_items_softwareversions`.`date_install` AS dateinstall
                FROM `glpi_items_softwareversions`
                LEFT JOIN `glpi_softwareversions`
                     ON (`glpi_items_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`
                         AND `glpi_items_softwareversions`.`itemtype` = '".$item->getType()."')
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                WHERE `glpi_items_softwareversions`.`items_id` = '$ID'
                      AND `glpi_items_softwareversions`.`itemtype` = '".$item->getType()."'
                      AND `glpi_items_softwareversions`.`is_deleted` = '0'
                ORDER BY `softwarecategories_id`, `softname`, `version`";

      $output              = [];

      $software_category   = new SoftwareCategory();
      $software_version    = new SoftwareVersion();

      foreach ($DB->request($query) as $softwareversion) {
         $output[] = $softwareversion;
      }

      $installed = [];
      $pdf->setColumnsSize(100);
      $title = '<b>'.__('Installed software').'</b>';

      if (!count($output)) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, count($output));
         $pdf->displayTitle($title);

         $cat = -1;
         foreach ($output as $soft) {
            if ($soft["softwarecategories_id"] != $cat) {
               $cat = $soft["softwarecategories_id"];
               if ($cat && $software_category->getFromDB($cat)) {
                  $catname = $software_category->getName();
               } else {
                  $catname = __('Uncategorized software');
               }

               $pdf->setColumnsSize(100);
               $pdf->displayTitle('<b>'.$catname.'</b>');

               $pdf->setColumnsSize(39,9,11,19,14,8);
               $pdf->displayTitle('<b>'.__('Name'), __('Status'), __('Version'), __('License'),
                                  __('Installation date'), __('Valid license').'</b>');
            }

            // From Item_SoftwareVersion::displaySoftsByCategory()
            $verid = $soft['verid'];
            $query = "SELECT `glpi_softwarelicenses`.*,
                             `glpi_softwarelicensetypes`.`name` AS type
                      FROM `glpi_items_softwarelicenses`
                      INNER JOIN `glpi_softwarelicenses`
                           ON (`glpi_items_softwarelicenses`.`softwarelicenses_id`
                                    = `glpi_softwarelicenses`.`id`)
                      LEFT JOIN `glpi_softwarelicensetypes`
                           ON (`glpi_softwarelicenses`.`softwarelicensetypes_id`
                                    =`glpi_softwarelicensetypes`.`id`
                               AND `glpi_items_softwarelicenses`.`itemtype` = 'Computer')
                      WHERE `glpi_items_softwarelicenses`.`items_id` = '$ID'
                            AND (`glpi_softwarelicenses`.`softwareversions_id_use` = '$verid'
                                 OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                                     AND `glpi_softwarelicenses`.`softwareversions_id_buy` = '$verid'))";

            $lic = '';
            foreach ($DB->request($query) as $licdata) {
               $installed[] = $licdata['id'];
               $lic .= (empty($lic)?'':', ').'<b>'.$licdata['name'].'</b> '.$licdata['serial'];
               if (!empty($licdata['type'])) {
                  $lic = sprintf(__('%1$s (%2$s)'), $lic, $licdata['type']);
               }
            }

            $pdf->displayLine($soft['softname'], $soft['state'], $soft['version'], $lic,
                              $soft['dateinstall'], $soft['softvalid']);
         } // Each version
      }

      // Affected licenses NOT installed
      $query = "SELECT `glpi_softwarelicenses`.*,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_states`.`name` AS state
                FROM `glpi_softwarelicenses`
                LEFT JOIN `glpi_items_softwarelicenses`
                      ON (`glpi_items_softwarelicenses`.softwarelicenses_id
                              = `glpi_softwarelicenses`.`id`
                          AND `glpi_items_softwarelicenses`.`itemtype` = 'Computer')
                INNER JOIN `glpi_softwares`
                      ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id`)
                LEFT JOIN `glpi_softwareversions`
                      ON (`glpi_softwarelicenses`.`softwareversions_id_use`
                              = `glpi_softwareversions`.`id`
                           OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                               AND `glpi_softwarelicenses`.`softwareversions_id_buy`
                                       = `glpi_softwareversions`.`id`))
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                WHERE `glpi_items_softwarelicenses`.`items_id` = '$ID'";

      if (count($installed)) {
         $query .= " AND `glpi_softwarelicenses`.`id` NOT IN (".implode(',',$installed).")";
      }

      $req = $DB->request($query);
      if ($req->numrows()) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle('<b>'.__('Affected licenses of not installed software', 'pdf').'</b>');

         $pdf->setColumnsSize(50,13,13,24);
         $pdf->displayTitle('<b>'.__('Name'), __('Status'), __('Version'), __('License').'</b>');

         $lic = '';
         foreach ($req as $data) {
            $lic .= '<b>'.$data['name'].'</b> '.$data['serial'];
            if (!empty($data['softwarelicensetypes_id'])) {
               $lic = sprintf(__('%1$s (%2$s)'), $lic,
                              Toolbox::stripTags(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                                   $data['softwarelicensetypes_id'])));
            }
            $pdf->displayLine($data['softname'], $data['state'], $data['version'], $lic);
         }
      }

      $pdf->displaySpace();
   }
}