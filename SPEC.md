This specification aims to formalize the Rack protocol.  You
can (and should) use Rack::Lint to enforce it.
When you develop middleware, be sure to add a Lint before and
after to catch all mistakes.

# Rack applications

A Rack application is a Ruby object (not a class) that
responds to `call`.

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

In addition to this, the Rack environment must include these
Rack-specific variables:

<dl>
    <dt>rack.version</dt>
    <dd>
       The Array [1,1], representing this version of Rack. 
    </dd>
    
    <dt>rack.url_scheme</dt>
    <dd>
        +http+ or +https+, depending on the request URL.
    </dd>

    <dt>rack.input</dt>
    <dd>
        See below, the input stream.
    </dd>

    <dt>rack.errors</dt>
    <dd>
        See below, the error stream.
    </dd>

    <dt>rack.multithread</dt>
    <dd>
        true if the application object may be simultaneously invoked 
        by another thread in the same process, false otherwise.
    </dd>

    <dt>rack.multiprocess</dt>
    <dd>
        true if an equivalent application object may be 
        simultaneously invoked by another process, false otherwise.
    </dd>

    <dt>rack.run_once</dt>
    <dd>
        true if the server expects (but does not guarantee!) that the 
        application will only be invoked this one time during the life 
        of its containing process. Normally, this will only be true 
        for a server based on CGI (or something similar).
        Additional environment specifications have approved to
        standardized middleware APIs.  None of these are required to
        be implemented by the server.
    </dd>
</dl>

The server or the application can store their own data in the
environment, too. The keys must contain at least one dot,
and should be prefixed uniquely. The prefix `rack.`
is reserved for use with the Rack core distribution and other
accepted specifications and must not be used otherwise.
The environment must not contain the keys
`HTTP_CONTENT_TYPE` or `HTTP_CONTENT_LENGTH`
(use the versions without `HTTP_`).

The CGI keys (named without a period) must have String values.
There are the following restrictions:

 * `rack.version` must be an array of Integers.
 * `rack.url_scheme` must either be `http` or `https`.
 * There must be a valid input stream in `rack.input`.
 * There must be a valid error stream in `rack.errors`.
 * The `REQUEST_METHOD` must be a valid token.
 * The `SCRIPT_NAME`, if non-empty, must start with `/`
 * The `PATH_INFO`, if non-empty, must start with `/`
 * The `CONTENT_LENGTH`, if given, must consist of digits only.
 * One of `SCRIPT_NAME` or `PATH_INFO` must be
   set.  `PATH_INFO` should be `/` if
   `SCRIPT_NAME` is empty.
   `SCRIPT_NAME` never should be `/`, but instead be empty.

### The Input Stream

The input stream is an IO-like object which contains the raw HTTP
POST data.
When applicable, its external encoding must be "ASCII-8BIT" and it
must be opened in binary mode, for Ruby 1.9 compatibility.
The input stream must respond to `gets`, `each`, `read` and `rewind`.

 * `gets` must be called without arguments and return a string,
   or `nil` on EOF.
 * `read` behaves like `IO#read`. Its signature is `read([length, [buffer]])`.
   If given, `length` must be a non-negative Integer (>= 0) or `nil`, and `buffer` must
   be a String and may not be nil. If `length` is given and not nil, then this method
   reads at most `length` bytes from the input stream. If `length` is not given or nil,
   then this method reads all data until EOF.
   When EOF is reached, this method returns nil if `length` is given and not `nil`, or `""`
   if `length` is not given or is nil.
   If `buffer` is given, then the read data will be placed into `buffer` instead of a
   newly created String object.
 * `each` must be called without arguments and only yield Strings.
 * `rewind` must be called without arguments. It rewinds the input
   stream back to the beginning. It must not raise Errno::ESPIPE:
   that is, it may not be a pipe or a socket. Therefore, handler
   developers must buffer the input data into some rewindable object
   if the underlying input stream is not rewindable.
 * `close` must never be called on the input stream.

### The Error Stream

The error stream must respond to `puts`, `write` and `flush`.

 * `puts` must be called with a single argument that responds to `to_s`.
 * `write` must be called with a single argument that is a String.
 * `flush` must be called without arguments and must be called
   in order to make the error appear for sure.
 * `close` must never be called on the error stream.

## The Response

### The Status

This is an HTTP status. When parsed as integer (`to_i`), it must be
greater than or equal to 100.

### The Headers

 * The header must respond to `each`, and yield values of key and value.
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

 * The Body must respond to `each`
   and must only yield String values.
 * The Body itself should not be an instance of String, as this will
   break in Ruby 1.9.
 * If the Body responds to `close`, it will be called after iteration.
 * If the Body responds to `to_path`, it must return a String
   identifying the location of a file whose contents are identical
   to that produced by calling `each`; this may be used by the
   server as an alternative, possibly more efficient way to
   transport the response.
 * The Body commonly is an Array of Strings, the application
   instance itself, or a File-like object.

## Thanks

Some parts of this specification are adopted from PEP333: Python
Web Server Gateway Interface
v1.0 (http://www.python.org/dev/peps/pep-0333/). I'd like to thank
everyone involved in that effort.
