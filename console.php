#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use JesusGoku\TheMovieDb\Command as TheMovieDb;

$app = new \Symfony\Component\Console\Application();

$app->add(new TheMovieDb\MovieCommand());

$app->run();