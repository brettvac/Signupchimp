<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.4
 * @license    GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

class Dispatcher implements DispatcherInterface, HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected $module;
    protected $app;

    public function __construct(\stdClass $module, CMSApplicationInterface $app, Input $input)
    {
        $this->module = $module;
        $this->app = $app;
    }

    public function dispatch()
    {
        // Load language file for front-end rendering
        $language = $this->app->getLanguage();
        $language->load('mod_signupchimp', JPATH_BASE . '/modules/mod_signupchimp');

        // Load only template-related language strings
        $emailPlaceholder = Text::_('MOD_SIGNUPCHIMP_EMAIL_PLACEHOLDER');
        $fnamePlaceholder = Text::_('MOD_SIGNUPCHIMP_FIRST_NAME_PLACEHOLDER');
        $gdprText = Text::_('MOD_SIGNUPCHIMP_GDPR_TEXT');
        $emailLabel = Text::_('MOD_SIGNUPCHIMP_LABEL_EMAIL_ADDRESS');
        $fnameLabel = Text::_('MOD_SIGNUPCHIMP_LABEL_FIRST_NAME');

        // Process module parameters
        $params = new Registry($this->module->params);

        $buttonText = $params->get('button', '');

        // Redirect settings
        $redirectAfterSubscribe = $params->get('redirectaftersubscribe', 0);
        $menuItemId = $params->get('menuitemaftersubscribe', '');
        $redirectDelay = $params->get('redirectdelay', '');

        // Advanced parameters
        $emailClass = $params->get('emailclass', '');
        $fnameClass = $params->get('fnameclass', '');
        $gdprClass = $params->get('gdprclass', '');
        $btnClass = $params->get('btnclass', '');
        $successmsgClass = $params->get('successmsgclass', '');
        $failuremsgClass = $params->get('failuremsgclass', '');

        // Render the module layout
        require ModuleHelper::getLayoutPath('mod_signupchimp');
    }
}