<?php

namespace JesusGoku\TheMovieDb\Service;


/**
 * Class MovieServiceInterface
 * @package JesusGoku\TheMovieDb\Service
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
interface MovieServiceInterface
{
    /**
     * Search movie by name and year
     *
     * @param string $query
     * @param null|integer $year
     * @param null|string $language
     *
     * @return array
     */
    public function search($query, $year = null, $language = null);
}
