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
use Glpi\Socket;

class PluginPdfNetworkPort extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new NetworkPort());
   }


   static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item){
      global $DB;

      $dbu  = new DbUtils();
      $ID       = $item->getField('id');
      $type     = get_class($item);

      $pdf->setColumnsSize(100);
      if ($result = $DB->request('glpi_networkports',
                                 ['SELECT' => ['id', 'name', 'logical_number'],
                                  'WHERE'  => ['items_id' => $ID,
                                               'itemtype' => $type],
                                  'ORDER'  => ['name', 'logical_number']])) {
         $nb_connect = count($result);

         $title = '<b>'._n('Network port', 'Network ports',$nb_connect).'</b>';
         if (!$nb_connect) {
            $pdf->displayTitle('<b>'.__('No network port found').'</b>');
         } else {
            if ($nb_connect > $_SESSION['glpilist_limit']) {
               $title = sprintf(__('%1$s: %2$s'), $title, $_SESSION['glpilist_limit'].' / '.$nb_connect);
            } else {
               $title = sprintf(__('%1$s: %2$d'), $title, $nb_connect);
            }
            $pdf->displayTitle($title);

            foreach ($result as $devid) {
               $netport = new NetworkPort;
               $netport->getfromDB(current($devid));
               $instantiation_type = $netport->fields["instantiation_type"];
               $instname = call_user_func([$instantiation_type, 'getTypeName']);
               $pdf->displayTitle('<b>'.$instname.'</b>');

               $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), '#</b>',
                                               $netport->fields["logical_number"]));

               $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('Name').'</b>',
                                               $netport->fields["name"]));

               $contact  = new NetworkPort;
               $netport2 = new NetworkPort;

               $add = __('Not connected.');
               if ($cid = $contact->getContact($netport->fields["id"])) {
                  if ($netport2->getFromDB($cid)
                      && ($device2 = $dbu->getItemForItemtype($netport2->fields["itemtype"]))) {
                     if ($device2->getFromDB($netport2->fields["items_id"])) {
                        $add = $netport2->getName().' '.__('on').' '.
                               $device2->getName().' ('.$device2->getTypeName().')';
                     }
                  }
               }

               if ($instantiation_type == 'NetworkPortEthernet') {
                  $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('Connected to').'</b>',
                                                   $add));
                  $netportethernet = new NetworkPortEthernet();
                  $speed = $type = '';

                  if ($netportethernet->getFromDBByCrit(['networkports_id' => $netport->fields['id']])) {
                     $speed = NetworkPortEthernet::getPortSpeed($netportethernet->fields['speed']);
                     $type  = NetworkPortEthernet::getPortTypeName($netportethernet->fields['type']);
                  }
                  $pdf->displayLine(
                     '<b>'.sprintf(__('%1$s: %2$s'), __('Ethernet port speed').'</b>', $speed));
                  $pdf->displayLine(
                     '<b>'.sprintf(__('%1$s: %2$s'), __('Ethernet port type').'</b>', $type));

                  $netpoint = new Socket();
                  $outlet = '';
                  if (isset($netportethernet->fields['networkports_id'])
                      && $netpoint->getFromDBByCrit(['networkports_id' => $netportethernet->fields['networkports_id']])) {
                     $outlet = $netpoint->fields['name'];
                  }
                  $pdf->displayLine(
                     '<b>'.sprintf(__('%1$s: %2$s'), __('Network outlet').'</b>',
                                   $outlet));

               }
               $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('MAC').'</b>',
                                              $netport->fields["mac"]));

               $sqlip = ['LEFT JOIN' => ['glpi_networknames'
                                           => ['FKEY' => ['glpi_ipaddresses'  =>'items_id',
                                                          'glpi_networknames' => 'id'],
                                                         ['glpi_ipaddresses.entities_id'
                                                               => $_SESSION["glpiactive_entity"]]]],
                         'WHERE'     => ['glpi_networknames.items_id' => $netport->fields["id"]]];

               $ipname = '';
               $ip     = new IPAddress();
               if ($ip->getFromDBByRequest($sqlip)) {
                  $ipname   = $ip->fields['name'];

                  $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('ip').'</b>', $ipname));

                  $sql = ['SELECT'    => 'glpi_ipaddresses_ipnetworks.ipnetworks_id',
                          'FROM'      => 'glpi_ipaddresses_ipnetworks',
                          'LEFT JOIN' => ['glpi_ipnetworks'
                                          => ['FKEY' => ['glpi_ipaddresses_ipnetworks' => 'ipnetworks_id',
                                                         'glpi_ipnetworks'             => 'id']]],
                          'WHERE'     => ['glpi_ipaddresses_ipnetworks.ipaddresses_id' => $ip->getID()]
                                         + $dbu->getEntitiesRestrictCriteria('glpi_ipnetworks')];

                  $res        = $DB->request($sql);
                  foreach ($res as $row) {
                     $ipnetwork = new IPNetwork();
                     if ($ipnetwork->getFromDB($row['ipnetworks_id'])) {
                        $pdf->displayLine('<b>'.sprintf(__('%1$s: %2$s'), __('IP network').'</b>',
                                                        $ipnetwork->fields['completename']));
                     }
                  }
               }
            } // each port

         } // Found
      } // Query
      $pdf->displaySpace();
   }
}