<?php

namespace Pod\Test;

use Pod\Lint;

class ResponseLintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseMustBeArray()
    {
        $response = null;
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseMustBeArrayOfThreeItems()
    {
        $response = array(100, array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseStatusMustBeNumeric()
    {
        $response = array("foo", array(), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseMustIncludeHeadersAsArray()
    {
        $response = array(200, "foo", array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeadersMustOnlyContainStringKeys()
    {
        $response = array(200, array("Foo" => "Bar", 200 => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testStatusCodeMustBeGreaterThanOrEqual100()
    {
        $response = array(99, array(), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeaderKeysMustNotIncludeNewlines()
    {
        $response = array(200, array("Key\n" => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeaderKeysMustNotIncludeColons()
    {
        $response = array(200, array("Key:" => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeadersMustNotIncludeStatusKey()
    {
        $response = array(200, array("status" => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeaderKeysMustNotEndWithDash()
    {
        $response = array(200, array("foo-" => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeaderKeysMustNotEndWithUnderscore()
    {
        $response = array(200, array("foo_" => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeaderKeyMustNotStartWithNumber()
    {
        $response = array(200, array("1foo" => "foo"), array());
        Lint::validateResponse($response);
    }

    /**
     * @expectedException \Pod\LintFailedException
     */
    function testResponseHeaderKeysMustNotIncludeCharactersBelow037()
    {
        $response = array(200, array("foo" => "\037"));
        Lint::validateResponse($response);
    }

    function testResponseBodyCanBeArray()
    {
        $response = array(200, array(), array());
        Lint::validateResponse($response);
    }
}
