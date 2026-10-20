<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_signupchimp
 * @version     2.0
 * @license     GNU General Public License version 2
 */
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Naftee\Module\Signupchimp\Site\Helper\MetaCapiHelper;

// Get the Web Asset Manager
$document = $app->getDocument();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $document->getWebAssetManager();

// Load the joomla.asset.json file
$wa->getRegistry()->addExtensionRegistryFile('mod_signupchimp');

// Script asset name to match joomla.asset.json
$wa->useScript('mod_signupchimp.signupchimp');

// Load language constants for JS usage
Text::script('MOD_SIGNUPCHIMP_MESSAGE_ADDING');

// Get configuration parameters
$moduleId   = $module->id;
$renderMode = $params->get('render_mode', 'inline');

// Only load Joomla! dialog scripts and styles if rendering as a modal
if ($renderMode === 'modal')
{
    $wa->useStyle('mod_signupchimp.signupchimp');
    $wa->useScript('joomla.dialog');
}

// Send a Meta ViewContent event when the Conversions API is enabled and the toggle is on.
if ((int) $params->get('meta_enabled', 0) === 1 && (int) $params->get('meta_send_viewcontent', 1) === 1)
{
    $metaDatasetId     = trim((string) $params->get('meta_dataset_id', ''));
    $metaAccessToken   = trim((string) $params->get('meta_access_token', ''));
    $metaGraphApi      = trim((string) $params->get('meta_graph_api', 'v24.0'));
    $metaTestEventCode = trim((string) $params->get('meta_test_event_code', ''));
	  MetaCapiHelper::sendViewContent($metaDatasetId, $metaAccessToken, $metaGraphApi, $metaTestEventCode, $moduleId);
}

// Get redirect query parameters
$redirectQueryParams = $params->get('redirectqueryparams', '');

// Only prepare token values and perform replacement when query parameters exist
if (trim($redirectQueryParams) !== '')
{
    $uri = Uri::getInstance();
    
    // Prepare values for the token replacement inside redirect query parameters
    $token_values = [
        'module_title'     => rawurlencode($module->title),
        'module_name'      => rawurlencode($module->name),
        'module_id'        => $moduleId,
        'page_title'       => rawurlencode($document->getTitle()),
        'page_description' => rawurlencode($document->getDescription()),
        'page_url'         => rawurlencode(Uri::current()),
    ];
    
    // Replace tokens inside the redirect query parameters
    $redirectQueryParams = preg_replace_callback(
        '/\{\{([a-z0-9_]+)\}\}/i',
        static function ($matches) use ($token_values) {
            return $token_values[$matches[1]] ?? $matches[0];
        },
        $redirectQueryParams
    );
    
    // Parse the configured redirect parameters and the current URL parameters
    $configured = [];
    parse_str($redirectQueryParams, $configured);
    $existing = [];
    parse_str($uri->getQuery(), $existing);
    
    // Merge existing parameters first, then add configured ones only when the key does not already exist.
    // Existing keys are preserved and new conflicting parameters are discarded.
    $merged = $existing;
    foreach ($configured as $k => $v)
    {
        if (!array_key_exists($k, $merged))
        {
            $merged[$k] = $v;
        }
    }
    
    // Safety: drop any value that still contains a query string
    foreach ($merged as $k => $v)
    {
        if (is_string($v) && (strpos($v, '?') !== false || strpos($v, '&') !== false))
        {
            unset($merged[$k]);
        }
    }
    // Rebuild clean query string
    $redirectQueryParams = http_build_query($merged);
}

// Prepare options array
$options = [
    'baseUri'             => Uri::base(),
    'redirect'            => (int) $params->get('redirectaftersubscribe', 0),
    'menuItemId'          => (int) $params->get('menuitemaftersubscribe', 0),
    'redirectQueryParams' => $redirectQueryParams,
    'delay'               => (int) $params->get('redirectdelay', 0),
    'successClass'        => $params->get('successmsgclass', ''),
    'failureClass'        => $params->get('failuremsgclass', '')
];
$document->addScriptOptions('mod_signupchimp.' . $moduleId, $options);

