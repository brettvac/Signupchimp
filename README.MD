# Sign Up Chimp
![Sign Up Chimp Image](Sign-Up-Chimp.jpg)
A Joomla! module for displaying a MailChimp signup form on your website which submits with AJAX.

Are you are interested in building a list and marketing your products? The first step is to capture e-mails from your website traffic, and you can do that using this module.
 
This is your first step to turning a sedentary website visitor into a raving Chimp who will lap up your grand slam offers.

## How To Use Sign Up Chimp
1. Install the module via the Joomla! Extensions Manager. You can install via URL by using this URL:  [https://github.com/brettvac/signupchimp/releases/latest/download/mod_signupchimp.zip](https://github.com/brettvac/signupchimp/releases/latest/download/mod_signupchimp.zip)  
2. Navigate to Content > Site Modules (or just click the modules button from the dashboard) and edit the Sign Up Chimp module.
3. Configure the module settings, including MailChimp API key and list ID. If you want to display a tripwire offer, set the menu item and the delay before redirect.
4. Publish the module in your desired position. Users can now enter their email and first name to subscribe to your list directly from your website.

## Features
- **AJAX Signup** Uses an AJAX-powered form to send data to MailChimp without reloading the page. 
- **Custom CSS Classes** Stylize your form to your heart's content by adding classes to the form elements

## Requirements
This plugin works with Joomla! versions greater than 4.4 and requires PHP 7.2.5 or later. You will also need a MailChimp account and an API key. [Read about API keys here](https://mailchimp.com/help/about-api-keys/)

## FAQ
**Q:Can I use this plugin with Mailerlite?**  
**A:** No, it's for MailChimp. I might vibe-code a Mailerlite version at some point.

**Q: Why didn't you use Camel casing in your code (i.e. SignupChimp)**  
**A:** Because Camel casing won't work with Joomla's com_ajax component. Because it dynamically creates the name of the ajax function that you're calling based on the module name, com_ajax fails to find your helper file if you use camel casing. It took me days to figure that out.

**Q: This plugin is awesome! Can I send a donation?**  
**A:** Sure! Send your cryptonation to the following wallets:

`BTC 1PXWZJcBfehqgV25zWdVDS6RF2yVMxFkZD`

`Eth 0xC9b695D4712645Ba178B4316154621B284e2783D`

## Credits
- **MailChimp API v3 wrapper** by [Drew McLellan](https://github.com/drewm) (MIT License)