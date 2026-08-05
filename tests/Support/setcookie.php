<?php

namespace Bili;

use Bili\Tests\Support\CookieSpy;

/**
 * Intercepts setcookie() for Bili classes so tests can assert its real arguments.
 * PHP resolves unqualified calls here before the global function.
 * headers_list() stays empty under the CLI SAPI.
 *
 * @param string $name
 * @param string $value
 * @param int $expires
 * @param string $path
 * @param string $domain
 * @param bool $secure
 * @param bool $httponly
 * @return bool
 */
function setcookie(
    $name,
    $value = "",
    $expires = 0,
    $path = "",
    $domain = "",
    $secure = false,
    $httponly = false
) {
    CookieSpy::record(array($name, $value, $expires, $path, $domain, $secure, $httponly));

    return true;
}
