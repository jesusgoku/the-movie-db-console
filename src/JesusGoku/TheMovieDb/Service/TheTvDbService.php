<?php

namespace JesusGoku\TheMovieDb\Service;
use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;


/**
 * Class TheTvDbService
 * @package JesusGoku\TheMovieDb\Service
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class TheTvDbService implements TvShowServiceInterface
{
    const BANNER_TYPE_FANART = 'fanart';
    const BANNER_TYPE_POSTER = 'poster';
    const BANNER_TYPE_SERIES = 'series';
    const BANNER_TYPE_SEASON = 'season';

    /** @var string */
    private $apiKey;

    /** @var string */
    private $baseUrl = 'http://thetvdb.com/api';

    /** @var string */
    private $baseBannerUrl = 'http://thetvdb.com/banners/';

    /** @var Client */
    private $client;

    /** @var CachePlugin */
    private $cachePlugin;

    /** @var string */
    private $defaultLanguage;

    public function __construct($apiKey, $defaultLanguage = 'en')
    {
        $this->apiKey = $apiKey;

        $this->defaultLanguage = $defaultLanguage;

        $this->initCachePlugin();
        $this->initClient();
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        $this->initClient();
    }

    private function initClient()
    {
        $this->client = new Client($this->baseUrl);
        $this->client->addSubscriber($this->cachePlugin);
    }

    private function initCachePlugin()
    {
        $this->cachePlugin = new CachePlugin(array(
            'storage' => new DefaultCacheStorage(new FilesystemCache(sys_get_temp_dir())),
        ));
    }

    public function setDefaultLanguage($language)
    {
        $this->defaultLanguage = $language;
    }

    /**
     * @inheritdoc
     */
    public function search($tvShowName, $language = null)
    {
        $req = $this->client->get('GetSeries.php', array(), array(
            'query' => array(
                'seriesname' => $tvShowName,
                'language' => null !== $language ? $language : $this->defaultLanguage,
            ),
        ));

        $res = $req->send();
        $xml = $res->xml();

        $found = array();
        foreach ($xml->Series as $serie) {
            $item = array(
                'id' => (int) $serie->seriesid,
                'name' => (string) $serie->SeriesName,
                'overview' => (string) $serie->Overview,
                'banner' => (string) $serie->banner,
            );
            $found[] = $item;
        }

        return $found;
    }

    /**
     * @inheritdoc
     */
    public function getEpisode($episodeId, $language = null)
    {
        $url_path = ':apiKey/episodes/:episodeId/:language.xml';
        $url_path = str_replace(
            array(':apiKey', ':episodeId', ':language'),
            array(
                $this->apiKey,
                $episodeId,
                (null !== $language ? $language : $this->defaultLanguage)
            ),
            $url_path
        );
        $req = $this->client->get($url_path);
        $res = $req->send();
        $xml = $res->xml();

        return $this->processEpisode($xml->Episode);
    }

    /**
     * @inheritdoc
     */
    public function getEpisodeByDefault($tvShowId, $season, $episode, $language = null)
    {
        $url_path = ':apiKey/series/:tvShowId/default/:season/:episode/:language.xml';
        $url_path = str_replace(
            array(':apiKey', ':tvShowId', ':season', ':episode', ':language'),
            array(
                $this->apiKey,
                $tvShowId,
                $season,
                $episode,
                (null !== $language ? $language : $this->defaultLanguage)
            ),
            $url_path
        );
        $req = $this->client->get($url_path);
        $res = $req->send();
        $xml = $res->xml();

        return $this->processEpisode($xml->Episode);
    }

    public function getTvShow($tvShowId, $language = null)
    {
        $url_path = ':apiKey/series/:tvShowId/:language.xml';
        $url_path = str_replace(
            array(':apiKey', ':tvShowId', ':language'),
            array(
                $this->apiKey,
                $tvShowId,
                (null !== $language ? $language : $this->defaultLanguage)
            ),
            $url_path
        );
        $res = $this->client->get($url_path)->send();
        $xml = $res->xml();

        return $this->processTvShow($xml->Series);
    }

    public function getBanners($tvShowId)
    {
        $url_path = ':apiKey/series/:tvShowId/banners.xml';
        $url_path = str_replace(
            array(':apiKey', ':tvShowId', ':language'),
            array(
                $this->apiKey,
                $tvShowId
            ),
            $url_path
        );
        $res = $this->client->get($url_path)->send();
        $xml = $res->xml();

        $banners = array_map(array($this, 'processBanner'), iterator_to_array($xml->Banner, false));

        return $banners;
    }

    public function getActors($tvShowId)
    {
        $url_path = ':apiKey/series/:tvShowId/actors.xml';
        $url_path = str_replace(
            array(':apiKey', ':tvShowId', ':language'),
            array(
                $this->apiKey,
                $tvShowId
            ),
            $url_path
        );
        $res = $this->client->get($url_path)->send();
        $xml = $res->xml();

        $actors = array_map(array($this, 'processActor'), iterator_to_array($xml->Actor, false));

        return $actors;
    }

    public function saveBanner($path, $dest)
    {
        $dirName = dirname($dest);

        if (!file_exists($dirName) || !is_writable($dirName)) {
            throw new \InvalidArgumentException('Destination folder is not exist or is not writable');
        }

        $this->client
            ->get(
                $this->bannerPath($path),
                array(),
                array('save_to' => $dest)
            )
            ->send()
        ;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     */
    private function processTvShow(\SimpleXMLElement $xml)
    {
        return array(
            'id' => (int) $xml->id,
            'name' => (string) $xml->SeriesName,
            'overview' => (string) $xml->Overview,
            'firstAired' => (string) $xml->FirstAired,
            'actors' => $this->processPipeDelimited((string) $xml->Actors),
            'genres' => $this->processPipeDelimited((string) $xml->Genre),
            'language' => (string) $xml->Language,
            'imdbId' => (string) $xml->IMDB_ID,
            'contentRating' => (string) $xml->ContentRating,
            'rating' => (float) $xml->Rating,
            'ratingCount' => (int) $xml->RatingCount,
            'runtime' => (int) $xml->Runtime,
            'airsDayOfWeek' => (string) $xml->Airs_DayOfWeek,
            'airsTime' => (string) $xml->Airs_Time,
            'banner' => (string) $xml->banner,
            'fanart' => (string) $xml->fanart,
            'poster' => (string) $xml->poster,
        );
    }

    /**
     * Process Episode xml element
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    private function processEpisode(\SimpleXMLElement $xml)
    {
        return array(
            'id' => (int) $xml->id,
            'name' => (string) $xml->EpisodeName,
            'overview' => (string) $xml->Overview,
            'firstAired' => (string) $xml->FirstAired,
            'director' => (string) $xml->Director,
            'guestStars' => $this->processPipeDelimited((string) $xml->GuestStars),
            'episodeNumber' => (int) $xml->EpisodeNumber,
            'seasonNumber' => (int) $xml->SeasonNumber,
            'dvdEpisodeNumber' => (int) $xml->DVD_episodenumber,
            'dvdSeasonNumber' => (int) $xml->DVD_season,
            'absoluteNumber' => (int) $xml->absolute_number,
            'language' => (string) $xml->Language,
            'rating' => (float) $xml->Rating,
            'ratingCount' => (int) $xml->RatingCount,
            'writer' => $this->processPipeDelimited((string) $xml->Writer),
            'thumb' => (string) $xml->filename,
        );
    }

    /**
     * Process Banner xml element
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    private function processBanner(\SimpleXMLElement $xml)
    {
        $banner = array(
            'id' => (int) $xml->id,
            'path' => (string) $xml->BannerPath,
            'type' => (string) $xml->BannerType, // poster, fanart, series, season
            'type2' => (string) $xml->BannerType2, // Series: text, graphical, blank, Season: season, seasonwide, Fanart: 1280x720, 1920x1080, Poster: 680x1000
            'language' => (string) $xml->Language,
            'season' => (int) $xml->Season,
            'rating' => (float) $xml->Rating,
            'ratingCount' => (int) $xml->RatingCount,
        );

        if (self::BANNER_TYPE_FANART === $banner['type']) {
            if (isset($xml->Colors)) {
                $banner['colors'] = array_map(
                    array($this, 'processColor'),
                    $this->processPipeDelimited((string)$xml->Colors)
                );
            }

            $banner['thumbnailPath'] = (string) $xml->ThumbnailPath;
            $banner['vignettePath'] = (string) $xml->VignettePath;
            $banner['seriesName'] = (bool) $xml->SeriesName;
        }

        return $banner;
    }

    /**
     * Process Actor xml element
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    private function processActor(\SimpleXMLElement $xml)
    {
        return array(
            'id' => (int) $xml->id,
            'image' => (string) $xml->Image, // 300x450
            'name' => (string) $xml->Name,
            'role' => (string) $xml->Role,
            'sortOrder' =>(string) $xml->SortOrder,
        );
    }

    /**
     * Process Color rgb element
     *
     * @param $color
     * @return array
     */
    private function processColor($color)
    {
        return array_map('intval', explode(',', $color));
    }

    /**
     * Process Pipe delimited string
     *
     * @param $str
     * @return array
     */
    private function processPipeDelimited($str)
    {
        return explode('|', trim($str, '|'));
    }

    private function bannerPath($path)
    {
        return $this->baseBannerUrl . $path;
    }
}
