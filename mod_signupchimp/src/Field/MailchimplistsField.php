<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_signupchimp
 * @version     2.1
 * @license     GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Naftee\Module\Signupchimp\Site\Helper\MailchimpHelper;

/**
 * Form element to retrieve and display Mailchimp Lists (audiences)
 * automatically in the module configuration when an API key is provided.
 *
 * @since  1.6
 */
class MailchimplistsField extends ListField
{
	protected $type = 'mailchimplists';

	/**
	 * Get the available Mailchimp lists as field options.
	 *
	 * @return  array
	 */
	protected function getOptions(): array
	{
		$options = [];
		$app = Factory::getApplication();

		// Retrieve the API key from the 'params' field group.
		$apiKey = $this->form->getValue('apikey', 'params');

		// Handle an empty API key first.
		if (empty($apiKey))
		{
			$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

			$options[] = HTMLHelper::_(
				'select.option',
				'-1',
				Text::alt('MOD_SIGNUPCHIMP_PROVIDE_API_KEY', $fieldname)
			);

			return array_merge(parent::getOptions(), $options);
		}

		// Initialize Mailchimp.
		try
		{
			$MChimp = new MailchimpHelper($apiKey);
		}
		catch (\Throwable $e)
		{
			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add($e->getMessage(), Log::DEBUG, 'mod_signupchimp');
			}

			$options[] = HTMLHelper::_(
				'select.option',
				'-1',
				Text::_('MOD_SIGNUPCHIMP_ERROR_INIT_FAILED')
			);

			return array_merge(parent::getOptions(), $options);
		}

		// Fetch available lists using the API.
		$result = $MChimp->get('lists');

		if ($MChimp->success() && is_array($result))
		{
			if (!empty($result['lists']))
			{
				foreach ($result['lists'] as $list)
				{
					$options[] = HTMLHelper::_(
						'select.option',
						$list['id'],
						$list['name']
					);
				}
			}
			else
			{
				$options[] = HTMLHelper::_(
					'select.option',
					'-1',
					Text::_('MOD_SIGNUPCHIMP_NO_LISTS_FOUND')
				);
			}
		}
		else
		{
			$error = $MChimp->getLastError() ?: 'Unknown Mailchimp API error.';

			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add($error, Log::DEBUG, 'mod_signupchimp');
			}

			$app->enqueueMessage(
				sprintf(Text::_('MOD_SIGNUPCHIMP_API_ERROR'), $error),
				'error'
			);

			$options[] = HTMLHelper::_(
				'select.option',
				'-1',
				Text::_('MOD_SIGNUPCHIMP_ERROR_INIT_FAILED')
			);
		}

		return array_merge(parent::getOptions(), $options);
	}
}
