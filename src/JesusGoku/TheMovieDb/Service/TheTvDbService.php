<?php

namespace JesusGoku\TheMovieDb\Service;
use Guzzle\Http\Client;


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

    /** @var string */
    private $defaultLanguage;

    public function __construct($api_key, $defaultLanguage = 'en')
    {
        $this->api_key = $api_key;

        $this->defaultLanguage = $defaultLanguage;

        $this->client = new Client($this->base_url);
    }

    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;

        $this->client = new Client($base_url);
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
        $request = $this->client->get('GetSeries.php', array(), array(
            'query' => array(
                'seriesname' => $tvShowName,
                'language' => null !== $language ? $language : $this->defaultLanguage,
            ),
        ));

        $response = $request->send();
        $xml = simplexml_load_string($response->getBody(true));

        $found = array();
        foreach ($xml->Series as $serie) {
            $item = array(
                'id' => (string) $serie->seriesid,
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
        $request = $this->client->get($url_path);

        $response = $request->send();
        $xml = simplexml_load_string($response->getBody(true));

        var_dump($xml);
    }
}
