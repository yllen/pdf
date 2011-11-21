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


   function __construct(Group $obj=NULL) {

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


   function defineAllTabs($options=array()) {
      global $LANG;

      $onglets = parent::defineAllTabs($options);

      unset($onglets['Group####4']);   // TODO Groupes
      unset($onglets['Group####1']);   // TODO Matériels utilisés
      unset($onglets['Group####2']);   // TODO Matériels gérés
      unset($onglets['Group####3']);   // TODO iaison annuaire LDAP
      unset($onglets['NotificationTarget####1']);  // TODO Notifications
      unset($onglets['Ticket####1']);  // TODO  Tickets créés

      $onglets['_tree'] = $LANG['entity'][7];

      return $onglets;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      $tree = isset($_REQUEST['item']['_tree']);

      switch ($tab) {
         case '_main_' :
            self::pdfMain($pdf, $item);
            break;

         case 'User####1' :
            PluginPdfGroup_User::pdfForGroup($pdf, $item, $tree);
            break;

         default :
            return false;
      }
      return true;
   }
}