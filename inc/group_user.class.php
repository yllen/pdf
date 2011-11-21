<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

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

// Original Author of file: Remi Collet
// ----------------------------------------------------------------------

class PluginPdfGroup_User extends PluginPdfCommon {

   function __construct(Group_User $obj=NULL) {

      $this->obj = ($obj ? $obj : new Group_User());
   }


   static function pdfForGroup(PluginPdfSimplePDF $pdf, Group $group, $tree) {
      global $DB,$CFG_GLPI, $LANG;

      $used        = array();
      $ids         = array();

      // Retrieve member list
      $entityrestrict = Group_User::getDataForGroup($group, $used, $ids, '', $tree);

      $title = "<b>".$LANG['Menu'][14]."</b> (D=".$LANG['profiles'][29].")";
      $number = count($used);
      if ($number > $_SESSION['glpilist_limit']) {
         $title .= " $number/".$_SESSION['glpilist_limit'];
      }
      $pdf->setColumnsSize(100);
      $pdf->displayTitle($title);

      if ($number) {
         $user  = new User();
         $group = new Group();

         if ($tree) {
            $pdf->setColumnsSize(35,45,10,10);
            $pdf->displayTitle(User::getTypeName(1), Group::getTypeName(1), $LANG['common'][64], $LANG['common'][123]);
         } else {
            $pdf->setColumnsSize(60,20,20);
            $pdf->displayTitle(User::getTypeName(1), $LANG['common'][64], $LANG['common'][123]);
         }

         for ($i=0 ; $i<$number && $i<$_SESSION['glpilist_limit'] ; $i++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);

            $name = $user->getName();
            if ($data["is_dynamic"]) {
               $name .= " <b>(D)</b>";
            }

            if ($tree) {
               $group->getFromDB($data["groups_id"]);
               $pdf->displayLine(
                  $name,
                  $group->getName(),
                  Dropdown::getYesNo($data['is_manager']),
                  Dropdown::getYesNo($data['is_userdelegate'])
               );
            } else {
               $pdf->displayLine(
                  $name,
                  Dropdown::getYesNo($data['is_manager']),
                  Dropdown::getYesNo($data['is_userdelegate'])
               );
            }
         }
      } else {
         $pdf->setColumnsAlign('center');
         $pdf->displayLine($LANG['search'][15]);
      }
      $pdf->displaySpace();
  }
}