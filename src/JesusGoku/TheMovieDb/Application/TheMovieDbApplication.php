<?php

namespace JesusGoku\TheMovieDb\Application;

use JesusGoku\TheMovieDb\DependencyInjection\TheMovieDbConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Yaml;


/**
 * Class TheMovieDbApplication
 * @package JesusGoku\TheMovieDb\Application
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class TheMovieDbApplication extends Application
{
    /** @var array */
    private $config;

    /** @var array */
    private $theMovieDBConfig;

    /** @var array */
    private $theTVDBConfig;

    public function __construct($name = 'The Movie DB', $version = '0.1')
    {
        parent::__construct($name, $version);

        // -- Load config
        $config1 = Yaml::parse(__DIR__ . '/../Resources/config/config.yml');

        $configs = array($config1);

        $processor = new Processor();
        $configuration = new TheMovieDbConfiguration();
        $this->config = $processor->processConfiguration($configuration, $configs);

        $this->theMovieDBConfig = $this->config['themoviedb'];
        $this->theTVDBConfig = $this->config['thetvdb'];
    }

    /**
     * Return all config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Return config for The Movie DB API
     *
     * @return array
     */
    public function getTheMovieDbConfig()
    {
        return $this->theMovieDBConfig;
    }

    /**
     * Return config for The TV DB API
     *
     * @return array
     */
    public function getTheTvDbConfig()
    {
        return $this->theTVDBConfig;
    }
}
