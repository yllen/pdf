#!/bin/bash

#
#  -------------------------------------------------------------------------
#  LICENSE
#
#  This file is part of PDF plugin for GLPI.
#
#  PDF is free software: you can redistribute it and/or modify
#  it under the terms of the GNU Affero General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  PDF is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#  GNU Affero General Public License for more details.
#
#  You should have received a copy of the GNU Affero General Public License
#  along with Reports. If not, see <http://www.gnu.org/licenses/>.
#
# @author    Nelly Mahu-Lasson, Remi Collet, Teclib
# @copyright Copyright (c) 2009-2022 PDF plugin team
# @license   AGPL License 3.0 or (at your option) any later version
# @link      https://github.com/pluginsGLPI/pdf/
# @link      http://www.glpi-project.org/
# @package   pdf
# @since     2009
#             http://www.gnu.org/licenses/agpl-3.0-standalone.html
#  --------------------------------------------------------------------------
#

soft='GLPI - PDF plugin'
version='0.84'
email=glpi-translation@gna.org
copyright='plugin PDF Development Team'

#xgettext *.php */*.php -copyright-holder='$copyright' --package-name=$soft --package-version=$version --msgid-bugs-address=$email -o locales/en_GB.po -L PHP --from-code=UTF-8 --force-po  -i --keyword=_n:1,2 --keyword=__ --keyword=_e

# Only strings with domain specified are extracted (use Xt args of keyword param to set number of args needed)

xgettext *.php */*.php -o locales/glpi.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po  \
      --keyword=_n:1,2,4t --keyword=__s:1,2t --keyword=__:1,2t --keyword=_e:1,2t --keyword=_x:1c,2,3t --keyword=_ex:1c,2,3t \
      --keyword=_sx:1c,2,3t --keyword=_nx:1c,2,3,5t


### for using tx :
##tx set --execute --auto-local -r GLPI_example.glpi-084-current 'locales/<lang>.po' --source-lang en --source-file locales/glpi.pot
## tx push -s
## tx pull -a


