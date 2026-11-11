<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_signupchimp
 * @version     2.1
 * @license     GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Helper class for the Sign Up Chimp module.
 *
 * @since  1.0
 */
class SignupchimpHelper
{
	private $MChimp;
	private $emailAddress;
	private $listID;
	private $fname;
	private $tags;
	private $successMsg;
	private $params;
	private $moduleId;
	private $eventSourceUrl;

	/**
	 * Check whether the email address already exists in the list.
	 *
	 * @return  bool
	 *
	 * @throws  \Exception  If the API request fails for a reason other than 404
	 */
	protected function checkEmailExists(): bool
	{
		$subscriberHash = MailchimpHelper::subscriberHash($this->emailAddress);

		$this->MChimp->get("lists/{$this->listID}/members/{$subscriberHash}");

		if ($this->MChimp->success())
		{
			return true;
		}

		$response = $this->MChimp->getLastResponse();
		$statusCode = $response['headers']['http_code'] ?? 0;

		// A 404 means the contact does not exist.
		if ($statusCode === 404)
		{
			return false;
		}

		$error = $this->MChimp->getLastError() ?: 'Unknown Mailchimp API error.';

		if (defined('JDEBUG') && JDEBUG)
		{
			Log::add($error, Log::DEBUG, 'mod_signupchimp');
		}

		throw new \Exception($error);
	}

	/**
	 * Subscribe a new user to the list.
	 *
	 * @return  string  Success message
	 *
	 * @throws  \Exception
	 */
	protected function subscribeUser(): string
	{
		$data = [
			'email_address' => $this->emailAddress,
			'status'        => 'subscribed',
			'merge_fields'  => ['FNAME' => $this->fname],
			'tags'          => $this->tags
		];

		$this->MChimp->post("lists/{$this->listID}/members", $data);

		if ($this->MChimp->success())
		{
			$this->sendMetaConversion();

			return $this->successMsg;
		}

		$error = $this->MChimp->getLastError() ?: 'Unknown Mailchimp API error.';

		if (defined('JDEBUG') && JDEBUG)
		{
			Log::add($error, Log::DEBUG, 'mod_signupchimp');
		}

		throw new \Exception($error);
	}

	/**
	 * Update an existing contact in the list.
	 *
	 * @return  string  Success message
	 *
	 * @throws  \Exception
	 */
	protected function updateUser(): string
	{
		$subscriberHash = MailchimpHelper::subscriberHash($this->emailAddress);

		// Get the current user.
		$current = $this->MChimp->get("lists/{$this->listID}/members/{$subscriberHash}");

		if (!$this->MChimp->success() || !is_array($current))
		{
			$error = $this->MChimp->getLastError() ?: 'Unable to retrieve the existing Mailchimp contact.';

			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add($error, Log::DEBUG, 'mod_signupchimp');
			}

			throw new \Exception($error);
		}

		// Initialize array with first names and tags.
		$data = [
			'merge_fields' => ['FNAME' => $this->fname],
			'tags'         => $this->tags
		];

		// Determine the contact status to set based on existing status.
		switch ($current['status'] ?? '')
		{
			case 'subscribed':
			case 'cleaned':
			case 'pending':
				// Keep the same status for subscribed, cleaned or pending contacts.
				break;

			case 'unsubscribed':
				// Set status to pending for unsubscribed contacts.
				$data['status'] = 'pending';
				break;

			default:
				// Set contact status to subscribed if no status previously set.
				$data['status'] = 'subscribed';
				break;
		}

		// Update the user (with new status if set).
		$this->MChimp->patch("lists/{$this->listID}/members/{$subscriberHash}", $data);

		if ($this->MChimp->success())
		{
			$this->sendMetaConversion();

			return $this->successMsg;
		}

		// Log and display the error message upon failure.
		$error = $this->MChimp->getLastError() ?: 'Unknown Mailchimp API error.';

		if (defined('JDEBUG') && JDEBUG)
		{
			Log::add($error, Log::DEBUG, 'mod_signupchimp');
		}

