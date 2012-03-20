<?php

$classLoader = require(dirname(__DIR__)."/vendor/.composer/autoload.php");

$classLoader->register("Pod\\Test", __DIR__);
