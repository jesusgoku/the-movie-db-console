<?php

namespace JesusGoku\TheMovieDb\Service;
use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;

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

        $this->initClient();

        return $this;
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

    /**
     * @inheritdoc
     */
    public function getMovieDetail($movie_id, $language = null, $options = null)
    {
        $attributes = array(
            'alternative_titles',
            'credits',
            'images',
            'keywords',
            'releases',
            'trailers',
            'translations',
            'similar_movies',
            'reviews',
            'lists',
            'changes',
        );

        $query = array(
            'api_key' => $this->api_key,
            'append_to_response' => implode(',', $attributes),
            'language' => null !== $language ? $language : $this->defaultLanguage,
        );

        if (null !== $options && is_array($options)) {
            if (isset($options['api_key'])) { unset($options['api_key']); }
            if (isset($options['language'])) { unset($options['language']); }
            $query = array_merge($query, $options);
        }

        $request = $this->client->get('movie/' . $movie_id, array(), array(
            'query' => $query,
        ));

        $response = $request->send();

        return $response->json();
    }
}
