<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.1
 * @copyright  (C) 2025 Brett Vachon
 * @license    GNU General Public License version 2
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// Load core JavaScript
$document = $this->app->getDocument();
$wa = $document->getWebAssetManager();
$wa->useScript('core');

// Load language constants
Text::script('MOD_SIGNUPCHIMP_MESSAGE_ADDING');
Text::script('MOD_SIGNUPCHIMP_LABEL_EMAIL_ADDRESS');
Text::script('MOD_SIGNUPCHIMP_LABEL_FIRST_NAME');
Text::script('MOD_SIGNUPCHIMP_GDPR_TEXT');

// Get the current module ID
$moduleId = $this->module->id;

// Get the base URI for the AJAX URL
$baseUri = Uri::base();
?>

<script>
function handleFormSubmit<?php echo $moduleId; ?>(form) {
    // Get moduleId from the form's data attribute
    const moduleId = form.getAttribute('data-module-id');

    // Display "adding to list" message
    document.getElementById('sc_result' + moduleId).innerHTML = Joomla.Text._('MOD_SIGNUPCHIMP_MESSAGE_ADDING') + '<br>';

    // Get form data
    const data = new URLSearchParams({
        email: form.querySelector('[name="email"]').value,
        fname: form.querySelector('[name="fname"]').value,
        moduleId: moduleId
    }).toString();

    // Send AJAX request
    Joomla.request({
        url: '<?php echo $baseUri; ?>index.php?option=com_ajax&module=signupchimp&method=signup&format=json',
        method: 'POST',
        data: data,
        processData: false,
        onSuccess: function(data) {
            const response = JSON.parse(data);
            document.getElementById('sc_result' + moduleId).innerHTML = '<div class="<?php echo $successmsgClass; ?>" role="alert">' + response.data + '</div>';

            // Handle redirect if enabled
            const redirectAfterSubscribe = <?php echo (int) $redirectAfterSubscribe; ?>;
            if (redirectAfterSubscribe == 1) {
                const menuItemIdAfterSubscribe = '<?php echo (int) $menuItemId ?? ''; ?>';
                const redirectUrl = '<?php echo $baseUri; ?>index.php?Itemid=' + menuItemIdAfterSubscribe;
                const redirectDelay = '<?php echo $redirectDelay ?? ''; ?>';

                if (menuItemIdAfterSubscribe) {
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, redirectDelay);
                }
            }
        },
        onError: function(xhr) {
            const response = JSON.parse(xhr.response);
            document.getElementById('sc_result' + moduleId).innerHTML = '<div class="<?php echo $failuremsgClass; ?>" role="alert">' + response.message + '</div>';
        }
    });

    return false; // Prevent traditional form submission
}
</script>

<div id="sc_result<?php echo $moduleId; ?>"></div>
<form name="signupchimp" id="sc_form<?php echo $moduleId; ?>" data-module-id="<?php echo $moduleId; ?>" onsubmit="return handleFormSubmit<?php echo $moduleId; ?>(this)">
    <div id="sc_email<?php echo $moduleId; ?>">
        <label for="email"><?php echo Text::_('MOD_SIGNUPCHIMP_LABEL_EMAIL_ADDRESS'); ?></label>
        <input type="email" name="email" class="<?php echo $emailClass; ?>" placeholder="<?php echo $emailPlaceholder; ?>" required>
    </div>
    <div id="sc_fname<?php echo $moduleId; ?>">
        <label for="fname"><?php echo Text::_('MOD_SIGNUPCHIMP_LABEL_FIRST_NAME'); ?></label>
        <input type="text" name="fname" class="<?php echo $fnameClass; ?>" placeholder="<?php echo $fnamePlaceholder; ?>">
    </div>
    <div id="sc_gdpr<?php echo $moduleId; ?>" class="<?php echo $gdprClass; ?>">
        <?php echo Text::_('MOD_SIGNUPCHIMP_GDPR_TEXT'); ?>
    </div>
    <div id="sc_btn<?php echo $moduleId; ?>" class="<?php echo $btnClass; ?>">
        <button type="submit" class="btn" id="sc_button<?php echo $moduleId; ?>"><?php echo $buttonText; ?></button>
    </div>
</form>
