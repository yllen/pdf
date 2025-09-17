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
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

define('PLUGIN_PDF_VERSION', '4.0.1');
define('PLUGIN_PDF_MIN_GLPI', '10.0.11');
define('PLUGIN_PDF_MAX_GLPI', '10.0.99');

function plugin_init_pdf()
{
    /** @var array $PDF_DEVICES */
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS, $PDF_DEVICES;

    $PLUGIN_HOOKS['csrf_compliant']['pdf'] = true;

    // manage autoload of tcppdf (glpi core now use mdpdf)
    include_once(Plugin::getPhpDir('pdf') . '/vendor/autoload.php');

    Plugin::registerClass('PluginPdfConfig', ['addtabon' => 'Config']);
    $PLUGIN_HOOKS['config_page']['pdf'] = 'front/config.form.php';

    include_once(Plugin::getPhpDir('pdf') . '/inc/config.class.php');
    $PDF_DEVICES = PluginPdfConfig::currency();

    Plugin::registerClass('PluginPdfProfile', ['addtabon' => 'Profile']);
    $PLUGIN_HOOKS['change_profile']['pdf'] = ['PluginPdfProfile', 'initProfile'];

    if (Session::haveRight('plugin_pdf', READ)) {
        Plugin::registerClass('PluginPdfPreference', ['addtabon' => 'Preference']);
    }

    if (
        Session::getLoginUserID()
        && Session::haveRight('plugin_pdf', READ)
    ) {
        $PLUGIN_HOOKS['use_massive_action']['pdf'] = 1;
    }

    $plugin = new Plugin();
    if ($plugin->isActivated('datainjection')) {
        $PLUGIN_HOOKS['menu_entry']['pdf'] = 'front/preference.form.php';
    } elseif ($plugin->isActivated('geststock')) {
        $PLUGIN_HOOKS['menu_entry']['pdf'] = 'front/preference.form.php';
    }


    // Define the type for which we know how to generate PDF :
    $PLUGIN_HOOKS['plugin_pdf']['Appliance']        = 'PluginPdfAppliance';
    $PLUGIN_HOOKS['plugin_pdf']['CartridgeItem']    = 'PluginPdfCartridgeItem';
    $PLUGIN_HOOKS['plugin_pdf']['ConsumableItem']   = 'PluginPdfConsumableItem';
    $PLUGIN_HOOKS['plugin_pdf']['Change']           = 'PluginPdfChange';
    $PLUGIN_HOOKS['plugin_pdf']['Computer']         = 'PluginPdfComputer';
    $PLUGIN_HOOKS['plugin_pdf']['Contract']         = 'PluginPdfContract';
    $PLUGIN_HOOKS['plugin_pdf']['Group']            = 'PluginPdfGroup';
    $PLUGIN_HOOKS['plugin_pdf']['KnowbaseItem']     = 'PluginPdfKnowbaseItem';
    $PLUGIN_HOOKS['plugin_pdf']['Monitor']          = 'PluginPdfMonitor';
    $PLUGIN_HOOKS['plugin_pdf']['NetworkEquipment'] = 'PluginPdfNetworkEquipment';
    $PLUGIN_HOOKS['plugin_pdf']['Peripheral']       = 'PluginPdfPeripheral';
    $PLUGIN_HOOKS['plugin_pdf']['Phone']            = 'PluginPdfPhone';
    $PLUGIN_HOOKS['plugin_pdf']['Printer']          = 'PluginPdfPrinter';
    $PLUGIN_HOOKS['plugin_pdf']['Problem']          = 'PluginPdfProblem';
    $PLUGIN_HOOKS['plugin_pdf']['Software']         = 'PluginPdfSoftware';
    $PLUGIN_HOOKS['plugin_pdf']['SoftwareLicense']  = 'PluginPdfSoftwareLicense';
    $PLUGIN_HOOKS['plugin_pdf']['SoftwareVersion']  = 'PluginPdfSoftwareVersion';
    $PLUGIN_HOOKS['plugin_pdf']['Ticket']           = 'PluginPdfTicket';
    $PLUGIN_HOOKS['plugin_pdf']['User']             = 'PluginPdfUser';



    // End init, when all types are registered by all plugins
    $PLUGIN_HOOKS['post_init']['pdf'] = 'plugin_pdf_postinit';

    // Integration with WebService plugin
    $PLUGIN_HOOKS['webservices']['pdf'] = 'plugin_pdf_registerMethods';
}


function plugin_version_pdf()
{
    return ['name'       => __('Print to pdf', 'pdf'),
        'version'        => PLUGIN_PDF_VERSION,
        'author'         => 'Teclib, Remi Collet, Nelly Mahu-Lasson',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/pluginsGLPI/pdf',
        'minGlpiVersion' => PLUGIN_PDF_MIN_GLPI,
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_PDF_MIN_GLPI,
                'max' => PLUGIN_PDF_MAX_GLPI,
            ],
        ],
    ];
}
