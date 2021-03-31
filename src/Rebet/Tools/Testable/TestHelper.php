<?php
namespace Rebet\Tools\Testable;

use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Files;
use Rebet\Tools\Utility\Path;

/**
 * Test Helper Trait
 * 
 * The assertion methods are declared static and can be invoked from any context, for instance, 
 * using static::assert*() or $this->assert*() in a class that use TestHelper.
 *
 * It expect this trait to be used in classes that extended PHPUnit\Framework\TestCase(actual PHPUnit\Framework\Assert).
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait TestHelper
{
    /**
     * @var string Original working directory
     */
    private static $original_working_dir = null;

    /**
     * @var string Working directory for testing
     */
    private static $test_working_dir = null;

    /**
     * Set up working directory for testing and change current directory to there.
     *
     * @param string $base_working_dir for testing
     * @return string new current directory absolute path
     */
    public static function setUpWorkingDir(string $base_working_dir) : string 
    {
        static::$original_working_dir = Path::normalize(getcwd());
        static::$test_working_dir     = Path::normalize($base_working_dir.'/'.getmypid());
        if (!file_exists(static::$test_working_dir)) {
            mkdir(static::$test_working_dir, 0777, true);
        }
        chdir(static::$test_working_dir);
        return static::$test_working_dir;
    }

    /**
     * Make sub working directory under the current test working directory.
     * NOTE: This method does not change current working directory.
     *
     * @param string $sub_dir name
     * @param bool $clean sub directory if already exists. (default: true)
     * @return string sub directory absolute path
     */
    public static function makeSubWorkingDir(string $sub_dir, bool $clean = true) : string
    {
        $sub_working_dir = Path::normalize(static::$test_working_dir.'/'.$sub_dir);
        if (!file_exists($sub_working_dir)) {
            mkdir($sub_working_dir, 0777, true);
        } else {
            if($clean) {
                Files::removeDir($sub_working_dir, false);
            }
        }
        return $sub_working_dir;
    }

    /**
     * Remove sub working directory under the current test working directory.
     *
     * @param string $sub_dir
     * @return void
     */
    public static function removeSubWorkingDir(string $sub_dir) : void
    {
        Files::removeDir(Path::normalize(static::$test_working_dir.'/'.$sub_dir));
    }

    /**
     * Tear down working directory for testing and change current directory back to original working directory.
     *
     * @return string new current directory absolute path
     */
    public static function tearDownWorkingDir() : string
    {
        if(static::$original_working_dir) {
            chdir(static::$original_working_dir);
            Files::removeDir(static::$test_working_dir);
        }
        return static::$original_working_dir ?? Path::normalize(getcwd()) ;
    }

    /**
     * Get value from an array/object/class-statics using "dot" notation.
     * This method can be accessed non-public property.
     *
     * @param mixed $target
     * @param string $key You can use dot notation
     * @return mixed
     */
    public static function get($target, string $key)
    {
        return Reflector::get($target, $key, null, true);
    }

    /**
     * Inspect property value of object/class-statics.
     * This method can be accessed non-public property.
     *
     * @param object|string $target
     * @param string $name
     * @return mixed
     */
    public function inspect($target, string $name)
    {
        $class = is_string($target) ? $target : get_class($target) ;
        $rp    = new \ReflectionProperty($class, $name);
        $rp->setAccessible(true);
        return $rp->getValue(is_string($target) ? null : $target);
    }

    /**
     * Set a value to an array/object/class-statics using "dot" notation.
     *
     * Please be aware that if you set a value with this method,
     * the DotAccessDelegator structure of the target object data will be lost.
     *
     * @param mixed $target
     * @param array $values [key => value, ...], the key can use dot notation.
     * @return mixed given $target object that injected values
     */
    public static function inject($target, array $values)
    {
        foreach($values as $key => $value) {
            Reflector::set($target, $key, $value, true);
        }
        return $target;
    }

    /**
     * It checks that current OS environment is Windows or not.
     *
     * @return bool
     */
    public function isWindows() : bool
    {
        return PHP_OS === 'WIN32' || PHP_OS === 'WINNT' || PHP_OS === 'Windows' ;
    }

    /**
     * Get current and peak memory usage information.
     *
     * @param string $format of sprintf(), 1st args for current memory, 2nd for peak memory. (default: "\nMemory(current/peak): %01.2f / %01.2f MB ")
     * @return string
     */
    public static function memory(string $format = "\nMemory(current/peak): %01.2f / %01.2f MB ") : string
    {
        return sprintf($format, memory_get_usage() / 1048576, memory_get_peak_usage() / 1048576);
    }

    /**
     * Invoke a method of given object/class
     * This method can be accessed non-public method.
     *
     * @param string|object $object
     * @param string $method
     * @param array $args that ordered or named (default: [])
     * @param boolean $type_convert (default: false)
     * @return mixed
     */
    public static function invoke($object, string $method, array $args = [], bool $type_convert = false)
    {
        return Reflector::invoke($object, $method, $args, true, $type_convert);
    }

    // ========================================================================
    // Dependent PHPUnit\Framework\Assert assertions
    // ========================================================================

    /**
     * @see PHPUnit\Framework\Assert::assertEquals
     */
    public abstract static function assertEquals($expected, $actual, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertNotEquals
     */
    public abstract static function assertNotEquals($expected, $actual, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertStringContainsString
     */
    public abstract static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertStringNotContainsString
     */
    public abstract static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertMatchesRegularExpression
     */
    public abstract static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertDoesNotMatchRegularExpression
     */
    public abstract static function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertTrue
     */
    public abstract static function assertTrue($condition, string $message = ''): void;

    /**
     * @see PHPUnit\Framework\Assert::assertFalse
     */
    public abstract static function assertFalse($condition, string $message = ''): void;

    // ========================================================================
    // Extended assertions
    // ========================================================================

    /**
     * Asserts that two string variables are equal.
     *
     * @param string $expect
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringEquals(string $expect, string $actual, string $message = '') : void
    {
        static::assertEquals($expect, $actual, $message);
    }

    /**
     * Asserts that two string variables are not equal.
     * If more than one expected value is given, it states that it is not equals any of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotEqualsAny($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertNotEquals($expect, $actual, $message);
        }
    }

    /**
     * Asserts that each two string variables are equal.
     *
     * @param string[] $expects
     * @param string[] $actuals
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringEqualsEach(array $expects, array $actuals, string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringEquals($expect, $actuals[$i], $message);
        }
    }

    /**
     * Asserts that an actual string contains expects.
     * If more than one expected value is given, it states that it is contains all of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringContainsAll($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertStringContainsString($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an actual string does not contains expects.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotContainsAny($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertStringNotContainsString($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an each actual string contains each expects.
     * If more than one expected value is given, it states that it is contains all of them.
     *
     * @param string[]|string[][] $expects
     * @param string[] $actuals
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringContainsEach(array $expects, array $actuals, string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringContainsAll($expect, $actuals[$i], $message);
        }
    }

    /**
     * Asserts that an actual string matches expected regular expressions.
     * If more than one expected value is given, it states that it matches all of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringRegExpAll($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertMatchesRegularExpression($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an actual string does not match expected regular expressions.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotRegExpAny($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertDoesNotMatchRegularExpression($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an each actual string matches each expected regular expressions.
     * If more than one expected value is given, it states that it matches all of them.
     *
     * @param string[]|string[][] $expects
     * @param string[] $actuals
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringRegExpEach(array $expects, array $actuals, string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringRegExpAll($expect, $actuals[$i], $message);
        }
    }

    /**
     * Asserts that an actual string matches expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     * 
     * @param string|string[] $expects
     * @param string $actual
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringWildcardAll($expects, string $actual, array $wildcards = [], string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        $message = empty($message) ? $message : "{$message}\n" ;
        foreach ($expects as $expect) {
            foreach($wildcards as $alias => $real) {
                $expect = addcslashes($expect, $real);
                $expect = str_replace($alias, $real, $expect);
            }
            static::assertTrue(\fnmatch($expect, $actual), "{$message}Failed asserting that wildcard match: expect \"{$expect}\" but actual \"{$actual}\".");
        }
    }

    /**
     * Asserts that an actual string matches expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     * 
     * @param string|string[] $expects
     * @param string $actual
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotWildcardAny($expects, string $actual, array $wildcards = [], string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        $message = empty($message) ? $message : "{$message}\n" ;
        foreach ($expects as $expect) {
            foreach($wildcards as $real => $alias) {
                $expect = addcslashes($expect, $real);
                $expect = str_replace($alias, $real, $expect);
            }
            static::assertFalse(\fnmatch($expect, $actual), "{$message}Failed asserting that wildcard not match: not expect \"{$expect}\" but actual \"$actual\".");
        }
    }

    /**
     * Asserts that an each actual string matches each expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     *
     * @param string[]|string[][] $expects
     * @param string[] $actuals
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringWildcardEach(array $expects, array $actuals, array $wildcards = [], string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringWildcardAll($expect, $actuals[$i], $wildcards, $message);
        }
    }

    /**
     * Asserts that STDERR output via evaluated test are equal expect.
     *
     * @param string $expect
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrEquals(string $expect, \Closure $test, string $message = '') : void
    {
        static::assertStringEquals($expect, StderrCapture::via($test), $message);
    }

    /**
     * Asserts that STDERR output via evaluated test are not equal expect.
     * If more than one expected value is given, it states that it is not equals any of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrNotEqualsAny($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringNotEqualsAny($expects, StderrCapture::via($test), $message);
    }

    /**
     * Asserts that STDERR output via evaluated test contains expects.
     * If more than one expected value is given, it states that it is contains all of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrContainsAll($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringContainsAll($expects, StderrCapture::via($test), $message);
    }

    /**
     * Asserts that STDERR output via evaluated test does not contains expects.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrNotContainsAny($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringNotContainsAny($expects, StderrCapture::via($test), $message);
    }

    /**
     * Asserts that STDERR output via evaluated test matches expected regular expressions.
     * If more than one expected value is given, it states that it matches all of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrRegExpAll($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringRegExpAll($expects, StderrCapture::via($test), $message);
    }

    /**
     * Asserts that STDERR output via evaluated test does not match expected regular expressions.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrNotRegExpAny($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringNotRegExpAny($expects, StderrCapture::via($test), $message);
    }

    /**
     * Asserts that STDERR output via evaluated test matches expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrWildcardAll($expects, \Closure $test, array $wildcards = [], string $message = '') : void
    {
        static::assertStringWildcardAll($expects, StderrCapture::via($test), $wildcards, $message);
    }

    /**
     * Asserts that STDERR output via evaluated test does not match expected wildcards.
     * If more than one expected value is given, it states that it is not contains any of them.
     * @see fnmatch()
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStderrNotWildcardAny($expects, \Closure $test, array $wildcards = [], string $message = '') : void
    {
        static::assertStringNotWildcardAny($expects, StderrCapture::via($test), $wildcards, $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test are equal expect.
     *
     * @param string $expect
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutEquals(string $expect, \Closure $test, string $message = '') : void
    {
        static::assertStringEquals($expect, StdoutCapture::via($test), $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test are not equal expect.
     * If more than one expected value is given, it states that it is not equals any of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutNotEqualsAny($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringNotEqualsAny($expects, StdoutCapture::via($test), $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test contains expects.
     * If more than one expected value is given, it states that it is contains all of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutContainsAll($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringContainsAll($expects, StdoutCapture::via($test), $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test does not contains expects.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutNotContainsAny($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringNotContainsAny($expects, StdoutCapture::via($test), $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test matches expected regular expressions.
     * If more than one expected value is given, it states that it matches all of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutRegExpAll($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringRegExpAll($expects, StdoutCapture::via($test), $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test does not match expected regular expressions.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutNotRegExpAny($expects, \Closure $test, string $message = '') : void
    {
        static::assertStringNotRegExpAny($expects, StdoutCapture::via($test), $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test matches expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutWildcardAll($expects, \Closure $test, array $wildcards = [], string $message = '') : void
    {
        static::assertStringWildcardAll($expects, StdoutCapture::via($test), $wildcards, $message);
    }

    /**
     * Asserts that STDOUT output via evaluated test does not match expected wildcards.
     * If more than one expected value is given, it states that it is not contains any of them.
     * @see fnmatch()
     *
     * @param string|string[] $expects
     * @param \Closure $test
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStdoutNotWildcardAny($expects, \Closure $test, array $wildcards = [], string $message = '') : void
    {
        static::assertStringNotWildcardAny($expects, StdoutCapture::via($test), $wildcards, $message);
    }
}
