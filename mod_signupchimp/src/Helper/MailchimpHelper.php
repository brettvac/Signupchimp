<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_signupchimp
 * @version     2.1
 * @license     GNU General Public License version 2
 */

namespace Naftee\Module\Signupchimp\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;

/**
 * Lightweight Mailchimp API v3 client using Joomla's HTTP client.
 *
 * @since  2.1
 */
class MailchimpHelper
{
	private string $api_key;

	private string $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';

	public const TIMEOUT = 10;

	/**
	 * SSL certificate verification.
	 *
	 * @var bool
	 */
	public bool $verify_ssl = true;

	private bool $request_successful = false;

	private string $last_error = '';

	private array $last_response = [
		'headers'     => null,
		'httpHeaders' => null,
		'body'        => null,
	];

	private array $last_request = [];

	/**
	 * Joomla HTTP client.
	 *
	 * @var \Joomla\CMS\Http\Http
	 */
	private $http;

	/**
	 * Create a new Mailchimp client.
	 *
	 * @param   string       $api_key       Mailchimp API key
	 * @param   string|null  $api_endpoint Optional custom API endpoint
	 *
	 * @throws  \Exception
	 */
	public function __construct(string $api_key, ?string $api_endpoint = null)
	{
		$this->api_key = $api_key;

		if ($api_endpoint === null)
		{
			if (!str_contains($this->api_key, '-'))
			{
				throw new \Exception('Invalid Mailchimp API key supplied.');
			}

			[, $data_center] = explode('-', $this->api_key, 2);

			$this->api_endpoint = str_replace(
				'<dc>',
				$data_center,
				$this->api_endpoint
			);
		}
		else
		{
			$this->api_endpoint = $api_endpoint;
		}
	}

	/**
	 * Get the API endpoint.
	 *
	 * @return  string
	 */
	public function getApiEndpoint(): string
	{
		return $this->api_endpoint;
	}

	/**
	 * Convert an email address into a subscriber hash.
	 *
	 * @param   string  $email  Subscriber email address
	 *
	 * @return  string
	 */
	public static function subscriberHash(string $email): string
	{
		return md5(strtolower($email));
	}

	/**
	 * Was the last request successful?
	 *
	 * @return  bool
	 */
	public function success(): bool
	{
		return $this->request_successful;
	}

	/**
	 * Get the last error.
	 *
	 * @return  string|false
	 */
	public function getLastError(): string|false
	{
		return $this->last_error ?: false;
	}

	/**
	 * Get the last HTTP response.
	 *
	 * @return  array
	 */
	public function getLastResponse(): array
	{
		return $this->last_response;
	}

	/**
	 * Get the last HTTP request.
	 *
	 * @return  array
	 */
	public function getLastRequest(): array
	{
		return $this->last_request;
	}

	/**
	 * Make an HTTP DELETE request.
	 *
	 * @param   string  $method   API method
	 * @param   array   $args     Optional arguments (retained for API compatibility)
	 * @param   int     $timeout  Request timeout
	 *
	 * @return  array|bool
	 */
	public function delete(
		string $method,
		array $args = [],
		int $timeout = self::TIMEOUT
	): array|bool {
		return $this->makeRequest('delete', $method, $args, $timeout);
	}

	/**
	 * Make an HTTP GET request.
	 *
	 * @param   string  $method   API method
	 * @param   array   $args     Request parameters
	 * @param   int     $timeout  Request timeout
	 *
	 * @return  array|bool
	 */
	public function get(
		string $method,
		array $args = [],
		int $timeout = self::TIMEOUT
	): array|bool {
		return $this->makeRequest('get', $method, $args, $timeout);
	}

	/**
	 * Make an HTTP PATCH request.
	 *
	 * @param   string  $method   API method
	 * @param   array   $args     Request data
	 * @param   int     $timeout  Request timeout
	 *
	 * @return  array|bool
	 */
	public function patch(
		string $method,
		array $args = [],
		int $timeout = self::TIMEOUT
	): array|bool {
		return $this->makeRequest('patch', $method, $args, $timeout);
	}

