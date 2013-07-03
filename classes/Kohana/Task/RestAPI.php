<?php defined('SYSPATH') or die('No direct script access.');

/**
 * An implementation of the Minion_Task interface,
 * to allow REST API requests from CLI.
 *
 * @package  RESTfulAPI
 * @category Minion
 * @author   Adi Oz
 */
class Kohana_Task_RestAPI extends Minion_Task {

	/**
	 * {@inheritdoc}
	 */
	protected $_options = array
	(
		'get' => NULL,
		'resource' => NULL,
		'headers' => NULL,
		'method' => NULL,
		'post' => NULL,
	);

	/**
	 * This is an execute task for REST API.
	 *
	 * @return null
	 */
	protected function _execute(array $params)
	{
		if (isset($params['headers']))
		{
			// Save the headers in $_SERVER
			if (NULL !== ($headers = json_decode($params['headers'], true)))
			{
				foreach ($headers as $name => $value)
				{
					$_SERVER['HTTP_'. strtoupper($name)] = (string) $value;
				}
			}

			// Remove the headers before execute the request.
			unset($params['headers']);
		}

		if (isset($params['method']))
		{
			// Use the specified method.
			$method = strtoupper($params['method']);
		}
		else
		{
			$method = 'GET';
		}

		if (isset($params['get']))
		{
			// Overload the global GET data.
			parse_str($params['get'], $_GET);
		}

		if (isset($params['post']))
		{
			// Overload the global POST data.
			parse_str($params['post'], $_POST);
		}

		print Request::factory($params['resource'])
				->method($method)
				->execute();
	}

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('headers', 'not_empty')  // Require this parameter.
			->rule('resource', 'not_empty'); // Require this parameter.
	}

} // END