		throw new \Exception($error);
	}

	/**
	 * Send a conversion event to the Meta Conversions API when enabled.
	 *
	 * Extracts only the required parameters and delegates to MetaCapiHelper.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function sendMetaConversion(): void
	{
		// Meta tracking must be explicitly enabled.
		if ((int) $this->params->get('meta_enabled', 0) !== 1)
		{
			return;
		}

		try
		{
			MetaCapiHelper::sendConversion(
				(int) $this->moduleId,
				$this->eventSourceUrl,
				$this->emailAddress,
				trim($this->params->get('meta_dataset_id', '')),
				trim($this->params->get('meta_access_token', '')),
				trim($this->params->get('meta_event_name', '')),
				trim($this->params->get('meta_event_data', '')),
				trim($this->params->get('meta_test_event_code', ''))
			);
		}
		catch (\Throwable $e)
		{
			// Conversion tracking must never break the signup flow.
			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add(
					'Meta CAPI failure: ' . $e->getMessage(),
					Log::DEBUG,
					'mod_signupchimp'
				);
			}
		}
	}

	/**
	 * Load module and return its parameters as a Registry object.
	 *
	 * @param   int  $moduleId  The ID of the module to load
	 *
	 * @return  Registry  The module parameters
	 *
	 * @throws  \Exception  If the module cannot be loaded or is not enabled
	 */
	protected function getParams($moduleId): Registry
	{
		// Module ID must be a string, not an integer.
		$moduleId = (string) $moduleId;

		// Load the module by ID.
		$module = ModuleHelper::getModuleById($moduleId);

		// Check if the module is enabled.
		if (!ModuleHelper::isEnabled($module->module))
		{
			throw new \Exception(
				'[' . __METHOD__ . '] ' .
				Text::sprintf('COM_AJAX_MODULE_NOT_ACCESSIBLE', $moduleId)
			);
		}

		return new Registry($module->params);
	}

	/**
	 * Handles AJAX requests for the module via com_ajax.
	 *
	 * @return  string  Success message
	 *
	 * @throws  \Exception  On validation, API, or processing errors
	 */
	public function signupAjax(): string
	{
		$app = Factory::getApplication();

		// Get the submitted form values (retrieved as POST data).
		$input = $app->getInput();

		// Check CSRF token.
		if (!Session::checkToken())
		{
			throw new \Exception(Text::_('JINVALID_TOKEN'));
		}

		// Check honeypot.
		$honeypot = $input->getString('sc_website', '');

		if (!empty($honeypot))
		{
			throw new \Exception(Text::_('MOD_SIGNUPCHIMP_ERROR_HONEYPOT_FILLED'));
		}

		// Get the module ID from the request.
		$moduleId = $input->getInt('moduleId', 0);

		if (!$moduleId)
		{
			throw new \Exception(
				'[' . __METHOD__ . '] ' .
				Text::sprintf(
					'COM_AJAX_MODULE_NOT_ACCESSIBLE',
					$moduleId
				)
			);
		}

		$this->moduleId = $moduleId;

		// Get module parameters.
		$params       = $this->getParams($moduleId);
		$this->params = $params;

		// Get the original page URL for Meta event_source_url.
		$this->eventSourceUrl = $input->getString('event_source_url', '');

		if (
			$this->eventSourceUrl === '' ||
			!preg_match('#^https?://#i', $this->eventSourceUrl)
		)
		{
			$this->eventSourceUrl = Uri::current();
		}

		$apiKey         = $params->get('apikey', '');
		$this->listID   = $params->get('listid', '');
		$tagsInput      = $params->get('tags', '');

		// Load language file.
		$language = $app->getLanguage();
		$language->load('mod_signupchimp', JPATH_SITE);

		// Get language strings.
		$this->successMsg = Text::_('MOD_SIGNUPCHIMP_MESSAGE_SUCCESS');

		// Validate API key and list ID.
		if (empty($apiKey))
		{
			throw new \Exception(Text::_('MOD_SIGNUPCHIMP_ERROR_NO_API_KEY'));
		}

		// Custom form sets a sentinel value of '-1' when the list field isn't populated.
		if (empty($this->listID) || $this->listID == '-1')
		{
			throw new \Exception(Text::_('MOD_SIGNUPCHIMP_ERROR_NO_LIST'));
		}

		// Initialize Mailchimp.
		try
		{
			$this->MChimp = new MailchimpHelper($apiKey);
		}
		catch (\Throwable $e)
		{
			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add($e->getMessage(), Log::DEBUG, 'mod_signupchimp');
			}

			throw new \Exception(Text::_('MOD_SIGNUPCHIMP_ERROR_INIT_FAILED'), 0, $e);
		}

		// Get form input.
		$this->emailAddress = urldecode($input->get('email', '', 'RAW'));
		$this->fname        = $input->getString('fname', '');

		// Process tags.
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

		// Check if email exists as a contact.
		if (!$this->checkEmailExists())
		{
			// User not found: subscribe new user.
			return $this->subscribeUser();
		}

		// Existing contact (subscribed, unsubscribed, pending, etc.) update.
		return $this->updateUser();
	}
}
