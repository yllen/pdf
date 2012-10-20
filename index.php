<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2012 by the pdf Development Team.

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

// Original Author of file: BALPE Dévi
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../..');

include (GLPI_ROOT."/inc/includes.php");
include_once (GLPI_ROOT."/lib/ezpdf/class.ezpdf.php");

Html::header($LANG['plugin_pdf']["title"][1], $_SERVER['PHP_SELF'],"pdf");

Html::redirect("front/preferences.php");

Html::footer();
?>
