Kohana RESTful API Module
=========================


This is yet another RESTful API module for Kohana, which started as a port from Kohana's core REST module in 3.1.1.1, and developed into a much more complete solution with many sweet features.

The module currently supports Kohana 3.3.

My role models:

* http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api
* https://blog.apigee.com/taglist/restful


Basic Usage
-----------

Recommended bootstrap route:

	Route::set('default', '<version>(/<directory>)/<controller>(.<format>)',
		array(
			'version' => 'v1',
			'format'  => '(json|xml|csv|html)',
		))
		->defaults(array(
			'format' => 'json',
		));



Things to Write About
---------------------

* User ACL
* Extending models
* jsonp
* suppressResponseCodes
* method overwrite
* Arbitrary actions
* Attachment header
* HTML views
* Throwing HTTP errors.
* Minion Task


TODO
----

* Implement RestUser.
* Finish writing this README (consider converting it to a Kohana Guide book).
* Write tests.


Colaborators
------------

Thanks to the following colaborators, I couldn't have this without you:

* [ozadi3](https://github.com/ozadi3)


Contributing
------------

As usual, [fork and send pull requests](https://help.github.com/articles/fork-a-repo)


Getting Help
------------

* [Contact me](https://github.com/alonpeer).
* Open issues in this project.
