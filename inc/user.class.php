<?php
/**
 * @version $Id: yllen $
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
 @copyright Copyright (c) 2009-2017 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/


class PluginPdfUser extends PluginPdfCommon {

   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new User());
   }


   static function pdfMain(PluginPdfSimplePDF $pdf, User $item) {
      global $DB;

      $ID = $item->getField('id');

      $pdf->setColumnsSize(50, 50);
      $pdf->displayTitle('<b>'.sprintf(__('%1$s %2$s'),__('ID'), $item->fields['id']).'</b>',
                         sprintf(__('%1$s: %2$s'), __('Last update'),
                                 Html::convDateTime($item->fields['date_mod'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Login').'</i></b>', $item->fields['name']),
            '<b><i>'.sprintf(__('Last login on %s').'</i></b>',
                             Html::convDateTime($item->fields['last_login'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Surname'), $item->fields['realname'].'</i></b>'),
            '<b><i>'.sprintf(__('%1$s - %2$s'),__('First name').'</i></b>',
                             $item->fields['firstname']));

      $end = '';
      if ($item->fields['end_date']) {
         $end = '<b><i> - '.sprintf(__('%1$s - %2$s'), __('Valid until').'</i></b>',
                                       Html::convDateTime($item->fields['end_date']));
      }
      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Active').'</i></b>', $item->fields['is_active']),
            '<b><i>'.sprintf(__('%1$s : %2$s'), __('Valid since').'</i></b>',
                             Html::convDateTime($item->fields['begin_date']).$end));

      foreach ($DB->request('glpi_useremails', ['users_id' => $item->getField('id')]) as $key => $email) {
         if ($email['is_default'] == 1) {
            $emails[]= $email['email'] ." (".__('Default email').")";
         } else {
            $emails[]= $email['email'];
         }
      }
      $pdf->setColumnsSize(100);
      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'),
                             _n('Email', 'Emails', Session::getPluralNumber()).'</i></b>',
                             implode(", ", $emails)));

      $pdf->setColumnsSize(50,50);
      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Phone').'</i></b>', $item->fields['phone']),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Phone 2').'</i></b>', $item->fields['phone2']));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Mobile phone').'</i></b>',
                             $item->fields['mobile']),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Category').'</i></b>',
                             Dropdown::getDropdownName('glpi_usercategories',
                                                       $item->fields['usercategories_id'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Administrative number').'</i></b>',
                             $item->fields['registration_number']),
            '<b><i>'.sprintf(__('%1$s: %2$s'), _x('person', 'Title').'</i></b>',
                             Dropdown::getDropdownName('glpi_usertitles',
                                                       $item->fields['usertitles_id'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Location').'</i></b>',
                             Dropdown::getDropdownName('glpi_locations',
                                                       $item->fields['locations_id'])),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Language').'</i></b>',
                  Dropdown::getLanguageName($item->fields['language'])));

      $pdf->displayLine(
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Default profile').'</i></b>',
                             Dropdown::getDropdownName('glpi_profiles',
                                                       $item->fields['profiles_id'])),
            '<b><i>'.sprintf(__('%1$s: %2$s'), __('Default entity').'</i></b>',
                             Dropdown::getDropdownName('glpi_entities',
                                                       $item->fields['entities_id'])));

      PluginPdfCommon::mainLine($pdf, $item, 'comment');

      $pdf->displaySpace();
   }


   // From Group::showLDAPForm()
   static function pdfLdapForm(PluginPdfSimplePDF $pdf, Group $item) {

      if (Session::haveRight("config", READ) && AuthLdap::useAuthLdap()) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle('<b>'.__('LDAP directory link').'</b>');

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
   static function pdfItems(PluginPdfSimplePDF $pdf, User $user, $tech) {
      global $CFG_GLPI, $DB;

      $dbu = new DbUtils();

      $ID = $user->getField('id');

      if ($tech) {
         $type_user   = $CFG_GLPI['linkuser_tech_types'];
         $type_group  = $CFG_GLPI['linkgroup_tech_types'];
         $field_user  = 'users_id_tech';
         $field_group = 'groups_id_tech';
         $title = __('Managed items');
      } else {
         $type_user   = $CFG_GLPI['linkuser_types'];
         $type_group  = $CFG_GLPI['linkgroup_types'];
         $field_user  = 'users_id';
         $field_group = 'groups_id';
         $title = __('Used items');
      }

      $group_where = "";
      $groups      = [];

      $result = $DB->request(['SELECT'    => ['glpi_groups_users.groups_id', 'name'],
                              'FROM'      => 'glpi_groups_users',
                              'LEFT JOIN' => ['glpi_groups'
                                                => ['FKEY' => ['glpi_groups'       => 'id',
                                                               'glpi_groups_users' => 'groups_id']]],
                              'WHERE'     => ['users_id' => $ID]]);
      $number = count($result);

      if ($number > 0) {
         $nb = 0;

         while ($datas = $result->next()) {
            $group_where[$field_group] = $datas["groups_id"];
            $groups[$datas["groups_id"]] = $datas["name"];
            $nb ++;
         }
         if ($nb > 1) {
            $wherenel = ['OR' => [$group_where]];
         }
      }

      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$title.'</b>');

         $pdf->setColumnsSize(15,15,15,15,15,15,10);
         $pdf->displayTitle(__('Type'),  __('Entity'), __('Name'), __('Serial number'),
               __('Inventory number'), __('Status'), '');

      foreach ($type_user as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);

            $query = "SELECT *
                      FROM `$itemtable`
                      WHERE `".$field_user."` = $ID";

            $where[$field_user] = $ID;
            if ($item->maybeTemplate()) {
               $where['is_template'] = 0;
            }
            if ($item->maybeDeleted()) {
               $where['is_deleted'] = 0;
            }

            $query = ['FROM'  => $itemtable,
                     'WHERE' => [$where]];

            $result    = $DB->request($query);
            $type_name = $item->getTypeName();

            if (count($result)) {
               while ($data = $result->next()) {
                  $name   = $data["name"];
                  if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                     $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                  }
                  $linktype = "";
                  if ($data[$field_user] == $ID) {
                     $linktype = User::getTypeName(1);
                  }
                  $pdf->displayLine($item->getTypeName(1),
                                    Dropdown::getDropdownName("glpi_entities", $data["entities_id"]),
                                    $name, $data["serial"], $data["otherserial"],
                                    Dropdown::getDropdownName("glpi_states", $data['states_id']),
                                    __('User'));
               }
            }
         }
      }
      $pdf->displaySpace();
   }


   function defineAllTabs($options=[]) {

      $onglets = parent::defineAllTabs($options);
      unset($onglets['Profile_User$1']);
      unset($onglets['Group_User$1']);
      unset($onglets['Config$1']);
 //     unset($onglets['User$1']);
 //     unset($onglets['User$2']);
      unset($onglets['Ticket$1']);
      unset($onglets['Item_Problem$1']);
      unset($onglets['Change_Item$1']);
      unset($onglets['Document_Item$1']);
      unset($onglets['Reservation$1']);
      unset($onglets['Synchronisation$1']);
      unset($onglets['Link$1']);
      unset($onglets['Certificate_Item$1']);
      unset($onglets['Auth$1']);

      return $onglets;
   }


   static function pdfChildren(PluginPdfSimplePDF $pdf, CommonTreeDropdown $item) {
      global $DB;

      $ID            = $item->getID();
      $fields        = $item->getAdditionalFields();
      $nb            = count($fields);
      $entity_assign = $item->isEntityAssign();

      $fk            = $item->getForeignKeyField();
      $crit          = [$fk     => $item->getID(),
                        'ORDER' => 'name'];

      if ($item->haveChildren()) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle(sprintf(__('Sons of %s'), '<b>'.$item->getNameID().'</b>'));

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
                                 Dropdown::getDropdownName("glpi_entities", $data["entities_id"]),
                                 $data['comment']);
            } else {
               $pdf->displayLine($data['name'], $data['comment']);
            }
         }
      } else {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle('<b>'.sprintf(__('No sons of %s', 'behaviors'), $item->getNameID().'</b>'));

      }

      $pdf->displaySpace();
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $tree = isset($_REQUEST['item']['_tree']);
      $user = isset($_REQUEST['item']['_user']);

      switch ($tab) {
         case 'User$1' :
            self::pdfItems($pdf, $item, false);
            break;

         case 'User$2' :
            self::pdfItems($pdf, $item, true);
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

         case 'Change_Item$1' :
            PluginPdfChange_Item::pdfForItem($pdf, $item, $tree);
            break;

         case 'Item_Problem$1' :
            PluginPdfItem_Problem::pdfForItem($pdf, $item, $tree);
               break;


         default :
            return false;
      }
      return true;
   }
}