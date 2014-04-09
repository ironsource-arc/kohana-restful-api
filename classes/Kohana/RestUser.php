<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * A RESTful user.
 *
 * @package  RESTfulAPI
 * @category Auth
 * @author   Alon Pe'er, Adi Oz
 */
abstract class Kohana_RestUser {

	/**
	 * User authentication types.
	 */
	const AUTH_TYPE_OFF		= 'off'; // No authentication, so no user. USE WITH CAUTION!
	const AUTH_TYPE_APIKEY	= 'apikey'; // User passes an API key.
	const AUTH_TYPE_SECRET	= 'secret'; // User passes an API key and another secret key.
	const AUTH_TYPE_HASH	= 'hash'; // User passes a hashed string. See _auth_hash() for more information.

	/**
	 * User authentication source.
	 */
	const AUTH_SOURCE_GET		= 1; // The user passes the authentication data as GET parameters.
	const AUTH_SOURCE_HEADER	= 2; // The user passes the authentication data as HTTP headers.

	/**
	 * Request parameter names.
	 */
	const AUTH_KEY_API		= 'apiKey'; // The API key parameter name expected in the request.
	const AUTH_KEY_SECRET	= 'secretKey'; // The secret key parameter name expected in the request.
	const AUTH_KEY_HASH		= 'authorization'; // The hash parameter name expected in the request.

	/**
	 * The time _in minutes_ to allow usage of an authentication hash key before it become invalid.
	 * Relevant for authentication of type "AUTH_TYPE_HASH" only.
	 */
	const MAX_AUTH_TIME = 60;

	/**
	 * Authentication related variables.
	 */
	protected
		$_loaded,
		$_auth_type,
		$_auth_source,
		$_api_key
	;

	/**
	 * Variables that the method _find() is required to populate.
	 */
	protected
		$_id,			// The user's unique identifier, usually an integer.
		$_secret_key,	// (Optional) The user's secret key, used in some of the authentication types.
		$_roles			// An array of roles that the user has. More inf in README.md.
	;

	/**
	 * A list of allowed actions, based on the user's roles and config data.
	 */
	private $_actions;


	public function __construct($auth_type, $auth_source)
	{
		$this->_auth_type = $auth_type; // @TODO validate
		$this->_auth_source = $auth_source; // @TODO validate
		$this->_auth();
		$this->_load(); // Just in case it hasn't run yet.
		$this->_populate_actions();
	}

	/**
	 * Authorizes the user.
	 */
	protected function _auth()
	{
		if (self::AUTH_TYPE_HASH == $this->_auth_type)
		{
			// We add the "Basic " prefix here, so that the GET parameter doesn't need to provide it.
			$this->_auth_hash($this->_get_auth_param(self::AUTH_KEY_HASH));
		}
		else
		{
			$this->_api_key = $this->_get_auth_param(self::AUTH_KEY_API);
			$this->_load();
			if (self::AUTH_TYPE_SECRET == $this->_auth_type && $this->_secret_key != $this->_get_auth_param(self::AUTH_KEY_SECRET))
			{
				throw $this->_altered_401_exception('Invalid API or secret key');
			}
		}
	}

	/**
	 * This function validates the hashed signature.
	 * Check out the implementation of get_auth() to understand
	 * how a valid hashed signature must be generated.
	 */
	protected function _auth_hash($hash)
	{
		// When the source is a header, it's expected that it'll begin
		// with "Basic ", so let's remove it.
		$prefix = 'Basic ';
		if (substr($hash, 0, strlen($prefix)) == $prefix) $hash = substr($hash, strlen($prefix));

		$split = array_filter(explode(':', base64_decode($hash)));
		if (count($split) != 3)
		{
			throw $this->_altered_401_exception('Invalid '. self::AUTH_KEY_HASH .' value');
		}

		$this->_api_key = $split[0];

		$timestamp = (int) $split[1];
		$secret_hash = $split[2];

		// Validate timestamp.
		if (time() > ($timestamp + (60 * self::MAX_AUTH_TIME))) {
			throw $this->_altered_401_exception('Invalid '. self::AUTH_KEY_HASH .' value');
		}

		// We load the user now, so that we can validate the hashed timestamp with the secret key.
		$this->_load();

		if (!$this->_secret_key || $secret_hash !== md5($timestamp . $this->_secret_key)) {
			throw $this->_altered_401_exception('Invalid '. self::AUTH_KEY_HASH .' value');
		}
	}

	/**
	 * Loads the user data.
	 */
	private function _load()
	{
		if ($this->_loaded) return;
		$this->_find();
		if (is_null($this->_id))
		{
			throw $this->_altered_401_exception('Unknown user');
		}
		$this->_loaded = true;
	}

	/**
	 * Returns a 401 HTTP_Exception with a "www-authenticate" header, in order to bypass
	 * Kohana 3.3's exception on missing such header (based on @ehlersd's solution).
	 */
	private function _altered_401_exception($message = NULL)
	{
		$exception = HTTP_Exception::factory(401, $message);
		$exception->headers('www-authenticate', 'None');
		return $exception;
	}

	/**
	 * This method must be implemented. It should populate the various object's
	 * members with data about the user.
	 * Usually, $this->_api_key should be used for identification.
	 * Check the members definition above to find out which ones
	 * you are required to populate.
	 */
	abstract protected function _find();

	/**
	 * Fetches an authentication parameter, based on the supported sources.
	 */
	protected function _get_auth_param($key)
	{
		$value = null;

		if ($this->_auth_source & self::AUTH_SOURCE_HEADER)
		{
			$value = (string) Request::$current->headers($key);
		}

		// Header auth is stronger than query auth, so fall back on this only
		// if header auth failed.
		if (empty($value) && ($this->_auth_source & self::AUTH_SOURCE_GET))
		{
			$value = Request::$current->query($key);
		}

		return $value;
	}

	/**
	 * Populates the actions array with actions that the user
	 * is allowed to perform.
	 */
	private function _populate_actions()
	{
		$this->_actions = array();

		foreach ((array) Kohana::$config->load('acl') as $action => $roles)
		{
			if (count(array_intersect($this->_roles, $roles)) > 0)
			{
				$this->_actions[$action] = true;
			}
		}
	}

	/**
	 * Checks the ACL configuration for user permission to perform an action.
	 *
	 * @param string|array $action
	 *   A string representing an action to validate. If array given,
	 *   all actions must be allowed, or just one, depending on the $justOne parameter.
	 * @param boolean $justOne
	 *   If $action is an array, this determines if all actions or just one
	 *   are required in order to return true.
	 * @return boolean
	 */
	public function can($action, $justOne = false)
	{
		if (is_array($action))
		{
			// Validate all conditions.
			foreach ($action as $a)
			{
				$allowed = $this->isAllowedTo($a);
				if ($allowed && $justOne) return true; // One is quite enough.
				if (!$allowed && !$justOne) return false; // All must pass.
			}
			return true;
		}

		return isset($this->_actions[$action]);
	}

	/**
	 * Checks if the user has this role.
	 */
	public function is_a($role)
	{
		return in_array($role, $this->_roles);
	}

	/**
	 * Returns an authentication string (without the "Basic " prefix)
	 * that can be used to perform API requests.
	 * Relevant for authentication of type "AUTH_TYPE_HASH" only.
	 */
	public function get_auth()
	{
		if ($this->_auth_type != self::AUTH_TYPE_HASH) return null;

		$now = time();
		return base64_encode($this->_api_key .':'. $now .':'. md5($now . $this->_secret_key));
	}

} // END
