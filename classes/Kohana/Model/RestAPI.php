<?php defined('SYSPATH') or die('No direct script access.');

/**
 * An extension of the base model class with user and ACL integration.
 *
 * @package  RESTfulAPI
 * @category Model
 * @author   Alon Pe'er
 */
class Kohana_Model_RestAPI extends Kohana_Model {

	/**
	 * User object for authentication.
	 *
	 * @var RestUser
	 */
	protected $_user;

	/**
	 * Create a new model instance.
	 *
	 *     $model = Model_RestAPI::factory($name, $user);
	 *
	 * @param string $name
	 *   Model name
	 * @param RestUser $user
	 *   An instance of the user model for ACL control
	 * @param array $extra
	 *   An array of extra parameters to pass to the model's constructor.
	 *   Requires support of the specific model.
	 * @return Model_RestAPI
	 */
	public static function factory($name, RestUser &$user = null, array $extra = null)
	{
		// Add the model prefix
		$class = 'Model_'.$name;
		return new $class($user, $extra);
	}

	public function __construct(RestUser &$user = null)
	{
		$this->_user = $user;
	}

} // END
