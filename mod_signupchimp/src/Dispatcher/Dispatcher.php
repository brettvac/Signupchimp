<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.0
 * @copyright  (C) 2025 Brett Vachon
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
      // Process language constants   
      $language = $this->app->getLanguage();
      $language->load('mod_signupchimp', JPATH_BASE . '/modules/mod_signupchimp'); 
 
      $emailPlaceholder = Text::_('MOD_SIGNUPCHIMP_EMAIL_PLACEHOLDER');
      $fnamePlaceholder = Text::_('MOD_SIGNUPCHIMP_FIRST_NAME_PLACEHOLDER');
      $noApiKeyErrMsg = Text::_('MOD_SIGNUPCHIMP_ERROR_NO_API_KEY');
      $noListErrMsg = Text::_('MOD_SIGNUPCHIMP_ERROR_NO_LIST');
      $successMsg = Text::_('MOD_SIGNUPCHIMP_MESSAGE_SUCCESS');
      
      //Process the basic parameters from the configuration file
      $params = new Registry($this->module->params);
      
      $apiKey = $params->get('apikey', '');
      $listID = $params->get('listid', ''); 
      $tagsInput = $params->get('tags', '');
      $buttonText = $params->get('button', '');
      
      // Find out if we want to redirect after successful subscription
      $redirectAfterSubscribe = $params->get('redirectaftersubscribe', 0); 
      
      //Get the redirection after subscription options
      $menuItemId = $params->get('menuitemaftersubscribe', '');
      $redirectDelay = $params->get('redirectdelay', '');
      
      //Process the advanced parameters
      $emailClass = $params->get('emailclass', '');
      $fnameClass = $params->get('fnameclass', '');
      $gdprClass = $params->get('gdprclass', '');
      $btnClass = $params->get('btnclass', '');
      $successmsgClass = $params->get('successmsgclass', '');
      $failuremsgClass = $params->get('failuremsgclass', '');

      //Store the parameters in the session for the helper file
      $session = Factory::getApplication()->getSession();
      $session->set('api_key', $apiKey, 'mod_signupchimp');
      $session->set('list_id', $listID, 'mod_signupchimp');
      $session->set('tags_input', $tagsInput, 'mod_signupchimp');
      $session->set('no_api_key_err_msg', $noApiKeyErrMsg, 'mod_signupchimp');
      $session->set('no_list_err_msg', $noListErrMsg, 'mod_signupchimp');
      $session->set('success_msg', $successMsg, 'mod_signupchimp');

      require ModuleHelper::getLayoutPath('mod_signupchimp');
      }
   
   }
