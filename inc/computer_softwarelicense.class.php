<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2013 by the pdf Development Team.

 https://forge.indepnet.net/projects/pdf
 -------------------------------------------------------------------------

 LICENSE

 This file is part of pdf.

 pdf is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 pdf is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with pdf. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/


class PluginPdfComputer_SoftwareLicense extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Computer_SoftwareLicense());
   }


   static function pdfForLicenseByEntity(PluginPdfSimplePDF $pdf, SoftwareLicense $license) {
      global $DB;

      $ID = $license->getField('id');

      $pdf->setColumnsSize(65,35);
      $pdf->setColumnsAlign('left', 'right');
      $pdf->displayTitle('<b><i>'.__('Entity').'</i></b>',
                         '<b><i>'.__('Number of affected computers').'</i></b>');

      $tot = 0;
      if (in_array(0,$_SESSION["glpiactiveentities"])) {
         $nb = Computer_SoftwareLicense::countForLicense($ID, 0);
         if ($nb > 0) {
            $pdf->displayLine(__('Root entity'), $nb);
            $tot += $nb;
         }
      }
      $sql = "SELECT `id`, `completename`
              FROM `glpi_entities` " .
              getEntitiesRestrictRequest('WHERE', 'glpi_entities') ."
              ORDER BY `completename`";

      foreach ($DB->request($sql) as $entity => $data) {
         $nb = Computer_SoftwareLicense::countForLicense($ID,$entity);
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
         $pdf->displayLine(__('No item found'));
      }
      $pdf->displaySpace();
   }


   static function pdfForLicenseByComputer(PluginPdfSimplePDF $pdf, SoftwareLicense $license) {
      global $DB;

      $ID = $license->getField('id');

      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_computers_softwarelicenses`
                       INNER JOIN `glpi_computers`
                           ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                       WHERE `glpi_computers_softwarelicenses`.`softwarelicenses_id` = '".$ID."'" .
                             getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                             AND `glpi_computers`.`is_deleted` = '0'
                             AND `glpi_computers`.`is_template` = '0'";

      $number = 0;
      if ($result =$DB->query($query_number)) {
         $number  = $DB->result($result,0,0);
      }

      $pdf->setColumnsSize(100);
      $pdf->setColumnsAlign('center');
      $title = '<b>'.__('Affected computers').'</b>';
      if ($number) {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s: %2$s'), $title,
                             $_SESSION['glpilist_limit'].' / '.$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $query = "SELECT `glpi_computers_softwarelicenses`.*,
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
                   FROM `glpi_computers_softwarelicenses`
                   INNER JOIN `glpi_softwarelicenses`
                        ON (`glpi_computers_softwarelicenses`.`softwarelicenses_id`
                                = `glpi_softwarelicenses`.`id`)
                   INNER JOIN `glpi_computers`
                        ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                   LEFT JOIN `glpi_entities`
                        ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_locations`
                        ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                   LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                   LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                   LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                   WHERE (`glpi_softwarelicenses`.`id` = '".$ID."') " .
                         getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                         AND `glpi_computers`.`is_deleted` = '0'
                         AND `glpi_computers`.`is_template` = '0'
                   ORDER BY `entity`, `compname`
                   LIMIT 0," . intval($_SESSION['glpilist_limit']);
            $result = $DB->query($query);

            $showEntity = ($license->isRecursive());
            if ($showEntity) {
               $pdf->setColumnsSize(12,12,12,12,16,12,12,12);
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
            while ($data = $DB->fetch_assoc($result)) {
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
         } else {
            $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item found')));
         }
         $pdf->displaySpace();
   }
}