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

chdir(__DIR__);
require('../../../inc/includes.php');
restore_error_handler();
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

require('../inc/simplepdf.class.php');

$pdf = new PluginPdfSimplePDF();
$pdf->setHeader('PDF test header');
$pdf->newPage();

$lorem = [
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut fringilla id ante id interdum.',
    'Morbi facilisis et lacus sit amet blandit. Nam ligula erat, euismod eget condimentum in, semper eget tellus.',
    'Cras vitae lacus fermentum, vestibulum eros sed, luctus massa. Vivamus commodo sodales interdum.',
    'Cras accumsan, nunc sit amet facilisis hendrerit, sem tellus gravida enim, ut facilisis tellus augue at dui.',
    'Morbi egestas nisi placerat nunc tempus mattis. ',
];

$pdf->setColumnsSize(100);
$pdf->displayTitle('PDF <b>test</b> title');
$pdf->setColumnsSize(60, 20, 20);
$pdf->displayLine('<b>PDF <i>test</i></b> line', 'one', 'two');
$pdf->displayText('<b>Comment:</b>', implode(' ', $lorem));
$pdf->displayLine(sprintf(
    '<a href="%s">%s</a>',
    'http://www.glpi-project.org/',
    'http://www.glpi-project.org/',
));
$pdf->displaySpace();

$pdf->setColumnsSize(100);
$pdf->displayTitle('Alignment');
$pdf->setColumnsSize(40, 20, 40);
$pdf->setColumnsAlign('right', 'center', 'left');
$pdf->displayLine('right', 'center', 'left');
$pdf->displayLine('1: ' . $lorem[0], '2: ' . $lorem[1], '3: ' . $lorem[2]);
/* 2 colums on 3 */
$pdf->displayLine('4: ' . $lorem[3], '5: ' . $lorem[4]);
/* 6 colums on 3 */
$pdf->displayLine('1', '2', '3', '4', '5', '6');
$pdf->displayText('out of tab:', 'check if column are preserved', 1);
$pdf->displayLine('1', '2', '3');

$pdf->displaySpace();

$pdf->setColumnsSize(100);
$pdf->displayTitle('Filling page');
for ($i = 1 ; $i < 40 ; $i++) {
    $pdf->displayLine("dummy line $i");
}
$pdf->displayTitle('End of Part 1');

$pdf->setHeader('Another header');
$pdf->newPage();
$pdf->displayTitle('Part 2');
$pdf->addPngFromFile(GLPI_ROOT . '/pics/logo-glpi-login.png', 168, 81);
$pdf->displayTitle('End of Part 2');

if (file_put_contents('pdftest.pdf', $pdf->output())) {
    echo "pdftest.pdf saved\n";
    if (file_exists('/usr/bin/evince')) {
        passthru('/usr/bin/evince pdftest.pdf');
    }
}
