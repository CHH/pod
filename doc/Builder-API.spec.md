
# Builder API Ideas

## Return Middleware Chain as Array

    # pod_config.php
    <?php

    use Pod\Component\UrlMapper,
        Pod\Component\FormatOverride;

    return [
        new FormatOverride,
        new UrlMapper([
            "{^/blog/?(.*)}" => new BlogApplication,
            "{^/hello/?(.*)}" => new HelloWorldApplication
        ]),
        "defaultApp"
    ];

Pro:

 * Makes order of middleware components within the chain visible.

Con:

 * App could be added in the middle of the chain, which means
   middleware after the app will not be called. Major WTF.

## Builder Instance as $this

    # pod_config.php
    <?php

    use Pod\Component\UrlMapper,
        Pod\Component\FormatOverride;

    $this->register(new FormatOverride);

    $this->register(new UrlMapper([
        "{^/blog/?(.*)}" => new BlogApplication,
        "{^/hello/?(.*)}" => new HelloWorldApplication
    ]);

    # This app is called after all middleware components.
    $this->run("fooApplication");
