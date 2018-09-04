<?php

/**
 * テスト用モッククラスを定義
 */
namespace Rebet\Common {
    use Rebet\Tests\DieException;
    use Rebet\Tests\ExitException;

    class System {
        public static $HEADER = [];

        private function __construct() {}

        public static function mock_init() {
            self::$HEADER = [];
        }

        public static function exit($status = null) : void {
            throw new ExitException($status);
        }

        public static function die($status = null) : void {
            throw new DieException($status);
        }

        public static function header(string $header,  bool $replace = true, int $http_response_code = null) : void {
            self::$HEADER[] = compact('header','replace','http_response_code');
        }
    }
}