<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.8
 * @license    GNU General Public License version 2
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

//Get the Web Asset Manager
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
if ($renderMode === 'modal') {
    $wa->useStyle('mod_signupchimp.signupchimp');
    $wa->useScript('joomla.dialog'); 
}

// Prepare options array
$options = [
    'baseUri'      => Uri::base(),
    'redirect'     => (int) $params->get('redirectaftersubscribe', 0),
    'menuItemId'   => (int) $params->get('menuitemaftersubscribe', 0),
    'delay'        => (int) $params->get('redirectdelay', 0),
    'successClass' => $params->get('successmsgclass', ''),
    'failureClass' => $params->get('failuremsgclass', '')
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

if ($renderMode === 'modal') {
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