<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * An example REST model
 *
 * @package  RESTfulAPI
 * @category Model
 * @author   Alon Pe'er
 */
class Model_Restexample extends Model_RestAPI {

	public function get($params)
	{
		// Process the request and fetch objects.

		// Returning a mock object.
		return array(
			'restexample' => array(
				array('id' => mt_rand(1, 100), 'name' => Text::random('alnum', 10)),
				array('id' => mt_rand(1, 100), 'name' => Text::random('alnum', 10)),
				array('id' => mt_rand(1, 100), 'name' => Text::random('alnum', 10)),
				array('id' => mt_rand(1, 100), 'name' => Text::random('alnum', 10)),
			),
		);
	}

	public function create($params)
	{
		// Enforce and validate some parameters.
		if (!isset($params['name']))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('Missing name'),
				'field' => 'name',
			));
		}

		if (!Valid::min_length($params['name'], 2) || !Valid::alpha_numeric($params['name']))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('The name must contain at least 2 characters and have alpha-numeric characters only'),
				'field' => 'name',
			));
		}

		// Process the request and create a new object.

		// Returning a mock object.
		return array(
			'restexample' => array('id' => mt_rand(1, 100), 'name' => $params['name']),
		);
	}

	public function update($params)
	{
		// Enforce and validate some parameters.
		if (!isset($params['id']))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('Missing id'),
				'field' => 'id',
			));
		}
		if (!Valid::numeric($params['id']))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('Invalid id'),
				'field' => 'id',
			));
		}

		if (isset($params['name']) && (!Valid::min_length($params['name'], 2) || !Valid::alpha_numeric($params['name'])))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('The name must contain at least 2 characters and have alpha-numeric characters only'),
				'field' => 'name',
			));
		}

		// Process the request and update object.

		// Returning a mock object.
		return array(
			'restexample' => array('id' => $params['id'], 'name' => isset($params['name']) ? $params['name'] : Text::random('alnum', 10)),
		);
	}

	public function delete($params)
	{
		// Enforce and validate some parameters.
		if (!isset($params['id']))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('Missing id'),
				'field' => 'id',
			));
		}
		if (!Valid::numeric($params['id']))
		{
			throw HTTP_Exception::factory(400, array(
				'error' => __('Invalid id'),
				'field' => 'id',
			));
		}

		// Process the request and delete object.

		return array(
			'status' => __('Deleted'),
			'id'     => $params['id'],
		);

	}

} // END