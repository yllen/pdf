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


class PluginPdfNetworkPort extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new NetworkPort());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $ID       = $item->getField('id');
      $type     = get_class($item);

      $query = "SELECT `glpi_networkports`.`id`
                FROM `glpi_networkports`
                WHERE `items_id` = '".$ID."'
                      AND `itemtype` = '".$type."'
                ORDER BY `name`, `logical_number`";
      $result = $DB->query($query);
      $data = $DB->fetch_assoc($result);
      $pdf->setColumnsSize(100);
      if ($result = $DB->query($query)) {
         $nb_connect = $DB->numrows($result);
         if (!$nb_connect) {
            $pdf->displayTitle('<b>0 '.__('No network port found').'</b>');
         } else {
            $pdf->displayTitle('<b>'.sprintf(__('%1$s: %2$d'),
                                             _n('Network port', 'Network ports',$nb_connect),
                                             $nb_connect."</b>"));

            while ($devid = $DB->fetch_row($result)) {
               $netport = new NetworkPort;
               $netport->getfromDB(current($devid));
               $instantiation_type = $netport->fields["instantiation_type"];
               $instname = call_user_func(array($instantiation_type, 'getTypeName'));
               $pdf->displayTitle('<b>'.$instname.'</b>');

               $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), '#</b>',
                                               $netport->fields["logical_number"]));

               $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('Name').'</b>',
                                               $netport->fields["name"]));

               $contact  = new NetworkPort;
               $netport2 = new NetworkPort;

               $add = __('Not connected.');
               if ($cid = $contact->getContact($netport->fields["id"])) {
                  if ($netport2->getfromDB($cid)
                      && ($device2 = getItemForItemtype($netport2->fields["itemtype"]))) {
                     if ($device2->getFromDB($netport2->fields["items_id"])) {
                        $add = $netport2->getName().' '.__('on').' '.
                               $device2->getName().' ('.$device2->getTypeName().')';
                     }
                  }
               }
               $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('Connected to').'</b>',
                                                   $add));
              $sql = "SELECT `speed`, `type`
                      FROM `glpi_networkportethernets`
                      WHERE `networkports_id` = '".$netport->fields['id']."'";
              $res = $DB->query($sql);
              $dateth = $DB->fetch_assoc($res);

              $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('Ethernet port speed').'</b>',
                                              NetworkPortEthernet::getPortSpeed($dateth['speed'])));

              $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('Ethernet port type').'</b>',
                                              NetworkPortEthernet::getPortTypeName($dateth['type'])));

//TODO revoir problème avec le retour des fonctions ci-dessus + reste à faire
            } // each port

         } // Found
      } // Query
      $pdf->displaySpace();
   }
}