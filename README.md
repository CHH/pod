Pod, a simple Web Server Interface for PHP [WIP]
------------------------------------------------

> Show me the source code!
>
> &mdash; You, just now.

No. There is no source code yet. I'm practicing Readme Driven
Development here. But don't turn away! I really appreciate your
Feedback on this, so please stay, read it and send your feedback
to [@yuri41](http://twitter.com/yuri41) or submit
an Issue to the [Issue Tracker](http://github.com/CHH/Pod/issues).

Everything is up for discussion (including the name)!

* * *

> Pod, the socket for holding the bit in a boring tool.
>
> &mdash; [Wordnik](http://www.wordnik.com/words/pod)

Pod is a simple interface between the PHP Application and the Web
Server. Pod is a port of the [Rack Specification][rack_spec], with
some things done the "PHP way".

* * *

I want to thank Christian Neukirchen for the [Rack
Specification][rack] and the Python Community for
[PEP 333][] (and [PEP 3333][]). These are _great_ examples
of __very well written__ Specs and were a big help and inspiration.

[rack]: http://rack.rubyforge.org/doc/SPEC.html 
[pep 333]: http://www.python.org/dev/peps/pep-0333/
[pep 3333]: http://www.python.org/dev/peps/pep-3333/

## Mission

 * Pod aims to provide a simple specification 
   for an easy to implement interface between PHP 
   applications and servers.
 * Provide an interface common to all web frameworks.
 * Provide an interface for middleware components which can be shared
   between web frameworks.
 * A common library which handles web server inconsistencies via
   Handlers.

### Why $\_SERVER is flawed

The `$_SERVER` variable has one big flaw: it's entirely up to the server
which keys exist and what the values contain.

> $_SERVER is an array containing information such as headers, paths, and script locations. 
> The entries in this array are created by the web server. There is no guarantee that 
> every web server will provide any of these; servers may omit some, 
> or provide others not listed here.
>
> &mdash; <http://www.php.net/$_SERVER>

Most of `$_SERVER` is taken from the CGI Specification though, but 
there ain't any obligatory contract, which **specifies** what must be there
and what values the application can expect.

### Benefits for Web Server Authors

 * A well though-out and tried Spec, which is also successful in other
   languages, like Ruby, Python and Perl.
 * Simple to implement, the environment is a simple PHP array,
   containing key value pairs.
 * Makes more efficient implementations possible, because the
   application can be loaded once and then the callback can be used
   to pass requests to the application.

### Benefits for Application Authors

 * Provides consistent request data, independent from the underlying Web
   Server.
 * Enables framework independent middleware components which 
   can be used to share common components between
   multiple PHP web applications, for example Routing, Mounting of
   multiple applications, Session handling and much more.
 * Enables mending of multiple applications without server
   configuration.

### Benefits for Framework Authors

 * Framework authors can focus on whatever features which make
   their framework special, instead of writing the same dumb
   workarounds for different web servers over and over again.
 * Common interface to share HTTP components (e.g. Routers) with other
   frameworks.

## Install

Pod is installed via [Composer](https://github.com/composer/composer).
To install it, add this to your `composer.json`:

    # composer.json
    {
        "require": {
            "chh/pod": ">=1.0.0,<2.0.0dev"
        }
    }

Then run in your project directory:

    # If you don't have composer already:
    % wget http://getcomposer.org/composer.phar

    # Download into the "vendor/" directory
    % php composer.phar install

## Usage

Like Rack, Pod distinguishes between two kinds of components:

 * __Apps:__ do the hard stuff.
 * __Middleware:__ Sit in a chain and modify the Pod Environment,
   _before_ it reaches the Application. Execution stops when a
   Middleware component returns a response.

Each component in Pod follows a simple interface: __the Callback__. This
means that they can be implemented by:

 * named Functions
 * anonymous Functions
 * Classes implementing `__invoke()`

Each callback takes exactly one argument (the environment) and returns
an array of _three_ elements:

 1. `status`, HTTP status code as Integer.
 2. `headers`, Array of `header => value` pairs.
 3. `body`, the response body as Array of Strings, Resource or Iterator.

A very minimal application could look like this:

    # app.php
    <?php

    function helloWorldApp(&$env)
    {
        return array(200, array(), array("<h1>Hello World</h1>"));
    }

To serve this application the traditional way, put this into your
`index.php`:

    # index.php
    <?php

    $pod = Pod\Builder::load("pod_config.php");
    # Serves the App as normal PHP app.
    $pod->launch();
    
    # pod_config.php
    <?php

    require_once(__DIR__."/app.php");

    $this->run("helloWorldApp");

Pod will then call your app and send your response back as HTTP
response.

Middleware components follow the same interface as Applications,
the only difference is that they sit in a chain in front of the
application.

A simple middleware which overrides the `Content-Type` with
the format in a `format` parameter could look like this:

    # FormatOverride.php
    <?php

    class FormatOverride
    {
        var $formats = array(
            'html' => 'text/html',
            'json' => 'application/json',
            'xml'  => 'application/xml'
        );

        function __invoke(&$env) 
        {
            $query = $env['QUERY_STRING'];
            parse_str($query, $params);

            if ($format = @$params['format'] and isset($this->formats[$format])) {
                $env['HTTP_CONTENT_TYPE'] = $this->formats[$format];
            }
        }
    }

Then register it in the chain:

    # pod_config.php
    <?php

    require_once(__DIR__."/FormatOverride.php");

    $this->register(new FormatOverride);

## Servers

Servers should provide a **Handler** to interface with applications.

Handlers should implement a `run` method which takes any Pod callback.
The `run` method should provide the initial environment, invoke the
callback and send a HTTP response back to the client.

