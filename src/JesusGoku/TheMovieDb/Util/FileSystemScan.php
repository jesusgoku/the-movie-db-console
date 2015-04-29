<?php

namespace JesusGoku\TheMovieDb\Util;

/**
 * Class FileSystemUtil
 * @package JesusGoku\TheMovieDb\Util
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class FileSystemScan
{
    /** @var array */
    private $config;

    /** @var array */
    private $defaults = array(
        'extensions' => array('mp4', 'avi', 'mkv', 'mov', 'webm'),
    );

    public function __construct($options = array())
    {
        $this->config = array_merge($this->defaults, $options);
    }

    public function findMovies($folderPath)
    {

    }

    public function extractMovieInfo()
    {

    }

    public function findTvShows()
    {

    }
}