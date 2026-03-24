/**
 * @package    Sign Up Chimp Module
 * @version    1.6
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
    const moduleId = form.getAttribute('data-module-id');
    
    // Retrieve options specifically for this module instance via addScriptOptions
    const options = Joomla.getOptions('mod_signupchimp.' + moduleId);
    
    const scResultDiv = document.getElementById('sc_result' + moduleId);

    // Display "adding to list" message
    scResultDiv.innerHTML = Joomla.Text._('MOD_SIGNUPCHIMP_MESSAGE_ADDING') + '<br>';

    // Get form data
    const data = new URLSearchParams({
        email: form.querySelector('[name="email"]').value,
        fname: form.querySelector('[name="fname"]').value,
        moduleId: moduleId
    }).toString();

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
                const redirectUrl = options.baseUri + 'index.php?Itemid=' + menuItemIdAfterSubscribe;
                const redirectDelay = options.delay;

                if (menuItemIdAfterSubscribe) {
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, redirectDelay);
                }
            }
        },
        onError: function(xhr) {
            // Get the error message, including from MC  
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