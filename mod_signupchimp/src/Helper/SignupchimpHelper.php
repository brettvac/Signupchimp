<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.4
 * @license    GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;
use DrewM\MailChimp\MailChimp;

require_once __DIR__ . '/../../lib/MailChimp.php';

class SignupchimpHelper
{
    private $MChimp;
    private $emailAddress;
    private $listID;
    private $fname;
    private $tags;
    private $successMsg;

    protected function checkEmailExists()
    {
        $subscriberHash = $this->MChimp::subscriberHash($this->emailAddress);
        $result = $this->MChimp->get("lists/{$this->listID}/members/{$subscriberHash}");

        return $this->MChimp->success();
    }

    /**
     * Subscribe a new user to the MailChimp audience.
     *
     * @return string The success message on successful subscription
     * @throws \Exception If the API request fails
     */
    protected function subscribeUser()
    {
        $data = [
            'email_address' => $this->emailAddress,
            'status' => 'subscribed',
            'merge_fields' => ['FNAME' => $this->fname],
            'tags' => $this->tags
        ];

        $result = $this->MChimp->post("lists/{$this->listID}/members", $data);

        if ($this->MChimp->success())
        {
           
            return $this->successMsg;    
        }

        throw new \Exception($this->MChimp->getLastError());
    }

    /**
     * Update an existing MailChimp subscriber with new data.
     *
     * @return string The success message on successful update
     * @throws \Exception If the API request fails or the subscriber cannot be retrieved
     */
    protected function updateUser()
    {
        $subscriberHash = $this->MChimp::subscriberHash($this->emailAddress);

        // Get the current user
        $current = $this->MChimp->get("lists/{$this->listID}/members/{$subscriberHash}");

        // Initialize array with first names and tags
        $data = [
            'merge_fields' => ['FNAME' => $this->fname],
            'tags' => $this->tags
        ];

        // Determine the status to set based on existing status
        switch ($current['status'] ?? '') {
            case 'subscribed':
            case 'cleaned':
                // Keep the same status for subscribed or cleaned users
                break;
            case 'unsubscribed':
            case 'pending':
                // Set status to pending for unsubscribed or pending users
                $data['status'] = 'pending';
                break;
            default:
                // Default case: set status to subscribed if no status previously set
                $data['status'] = 'subscribed';
                break;
        }

        //Update the user (with new status if set)
        $result = $this->MChimp->patch("lists/{$this->listID}/members/{$subscriberHash}", $data);

        if ($this->MChimp->success())
        {
            return $this->successMsg;
        }

        throw new \Exception($this->MChimp->getLastError());
    }

    /**
     * Load module and return its parameters as a Registry object.
     *
     * @param int $moduleId The ID of the module to load
     * @return Registry The module parameters
     * @throws \Exception If the module cannot be loaded or is not enabled
     */
    protected function getParams($moduleId)
    {
        // Load the module by ID
        $moduleId = (string) $moduleId;  // Module ID must be a String, not an Integer
        $module = ModuleHelper::getModuleById($moduleId);

        // Check if the module is enabled, assigned to current menu item or all items, and accessible by the user
        if (!ModuleHelper::isEnabled($module->module))
        {
            throw new \Exception(Text::_('COM_AJAX_MODULE_NOT_ACCESSIBLE'));
        }

        return new Registry($module->params);
    }

    public function signupAjax()
    {
      
        // Get the submitted form values (retrieved as POST data)
        $input = Factory::getApplication()->getInput();

        // Get the module ID from the request
        $moduleId = $input->getInt('moduleId', 0);
        if (!$moduleId)
        {
            throw new \Exception(Text::_('COM_AJAX_MODULE_NOT_ACCESSIBLE'));
        }

        // Get module parameters
        $params = $this->getParams($moduleId);
        $apiKey = $params->get('apikey', '');
        $this->listID = $params->get('listid', '');
        $tagsInput = $params->get('tags', '');

        // Load language file
        $app = Factory::getApplication('site');
        $language = $app->getLanguage();
        $language->load('mod_signupchimp', JPATH_BASE . '/modules/mod_signupchimp');

        // Get language strings
        $this->successMsg = Text::_('MOD_SIGNUPCHIMP_MESSAGE_SUCCESS');

        // Validate API key and list ID
        if (empty($apiKey))
        {
            throw new \Exception(Text::_('MOD_SIGNUPCHIMP_ERROR_NO_API_KEY'));
        }

        if (empty($this->listID))
        {
            throw new \Exception(Text::_('MOD_SIGNUPCHIMP_ERROR_NO_LIST'));
        }

        // Initialize MailChimp
        $this->MChimp = new \DrewM\MailChimp\MailChimp($apiKey);

        // Get form input
        $this->emailAddress = urldecode($input->get('email', '', 'RAW'));
        $this->fname = $input->getString('fname', '');

        // Process tags
        $this->tags = [];
        if (!empty($tagsInput))
        {
            $tagsArray = explode(',', $tagsInput);
            foreach ($tagsArray as $tag)
            {
                $tag = trim($tag);
                if ($tag !== '')
                {
                    $this->tags[] = $tag;
                }
            }
        }

        // Check if email exists
        $status = $this->checkEmailExists();

        if (!$status)
        {
            // User not found: subscribe new user
            return $this->subscribeUser();
        }

        // Existing user (subscribed, unsubscribed, pending, etc.) — update & trigger opt-in
        return $this->updateUser();
    }
}