<?php
namespace Helper;

use PHPUnit\Framework\Assert;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * @param string|float|int $expected
     * @param string|float|int $actual
     * @param float $delta
     */
    public function assertStringFloatValues($expected, $actual, $delta = 0.15)
    {
        if (is_string($expected)) {
            $expected = $this->getFloatValueFromString($expected);
        }
        if (is_string($actual)) {
            $actual = $this->getFloatValueFromString($actual);
        }

        Assert::assertEquals($expected, $actual, 'Asserting values of fields', $delta);
    }

    /**
     * @param $string
     * @param int $position (negative value matches the position from the end of the line)
     * @return float
     */
    public function getFloatValueFromString($string, int $position = -1)
    {
        $matches = [];
        $count = (int)preg_match_all("~\d+(\.\d{1,2})?~", $string,  $matches);
        $position = $position < 0 ? $count + $position : $position;
        if ($count > 0 && $position < $count) {
            return (float)$matches[0][$position];
        } else {
            Assert::fail("String '{$string}' doesn't have float on {$position} position");
        }
    }
}
