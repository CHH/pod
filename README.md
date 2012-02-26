Pod, a simple Web Server Interface for PHP [WIP]
------------------------------------------------

> Show me the source code!
> -- You, just now.

No. There is no source code yet. I'm practicing Readme Driven
Development here. But don't turn away! I really appreciate your
Feedback on this, so please stay, read it and tell me your feedback
to [@yuri41](http://twitter.com/yuri41) or submitting
an Issue in the [Issue Tracker](http://github.com/CHH/Pod/issues).

---

> Pod &mdash; The socket for holding the bit in a boring tool.
> &mdash; [Wordnik](http://www.wordnik.com/words/pod)

Pod is a simple interface between the PHP Application and the Web
Server. Pod is a port of the [Rack Specification][rack_spec], with
some things done the "PHP way".

[rack_spec]: http://rack.rubyforge.org/doc/SPEC.html 

Pod's mission is to make applications truly agnostic of the
web server they run on and provide a unifed interface between
traditional ways of running PHP web applications (Apache, FCGI) _and_
Application Servers.

## Install

Pod is installed via [Composer](https://github.com/composer/composer).
To install it add this to your `composer.json`:

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
                $env['CONTENT_TYPE'] = $this->formats[$format];
            }
            return true;
        }
    }

Then register it in the chain:

    # pod_config.php
    <?php

    require_once(__DIR__."/FormatOverride.php");

    $this->register(new FormatOverride);

### Connecting to an Application Server

Handlers should implement a `run` method which takes any Pod callback.
The `run` method should provide the initial environment and build up a
HTTP response from the response array returned by the callback.
