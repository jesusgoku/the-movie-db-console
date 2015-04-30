<?php

namespace JesusGoku\TheMovieDb\Util;

use Symfony\Component\Finder\Finder;

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
        $finder = new Finder();
        $formatsString = implode('|', $this->config['extensions']);

        $finder
            ->files()
            ->name('/\.(?:' . $formatsString  . ')$/')
            ->depth(0)
            ->in($folderPath)
        ;


        $files = array();
        foreach ($finder as $item) {
            $files[] = $item->getRealpath();
        }

        return $files;
    }

    public function extractMovieInfo()
    {

    }

    public function findTvShows()
    {

    }
}
