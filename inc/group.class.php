<?php
/*
 * @version $Id: monitor.class.php 303 2011-11-08 11:38:25Z remi $
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

class PluginPdfGroup extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new Group());
   }



   static function pdfMain(PluginPdfSimplePDF $pdf, Group $item) {
      global $LANG;

      $ID = $item->getField('id');

      $pdf->setColumnsSize(50, 50);
      $pdf->displayTitle(
         '<b>'.$LANG['common'][2].' '.$item->fields['id'].'</b>',
         $LANG['common'][26].' : '.Html::convDateTime($item->fields['date_mod'])
      );

      $pdf->setColumnsSize(100);
      $pdf->displayLine(
         '<b><i>'.$LANG['common'][51].' :</i></b> '.$item->fields['completename']
      );
      $pdf->setColumnsSize(50, 50);
      $pdf->displayLine(
         '<b><i>'.$LANG['entity'][9].' :</i></b> '.
            Dropdown::getYesNo($item->fields['is_recursive']),
         '<b><i>'.$LANG['group'][1].' :</i></b> '.
            Dropdown::getYesNo($item->fields['is_notif'])
      );
      $pdf->displayLine(
         '<b><i>'.$LANG['group'][0].' - '.$LANG['job'][4].' :</i></b> '.
            Dropdown::getYesNo($item->fields['is_requester']),
         '<b><i>'.$LANG['group'][0].' - '.$LANG['job'][5].' :</i></b> '.
            Dropdown::getYesNo($item->fields['is_assign'])
      );
      $pdf->displayLine(
         '<b><i>'.$LANG['group'][2].' - '.$LANG['common'][96].' :</i></b> '.
            Dropdown::getYesNo($item->fields['is_itemgroup']),
         '<b><i>'.$LANG['group'][2].' - '.$LANG['Menu'][14].' :</i></b> '.
            Dropdown::getYesNo($item->fields['is_usergroup'])
      );
      $pdf->displayText('<b><i>'.$LANG['common'][25].' :</i></b>', $item->fields['comment']);

      $pdf->displaySpace();
   }


   // From Group::showLDAPForm()
   static function pdfLdapForm(PluginPdfSimplePDF $pdf, Group $item) {
      global $LANG;

      if (Session::haveRight("config","r") && AuthLdap::useAuthLdap()) {
         $pdf->setColumnsSize(100);
         $pdf->displayTitle($LANG['setup'][3]);

         $pdf->displayText('<b>'.$LANG['setup'][260].' : </b>', $item->getField('ldap_field'), 1);
         $pdf->displayText('<b>'.$LANG['setup'][601].' : </b>', $item->getField('ldap_value'), 1);
         $pdf->displayText('<b>'.$LANG['setup'][261].' : </b>', $item->getField('ldap_group_dn'), 1);

         $pdf->displaySpace();
      }
   }


   // From Group::showItems()
   static function pdfItems(PluginPdfSimplePDF $pdf, Group $group, $tech, $tree, $user) {
      global $LANG, $CFG_GLPI;

      if ($tech) {
         $types = $CFG_GLPI['linkgroup_tech_types'];
         $field = 'groups_id_tech';
         $title = $LANG['common'][112];
      } else {
         $types = $CFG_GLPI['linkgroup_types'];
         $field = 'groups_id';
         $title = $LANG['common'][111];
      }

      $datas  = array();
      $max = $group->getDataItems($types, $field, $tree, $user, 0, $datas);
      $nb = count($datas);

      if ($nb<$max) {
         $title .= " ($nb/$max)";
      } else {
         $title .= " ($nb)";
      }
      $pdf->setColumnsSize(100);
      $pdf->displayTitle($title);

      if ($nb) {
         if ($tree || $user) {
            $pdf->setColumnsSize(16, 20, 34, 30);
            $pdf->displayTitle(
               $LANG['common'][17], // Type
               $LANG['common'][16], // Name
               $LANG['entity'][0],  // Entity
               Group::getTypeName(1)." / ".User::getTypeName(1)
            );
         } else {
            $pdf->setColumnsSize(20, 25, 55);
            $pdf->displayTitle(
               $LANG['common'][17], // Type
               $LANG['common'][16], // Name
               $LANG['entity'][0]   // Entity
            );
         }
      } else {
         $pdf->displayLine($LANG['search'][15]);
      }

      $tmpgrp = new Group();
      $tmpusr = new User();

      foreach ($datas as $data) {
         if (!($item = getItemForItemtype($data['itemtype']))) {
            continue;
         }
         $item->getFromDB($data['items_id']);

         $col4='';
         if ($tree || $user) {
            if ($grp = $item->getField($field)) {
               if ($tmpgrp->getFromDB($grp)) {
                  $col4 = $tmpgrp->getNameID();
               }
               // $col4 = Html::clean(Dropdown::getDropdownName('glpi_groups', ));

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
      global $LANG;

      $onglets = parent::defineAllTabs($options);

      unset($onglets['NotificationTarget$1']);  // TODO Notifications

      $onglets['_tree'] = $LANG['group'][3];
      $onglets['_user'] = $LANG['plugin_pdf']['group'][1];

      return $onglets;
   }


   static function pdfChildren(PluginPdfSimplePDF $pdf, CommonTreeDropdown $item) {
      global $LANG, $DB;

      $ID = $item->getID();
      $fields        = $item->getAdditionalFields();
      $nb            = count($fields);
      $entity_assign = $item->isEntityAssign();

      $fk   = $item->getForeignKeyField();
      $crit = array($fk     => $item->getID(),
                    'ORDER' => 'name');

      $pdf->setColumnsSize(100);
      $pdf->displayTitle($LANG['setup'][76].' <b>'.$item->getNameID().'</b>');

      if ($item->haveChildren()) {
         if ($entity_assign) {
            if ($fk == 'entities_id') {
               $crit['id']  = $_SESSION['glpiactiveentities'];
               $crit['id'] += $_SESSION['glpiparententities'];
            } else {
               $crit['entities_id'] = $_SESSION['glpiactiveentities'];
            }

            $pdf->setColumnsSize(30, 30, 40);
            $pdf->displayTitle(
               $LANG['common'][16],       // Name
               $LANG['entity'][0],        // Entity
               $LANG['common'][25]        // Comment
            );
         } else {
            $pdf->setColumnsSize(45, 55);
            $pdf->displayTitle(
               $LANG['common'][16],       // Name
               $LANG['common'][25]        // Comment
            );
         }

         foreach ($DB->request($item->getTable(), $crit) as $data) {
            if ($entity_assign) {
               $pdf->displayLine(
                  $data['name'],
                  Html::clean(Dropdown::getDropdownName("glpi_entities", $data["entities_id"])),
                  $data['comment']
               );
            } else {
               $pdf->displayLine(
                  $data['name'],
                  $data['comment']
               );
            }
         }
      } else {
         $pdf->displayLine($LANG['search'][15]);
      }

      $pdf->displaySpace();
   }

   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $tree = isset($_REQUEST['item']['_tree']);
      $user = isset($_REQUEST['item']['_user']);

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

         case 'Group$1' :
            self::pdfItems($pdf, $item, false, $tree, $user);
            break;

         case 'Group$2' :
            self::pdfItems($pdf, $item, true, $tree, $user);
            break;

         case 'Group$3' :
            self::pdfLdapForm($pdf, $item);
            break;

         case 'Group$4' :
            self::pdfChildren($pdf, $item);
            break;

         case 'User$1' :
            PluginPdfGroup_User::pdfForGroup($pdf, $item, $tree);
            break;

         case 'Ticket$1' :
            PluginPdfTicket::pdfForItem($pdf, $item, $tree);
            break;

         // Igone tabs which are export options
         case '_tree' :
         case '_user' :
            break;

         default :
            return false;
      }
      return true;
   }
}