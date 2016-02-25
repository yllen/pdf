<?php
/**
 * @version $Id: $
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


class PluginPdfChangeValidation extends PluginPdfCommon {


   static $rightname = "plugin_pdf";


   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new ChangeValidation());
   }


   static function pdfForChange(PluginPdfSimplePDF $pdf, Change $change) {
      global $CFG_GLPI, $DB;

      $pdf->setColumnsSize(100);
      $pdf->displayTitle("<b>".__('Approvals for the change', 'pdf')."</b>");

      if (!Session::haveRightsOr('changevalidation',
                                 array_merge(CommonITILValidation::getCreateRights(),
                                             CommonITILValidation::getValidateRights(),
                                             CommonITILValidation::getPurgeRights()))) {
         return false;
      }
     $ID = $change->getField('id');

      $query = "SELECT *
                FROM `glpi_changevalidations`
                WHERE `changes_id` = '".$change->getField('id')."'
                ORDER BY submission_date DESC";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number) {
         $pdf->setColumnsSize(10,10,15,20,10,15,20);
         $pdf->displayTitle(_x('item', 'State'), __('Request date'), __('Approval requester'),
                            __('Request comments'), __('Approval status'), __('Approver'),
                            __('Approval comments'));

         while ($row = $DB->fetch_assoc($result)) {
            $pdf->displayLine(TicketValidation::getStatus($row['status']),
                              Html::convDateTime($row["submission_date"]),
                              getUserName($row["users_id"]),
                              trim($row["comment_submission"]),
                              Html::convDateTime($row["validation_date"]),
                              getUserName($row["users_id_validate"]),
                              trim($row["comment_validation"]));
         }
      } else {
         $pdf->displayLine(__('No item found'));
      }
      $pdf->displaySpace();
   }
}