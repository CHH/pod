This specification aims to formalize the Pod protocol.

_This is a Draft._ Everything may change at any time.

# Pod applications

A Pod application is any valid PHP callback.

It takes exactly one argument, the *environment*
and returns an Array of exactly three values:

 * The *status*,
 * the *headers*,
 * and the *body*.

## The Environment

The environment must be an instance of Hash that includes
CGI-like headers. The application is free to modify the
environment.

The environment is required to include these variables
(adopted from PEP333), except when they'd be empty, but see
below.

<dl>
    <dt>REQUEST_METHOD</dt>
    <dd>
        The HTTP request method, such as
        "GET" or "POST". This cannot ever
        be an empty string, and so is
        always required.
    </dd>

    <dt>SCRIPT_NAME</dt>
    <dd>
        The initial portion of the request
        URL's "path" that corresponds to the
        application object, so that the
        application knows its virtual
        "location". This may be an empty
        string, if the application corresponds
        to the "root" of the server.
    </dd>
    
    <dt>PATH_INFO</dt>
    <dd>
        The remainder of the request URL's
        "path", designating the virtual
        "location" of the request's target
        within the application. This may be an
        empty string, if the request URL targets
        the application root and does not have a
        trailing slash. This value may be
        percent-encoded when I originating from
        a URL.
    </dd>

    <dt>QUERY_STRING</dt>
    <dd>
        The portion of the request URL that
        follows the <code>?</code>, if any. May be
        empty, but is always required!
    </dd>

    <dt>SERVER_NAME</dt>
    <dt>SERVER_PORT</dt>
    <dd>
        When combined with <code>SCRIPT_NAME</code> and <code>PATH_INFO</code>, these 
        variables can be used to complete the URL. Note, however, that 
        <code>HTTP_HOST</code>, if present, should be used in preference to 
        <code>SERVER_NAME</code> for reconstructing the request URL.  
        <code>SERVER_NAME</code> and <code>SERVER_PORT</code> can never be empty strings, 
        and so are always required.
    </dd>

    <dt>HTTP_ Variables</dt>
    <dd>
        Variables corresponding to the 
        client-supplied HTTP request
        headers (i.e., variables whose
        names begin with <code>HTTP_</code>). The
        presence or absence of these
        variables should correspond with
        the presence or absence of the
        appropriate HTTP header in the
        request.
    </dd>
</dl>

In addition to this, the Pod environment must include these
Pod-specific variables:

<dl>
    <dt>pod.version</dt>
    <dd>
       The Array [1,0], representing this version of Pod. 
    </dd>
    
    <dt>pod.url_scheme</dt>
    <dd>
        `http` or `https`, depending on the request URL.
    </dd>

    <dt>pod.input</dt>
    <dd>
        See below, the input stream.
    </dd>

    <dt>pod.errors</dt>
    <dd>
        See below, the error stream.
    </dd>

    <dt>pod.multithread</dt>
    <dd>
        `true` if the application object may be simultaneously invoked 
        by another thread in the same process, false otherwise.
    </dd>

    <dt>pod.multiprocess</dt>
    <dd>
        `true` if an equivalent application object may be 
        simultaneously invoked by another process, `false` otherwise.
    </dd>

    <dt>pod.run_once</dt>
    <dd>
        `true` if the server expects (but does not guarantee!) that the 
        application will only be invoked this one time during the life 
        of its containing process. Normally, this will only be `true` 
        for a server based on CGI (or something similar).
        Additional environment specifications have approved to
        standardized middleware APIs. None of these are required to
        be implemented by the server.
    </dd>
</dl>

The server or the application can store their own data in the
environment, too. The keys must contain at least one dot,
and should be prefixed uniquely. The prefix `pod.`
is reserved for use with the Pod core distribution and other
accepted specifications and must not be used otherwise.
The environment must not contain the keys
`HTTP_CONTENT_TYPE` or `HTTP_CONTENT_LENGTH`
(use the versions without `HTTP_`).

The CGI keys (named without a period) must have String values.
There are the following restrictions:

 * `pod.version` must be an array of Integers.
 * `pod.url_scheme` must either be `http` or `https`.
 * There must be a valid input stream in `pod.input`.
 * There must be a valid error stream in `pod.errors`.
 * The `REQUEST_METHOD` must be a valid token.
 * The `SCRIPT_NAME`, if non-empty, must start with `/`
 * The `PATH_INFO`, if non-empty, must start with `/`
 * The `CONTENT_LENGTH`, if given, must consist of digits only.
 * One of `SCRIPT_NAME` or `PATH_INFO` must be
   set.  `PATH_INFO` should be `/` if
   `SCRIPT_NAME` is empty.
   `SCRIPT_NAME` never should be `/`, but instead be empty.

### The Input Stream

The input stream is a PHP stream resource which contains the raw HTTP
POST data.

`fclose` must _never_ be called on the input stream.

### The Error Stream

The error stream is a PHP stream resource which is used to write errors
to.

`fclose` must _never_ be called on the error stream.

## The Response

### The Status

This is an HTTP status. When parsed as integer, it must be
greater than or equal to 100.

### The Headers

 * The header must be usable with `foreach` and yield the header name
   as key and the header value as value.
 * The header keys must be Strings.
 * The header must not contain a `Status` key,
   contain keys with `:` or newlines in their name,
   contain keys names that end in `-` or `_`,
   but only contain keys that consist of
   letters, digits, `_` or `-` and start with a letter.
 * The values of the header must be Strings,
   consisting of lines (for multiple header values, e.g. multiple
   `Set-Cookie` values) seperated by "\n".
 * The lines must not contain characters below 037.

### The Content-Type

There must be a `Content-Type`, except when the
`Status` is 1xx, 204 or 304, in which case there must be none
given.

### The Content-Length

There must not be a `Content-Length` header when the
`Status` is 1xx, 204 or 304.

### The Body

These are valid values for the body:

 * Variables which are usable in `foreach`, each iteration must yield
   a String.
 * Variables of type `resource`, `fclose` will be called after the response was sent.
 * Instances of `SplFileInfo`.

## Thanks

This is a port of the [Rack Specification][rack_spec].
Many thanks to Christian Neukirchen for creating the Rack Spec.

Please see the [Rack Specification][rack_spec] for additional thanks.

[rack_spec]: http://rack.rubyforge.org/doc/SPEC.html
