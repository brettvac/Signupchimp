<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_signupchimp
 * @version     2.0
 * @license     GNU General Public License version 2
 */
namespace Naftee\Module\Signupchimp\Site\Helper;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
/**
 * Helper for Meta Conversions API (CAPI) events.
 *
 * @since  2.0
 */
class MetaCapiHelper
{
        /**
         * Send a ViewContent event to the Meta Conversions API when enabled.
         *
         * @param   string  $metaDatasetId   Meta Dataset ID
         * @param   string  $accessToken     Meta Access Token
         * @param   string  $graphApi        Graph API version (e.g. "v24.0")
         * @param   string  $testEventCode   Optional test_event_code
         *
         * @return  void
         *
         * @since   2.0
         */
        public static function sendViewContent(
            string $metaDatasetId,
            string $accessToken,
            string $graphApi,
            string $testEventCode = '',
            int $moduleId = 0
        ): void
        {
            $metaDatasetId = trim($metaDatasetId);
            $accessToken = trim($accessToken);
            $graphApi = trim($graphApi, "/ \t\n\r\0\x0B");
            $testEventCode = trim($testEventCode);

            if (
                $metaDatasetId === '' ||
                $accessToken === '' ||
                !preg_match('/^v\d+\.\d+$/', $graphApi)
            ) {
                return;
            }

            $app = Factory::getApplication();
            $input = $app->getInput();
            $document = $app->getDocument();

            $eventSourceUrl = Uri::current();
            $pageTitle = $document->getTitle();

            $userAgent = $input->server->getString('HTTP_USER_AGENT', '');
            $clientIp = $input->server->getString('REMOTE_ADDR', '');
            $fbp = $input->cookie->getString('_fbp', '');
            $fbc = $input->cookie->getString('_fbc', '');

            // Build user data.
            $userData = [];

            if ($clientIp !== '') {
                $userData['client_ip_address'] = $clientIp;
            }

            if ($userAgent !== '') {
                $userData['client_user_agent'] = $userAgent;
            }

            if ($fbp !== '') {
                $userData['fbp'] = $fbp;
            }

            if ($fbc !== '') {
                $userData['fbc'] = $fbc;
            }

            // Build custom event data.
            $customData = [
                'page_title' => $pageTitle,
                'page_url'   => $eventSourceUrl,
            ];

            // Add module information when a valid module ID is supplied.
            if ($moduleId > 0) {
                $module = ModuleHelper::getModuleById((string) $moduleId);

                if ($module && !empty($module->id)) {
                    $customData['module_id'] = (string) $module->id;
                    $customData['module_name'] = $module->name;
                    $customData['module_title'] = $module->title;
                }
            }

            $payload = [
                'data' => [
                    [
                        'event_name'       => 'ViewContent',
                        'event_time'       => time(),
                        'event_id'         => bin2hex(random_bytes(16)),
                        'event_source_url' => $eventSourceUrl,
                        'action_source'    => 'website',
                        'user_data'        => $userData,
                        'custom_data'      => $customData,
                    ],
                ],
            ];

            if ($testEventCode !== '') {
                $payload['test_event_code'] = $testEventCode;
            }

            try {
                $url = 'https://graph.facebook.com/' .
                    $graphApi . '/' .
                    rawurlencode($metaDatasetId) .
                    '/events?access_token=' .
                    rawurlencode($accessToken);

                $response = HttpFactory::getHttp()->post(
                    $url,
                    json_encode($payload),
                    [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ],
                    15
                );

                if ($response->code < 200 || $response->code >= 300) {
                    if (defined('JDEBUG') && JDEBUG) {
                        Log::add(
                            'Meta ViewContent CAPI error: HTTP ' .
                            $response->code . ' ' . $response->body,
                            Log::DEBUG,
                            'mod_signupchimp'
                        );
                    }
                } elseif (defined('JDEBUG') && JDEBUG) {
                    Log::add(
                        'Meta ViewContent CAPI event sent successfully: ' .
                        $response->body,
                        Log::DEBUG,
                        'mod_signupchimp'
                    );
                }
            } catch (\Throwable $e) {
                if (defined('JDEBUG') && JDEBUG) {
                    Log::add(
                        'Meta ViewContent CAPI exception: ' .
                        $e->getMessage(),
                        Log::DEBUG,
                        'mod_signupchimp'
                    );
                }
            }
        }

