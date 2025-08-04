<?php

require_once('plugins/login-password-less.php');

/** Set allowed password
 * @param string $password_hash result of password_hash()
 */
return new AdminerLoginPasswordLess(
	$password_hash = password_hash("sqlite", PASSWORD_DEFAULT)
);