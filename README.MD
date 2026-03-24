# Sign Up Chimp

![Sign Up Chimp Image](Sign-Up-Chimp.jpg)

Sign Up Chimp is a Joomla! module which, when published in a visible module position, displays a MailChimp signup form on your website which submits with AJAX.

## Why Use Sign Up Chimp
Are you are interested in building a list and marketing your products? The first step is to capture e-mails from your website traffic, and you can do that using this module.
 
Sign Up Chimp is your first step to turning a sedentary website visitor who stumbles upon your modest Joomla! site into a raving Chimp who won't stop at anything to lap up your grand slam offers.

## How To Use Sign Up Chimp
1. Install the module via the Joomla! Extensions Manager. You can install via URL by using this URL: [https://github.com/brettvac/signupchimp/releases/latest/download/mod_signupchimp.zip](https://github.com/brettvac/signupchimp/releases/latest/download/mod_signupchimp.zip)  
2. Navigate to Content > Site Modules (or just click the modules button from the dashboard) to start editing the Sign Up Chimp module.
3. Configure the module settings, starting with your MailChimp API key. The list field will populate automatically when you save the module. 
4. If you want to display a tripwire offer, set the menu item and the delay before redirect option will appear.
5. Publish the module in your desired position. 

Once the module is visible on your site, visitors can now enter their email and first name to subscribe to your mailing list directly from your website.

## Sign Up Chimp Features
- **AJAX Signup**. Sign Up Chimp uses an AJAX-powered form to send data to MailChimp without reloading the page, allowing your user to stay on the page after submitting the form. 
- **Custom CSS Classes**. You can stylize your form to your heart's content by adding classes to the form elements directly in the module configuration.
- **Multiple Forms**. You can also publish multiple Sign Up Chimp modules on the same page, for example in the top or bottom positions of your template.

## Sign Up Chimp Requirements
The Sign Up Chimp module works with Joomla! versions greater than 4.4 and requires PHP 7.2.5 or later. 

You will also need a MailChimp account and an API key. [Read about API keys here](https://mailchimp.com/help/about-api-keys/)

## FAQ
**Q:Can I use this module with Mailerlite?**  
**A:** No, Sign Up Chimp is designed solely for use with MailChimp.

**Q: Why didn't you use Camel casing in your namespace code (i.e. SignupChimp)**  
**A:** Because Camel casing won't work with Joomla's com_ajax component. Annoyingly enough, [com_ajax](https://manual.joomla.org/docs/4.4/general-concepts/javascript/com-ajax/) dynamically creates the name of the ajax function that you're calling based on the module name. Therefore, com_ajax fails to find your helper file if you use camel casing in your namespace. It took me days to figure that out.

**Q: This Joomla! extension is absolutely fantastic! Can I send a donation?**  
**A:** Sure! Send your cryptonation to the following wallets:

`BTC 1PXWZJcBfehqgV25zWdVDS6RF2yVMxFkZD`

`Eth 0xC9b695D4712645Ba178B4316154621B284e2783D`

## Credits
- **MailChimp API v3 wrapper** by [Drew McLellan](https://github.com/drewm) (MIT License)
