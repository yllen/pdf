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


class PluginPdfComputer_SoftwareVersion extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_SoftwareVersion());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $dbu = new DbUtils();

      $ID   = $item->getField('id');
      $type = $item->getType();
      $crit = ($type=='Software' ? 'softwares_id' : 'id');


      $query_number = ['FROM'       => 'glpi_computers_softwareversions', 'COUNT' => 'cpt',
                       'INNER JOIN' => ['glpi_computers'
                                        => ['FKEY' => ['glpi_computers_softwareversions' => 'computers_id',
                                                       'glpi_computers'                  => 'id']]],
                       'WHERE'      => ['glpi_computers.is_deleted'                  => 0,
                                        'glpi_computers.is_template'                 => 0,
                                        'glpi_computers_softwareversions.is_deleted' => 0]
                                        + $dbu->getEntitiesRestrictCriteria('glpi_computers')];

      if ($type == 'Software') {
         $crit      = 'softwares_id';
         // Software ID
         $query_number['INNER JOIN']['glpi_softwareversions']
                           = ['FKEY' => ['glpi_computers_softwareversions' => 'softwareversions_id',
                                         'glpi_softwareversions'           => 'id']];
         $query_number['WHERE']['glpi_softwareversions.softwares_id'] = $ID;

      } else {
         $crit      = 'id';
         //SoftwareVersion ID
         $query_number['WHERE']['glpi_computers_softwareversions.softwareversions_id'] = $ID;
      }

      $total = 0;
      if ($result = $DB->request($query_number)) {
         foreach ($result as $row) {
            $total  = $row['cpt'];
         }
      }

      $query = "SELECT DISTINCT `glpi_computers_softwareversions`.*,
                          `glpi_computers`.`name` AS compname,
                          `glpi_computers`.`id` AS cID,
                          `glpi_computers`.`serial`,
                          `glpi_computers`.`otherserial`,
                          `glpi_users`.`name` AS username,
                          `glpi_users`.`id` AS userid,
                          `glpi_users`.`realname` AS userrealname,
                          `glpi_users`.`firstname` AS userfirstname,
                          `glpi_softwareversions`.`name` AS version,
                          `glpi_softwareversions`.`id` AS vID,
                          `glpi_softwareversions`.`softwares_id` AS sID,
                          `glpi_softwareversions`.`name` AS vername,
                          `glpi_entities`.`completename` AS entity,
                          `glpi_locations`.`completename` AS location,
                          `glpi_states`.`name` AS state,
                          `glpi_groups`.`name` AS groupe
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                              = `glpi_softwareversions`.`id`)
                INNER JOIN `glpi_computers`
                     ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                LEFT JOIN `glpi_locations`
                     ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                WHERE (`glpi_softwareversions`.`$crit` = '$ID') " .
                      $dbu->getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0'
                ORDER BY version, compname
                LIMIT 0," . intval($_SESSION['glpilist_limit']);

      $pdf->setColumnsSize(100);

      if (($result = $DB->request($query))
          && (($number = count($result)) > 0)) {
         if ($number == $total) {
            $pdf->displayTitle('<b>'.sprintf(__('%1$s: %2$s'),
                                             _n('Installation', 'Installations', 2), $number)."</b>");
         } else {
            $pdf->displayTitle('<b>'.sprintf(__('%1$s: %2$s'), _n('Installation', 'Installations', 2),
                                             $number." / ".$total)."</b>");
         }
         $pdf->setColumnsSize(8,12,10,10,12,8,10,5,17,8);
         $pdf->displayTitle('<b><i>'._n('Version', 'Versions', 2), __('Name'), __('Serial number'),
                            __('Inventory number'), __('Location'), __('Status'), __('Group'),
                            __('User'), _n('License', 'Licenses', 2),
                            __('Installation date').'</i></b>');

         foreach ($result as $data) {
            $compname = $data['compname'];
            if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
               $compname = sprintf(__('%1$s (%2$s)'), $compname, $data['cID']);
            }
            $lics = Item_SoftwareLicense::GetLicenseForInstallation('Computer', $data['cID'],
                                                                    $data['vID']);

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
            $pdf->displayLine($data['version'], $compname,$data['serial'], $data['otherserial'],
                              $data['location'], $data['state'], $data['groupe'],
                              formatUserName($data['userid'], $data['username'], $data['userrealname'],
                                             $data['userfirstname'], $linkUser), implode(', ', $tmp),
                              Html::convDate($data['date_install']));
         }
      } else {
         $pdf->displayTitle('<b>'._n('Installation', 'Installations', 2).'</b>');
         $pdf->displayLine(__('No item found'));
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
         $nb = Item_SoftwareVersion::countForVersion('Computer',$softwareversions_id,0);
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
         $nb = Item_SoftwareVersion::countForVersion('Computer',$softwareversions_id,$ID);
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


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $comp){
      global $DB;

      $ID = $comp->getField('id');

      // From Computer_SoftwareVersion::showForComputer();
      $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_computers_softwareversions`.`id`,
                       `glpi_states`.`name` AS state,
                       `glpi_softwareversions`.`id` AS verid,
                       `glpi_softwareversions`.`softwares_id`,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_softwares`.`is_valid` AS softvalid,
                       `glpi_computers_softwareversions`.`date_install` AS dateinstall
                FROM `glpi_computers_softwareversions`
                LEFT JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`)
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                WHERE `glpi_computers_softwareversions`.`computers_id` = '$ID'
                      AND `glpi_computers_softwareversions`.`is_deleted` = '0'
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

            // From Computer_SoftwareVersion::displaySoftsByCategory()
            $verid = $soft['verid'];
            $query = "SELECT `glpi_softwarelicenses`.*,
                             `glpi_softwarelicensetypes`.`name` AS type
                      FROM `glpi_computers_softwarelicenses`
                      INNER JOIN `glpi_softwarelicenses`
                           ON (`glpi_computers_softwarelicenses`.`softwarelicenses_id`
                                    = `glpi_softwarelicenses`.`id`)
                      LEFT JOIN `glpi_softwarelicensetypes`
                           ON (`glpi_softwarelicenses`.`softwarelicensetypes_id`
                                    =`glpi_softwarelicensetypes`.`id`)
                      WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$ID'
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
                LEFT JOIN `glpi_computers_softwarelicenses`
                      ON (`glpi_computers_softwarelicenses`.softwarelicenses_id
                              = `glpi_softwarelicenses`.`id`)
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
                WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$ID' ";

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