<?php defined('SYSPATH') or die('No direct script access.');

/**
 * An extension of the HTTP Exception class to support field name.
 *
 * @package  RESTfulAPI
 * @category Exceptions
 * @author   Kohana Team, Alon Pe'er
 */
class HTTP_Exception extends Kohana_HTTP_Exception {

	/**
	 * The field name associated with the error.
	 *
	 * @var string
	 */
	protected $_field = NULL;

	/**
	 * Creates a new translated exception.
	 *
	 *     throw new Kohana_Exception('Something went terrible wrong, :user',
	 *         array(':user' => $user));
	 *
	 * @param   string|array   status message, custom content to display with error.
	 *                         Can also be an array with the "error" message and the "field" name.
	 * @param   array          translation variables
	 * @param   integer        the http status code
	 * @return  void
	 */
	public function __construct($message = NULL, array $variables = NULL, Exception $previous = NULL)
	{
		if (is_array($message))
		{
			$this->_field = $message['field'];
			$message = $message['error'];
		}

		parent::__construct($message, $variables, $previous);
	}

	public function getField()
	{
		return $this->_field;
	}

} // END
