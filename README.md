# Kohana RESTful API Module


This is yet another RESTful API module for Kohana, which started as a port from Kohana's core REST module in 3.1.1.1, and developed into a much more complete solution with many sweet features.

The module currently supports Kohana 3.3.

My role models:

* http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api
* https://blog.apigee.com/taglist/restful


## Features

* Built-in support for GET/POST/PUT/DELETE HTTP methods (other methods can be easily extended).
* Encapsulated query and post parameters parsing.
* Multiple output formats - JSON (including jsonp), CSV, XML and HTML.
* Method overriding and response code suppressing for limited clients.
* Multiple and extendible user authorization methods, with built-in ACL support.
* Cache control.
* Attachment header.
* Command line support, using a Minion task.

## Example Module

The complimentary [example module](https://github.com/SupersonicAds/kohana-restful-api-example) can be installed to get some working knowledge of the RESTful module and some of its basic features, such as controller, model and user classes implementation.


## Basic Usage

After enabling the module in `Kohana::modules`, you must create a route for your application.

Recommended bootstrap route:

	Route::set('default', '<version>(/<directory>)/<controller>(.<format>)',
		array(
			'version' => 'v1',
			'format'  => '(json|xml|csv|html)',
		))
		->defaults(array(
			'format' => 'json',
		));
		
### Controllers

Each REST controller in your app must extend `Controller_REST`. Your controller will then have access to the following variables:

* `$this->_params` - an associated array with all the parameters passed in the request, no matter which method was used.
* `$this->_user`, `$this->_auth_type` and `$this->_auth_source` - user authorization stuff. Read the User Authorization section below for more info.

The following action functions can be implemented to support each one of the corresponding HTTP methods:

* `action_index()` - for GET requests.
* `action_create()` - for POST requests.
* `action_update()` - for PUT requests.
* `action_delete()` - for DELETE requests.

### Models

You can use any model class you want, but if you intend to implement user authorization and ACL, it's recommended to extend `Model_RestAPI`, which forces passing an instance of `RestUser` (see User Authorization for more info).

### Views

By default, the output format is JSON, which doesn't require any special views.
However, the module supports an HTML output format, which relies on Views to output the data.

When the request's output format is `.html`, the module searches for a relevant View file using the same directory structure as the request. For example, is the request was for `/path/to/object.html`, then the module searches for the View file `/path/to/object.php`.

All the data that would usually return in a JSON format is available for the View file in the variable `$data`.

## Error Handling

The module uses HTTP exceptions to manage errors. If you wish to report an error, such as bad request (400), unauthorized (401) etc., you should use the following command:

	$code = 400; // Or any other HTTP code.
	$message = 'A positive integer value is expected'; // Free text.
	$field = 'id'; // (Optional) The name of the field that produced an error.
	throw HTTP_Exception::factory($code, array('error' => $message, 'field' => $field), array(), NULL);

## User Authorization

By default, there's no authorization for any of your REST requests.

In order to add authorization, the following steps must be taken:

### Extend Kohana_RestUser Class

The RestUser class is used to represent the user currently running the request.

The module comes with an abstract `Kohana_RestUser` class, which you must extend in your app. The only function that requires implementation is the protected function `_find()`. The function's implementation is expected to load any user related data, based on an API key. 

Please read the various code comments in `Kohana_RestUser` to understand this implementation better. If something isn't clear, please contact me.

### Controller Setup

Each controller in your app can implement a different type and source of authorization. By default your controller's `$_auth_type` is off (i.e. no authorization).

You should set `$_auth_type` to one of the following to enable authorization:

* `RestUser::AUTH_TYPE_APIKEY` - the user must pass an API key.
* `RestUser::AUTH_TYPE_SECRET` - the user must pass an API key and another secret key.
* `RestUser::AUTH_TYPE_HASH` - the user passes a hashed string signature.

You should also set `$_auth_source` to one of the following to define how the auth data is transfered in the request:

* `RestUser::AUTH_SOURCE_GET` - the authorization data is expected to be found in the request's query parameters.
* `RestUser::AUTH_SOURCE_HEADER` - the authorization data is expected to be found in the request's headers.
* `RestUser::AUTH_SOURCE_GET | RestUser::AUTH_SOURCE_HEADER` - either GET or HEADER can be used.

### ACL Configuration (optional)

The module supports native ACL.

To use this feature, you must create a config file named `acl.php`. The file must return an associated array, where the key is an arbitrary string that describes an action (e.g. "create redmine issues"), and the value is an array that lists user groups that are allowed to perform this action (e.g. `array('manager', 'admin')`).

Notice that you will have to load your user's groups as part of your `RestUser`'s implementation of `_find()`.

Once this is set, you may use the `RestUser` functions `can()` and `is_a()` in your controllers and models to authorize different actions. For example:

	if (!$this->_user->can('create redmine issues))
	{
		throw HTTP_Exception::factory(401, array('error' => 'You cannot create issues'), array(), NULL);
	}
	
	if ($this->_user->is_a('manager'))
	{
		// Do some extra stuff for managers.
	}

## Special Parameters

The following special query parameters are supported:

* `callback` - if this parameter is passed, and the output format is JSON, then the returned data is passed in JSONP format, so the data will be wrapped in a function named by the value passed in `callback`.
* `suppressResponseCodes` - some clients cannot handle HTTP responses different than 200. Passing `suppressResponseCodes=true` will make the response always return `200 OK`, while attaching the real response code as an extra key in the response body. More information here: <https://blog.apigee.com/detail/restful_api_design_tips_for_handling_exceptional_behavior>
* `method` - some clients cannot set an HTTP method different than GET. For these clients, we support simply passing the method as a query parameter. `method` can simply be set to POST, PUT, DELETE or any other method you'd like to support.
* `attachement` - you may sometimes like to allow your users to query your API directly from their browser with a direct link to download the data. For these occasions you may add this parameter with a value representing a file name. This will make the module declare a "content-disposition" header that'll make the user's browser open a download window.

## Command Line

You may create requests to your REST API using CLI commands. The following parameters are expected:

* `headers` - the request's headers.
* `method` - the request's method (GET, POST etc.).
* `get` - the GET query parameters.
* `post` - the POST parameters.
* `resource` - the resource, usually represented by a URL, to which the request should be sent.

## TODO

* Convert this README into a Kohana guide book.
* Write tests.

## Colaborators

Thanks a lot to [ozadi3](https://github.com/ozadi3), I couldn't have this without you!

The module is maintained by [Supersonic](http://www.supersonic.com).

## Contributing

As usual, [fork and send pull requests](https://help.github.com/articles/fork-a-repo)

## Getting Help

* Open issues in this project.
