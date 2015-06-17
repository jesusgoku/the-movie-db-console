<?php

namespace JesusGoku\TheMovieDb\Service;


/**
 * Class TvShowServiceInterface
 * @package JesusGoku\TheMovieDb\Service
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
interface TvShowServiceInterface
{
    /**
     * Search tv show by name
     *
     * @param string $tvShowName
     * @param null|string $language
     *
     * @return array
     */
    public function search($tvShowName, $language = null);

    /**
     * Get detail about a tv show episode
     *
     * @param string $episodeId
     * @param null|string $language
     *
     * @return array
     */
    public function getEpisode($episodeId, $language = null);

    /**
     * Get detail about a tv show episode by default method
     * @param int $tvShowId
     * @param int $season
     * @param int $episode
     * @param string|null $language
     * @return array
     */
    public function getEpisodeByDefault($tvShowId, $season, $episode, $language = null);
}
