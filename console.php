#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use JesusGoku\TheMovieDb\Application\TheMovieDbApplication;
use JesusGoku\TheMovieDb\Command as TheMovieDb;

$app = new TheMovieDbApplication();

// -- Movie Commands
$app->add(new TheMovieDb\MovieCommand());
$app->add(new TheMovieDb\MovieSearchCommand());

// -- TVShow Commands
$app->add(new TheMovieDb\TvShowSearchCommand());
$app->add(new TheMovieDb\TvShowEpisodeCommand());
$app->add(new TheMovieDb\TvShowCoveredCommand());

$app->run();
