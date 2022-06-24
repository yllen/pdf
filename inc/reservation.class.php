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


class PluginPdfReservation extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Reservation());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item) {
      global $DB;

      $ID   = $item->getField('id');
      $type = get_class($item);

      if (!Session::haveRight("reservation",  READ)) {
         return;
      }

      $user = new User();
      $ri   = new ReservationItem;
      $dbu  = new DbUtils();

      $pdf->setColumnsSize(100);
      if ($ri->getFromDBbyItem($type,$ID)) {
         $now = $_SESSION["glpi_currenttime"];
         $query = ['FROM'        => 'glpi_reservationitems',
                   'INNER JOIN'  => ['glpi_reservations'
                                    => ['FKEY' => ['glpi_reservations'     => 'reservationitems_id',
                                                   'glpi_reservationitems' => 'id']]],
                   'WHERE'       => ['end'      => ['>', $now],
                                     'items_id' => $ID],
                   'ORDER'       => 'begin'];

         $result = $DB->request($query);

         $pdf->setColumnsSize(100);

         if (!count($result)) {
            $pdf->displayTitle("<b>".__('No current and future reservations', 'pdf')."</b>");
         } else {
            $pdf->displayTitle("<b>".__('Current and future reservations')."</b>");
            $pdf->setColumnsSize(14,14,26,46);
            $pdf->displayTitle('<i>'.__('Start date'), __('End date'), __('By'), __('Comments').
                               '</i>');

            foreach ($result as $data) {
               if ($user->getFromDB($data["users_id"])) {
                  $name = $dbu->formatUserName($user->fields["id"], $user->fields["name"],
                                               $user->fields["realname"], $user->fields["firstname"]);
               } else {
                  $name = "(".$data["users_id"].")";
               }
               $pdf->displayLine(Html::convDateTime($data["begin"]),
                                 Html::convDateTime($data["end"]),
                                 $name, str_replace(["\r","\n"]," ",$data["comment"]));
            }
         }

         $query = ['FROM'        => 'glpi_reservationitems',
                   'INNER JOIN'  => ['glpi_reservations'
                                     => ['FKEY' => ['glpi_reservations'     => 'reservationitems_id',
                                                    'glpi_reservationitems' => 'id']]],
                   'WHERE'       => ['end'      => ['<=', $now],
                                     'items_id' => $ID],
                   'ORDER'       => 'begin DESC'];

         $result = $DB->request($query);

         $pdf->setColumnsSize(100);

         if (!count($result)) {
            $pdf->displayTitle("<b>".__('No past reservations', 'pdf')."</b>");
         } else {
            $pdf->displayTitle("<b>".__('Past reservations')."</b>");
            $pdf->setColumnsSize(14,14,26,46);
            $pdf->displayTitle('<i>'.__('Start date'), __('End date'), __('By'), __('Comments').
                               '</i>');

            foreach ($result as $data) {
               if ($user->getFromDB($data["users_id"])) {
                  $name = $dbu->formatUserName($user->fields["id"], $user->fields["name"],
                                         $user->fields["realname"], $user->fields["firstname"]);
               } else {
                  $name = "(".$data["users_id"].")";
               }
               $pdf->displayLine(Html::convDateTime($data["begin"]),
                                                    Html::convDateTime($data["end"]), $name,
                                                    str_replace(["\r","\n"]," ",$data["comment"]));
            }
         }
      } else {
         $pdf->displayTitle("<b>".__('Item not reservable', 'pdf')."</b>");
      }

      $pdf->displaySpace();
   }


   static function pdfForUser(PluginPdfSimplePDF $pdf, User $user) {
      global $DB;

      $ID   = $user->getField('id');
      if (!Session::haveRight("reservation", READ)) {
         return false;
      }

      $pdf->setColumnsSize(100);
      $now = $_SESSION["glpi_currenttime"];

      // Print reservation in progress
      $query = ['SELECT'     => ['begin', 'end', 'items_id', 'glpi_reservationitems.entities_id',
                                 'users_id', 'glpi_reservations.comment', 'reservationitems_id',
                                 'completename'],
                'FROM'       => 'glpi_reservations',
                'LEFT JOIN'  => ['glpi_reservationitems'
                                  => ['FKEY' => ['glpi_reservations'     => 'reservationitems_id',
                                                 'glpi_reservationitems' => 'id']],
                                 'glpi_entities'
                                  => ['FKEY' => ['glpi_reservationitems' => 'entities_id',
                                                 'glpi_entities'         => 'id']]],
                'WHERE'       => ['end'      => ['>', $now],
                                  'users_id' => $ID],
               'ORDER'       => 'begin'];

      $result = $DB->request($query);

      if (!count($result)) {
         $pdf->displayTitle("<b>".__('No current and future reservations', 'pdf')."</b>");
      } else {
         $pdf->displayTitle("<b>".__('Current and future reservations')."</b>");
         $pdf->setColumnsSize(10,10,10,20,15,35);
         $pdf->displayTitle('<i>'.__('Start date'), __('End date'), __('Item'), __('Entity'),
                                  __('By'), __('Comments').
                           '</i>');
      }
      $ri = new ReservationItem();

      foreach ($result as $data) {
         if ($ri->getFromDB($data["reservationitems_id"])) {
            if ($item = getItemForItemtype($ri->fields['itemtype'])) {
               if ($item->getFromDB($ri->fields['items_id'])) {
                  $name = $item->fields['name'];
               }
            }
         }
         $pdf->displayLine(Html::convDateTime($data["begin"]), Html::convDateTime($data["end"]),
                           $name, $data['completename'], getUserName($data["users_id"]),
                           str_replace(["\r","\n"]," ",$data["comment"]));
      }

      // Print old reservations
      $pdf->setColumnsSize(100);
      $query = ['SELECT'     => ['begin', 'end', 'items_id', 'glpi_reservationitems.entities_id',
                                 'users_id', 'glpi_reservations.comment', 'reservationitems_id',
                                 'completename'],
                'FROM'       => 'glpi_reservations',
                'LEFT JOIN'  => ['glpi_reservationitems'
                                  => ['FKEY' => ['glpi_reservations'     => 'reservationitems_id',
                                                 'glpi_reservationitems' => 'id']],
                                 'glpi_entities'
                                  => ['FKEY' => ['glpi_reservationitems' => 'entities_id',
                                                 'glpi_entities'         => 'id']]],
                'WHERE'       => ['end'      => ['<=', $now],
                                  'users_id' => $ID],
               'ORDER'       => 'begin DESC'];

      $result = $DB->request($query);

      if (!count($result)) {
         $pdf->displayTitle("<b>".__('No past reservations', 'pdf')."</b>");
      } else {
         $pdf->displayTitle("<b>".__('Past reservations')."</b>");
         $pdf->setColumnsSize(10,10,10,20,15,35);
         $pdf->displayTitle('<i>'.__('Start date'), __('End date'), __('Item'), __('Entity'),
                                  __('By'), __('Comments').
                            '</i>');
      }

      foreach ($result as $data) {
         if ($ri->getFromDB($data["reservationitems_id"])) {
            if ($item = getItemForItemtype($ri->fields['itemtype'])) {
               if ($item->getFromDB($ri->fields['items_id'])) {
                  $name = $item->fields['name'];
               }
            }
         }
         $pdf->displayLine(Html::convDateTime($data["begin"]), Html::convDateTime($data["end"]),
                           $name, $data['completename'], getUserName($data["users_id"]),
                           str_replace(["\r","\n"]," ",$data["comment"]));
      }
      $pdf->displaySpace();
   }

}