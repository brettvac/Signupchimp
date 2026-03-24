<?php
/**
 * @package      Sign Up Chimp Module
 * @version      1.6
 * @license      GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use DrewM\MailChimp\MailChimp;

require_once __DIR__ . '/../../lib/MailChimp.php';

/**
 * Form element to display Mailchimp Lists automatically
 *
 * @since  1.6
 */
class MailchimplistsField extends ListField
{
    /**
     * The form field type which matches the case of the XML definition exactly.
     *
     * @var    string
     * @since  1.6.0
     */
    protected $type = 'mailchimplists';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   1.6.0
     */
    protected function getOptions()
    {
        $options = [];
        $app = Factory::getApplication();
        $input = $app->getInput();

        // Retrieve the API key from the 'params' field group.
        $apiKey = $this->form->getValue('apikey', 'params');

        // Handle Empty API Key first (always showing because it's empty for the time being until I find the correct way to load the value from the form)
        if (empty($apiKey)) {
            $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
            $options[] = HTMLHelper::_('select.option', '-1', Text::alt('MOD_SIGNUPCHIMP_PROVIDE_API_KEY', $fieldname));
            
            return array_merge(parent::getOptions(), $options);
        }

        $MChimp = null;
        try {
            $MChimp = new \DrewM\MailChimp\MailChimp($apiKey);
        } catch (\Exception $e) {
            if (defined('JDEBUG') && JDEBUG) {
                Log::add($e->getMessage(), Log::DEBUG, 'mod_signupchimp');
            }
            
            $options[] = HTMLHelper::_('select.option', '-1', Text::_('MOD_SIGNUPCHIMP_ERROR_INIT_FAILED'));
            
            return array_merge(parent::getOptions(), $options);
        }

        // Fetch available lists using the API
        $result = $MChimp->get('lists');

        if ($MChimp->success()) {
            if (!empty($result['lists'])) {
                foreach ($result['lists'] as $list) {
                    $options[] = HTMLHelper::_('select.option', $list['id'], $list['name']);
                }
            } else {
                $options[] = HTMLHelper::_('select.option', '-1', Text::_('MOD_SIGNUPCHIMP_NO_LISTS_FOUND'));
            }
        } else {
            $error = $MChimp->getLastError();
            
            if (defined('JDEBUG') && JDEBUG) {
                Log::add($error, Log::DEBUG, 'mod_signupchimp');
            }
            
            $app->enqueueMessage(Text::_('MOD_SIGNUPCHIMP_API_ERROR') . ': ' . $error, 'error');
            $options[] = HTMLHelper::_('select.option', '-1', Text::_('MOD_SIGNUPCHIMP_ERROR_INIT_FAILED'));
        }

        return array_merge(parent::getOptions(), $options);
    }
}