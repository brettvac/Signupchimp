<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.6
 * @license    GNU General Public License version 2
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();
$document = $app->getDocument();
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
// Load the joomla.asset.json file
$wa->getRegistry()->addExtensionRegistryFile('mod_signupchimp');

// Script asset name to match joomla.asset.json
$wa->useScript('mod_signupchimp.signupchimp');

// Load language constants for JS usage
Text::script('MOD_SIGNUPCHIMP_MESSAGE_ADDING');

//Get the module ID for the form
$moduleId = $module->id;

// Prepare options array
$options = [
    'baseUri'      => Uri::base(),
    'redirect'     => (int) $params->get('redirectaftersubscribe', 0),
    'menuItemId'   => (int) $params->get('menuitemaftersubscribe', 0),
    'delay'        => (int) $params->get('redirectdelay', 0),
    'successClass' => $params->get('successmsgclass', ''),
    'failureClass' => $params->get('failuremsgclass', '')
];

// Pass options to JavaScript using the specific moduleId as a key
$document->addScriptOptions('mod_signupchimp.' . $moduleId, $options);

// Get remaining parameters for the HTML form
$buttonText = $params->get('button', '');
$emailClass = $params->get('emailclass', '');
$fnameClass = $params->get('fnameclass', '');
$gdprClass  = $params->get('gdprclass', '');
$gdprText   = $params->get('gdprtext', ''); 
?>

<div id="sc_result<?php echo $moduleId; ?>"></div>

<form name="signupchimp" id="sc_form<?php echo $moduleId; ?>" data-module-id="<?php echo $moduleId; ?>">
      
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
    
    <button type="submit" class="<?php echo $params->get('btnclass'); ?>" id="sc_button<?php echo $moduleId; ?>">
        <?php echo $buttonText; ?>
    </button>
</form>