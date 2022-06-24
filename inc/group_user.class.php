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


class PluginPdfGroup_User extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Group_User());
   }


   static function pdfForGroup(PluginPdfSimplePDF $pdf, Group $group, $tree) {

      $dbu  = new DbUtils();
      $used = [];
      $ids  = [];

      // Retrieve member list
      $entityrestrict = Group_User::getDataForGroup($group, $used, $ids, '', $tree);

      $number = count($used);

      $pdf->setColumnsSize(100);
      $title = '<b>'._n('User', 'Users', 2).'</b>';

      if (!$number) {
         $pdf->displayTitle(sprintf(__('%1$s: %2$s'), $title, __('No item to display')));
      } else {
         if ($number > $_SESSION['glpilist_limit']) {
            $title = sprintf(__('%1$s (%2$s)'), $title, $_SESSION['glpilist_limit']."/".$number);
         } else {
            $title = sprintf(__('%1$s: %2$s'), $title, $number);
         }
         $pdf->displayTitle($title);

         $user  = new User();
         $group = new Group();

         if ($tree) {
            $pdf->setColumnsSize(35,25,10,10,10,10);
            $pdf->displayTitle(User::getTypeName(1), Group::getTypeName(1), __('Dynamic'),
                               __('Manager'), __('Delegatee'), __('Active'));
         } else {
            $pdf->setColumnsSize(40,15,15,15,15);
            $pdf->displayTitle(User::getTypeName(1), __('Dynamic'),__('Manager'), __('Delegatee'),
                                __('Active'));
         }

         $user = new User();
         for ($i=0 ; $i<$number && $i<$_SESSION['glpilist_limit'] ; $i++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);
            $name = Toolbox::stripTags($dbu->getUserName($data["id"]));

            if ($tree) {
               $group->getFromDB($data["groups_id"]);
               $pdf->displayLine($name, $group->getName(), Dropdown::getYesNo($data['is_dynamic']),
                                 Dropdown::getYesNo($data['is_manager']),
                                 Dropdown::getYesNo($data['is_userdelegate']),
                                 Dropdown::getYesNo($user->fields['is_active']));
            } else {
               $pdf->displayLine($name, Dropdown::getYesNo($data['is_dynamic']),
                                 Dropdown::getYesNo($data['is_manager']),
                                 Dropdown::getYesNo($data['is_userdelegate']),
                                 Dropdown::getYesNo($user->fields['is_active']));
            }
         }
      }
      $pdf->displaySpace();
  }
}