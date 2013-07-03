<?php
/**
 * Default REST view for unsuccessful responses in HTML format.
 *
 * @package RESTful_API
 * @category View
 * @author  Alon Pe'er
 *
 * The PHP variable $data is available as an array with the following values:
 *
 * "error":
 *   The error message.
 *
 * "code":
 *   The HTTP response code.
 *
 * "field" (optional):
 *   The request field name on which the error occured (usually on 400 Bad Request errors).
 */
?>
<!DOCTYPE html>
<!--[if IE 8]>         <html class="lt-ie9 ie8"> <![endif]-->
<!--[if gt IE 8]><!--> <html> <!--<![endif]-->
<head>
	<title>Error</title>
	<meta charset="utf-8">
</head>
<body>
	<div class="content">
		(<?= $data['code']; ?>) <?= $data['error']; ?>
	</div>

</body>
</html>