        /**
         * Send a conversion event to the Meta Conversions API.
         *
         * @param   int     $moduleId        Module ID
         * @param   string  $eventSourceUrl  Event source URL
         * @param   string  $emailAddress    Subscriber email address
         * @param   string  $metaDatasetId   Meta Pixel ID
         * @param   string  $accessToken     Meta Access Token
         * @param   string  $eventName       Event name
         * @param   string  $graphApi        Graph API version (e.g. "v24.0")
         * @param   string  $eventData       Optional custom event data (comma-separated key=value pairs)
         * @param   string  $testEventCode   Optional test_event_code
         *
         * @return  void
         *
         * @since   2.0
         */
        public static function sendConversion(int $moduleId, string $eventSourceUrl, string $emailAddress, string $metaDatasetId, string $accessToken, string $eventName, string $graphApi, string $eventData = '', string $testEventCode = ''):
            void
            {
                $metaDatasetId = trim($metaDatasetId);
                $accessToken = trim($accessToken);
                $eventName = trim($eventName);
                $graphApi = trim($graphApi);
                $testEventCode = trim($testEventCode);
                
                // Stop if any required Meta Conversions API setting is missing.
                if ($metaDatasetId === '' || $accessToken === '' || $eventName === '') {
                    Log::add(
                        'Meta Conversions API: Missing required configuration (Pixel ID, Access Token, or Event Name).',
                        Log::WARNING,
                        'mod_signupchimp'
                    );
                    return;
                }

                // Stop if the Meta Graph API version is not configured.
                if ($graphApi === '') {
                    Log::add(
                        'Meta Conversions API: Graph API version is not configured.',
                        Log::WARNING,
                        'mod_signupchimp'
                    );
                    return;
                }
                
                $graphApi = trim($graphApi, "/ \t\n\r\0\x0B");
                
                if (!preg_match('/^v\d+\.\d+$/', $graphApi))
                {
                    if (defined('JDEBUG') && JDEBUG)
                    {
                        Log::add('Invalid Meta Graph API version: ' . $graphApi, Log::DEBUG, 'mod_signupchimp');
                    }
                    return;
                }
                
                // Load the actual module instance.
                $module = ModuleHelper::getModuleById((string)$moduleId);
                
                // Check if the module is enabled, assigned to current menu item or all items, and accessible by the user           
                if (!ModuleHelper::isEnabled($module->module))
                {
                    throw new \Exception('[' . __METHOD__ . '] ' . Text::sprintf('COM_AJAX_MODULE_NOT_ACCESSIBLE', $moduleId));
                }
                
                // Make the current page title and URL available for use in event data.
                $tokenValues = [
                    'pagetitle' => Factory::getApplication()->getDocument()->getTitle(),
                    'pageurl'   => $eventSourceUrl,
                ];

                // Replace recognized placeholders (such as {{pagetitle}}) with their actual values.
                $resolveTokens = static function ($value) use ($tokenValues)
                {
                    return preg_replace_callback('/\{\{([a-z0-9_]+)\}\}/i', static function ($matches) use ($tokenValues)
                    {
                        $key = strtolower($matches[1]);
                        return array_key_exists($key, $tokenValues) ? $tokenValues[$key] : $matches[0];
                    }
                    , $value);
                };
                $eventName = $resolveTokens($eventName);
                
                // Collapse whitespace in the resulting event name.
                $eventName = preg_replace('/\s+/', ' ', trim($eventName));
                if ($eventName === '')
                {
                    return;
                }
                
                // Retrieve Meta Pixel browser (_fbp) and click (_fbc) identifiers for Conversions API events
                $app = Factory::getApplication();
                $input = $app->getInput();
                $fbp = $input
                    ->cookie
                    ->getString('_fbp', '');
                $fbc = $input
                    ->cookie
                    ->getString('_fbc', '');
                    
                // Reconstruct the Meta click identifier when fbclid is available but the _fbc cookie is missing
                if ($fbc === '')
                {
                    $fbclid = $input->getString('fbclid', '');
                    if ($fbclid !== '')
                    {
                        $fbc = 'fb.1.' . (int)round(microtime(true) * 1000) . '.' . $fbclid;
                    }
                }
                // Normalize the email
                $email = strtolower(trim($emailAddress));
                
                // Hash the email address using SHA-256 when it's supplied as customer information in the user_data object
                $userData = ['em' => [hash('sha256', $email) ]];
                $clientIp = $input
                    ->server
                    ->getString('REMOTE_ADDR', '');
                $userAgent = $input
                    ->server
                    ->getString('HTTP_USER_AGENT', '');
                if ($clientIp !== '')
                {
                    $userData['client_ip_address'] = $clientIp;
                }
                if ($userAgent !== '')
                {
                    $userData['client_user_agent'] = $userAgent;
                }
                if ($fbp !== '')
                {
                    $userData['fbp'] = $fbp;
                }
                if ($fbc !== '')
                {
                    $userData['fbc'] = $fbc;
                }
                
                // Build custom event data from the module configuration
                $customData = ['module_id' => (string)$module->id, 'module_name' => $module->name, 'module_title' => $module->title];
                if ($eventData !== '')
                {
                    $pairs = explode(',', $eventData);
                    foreach ($pairs as $pair)
                    {
                        $parts = explode('=', $pair, 2);
                        if (count($parts) !== 2)
                        {
                            continue;
                        }
                        $key = trim($parts[0]);
                        $value = trim($parts[1]);
                        if ($key === '')
                        {
                            continue;
                        }
                        $customData[$key] = $resolveTokens($value);
                    }
                }
                
                // Generate a unique event ID
                $eventId = bin2hex(random_bytes(16));
                $payload = ['data' => [['event_name' => $eventName, 'event_time' => time() , 'event_id' => $eventId, 'event_source_url' => $eventSourceUrl, 'action_source' => 'website', 'user_data' => $userData, 'custom_data' => $customData]]];

                if ($testEventCode !== '')
                {
                    $payload['test_event_code'] = $testEventCode;
                }

                try
                {
                    $http = HttpFactory::getHttp();
                    $url = 'https://graph.facebook.com/' . $graphApi . '/' . rawurlencode($metaDatasetId) . '/events';
                    $response = $http->post($url, json_encode($payload) , ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'Authorization' => 'Bearer ' . $accessToken], 5);
                    if ($response->code < 200 || $response->code >= 300)
                    {
                        if (defined('JDEBUG') && JDEBUG)
                        {
                            Log::add('Meta CAPI error: HTTP ' . $response->code . ' ' . $response->body, Log::DEBUG, 'mod_signupchimp');
                        }
                        return;
                    }
                    if (defined('JDEBUG') && JDEBUG)
                    {
                        Log::add('Meta CAPI event sent: ' . $eventName . ' [' . $eventId . ']', Log::DEBUG, 'mod_signupchimp');
                    }
                }
                catch(\Throwable $e)
                {
                    // Meta failure must never turn a successful Mailchimp subscription into a failed signup
                    if (defined('JDEBUG') && JDEBUG)
                    {
                        Log::add('Meta CAPI exception: ' . $e->getMessage() , Log::DEBUG, 'mod_signupchimp');
                    }
                }
            }
        }
        