	/**
	 * Make an HTTP POST request.
	 *
	 * @param   string  $method   API method
	 * @param   array   $args     Request data
	 * @param   int     $timeout  Request timeout
	 *
	 * @return  array|bool
	 */
	public function post(
		string $method,
		array $args = [],
		int $timeout = self::TIMEOUT
	): array|bool {
		return $this->makeRequest('post', $method, $args, $timeout);
	}

	/**
	 * Make an HTTP PUT request.
	 *
	 * @param   string  $method   API method
	 * @param   array   $args     Request data
	 * @param   int     $timeout  Request timeout
	 *
	 * @return  array|bool
	 */
	public function put(
		string $method,
		array $args = [],
		int $timeout = self::TIMEOUT
	): array|bool {
		return $this->makeRequest('put', $method, $args, $timeout);
	}

	/**
	 * Perform the underlying HTTP request.
	 *
	 * @param   string  $http_verb  HTTP method
	 * @param   string  $method     API endpoint
	 * @param   array   $args       Request arguments
	 * @param   int     $timeout    Request timeout
	 *
	 * @return  array|bool
	 */
	private function makeRequest(
		string $http_verb,
		string $method,
		array $args = [],
		int $timeout = self::TIMEOUT
	): array|bool {
		$url = $this->api_endpoint . '/' . ltrim($method, '/');

		$this->prepareStateForRequest(
			$http_verb,
			$method,
			$url,
			$timeout
		);

		// Prepare HTTP headers.
		$headers = [
			'Accept'        => 'application/vnd.api+json',
			'Content-Type'  => 'application/vnd.api+json',
			'Authorization' => 'apikey ' . $this->api_key,
			'User-Agent'    => 'DrewM/MailChimp-API/3.0 (github.com/drewm/mailchimp-api)',
		];

		if (isset($args['language']))
		{
			$headers['Accept-Language'] = $args['language'];
		}

		if ($http_verb === 'put')
		{
			$headers['Allow'] = 'PUT, PATCH, POST';
		}

		// Encode the request payload.
		$payload = null;

		if (in_array($http_verb, ['post', 'patch', 'put'], true))
		{
			$payload = json_encode($args);

			if ($payload === false)
			{
				$this->last_error = 'Unable to encode request data as JSON.';

				return false;
			}

			$this->last_request['body'] = $payload;
		}

		// Record the request headers.
		$this->last_request['headers'] = $this->formatRequestHeaders($headers);

		// GET parameters are appended to the URL.
		if ($http_verb === 'get' && !empty($args))
		{
			$url .= '?' . http_build_query($args, '', '&');
		}

		try
		{
			// Configure SSL verification for this request.
			$options = [];

			if (!$this->verify_ssl && defined('CURLOPT_SSL_VERIFYPEER'))
			{
				$options['transport.curl'] = [
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => 0,
				];
			}

			// Create the HTTP client using the current SSL setting.
			$this->http = HttpFactory::getHttp($options);

			// Send the request using Joomla's HTTP client.
			switch ($http_verb)
			{
				case 'get':
					$response = $this->http->get(
						$url,
						$headers,
						$timeout
					);
					break;

				case 'post':
					$response = $this->http->post(
						$url,
						$payload,
						$headers,
						$timeout
					);
					break;

				case 'patch':
					$response = $this->http->patch(
						$url,
						$payload,
						$headers,
						$timeout
					);
					break;

				case 'put':
					$response = $this->http->put(
						$url,
						$payload,
						$headers,
						$timeout
					);
					break;

				case 'delete':
					$response = $this->http->delete(
						$url,
						$headers,
						$timeout
					);
					break;

				default:
					throw new \InvalidArgumentException(
						'Unsupported HTTP method: ' . $http_verb
					);
			}

			// Store response details.
			$responseBody = (string) $response->body;
			$responseHeaders = $this->formatResponseHeaders(
				(array) $response->headers
			);

			$statusCode = (int) $response->code;

			$this->last_response = [
				'headers' => [
					'http_code' => $statusCode,
				],
				'httpHeaders' => $responseHeaders,
				'body' => $responseBody,
			];

			// Decode JSON response.
			$formattedResponse = false;

			if ($responseBody !== '')
			{
				$decodedResponse = json_decode($responseBody, true);

				if (json_last_error() !== JSON_ERROR_NONE)
				{
					$this->last_error = 'Unable to decode Mailchimp API response as JSON.';

					return false;
				}

				if (is_array($decodedResponse))
				{
					$formattedResponse = $decodedResponse;
				}
			}

			// Determine whether the request succeeded.
			$this->determineSuccess(
				$statusCode,
				$formattedResponse
			);

			// Return the decoded response when available.
			// Empty or non-array JSON responses return the request status.
			return is_array($formattedResponse)
				? $formattedResponse
				: $this->request_successful;
		}
		catch (\Throwable $e)
		{
			// Record transport errors rather than interrupting the request.
			$this->last_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * Prepare state before a request.
	 *
	 * @param   string  $http_verb  HTTP method
	 * @param   string  $method     API method
	 * @param   string  $url        Request URL
	 * @param   int     $timeout    Request timeout
	 *
	 * @return  void
	 */
	private function prepareStateForRequest(
		string $http_verb,
		string $method,
		string $url,
		int $timeout
	): void {
		$this->last_error = '';

		$this->request_successful = false;

		$this->last_response = [
			'headers'     => null,
			'httpHeaders' => null,
			'body'        => null,
		];

		$this->last_request = [
			'method'  => $http_verb,
			'path'    => $method,
			'url'     => $url,
			'body'    => '',
			'timeout' => $timeout,
		];
	}

	/**
	 * Convert response headers into an associative array.
	 *
	 * Mailchimp Link headers are expanded into rel => URL pairs.
	 *
	 * @param   array  $headers  HTTP response headers
	 *
	 * @return  array
	 */
	private function formatResponseHeaders(array $headers): array
	{
		$formatted = [];

		foreach ($headers as $key => $value)
		{
			if (is_array($value))
			{
				$value = implode(', ', $value);
			}

			if (strcasecmp((string) $key, 'Link') === 0)
			{
				$formatted[$key] = array_merge(
					['_raw' => $value],
					$this->getLinkHeaderAsArray((string) $value)
				);
			}
			else
			{
				$formatted[$key] = $value;
			}
		}

		return $formatted;
	}

	/**
	 * Extract rel => URL pairs from a Link header.
	 *
	 * @param   string  $linkHeaderAsString  Link header
	 *
	 * @return  array
	 */
	private function getLinkHeaderAsArray(
		string $linkHeaderAsString
	): array {
		$urls = [];

		if (
			preg_match_all(
				'/<(.*?)>\s*;\s*rel="(.*?)"\s*/',
				$linkHeaderAsString,
				$matches
			)
		) {
			foreach ($matches[2] as $i => $relName)
			{
				$urls[$relName] = $matches[1][$i];
			}
		}

		return $urls;
	}

	/**
	 * Format request headers for getLastRequest().
	 *
	 * @param   array  $headers  Request headers
	 *
	 * @return  string
	 */
	private function formatRequestHeaders(array $headers): string
	{
		$formatted = [];

		foreach ($headers as $key => $value)
		{
			$formatted[] = $key . ': ' . $value;
		}

		return implode("\r\n", $formatted);
	}

	/**
	 * Determine whether the response was successful.
	 *
	 * @param   int          $status             HTTP status code
	 * @param   array|false  $formattedResponse  Decoded response
	 *
	 * @return  bool
	 */
	private function determineSuccess(
		int $status,
		array|false $formattedResponse
	): bool {
		if ($status >= 200 && $status <= 299)
		{
			$this->request_successful = true;

			return true;
		}

		if (
			is_array($formattedResponse) &&
			isset($formattedResponse['detail'])
		) {
			$this->last_error = sprintf(
				'%d: %s',
				$formattedResponse['status'] ?? $status,
				$formattedResponse['detail']
			);

			return false;
		}

		$this->last_error = 'Unknown error, call getLastResponse() to find out what happened.';

		return false;
	}
}
