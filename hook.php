<?php

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

function plugin_pdf_postinit()
{
    /** @var array $CFG_GLPI */
    /** @var array $PLUGIN_HOOKS */
    global $CFG_GLPI, $PLUGIN_HOOKS;

    foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $typepdf) {
        CommonGLPI::registerStandardTab($type, $typepdf);
    }
}


function plugin_pdf_MassiveActions($type)
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    switch ($type) {
        default:
            if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])) {
                return ['PluginPdfCommon' . MassiveAction::CLASS_ACTION_SEPARATOR . 'DoIt'
                         => __('Print to pdf', 'pdf')];
            }
    }

    return [];
}


function plugin_pdf_install()
{
    /** @var DBmysql $DB */
    global $DB;

    $migration = new Migration('3.0.0');

    include_once(Plugin::getPhpDir('pdf') . '/inc/profile.class.php');
    PluginPdfProfile::install($migration);

    include_once(Plugin::getPhpDir('pdf') . '/inc/preference.class.php');
    PluginPdfPreference::install($migration);

    include_once(Plugin::getPhpDir('pdf') . '/inc/config.class.php');
    PluginPdfConfig::install($migration);

    $migration->executeMigration();

    return true;
}


function plugin_pdf_uninstall()
{
    /** @var DBmysql $DB */
    global $DB;

    $migration = new Migration('3.0.0');

    $tables = ['glpi_plugin_pdf_configs',
        'glpi_plugin_pdf_preferences'];

    foreach ($tables as $table) {
        $migration->dropTable($table);
    }

    //Delete rights associated with the plugin
    $query = "DELETE
             FROM `glpi_profilerights`
             WHERE `name` = 'plugin_pdf'";
    $DB->doQueryOrDie($query, $DB->error());

    $migration->executeMigration();

    return true;
}


/**
 * @since version 1.0.2
**/
function plugin_pdf_registerMethods()
{
    /** @var array $WEBSERVICES_METHOD */
    global $WEBSERVICES_METHOD;

    $WEBSERVICES_METHOD['pdf.getTabs'] = ['PluginPdfRemote', 'methodGetTabs'];
    $WEBSERVICES_METHOD['pdf.getPdf']  = ['PluginPdfRemote', 'methodGetPdf'];
}
