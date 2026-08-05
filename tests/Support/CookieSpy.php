<?php

namespace Bili\Tests\Support;

/**
 * Records the arguments of every setcookie() call made from the Bili namespace.
 * Backs the namespaced shim in setcookie.php.
 *
 * @see \Bili\setcookie()
 */
class CookieSpy
{
    /** @var array<int, array<int, mixed>> */
    private static $calls = array();

    /**
     * Stores one intercepted setcookie() argument list.
     *
     * @param array<int, mixed> $arrArguments
     * @return void
     */
    public static function record(array $arrArguments)
    {
        self::$calls[] = $arrArguments;
    }

    /**
     * Returns every intercepted call, in invocation order.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function calls()
    {
        return self::$calls;
    }

    /**
     * Discards recorded calls so each test starts from a clean slate.
     *
     * @return void
     */
    public static function reset()
    {
        self::$calls = array();
    }
}
