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

    /**
     * Search movie files on folder
     *
     * @param string $folderPath
     *
     * @return array
     */
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

    /**
     * Process file names to extract information (Title and Year)
     *
     * @param array $files
     *
     * @return array
     */
    public function extractMoviesInfo($files)
    {
        $data = array();

        foreach ($files as $item) {
            $fileInfo = pathinfo($item);

            $matches = array();
            if (!preg_match('/^(.+)\.(\d{4})\..+$/', $fileInfo['basename'], $matches)) {
                continue;
            }

            $data[] = array(
                'title' => str_replace('.', ' ', $matches[1]),
                'year' => $matches[2],
                'prev_title' => $fileInfo['basename'],
                'path' => $item,
            );
        }

        return $data;
    }

    public function findTvShows($folderPath)
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
}
