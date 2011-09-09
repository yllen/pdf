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

class PluginPdfReservation extends PluginPdfCommon {

   function __construct(Reservation $obj=NULL) {

      $this->obj = ($obj ? $obj : new Reservation());
   }

   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB,$LANG,$CFG_GLPI;

      $ID = $item->getField('id');
      $type = get_class($item);

      if (!Session::haveRight("reservation_central","r")) {
         return;
      }

      $user = new User();
      $ri = new ReservationItem;
      $pdf->setColumnsSize(100);
      if ($ri->getFromDBbyItem($type,$ID)) {
         $now = $_SESSION["glpi_currenttime"];
         $query = "SELECT *
                   FROM `glpi_reservationitems`
                   INNER JOIN `glpi_reservations`
                        ON (`glpi_reservations`.`reservationitems_id` = `glpi_reservationitems`.`id`)
                   WHERE `end` > '".$now."'
                         AND `glpi_reservationitems`.`items_id` = '$ID'
                   ORDER BY `begin`";

         $result = $DB->query($query);

         $pdf->setColumnsSize(100);
         $pdf->displayTitle("<b>".$LANG["reservation"][35]."</b>");

         if (!$DB->numrows($result)) {
            $pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
         } else {
            $pdf->setColumnsSize(14,14,26,46);
            $pdf->displayTitle('<i>'.$LANG["search"][8].'</i>',
                               '<i>'.$LANG["search"][9].'</i>',
                               '<i>'.$LANG["reservation"][31].'</i>',
                               '<i>'.$LANG["common"][25].'</i>');

            while ($data = $DB->fetch_assoc($result)) {
               if ($user->getFromDB($data["users_id"])) {
                  $name = formatUserName($user->fields["id"], $user->fields["name"],
                                         $user->fields["realname"], $user->fields["firstname"]);
               } else {
                  $name = "(".$data["users_id"].")";
               }
               $pdf->displayLine(Html::convDateTime($data["begin"]), Html::convDateTime($data["end"]),
                                 $name, str_replace(array("\r","\n")," ",$data["comment"]));
            }
         }

         $query = "SELECT *
                   FROM `glpi_reservationitems`
                   INNER JOIN `glpi_reservations`
                        ON (`glpi_reservations`.`reservationitems_id` = `glpi_reservationitems`.`id`)
                   WHERE `end` <= '".$now."'
                         AND `glpi_reservationitems`.`items_id` = '$ID'
                   ORDER BY `begin`
                   DESC";

         $result = $DB->query($query);

         $pdf->setColumnsSize(100);
         $pdf->displayTitle("<b>".$LANG["reservation"][36]."</b>");

         if (!$DB->numrows($result)) {
            $pdf->displayLine("<b>".$LANG["reservation"][37]."</b>");
         } else {
            $pdf->setColumnsSize(14,14,26,46);
            $pdf->displayTitle('<i>'.$LANG["search"][8].'</i>',
                               '<i>'.$LANG["search"][9].'</i>',
                               '<i>'.$LANG["reservation"][31].'</i>',
                               '<i>'.$LANG["common"][25].'</i>');

            while ($data = $DB->fetch_assoc($result)) {
               if ($user->getFromDB($data["users_id"])) {
                  $name = formatUserName($user->fields["id"], $user->fields["name"],
                                         $user->fields["realname"], $user->fields["firstname"]);
               } else {
                  $name = "(".$data["users_id"].")";
               }
               $pdf->displayLine(Html::convDateTime($data["begin"]), Html::convDateTime($data["end"]),$name,
                                              str_replace(array("\r","\n")," ",$data["comment"]));
            }
         }

      } else { // Not isReservable
         //$pdf->displayTitle("<b>".$LANG["reservation"][37]."</b>");
      }
      $pdf->displaySpace();
   }
}