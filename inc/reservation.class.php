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


class PluginPdfReservation extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Reservation());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      $ID   = $item->getField('id');
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
         $pdf->displayTitle("<b>".__('Current and future reservations')."</b>");

         if (!$DB->numrows($result)) {
            $pdf->displayLine("<b>".__('No reservation')."</b>");
         } else {
            $pdf->setColumnsSize(14,14,26,46);
            $pdf->displayTitle('<i>'.__('Start date'), __('End date'), __('By'), __('Comments').
                               '</i>');

            while ($data = $DB->fetch_assoc($result)) {
               if ($user->getFromDB($data["users_id"])) {
                  $name = formatUserName($user->fields["id"], $user->fields["name"],
                                         $user->fields["realname"], $user->fields["firstname"]);
               } else {
                  $name = "(".$data["users_id"].")";
               }
               $pdf->displayLine(Html::convDateTime($data["begin"]),
                                 Html::convDateTime($data["end"]),
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
         $pdf->displayTitle("<b>".__('Past reservations')."</b>");

         if (!$DB->numrows($result)) {
            $pdf->displayLine("<b>".__('No reservation')."</b>");
         } else {
            $pdf->setColumnsSize(14,14,26,46);
            $pdf->displayTitle('<i>'.__('Start date'), __('End date'), __('By'), __('Comments').
                               '</i>');

            while ($data = $DB->fetch_assoc($result)) {
               if ($user->getFromDB($data["users_id"])) {
                  $name = formatUserName($user->fields["id"], $user->fields["name"],
                                         $user->fields["realname"], $user->fields["firstname"]);
               } else {
                  $name = "(".$data["users_id"].")";
               }
               $pdf->displayLine(Html::convDateTime($data["begin"]),
                                                    Html::convDateTime($data["end"]), $name,
                                                    str_replace(array("\r","\n")," ",$data["comment"]));
            }
         }

      }
      $pdf->displaySpace();
   }
}