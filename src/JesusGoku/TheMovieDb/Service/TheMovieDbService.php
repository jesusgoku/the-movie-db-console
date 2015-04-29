<?php

namespace JesusGoku\TheMovieDb\Service;
use Guzzle\Http\Client;

/**
 * Class TheMovieDbService
 * @package JesusGoku\TheMovieDb\Service
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class TheMovieDbService implements MovieServiceInterface
{
    /** @var string */
    private $api_key;

    /** @var string */
    private $base_url = 'https://api.themoviedb.org/3';

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

    /**
     * Set Base url por request API
     *
     * @param $base_url
     *
     * @return $this
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function search($query, $year = null, $language = null)
    {
        $query = array(
            'api_key' => $this->api_key,
            'query' => $query,
            'language' => (null !== $language && 2 === strlen($language)) ? $language : $this->defaultLanguage,
        );

        if (null !== $year && preg_match('/^\d{4}$/', $year)) { $query['year'] = $year; }

        $request = $this->client->get('search/movie', array(), array(
            'query' => $query,
        ));
        $response = $request->send();
        $result = $response->json();

        return $result['results'];
    }
}
