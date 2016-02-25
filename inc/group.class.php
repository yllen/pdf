<?php
/**
 * @version $Id: setup.php 378 2014-06-08 15:12:45Z yllen $
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
 @copyright Copyright (c) 2009-2016 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfGroup extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Group());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, Group $item) {

      $ID = $item->getField('id');

      $pdf->setColumnsSize(50, 50);
      $pdf->displayTitle('<b>'.sprintf(__('%1$s %2$s'),__('ID'), $item->fields['id']).'</b>',
                         sprintf(__('%1$s: %2$s'), __('Last update'),
                                 Html::convDateTime($item->fields['date_mod'])));

      $pdf->setColumnsSize(100);
      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Complete name').'</i></b>',
                                         $item->fields['completename']));

      $pdf->setColumnsSize(34,22,22,22);
      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Visible in a ticket'), ''.'</i></b>'),
                        '<b><i>'.sprintf(__('%1$s - %2$s'),__('Requester').'</i></b>',
                                         Dropdown::getYesNo($item->fields['is_requester'])),
                        '<b><i>'.sprintf(__('%1$s - %2$s'), __('Assigned to').'</i></b>',
                                         Dropdown::getYesNo($item->fields['is_assign'])),
                        '<b><i>'.sprintf(__('%1$s: %2$s'), __('Can be notified').'</i></b>',
                                         Dropdown::getYesNo($item->fields['is_notify'])));

      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Visible in a project'), ''),
                        '<b><i>'.sprintf(__('%1$s - %2$s'), __('Can be manager').'</i></b>',
                                         Dropdown::getYesNo($item->fields['is_manager'])));

      $pdf->displayLine('<b><i>'.sprintf(__('%1$s: %2$s'), __('Can contain'), ''),
                        '<b><i>'.sprintf(__('%1$s - %2$s'), _n('Item', 'Items', 2).'</i></b>',
                                         Dropdown::getYesNo($item->fields['is_itemgroup'])),
                        '<b><i>'.sprintf(__('%1$s: %2$s'), _n('User', 'Users', 2).'</i></b>',
                                         Dropdown::getYesNo($item->fields['is_usergroup'])));

      PluginPdfCommon::mainLine($pdf, $item, 'comment');

      $pdf->displaySpace();
   }


   // From Group::showLDAPForm()
   static function pdfLdapForm(PluginPdfSimplePDF $pdf, Group $item) {

      if (Session::haveRight("config", READ) && AuthLdap::useAuthLdap()) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle(__('LDAP directory link'));

         $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'),
                                         __('User attribute containing its groups').'</b>', ''),
                                         $item->getField('ldap_field'));
         $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), __('Attribute value').'</b>', ''),
                                         $item->getField('ldap_value'));
         $pdf->displayText('<b>'.sprintf(__('%1$s: %2$s'), __('Group DN').'</b>', ''),
                                         $item->getField('ldap_group_dn'));

         $pdf->displaySpace();
      }
   }


   // From Group::showItems()
   static function pdfItems(PluginPdfSimplePDF $pdf, Group $group, $tech, $tree, $user) {
      global $CFG_GLPI;

      if ($tech) {
         $types = $CFG_GLPI['linkgroup_tech_types'];
         $field = 'groups_id_tech';
         $title = __('Managed items');
      } else {
         $types = $CFG_GLPI['linkgroup_types'];
         $field = 'groups_id';
         $title = __('Used items');
      }

      $datas  = array();
      $max = $group->getDataItems($types, $field, $tree, $user, 0, $datas);
      $nb = count($datas);

      if ($nb < $max) {
         $title = sprintf(__('%1$s (%2$s)'), $title, $nb."/".$max);
      } else {
         $title = sprintf(__('%1$s (%2$s)'), $title, $nb);
      }
      $pdf->setColumnsSize(100);
      $pdf->displayTitle($title);

      if ($nb) {
         if ($tree || $user) {
            $pdf->setColumnsSize(16, 20, 34, 30);
            $pdf->displayTitle(__('Type'), __('Name'), __('Entity'),
                               Group::getTypeName(1)." / ".User::getTypeName(1));
         } else {
            $pdf->setColumnsSize(20, 25, 55);
            $pdf->displayTitle(__('Type'), __('Name'), __('Entity'));
         }
      } else {
         $pdf->displayLine(__('No item found'));
      }

      $tmpgrp = new Group();
      $tmpusr = new User();

      foreach ($datas as $data) {
         if (!($item = getItemForItemtype($data['itemtype']))) {
            continue;
         }
         $item->getFromDB($data['items_id']);

         $col4 = '';
         if ($tree || $user) {
            if ($grp = $item->getField($field)) {
               if ($tmpgrp->getFromDB($grp)) {
                  $col4 = $tmpgrp->getNameID();
               }

            } else if ($usr = $item->getField(str_replace('groups', 'users', $field))) {
               $col4 = Html::clean(getUserName($usr));
            }

         }
         $pdf->displayLine(
            $item->getTypeName(1),
            $item->getName(),
            Html::clean(Dropdown::getDropdownName("glpi_entities", $item->getEntityID())),
            $col4
         );
      }
      $pdf->displaySpace();
   }


   function defineAllTabs($options=array()) {

      $onglets = parent::defineAllTabs($options);

      unset($onglets['NotificationTarget$1']);  // TODO Notifications
      unset($onglets['Item_Problem$1']); // TODO add method to print linked Problems
      unset($onglets['Change_Item$1']); // TODO add method to print linked Changes

      return $onglets;
   }


   static function pdfChildren(PluginPdfSimplePDF $pdf, CommonTreeDropdown $item) {
      global $DB;

      $ID            = $item->getID();
      $fields        = $item->getAdditionalFields();
      $nb            = count($fields);
      $entity_assign = $item->isEntityAssign();

      $fk            = $item->getForeignKeyField();
      $crit          = array($fk     => $item->getID(),
                             'ORDER' => 'name');

      $pdf->setColumnsSize(100);
      $pdf->displayTitle(sprintf(__('Sons of %s'), '<b>'.$item->getNameID().'</b>'));

      if ($item->haveChildren()) {
         if ($entity_assign) {
            if ($fk == 'entities_id') {
               $crit['id']  = $_SESSION['glpiactiveentities'];
               $crit['id'] += $_SESSION['glpiparententities'];
            } else {
               $crit['entities_id'] = $_SESSION['glpiactiveentities'];
            }

            $pdf->setColumnsSize(30, 30, 40);
            $pdf->displayTitle(__('Name'), __('Entity'), __('Comments'));
         } else {
            $pdf->setColumnsSize(45, 55);
            $pdf->displayTitle(__('Name'), __('Comments'));
         }

         foreach ($DB->request($item->getTable(), $crit) as $data) {
            if ($entity_assign) {
               $pdf->displayLine($data['name'],
                                 Html::clean(Dropdown::getDropdownName("glpi_entities",
                                                                       $data["entities_id"])),
                                 $data['comment']);
            } else {
               $pdf->displayLine($data['name'], $data['comment']);
            }
         }
      } else {
         $pdf->displayLine(__('No item found'));
      }

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $tree = isset($_REQUEST['item']['_tree']);
      $user = isset($_REQUEST['item']['_user']);

      switch ($tab) {
         case 'Group$1' :
            self::pdfItems($pdf, $item, false, $tree, $user);
            break;

         case 'Group$3' :
            self::pdfLdapForm($pdf, $item);
            break;

         case 'Group$4' :
            self::pdfChildren($pdf, $item);
            break;

         case 'Group_User$1' :
            PluginPdfGroup_User::pdfForGroup($pdf, $item, $tree);
            break;

         case 'Ticket$1' :
            PluginPdfTicket::pdfForItem($pdf, $item, $tree);
            break;

         default :
            return false;
      }
      return true;
   }
}