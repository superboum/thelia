<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Core\Template\Smarty\Plugins;

use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Model\ModuleQuery;

class Module extends AbstractSmartyPlugin
{
    /**
     * Process theliaModule template inclusion function
     *
     * @param  unknown $params
     * @param \Smarty_Internal_Template $template
     * @internal param \Thelia\Core\Template\Smarty\Plugins\unknown $smarty
     *
     * @return string
     */
    public function theliaModule($params, \Smarty_Internal_Template $template)
    {
        $content = null;

        if (false !== $location = $this->getParam($params, 'location', false)) {

            $modules = ModuleQuery::getActivated();

            foreach ($modules as $module) {

                $file = sprintf("%s/%s/AdminIncludes/%s.html", THELIA_MODULE_DIR, ucfirst($module->getCode()), $location);

                if (file_exists($file)) {
                    $content .= file_get_contents($file);
                }
            }
        }

        if (! empty($content))
            return $template->fetch(sprintf("string:%s", $content));

        return "";
    }

    /**
     * Define the various smarty plugins hendled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('function', 'module_include', $this, 'theliaModule'),
        );
    }
}
