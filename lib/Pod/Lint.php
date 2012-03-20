<?php

namespace Pod;

use ReflectionFunction,
    ReflectionMethod;

class LintFailedException extends Exception
{}

class Lint 
{
    static $httpMethods = array(
        "GET", "POST", "PUT", "DELETE", "HEAD", "OPTION", "TRACE", "PATCH"
    );

    static function validateEnvironment($environment)
    {
        static::ok(is_array($environment), "Environment must be an Array.");

        static::assertKeyExists($environment, "REQUEST_METHOD");
        static::assertKeyExists($environment, "SCRIPT_NAME");
        static::assertKeyExists($environment, "QUERY_STRING");
        static::assertKeyExists($environment, "SERVER_NAME");
        static::assertKeyExists($environment, "SERVER_PORT");

        static::assertKeyExists($environment, "pod.version");
        static::assertKeyExists($environment, "pod.url_scheme");
        static::assertKeyExists($environment, "pod.input");
        static::assertKeyExists($environment, "pod.errors");
        static::assertKeyExists($environment, "pod.multithread");
        static::assertKeyExists($environment, "pod.multiprocess");
        static::assertKeyExists($environment, "pod.run_once");

        static::ok(is_resource($environment["pod.input"]), "pod.input must be a valid resource.");
        static::ok(is_resource($environment["pod.errors"]), "pod.errors must be a valid resource.");

        static::ok(
            in_array($environment["pod.url_scheme"], array("http", "https")), 
            "pod.url_scheme must be either http or https."
        );

        static::ok(is_array($environment["pod.version"]), "pod.version must be an array.");
        static::ok(
            count(array_filter($environment["pod.version"], "is_integer")) === 0,
            "pod.version must be an array of integers."
        );

        static::ok(
            in_array($environment["REQUEST_METHOD"], static::$httpMethods),
            "REQUEST_METHOD must be a valid token."
        );

        if (!empty($environment["SCRIPT_NAME"])) {
            static::ok(
                strpos($environment["SCRIPT_NAME"], "/") === 0,
                "SCRIPT_NAME must start with a / when not empty."
            );
        }

        if (!empty($environment["PATH_INFO"])) {
            static::ok(
                strpos($environment["PATH_INFO"], "/") === 0,
                "PATH_INFO must start with a / when not empty."
            );
        }
        
        if (!empty($environment["CONTENT_LENGTH"])) {
            static::ok(
                is_numeric($environment["CONTENT_LENGTH"]),
                "CONTENT_LENGTH must consist of digits only."
            );
        }

        if (empty($environment["SCRIPT_NAME"])) {
            static::ok(
                $environment["PATH_INFO"] == "/",
                "PATH_INFO should be / when SCRIPT_NAME is empty."
            );
        }
    }

    static function validateComponent($component)
    {
        static::ok(
            is_callable($component), 
            "Components must be valid callbacks."
        );

        if (is_string($component) or $component instanceof \Closure) {
            $ref = new ReflectionFunction($component);

        } else if (is_array($component)) {
            list($class, $method) = $component;
            $ref = new ReflectionMethod($class, $method);

        } else {
            throw new LintFailedException("Unknown Error.");
        }

        static::ok(
            $ref->getNumberOfRequiredParameters() === 1,
            "The callback must be a function of exactly one argument, the environment."
        );
    }

    static function validateResponse($response)
    {
        static::ok(
            is_array($response), 
            "Response must be an array."
        );

        static::ok(
            count($response) === 3, 
            "Response must be an array of 3 items: [status, headers, body]"
        );

        list($status, $headers, $body) = $response;

        static::ok(is_numeric($status), "Status Code must be numeric.");
        
        static::ok($status >= 100, "Status Code must be greater than or equal 100.");

        static::ok(is_array($headers), "Headers must be an array.");

        if ($headers) {
            static::ok(count(array_filter($headers, "is_string")) > 0, "Headers must only have String Keys.");
        }

        foreach ($headers as $key => $value) {
            static::ok(
                stripos($key, "Status") === false,
                "The Headers must not include a \"Status\" key."
            );

            static::ok(
                strpos($key, ":") === false,
                "The Header name \"$key\" must not include a \":\"."
            );

            # Todo: Escape newline in output.
            static::ok(
                strpos($key, "\n") === false,
                "The Header name \"" . addcslashes($key, "\n") . "\" must not include a newline."
            );

            static::ok(!preg_match("/[-_]$/", $key), "Header name must not end with \"-\" or \"_\".");
            static::ok(!preg_match("/^[0-9]/", $key), "Header name must not start with a number.");

            # Value Validation:
            static::ok(is_string($value), "Value must be a String.");
            static::ok(!preg_match('/[\000-\037]/', $value), "Value must not contain characters below 037.");
        }

        if (($status >= 100 and $status < 200) or in_array($status, array(204, 304))) {
            static::ok(
                !array_key_exists("content-type", array_change_key_case($headers)), 
                "Responses with status code 1xx, 204 or 304 must not include a Content-Type header."
            );

            static::ok(
                !array_key_exists("content-length", array_change_key_case($headers)),
                "Responses with status code 1xx, 204 or 304 must not include a Content-Length header."
            );

            static::ok(
                empty($body),
                "Responses with status code 1xx, 204 or 304 must not include a response body"
            );
        }

        static::ok(
            is_array($body) 
            or (is_object($body) and $body instanceof \Traversable)
            or is_resource($body)
            or $body instanceof \SplFileInfo,
            "Body must be either an array, an Iterator, a resource or an instance of SplFileInfo."
        );
    }

    static protected function assertKeyExists($array, $key)
    {
        return static::ok(array_key_exists($key, $array), "Key $key is required.");
    }

    static protected function assertKeyNotEmpty($array, $key)
    {
        return static::ok(!empty($array[$key]), "Key $key must not be empty.");
    }

    static protected function ok($expression, $msg = null)
    {
        if (!$expression) {
            throw new LintFailedException(
                $msg ? $msg : "Expected expression to evaluate to TRUE, got FALSE."
            );
        }
        return true;
    }
}
