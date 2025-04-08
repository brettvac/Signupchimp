<?php
/**
 * @package    Sign Up Chimp Module
 * @version    1.0
 * @copyright  (C) 2025 Brett Vachon
 * @license    GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use DrewM\MailChimp\MailChimp;

// pull in the MailChimp API     
require_once __DIR__ . '/../../lib/MailChimp.php';

class SignupchimpHelper 
  { 
  
  private function checkEmailExists($MChimp, $emailAddress, $listID)
    {
    $subscriberHash = $MChimp::subscriberHash($emailAddress);
       
    $result = $MChimp->get("lists/{$listID}/members/{$subscriberHash}");

    if ($MChimp->success())
      {
      return true; //E-mail exists in the database
      }
    else 
      {
      return false; // Email Not found
      }
    }
 
  private function subscribeUser($MChimp, $emailAddress, $listID, $fname, $tags, $successMsg)
    {
    $data = [
      'email_address' => $emailAddress,
      'status' => 'subscribed',
      'merge_fields' => ['FNAME' => $fname],
      'tags' => $tags
       ];

    $result = $MChimp->post("lists/{$listID}/members", $data);

    if ($MChimp->success())
      {
      return $successMsg;
      } 
    else if(!$MChimp->success())
      {
      throw new \Exception($MChimp->getLastError()); 
      }       
    }
 
  private function updateUser($MChimp, $emailAddress, $listID, $fname, $tags, $successMsg)
    {
    
    $subscriberHash = $MChimp->subscriberHash($emailAddress);

    $data = [
      'merge_fields' => ['FNAME' => $fname],
      'tags' => $tags,
      'status' => 'pending' // Force opt-in confirmation
      ];

    $result = $MChimp->patch("lists/{$listID}/members/{$subscriberHash}", $data);

    if ($MChimp->success())
      {
      return $successMsg; 
      }
    else if(!MChimp->success())
      {
      throw new \Exception($MChimp->getLastError()); 
      }       
    }
 
  public function signupAjax() 
    {
    //Get the parameters from the session
    $session = Factory::getApplication()->getSession();
    $apiKey = $session->get('api_key', '', 'mod_signupchimp');
    $listID = $session->get('list_id', '', 'mod_signupchimp');
    $tagsInput = $session->get('tags_input', '', 'mod_signupchimp');
    $noApiKeyErrMsg = $session->get('no_api_key_err_msg', '', 'mod_signupchimp');
    $noListErrMsg = $session->get('no_list_err_msg', '', 'mod_signupchimp');
    $successMsg = $session->get('success_msg', '', 'mod_signupchimp');

    if (empty($apiKey))
      {
      throw new \Exception($noApiKeyErrMsg);
      }
    
    if(empty($listID))
      {
      throw new \Exception($this->$noListErrMsg);
      }
    
    //Populate the mailchimp object with API key
    $MChimp = new \DrewM\MailChimp\MailChimp($apiKey);
    
    //Try to get the lists
    $mList = $MChimp->get('lists');   
    
    // Get the submitted form values (retrieved as POST data)
    $input = Factory::getApplication()->getInput();
    $email = urldecode($input->get('email', '','RAW'));
    $fname = $input->getString('fname', '');
    
    //Create array from tags for Mailchimp subscription
    $tags = [];
    if (!empty($tagsInput)) 
       {
       $tagsArray = explode(',', $tagsInput);
    
       foreach ($tagsArray as $tag) 
         {
         $tag = trim($tag);
         if ($tag !== '') 
           { 
           $tags[] = $tag; // Just push the tag as a string
           }   
         }
       }  
    
    //Check if we have the user in the database
    $status = $this->checkEmailExists($MChimp, $email, $listID);

    if (!$status) 
      { // User not found — subscribe as new
      return $this->subscribeUser($MChimp, $email, $listID, $fname, $tags, $successMsg); 
      }
    else 
      {// Existing user (subscribed, unsubscribed, pending, etc.) — update & trigger opt-in
      return $this->updateUser($MChimp, $email, $listID, $fname, $tags, $successMsg);
      } 
    } 
  }