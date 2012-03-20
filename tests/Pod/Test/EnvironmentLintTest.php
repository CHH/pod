<?php

namespace Pod\Test;

use Pod\Lint;

class EnvironmentLintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Pod\LintFailedException
     */
    function testMustBeArray()
    {
        Lint::validateEnvironment(null);
    }

    function testMustContainRequestMethod()
    {
    }
}
