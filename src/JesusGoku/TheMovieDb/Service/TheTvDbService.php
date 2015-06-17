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
    /** @var string */
    private $api_key;

    /** @var string */
    private $base_url = 'http://thetvdb.com/api';

    /** @var Client */
    private $client;

    /** @var CachePlugin */
    private $cachePlugin;

    /** @var string */
    private $defaultLanguage;

    public function __construct($api_key, $defaultLanguage = 'en')
    {
        $this->api_key = $api_key;

        $this->defaultLanguage = $defaultLanguage;

        $this->initCachePlugin();
        $this->initClient();
    }

    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;

        $this->initClient();
    }

    private function initClient()
    {
        $this->client = new Client($this->base_url);
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
                $this->api_key,
                $episodeId,
                (null !== $language ? $language : $this->defaultLanguage)
            ),
            $url_path
        );
        $req = $this->client->get($url_path);

        $res = $req->send();
        $xml = $res->xml();

        var_dump($xml);
    }

    /**
     * @inheritdoc
     */
    public function getEpisodeByDefault($tvShowId, $season, $episode, $language = null)
    {
        $url_path = ':apiKey/series/:tvShowId/default/:season/:episode/:language.xml';
        $url_path = str_replace(
            array('apiKey', 'tvShowId', 'season', 'episode', 'language'),
            array(
                $this->api_key,
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

        var_dump($xml);
    }
}
