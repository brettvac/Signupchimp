/**
 * @package    Sign Up Chimp Module
 * @version    1.9
 * @license    GNU General Public License version 2
 */

document.addEventListener('DOMContentLoaded', () => {
    // Find and select all forms with name 'signupchimp' 
    const forms = document.querySelectorAll('form[name="signupchimp"]');
    
    // Attach a 'submit event' listener to each signupchimp form
    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            handleSignupSubmit(form);
        });
    });
});

function handleSignupSubmit(form) {
    // Get moduleId from the form's data attribute
    const moduleId = form.dataset.scModuleId;
    
    // Retrieve options specifically for this module instance via addScriptOptions
    const options = Joomla.getOptions('mod_signupchimp.' + moduleId);
    
    const scResultDiv = document.getElementById('sc_result' + moduleId);

    // Display "adding to list" message
    scResultDiv.innerHTML = Joomla.Text._('MOD_SIGNUPCHIMP_MESSAGE_ADDING') + '<br>';

    // Get form data
    const formData = new FormData(form);
    const data = new URLSearchParams(formData).toString();

    // Send AJAX request using Joomla.request
    Joomla.request({
        url: options.baseUri + 'index.php?option=com_ajax&module=signupchimp&method=signup&format=json',
        method: 'POST',
        data: data,
        processData: false,
        onSuccess: function(data) {
            const response = JSON.parse(data);
            
            // Display the success message after adding to the list
            scResultDiv.innerHTML = ''; // Clear the "adding to list" message

            const divElement = document.createElement('div');
            divElement.className = options.successClass;
            divElement.setAttribute('role', 'alert');
            divElement.textContent = response.data; // Use response.data for success message

            scResultDiv.appendChild(divElement);
            
            // Handle redirect if enabled
            if (options.redirect === 1) {
                const menuItemIdAfterSubscribe = options.menuItemId;
                const redirectDelay = options.delay;

                if (menuItemIdAfterSubscribe) {
                    let redirectUrl = options.baseUri + 'index.php?Itemid=' + menuItemIdAfterSubscribe;

                    // Add optional tracking/query parameters
                    if (options.redirectQueryParams) {
                        let queryParams = options.redirectQueryParams.trim();

                        // Remove a leading ? or & so we can safely append the parameters
                        queryParams = queryParams.replace(/^[?&]+/, '');

                        if (queryParams) {
                            redirectUrl += '&' + queryParams;
                        }
                    }

                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, redirectDelay);
                }
            }
        },
        onError: function(xhr) {
            // Get the error message, including from Mailchimp
            const response = JSON.parse(xhr.response);
            
            scResultDiv.innerHTML = ''; // Clear any existing content in sc_resultDiv before appending new content

            const divElement = document.createElement('div');
            divElement.className = options.failureClass;
            divElement.setAttribute('role', 'alert');
            divElement.textContent = response.message;

            scResultDiv.appendChild(divElement);
        }
    });

    return false; // Prevent traditional form submission
}