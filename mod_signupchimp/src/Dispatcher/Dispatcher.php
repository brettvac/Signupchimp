<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.6
 * @license    GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * Dispatcher for mod_signupchimp
 *
 * @since  1.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    // Provide the getHelperFactory() and setHelperFactory() methods required to inject the module's helper.
    use HelperFactoryAwareTrait; 	

    /**
     * Returns the layout data.
     * Overrides AbstractModuleDispatcher::getLayoutData() to inject custom variables.
     *
     * @return  array
     */
    protected function getLayoutData()
    {
        // Get base data: module, app, input, params, template
        $data = parent::getLayoutData();

        // Add template-related language strings to the data array
        // These will be automatically extracted into variables in the layout file
        $data['emailLabel']       = Text::_('MOD_SIGNUPCHIMP_LABEL_EMAIL_ADDRESS');
        $data['emailPlaceholder'] = Text::_('MOD_SIGNUPCHIMP_EMAIL_PLACEHOLDER');
        $data['fnameLabel']       = Text::_('MOD_SIGNUPCHIMP_LABEL_FIRST_NAME');
        $data['fnamePlaceholder'] = Text::_('MOD_SIGNUPCHIMP_FIRST_NAME_PLACEHOLDER');
        $data['gdprText']         = Text::_('MOD_SIGNUPCHIMP_GDPR_TEXT');

        return $data;
    }
}
