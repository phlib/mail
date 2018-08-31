<?php

namespace Phlib\Mail\Tests;

class PerformanceTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        if (!isset($GLOBALS['RUN_PERFORMANCE_TESTS']) || !$GLOBALS['RUN_PERFORMANCE_TESTS']) {
            $this->markTestSkipped('Skipping performance tests');
        }
        parent::setUp();
    }

    /**
     * @large
     */
    public function testMemoryLeak()
    {
        list($return, $output) = $this->runScriptProcess('testMemLeak.php');

        if ($return === 11) {
            $this->fail('Memory test failed with segfault');
        }
        $memDiff = (int)$output;
        $this->assertEquals(0, $memDiff, 'Memory leak test failed', /* within 10 bytes */10);
    }

    /**
     * @large
     */
    public function testSpeed()
    {
        list($return, $output) = $this->runScriptProcess('testSpeed.php');

        if ($return !== 0) {
            throw new \RuntimeException("Failed to run speed test (returned $return)");
        }
        $parsesPerSecond = (int)$output;
        $this->assertGreaterThanOrEqual(75, $parsesPerSecond, 'Failed to meet expected parses per second');
    }

    private function runScriptProcess($script)
    {
        $proc = proc_open(__DIR__ . "/__scripts/$script", [
            ['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']
        ], $pipes);

        if (!is_resource($proc)) {
            throw new \RuntimeException('Unable to run script process');
        }
        $output = stream_get_contents($pipes[1]);

        $return = proc_close($proc);
        return [$return, $output];
    }
}
