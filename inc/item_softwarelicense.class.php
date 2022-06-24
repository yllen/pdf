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


class PluginPdfItem_SoftwareLicense extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Item_SoftwareLicense());
   }


   static function pdfForLicenseByEntity(PluginPdfSimplePDF $pdf, SoftwareLicense $license) {
      global $DB;

      $dbu = new DbUtils();

      $ID = $license->getField('id');

      $pdf->setColumnsSize(65,35);
      $pdf->setColumnsAlign('left', 'right');
      $pdf->displayTitle('<b><i>'.__('Entity').'</i></b>',
                         '<b><i>'.__('Number of affected computers').'</i></b>');

      $tot = 0;
      if (in_array(0,$_SESSION["glpiactiveentities"])) {
         $nb = Item_SoftwareLicense::countForLicense($ID, 0);
         if ($nb > 0) {
            $pdf->displayLine(__('Root entity'), $nb);
            $tot += $nb;
         }
      }
      $sql = ['SELECT'  => ['id', 'completename'],
              'FROM'    => 'glpi_entities',
              'WHERE'   => $dbu->getEntitiesRestrictCriteria('glpi_entities'),
              'ORDER'   => 'completename'];

      foreach ($DB->request($sql) as $entity => $data) {
         $nb = Item_SoftwareLicense::countForLicense($ID,$entity);
         if ($nb > 0) {
            $pdf->displayLine($data["completename"], $nb);
            $tot += $nb;
         }
      }

      if ($tot > 0) {
         $pdf->displayLine(__('Total'), $tot);
      } else {
         $pdf->setColumnsSize(100);
         $pdf->setColumnsAlign('center');
         $pdf->displayLine(__('No item to display'));
      }
      $pdf->displaySpace();
   }


   static function pdfForLicenseByComputer(PluginPdfSimplePDF $pdf, SoftwareLicense $license) {
      global $DB;

      $dbu = new DbUtils();

      $ID = $license->getField('id');

      $query = ['FROM'        => 'glpi_items_softwarelicenses', 'COUNT' => 'cpt',
                'INNER JOIN'  => ['glpi_computers'
                                  => ['FKEY' => ['glpi_items_softwarelicenses' => 'items_id',
                                                 'glpi_computers'                  => 'id',
                                                 ['AND' => ['glpi_items_softwarelicenses.itemtype' => 'Computer']]]]],
                'WHERE'       => ['softwarelicenses_id'       => $ID,
                                  'glpi_computers.is_deleted' => 0,
                                  'is_template'               => 0]
                                  + $dbu->getEntitiesRestrictCriteria('glpi_computers')];

      $number = 0;
      if ($result = $DB->request($query)) {
         $number  = count($result);
      }

      $pdf->setColumnsSize(100);
      $pdf->setColumnsAlign('center');
      $title = '<b>'.__('Affected computers').'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         $title = sprintf(__('%1$s: %2$s'), $title, $number);
         $pdf->displayTitle($title);

         $query = "SELECT `glpi_items_softwarelicenses`.*,
                          `glpi_computers`.`name` AS compname,
                          `glpi_computers`.`id` AS cID,
                          `glpi_computers`.`serial`,
                          `glpi_computers`.`otherserial`,
                          `glpi_users`.`name` AS username,
                          `glpi_softwarelicenses`.`name` AS license,
                          `glpi_softwarelicenses`.`id` AS vID,
                          `glpi_softwarelicenses`.`name` AS vername,
                          `glpi_entities`.`name` AS entity,
                          `glpi_locations`.`completename` AS location,
                          `glpi_states`.`name` AS state,
                          `glpi_groups`.`name` AS groupe,
                          `glpi_softwarelicenses`.`name` AS lname,
                          `glpi_softwarelicenses`.`id` AS lID
                   FROM `glpi_items_softwarelicenses`
                   INNER JOIN `glpi_softwarelicenses`
                        ON (`glpi_items_softwarelicenses`.`softwarelicenses_id`
                                = `glpi_softwarelicenses`.`id`)
                   INNER JOIN `glpi_computers`
                        ON (`glpi_items_softwarelicenses`.`items_id` = `glpi_computers`.`id`
                            AND `glpi_items_softwarelicenses`.`itemtype` = 'Computer')
                   LEFT JOIN `glpi_entities`
                        ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_locations`
                        ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                   LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                   LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                   LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                   WHERE (`glpi_softwarelicenses`.`id` = '".$ID."') " .
                         $dbu->getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                         AND `glpi_computers`.`is_deleted` = '0'
                         AND `glpi_computers`.`is_template` = '0'
                   ORDER BY `entity`, `compname`
                   LIMIT 0," . intval($_SESSION['glpilist_limit']);

         $result = $DB->request($query);

         $showEntity = ($license->isRecursive());
         if ($showEntity) {
            $pdf->setColumnsSize(12,12,12,12,18,10,12,12);
            $pdf->displayTitle('<b><i>'.__('Entity'), __('Name'), __('Serial number'),
                                        __('Inventory number'), __('Location'), __('Status'),
                                        __('Group'), __('User').
                               '</i></b>');
         } else {
            $pdf->setColumnsSize(14,14,14,18,14,13,13);
            $pdf->displayTitle('<b><i>'.__('Name'), __('Serial number'), __('Inventory number'),
                                        __('Location'), __('Status'), __('Group'), __('User').
                               '</i></b>');
         }
         foreach ($result as $data) {
            $compname = $data['compname'];
            if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
               $compname = sprintf(__('%1$s (%2$s)'), $compname, $data['cID']);
            }
            $entname = (empty($data['entity']) ? __('Root entity') : $data['entity']);

            if ($showEntity) {
               $pdf->displayLine($entname, $compname, $data['serial'],
                                 $data['otherserial'], $data['location'], $data['state'],
                                 $data['groupe'], $data['username']);
            } else {
               $pdf->displayLine($compname, $data['serial'], $data['otherserial'],
                                 $data['location'], $data['state'], $data['groupe'],
                                 $data['username']);
            }
         }
      }
      $pdf->displaySpace();
   }
}