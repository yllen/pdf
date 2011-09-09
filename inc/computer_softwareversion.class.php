<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
*/

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfComputer_SoftwareVersion extends PluginPdfCommon {

   function __construct(Computer_SoftwareVersion $obj=NULL) {

      $this->obj = ($obj ? $obj : new Computer_SoftwareVersion());
   }


   static function pdfForComputer(PluginPdfSimplePDF $pdf, Computer $comp) {
      global $DB,$LANG;

      $ID = $comp->getField('id');

      // From Computer_SoftwareVersion::showForComputer();
      $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_computers_softwareversions`.`id`,
                       `glpi_states`.`name` AS state,
                       `glpi_softwareversions`.`id` AS verid,
                       `glpi_softwareversions`.`softwares_id`,
                       `glpi_softwareversions`.`name` AS version
                FROM `glpi_computers_softwareversions`
                LEFT JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`)
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                WHERE `glpi_computers_softwareversions`.`computers_id` = '$ID'
                ORDER BY `softwarecategories_id`, `softname`, `version`";

      $output = array();

      $software_category      = new SoftwareCategory();
      $software_version       = new SoftwareVersion();

      foreach ($DB->request($query) as $softwareversion) {
         $output[] = $softwareversion;
      }

      $installed = array();
      if (count($output)) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle('<b>'.$LANG["software"][17].'</b>');

         $cat = -1;
         foreach ($output as $soft) {
            if ($soft["softwarecategories_id"] != $cat) {
               $cat = $soft["softwarecategories_id"];
               if ($cat && $software_category->getFromDB($cat)) {
                  $catname = $software_category->getName();
               } else {
                  $catname = $LANG["softwarecategories"][2];
               }

               $pdf->setColumnsSize(100);
               $pdf->displayTitle('<b>'.$catname.'</b>');

               $pdf->setColumnsSize(50,13,13,24);
               $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                                  '<b>'.$LANG['state'][0].'</b>',
                                  '<b>'.$LANG['rulesengine'][78].'</b>',
                                  '<b>'.$LANG['install'][92].'</b>');
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
                  $lic .= ' ('.$licdata['type'].')';
               }
            }

            $pdf->displayLine($soft['softname'], $soft['state'], $soft['version'], $lic);
         } // Each version

      } else {
         $pdf->displayTitle('<b>'.$LANG['plugin_pdf']['software'][1].'</b>');
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
         $pdf->displayTitle('<b>'.$LANG['software'][3].'</b>');

         $pdf->setColumnsSize(50,13,13,24);
         $pdf->displayTitle('<b>'.$LANG['common'][16].'</b>',
                            '<b>'.$LANG['state'][0].'</b>',
                            '<b>'.$LANG['rulesengine'][78].'</b>',
                            '<b>'.$LANG['install'][92].'</b>');

         foreach ($req as $data) {
            $lic .= '<b>'.$data['name'].'</b> '.$data['serial'];
            if (!empty($data['softwarelicensetypes_id'])) {
               $lic .= ' ('.Html::clean(Dropdown::getDropdownName('glpi_softwarelicensetypes',
                                                                 $data['softwarelicensetypes_id'])).')';
            }
            $pdf->displayLine($data['softname'], $data['state'], $data['version'], $lic);
         }
      }

      $pdf->displaySpace();
   }
}