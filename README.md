# Addvilz/pharaoh

Library to simplify .phar build scripts.

Usage:

Create a new file, for example, build.php:

```
#!/usr/bin/env php
<?php

$composer = (new \Symfony\Component\Finder\Finder())
    ->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->exclude(['test', 'tests', 'spec'])
    ->in(__DIR__ . '/vendor/');

$src = (new \Symfony\Component\Finder\Finder())
    ->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->in(__DIR__ . '/src/');

$builder = (new \Addvilz\Pharaoh\Builder('myapp.phar', __DIR__, __DIR__))
    ->addFinder($composer)
    ->addFinder($src)
//    ->addFile('LICENSE')
//    ->addFile('README.md')
    ->build('index.php') // file that contains the "index" code of your app
;
```