// Get remaining parameters for the HTML form
$buttonText  = $params->get('buttontext', '');
$buttonClass = $params->get('buttonclass');
$emailClass  = $params->get('emailclass', '');
$fnameClass  = $params->get('fnameclass', '');
$gdprClass   = $params->get('gdprclass', '');

ob_start();
?>
<div id="sc_result<?php echo $moduleId; ?>"></div>
<form name="signupchimp" id="sc_form<?php echo $moduleId; ?>" data-sc-module-id="<?php echo $moduleId; ?>">
    <div id="sc_email<?php echo $moduleId; ?>">
        <label for="email<?php echo $moduleId; ?>"><?php echo $emailLabel; ?></label>
        <input type="email" name="email" id="email<?php echo $moduleId; ?>" class="<?php echo $emailClass; ?>" placeholder="<?php echo $emailPlaceholder; ?>" required>
    </div>
    <div id="sc_fname<?php echo $moduleId; ?>">
        <label for="fname<?php echo $moduleId; ?>"><?php echo $fnameLabel; ?></label>
        <input type="text" name="fname" id="fname<?php echo $moduleId; ?>" class="<?php echo $fnameClass; ?>" placeholder="<?php echo $fnamePlaceholder; ?>">
    </div>
    <div id="sc_gdpr<?php echo $moduleId; ?>" class="<?php echo $gdprClass; ?>">
        <?php echo $gdprText; ?>
    </div>
    <button type="submit" class="<?php echo $buttonClass; ?>" id="sc_button<?php echo $moduleId; ?>">
        <?php echo $buttonText; ?>
    </button>
    <div style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">
        <label for="sc_website<?php echo $moduleId; ?>">Gotcha</label>
        <input type="text" name="sc_website" id="sc_website<?php echo $moduleId; ?>" tabindex="-1" autocomplete="off">
    </div>
    <input type="hidden" name="moduleId" value="<?php echo $moduleId; ?>">
    <input type="hidden" name="Itemid" value="<?php echo $app->getInput()->getInt('Itemid'); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php

$formHtml = ob_get_clean();

if ($renderMode === 'modal')
{
    // Safely escape the module title for JavaScript injection
    $moduleTitle = htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8');
    
    // Inject Dialog setup script ONLY when modal is active
    $scFormScript = "
    import JoomlaDialog from 'joomla.dialog';
    document.addEventListener('DOMContentLoaded', () => {
        const triggerBtn = document.getElementById('sc_trigger_{$moduleId}');
        const formContainer = document.getElementById('sc_container_{$moduleId}');
        if (triggerBtn && formContainer) {
            // Initialize the dialog on page load
            const scDialog = new JoomlaDialog({
                textHeader: '{$moduleTitle}',
                popupContent: formContainer,
                width: 'fit-content',
                height: 'fit-content',
                className: 'sc-popup-window'
            });
            triggerBtn.addEventListener('click', () => {
                formContainer.style.display = 'block';
                scDialog.show();
            });
        }
    });
    ";
    
    $wa->addInlineScript($scFormScript, ['position' => 'after'], ['type' => 'module'], ['joomla.dialog']);
}
?>
<?php if ($renderMode === 'inline'): ?>
    <div id="sc_container_<?php echo $moduleId; ?>">
        <?php echo $formHtml; ?>
    </div>
<?php else: ?>
    <button type="button" class="<?php echo $buttonClass; ?>" id="sc_trigger_<?php echo $moduleId; ?>">
        <?php echo $buttonText; ?>
    </button>
    <div id="sc_container_<?php echo $moduleId; ?>" style="display: none;">
        <?php echo $formHtml; ?>
    </div>
<?php endif; ?>