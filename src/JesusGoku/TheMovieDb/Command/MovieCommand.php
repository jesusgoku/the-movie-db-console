<?php

namespace JesusGoku\TheMovieDb\Command;

use Guzzle\Http\Client;
use Guzzle\Http\StaticClient;
use JesusGoku\TheMovieDb\DependencyInjection\TheMovieDbConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class MovieCommand extends Command
{
    private $config;
    private $config_tmdb;
    private $options;

    private $output;

    protected function configure()
    {
        $this
            ->setName('themoviedb:movie')
            ->setAliases(array('tmdb:m'))
            ->setDescription('Download info from The Movie DB for your movies')
            ->addArgument(
                'files',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Movie files or folders'
            )
            ->addOption(
                'formats',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Format to scan directories',
                array('avi', 'mov', 'mp4', 'mkv')
            )
            ->addOption(
                'overwrite-xml',
                null,
                InputOption::VALUE_NONE,
                'Overwrite xml file'
            )
            ->addOption(
                'overwrite-image',
                null,
                InputOption::VALUE_NONE,
                'Overwrite image file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // -- Load parameters
        $filesInput = $input->getArgument('files');
        $formats = $input->getOption('formats');
        $this->options = array(
            'overwrite-xml' => $input->getOption('overwrite-xml'),
            'overwrite-image' => $input->getOption('overwrite-image'),
        );
        $this->output = $output;


        // -- Load config
        $config1 = Yaml::parse(__DIR__ . '/../Resources/config/config.yml');

        $configs = array($config1);

        $processor = new Processor();
        $configuration = new TheMovieDbConfiguration();
        $this->config = $processor->processConfiguration($configuration, $configs);

        // -- Load The Movie DB Configuration
        $client = new Client($this->config['api_base_url']);
        $request = $client->get('configuration', array(), array(
            'query' => array(
                'api_key' => $this->config['api_key'],
            )
        ));
        $this->config_tmdb = $request->send()->json();

        // -- Load files
        $files = $this->processFilesInput($filesInput, $formats);

        // -- Parse file names
        $data = $this->processFiles($files);

        // -- Search for files
        $data = $this->searchMovies($data);

        // -- Get movie details
        $data = $this->getMoviesDetails($data);

        // -- Make XML
        $this->makeXmlAndImage($data);
    }

    private function processFilesInput($filesInput, $formats)
    {
        $files = array();

        foreach ($filesInput as $item) {
            if (is_dir($item)) {
                $files = array_merge($files, $this->processFolder($item, $formats));
            } else {
                $files[] = $item;
            }
        }

        return $files;
    }

    private function processFolder($folderPath, $formats)
    {
        $finder = new Finder();
        $formatsString = implode('|', $formats);

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

    private function processFiles($files)
    {
        $data = array();

        foreach ($files as $item) {
            $fileInfo = pathinfo($item);

            if (!$this->options['overwrite-xml'] && file_exists($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.xml')) {
                continue;
            }

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

    private function searchMovies($data)
    {
        $client = new Client($this->config['api_base_url']);
        $found = array();
        foreach ($data as $k => $item) {
            $request = $client->get('search/movie', array(), array(
                'query' => array(
                    'api_key' => $this->config['api_key'],
                    'query' => $item['title'],
                    'year' => $item['year'],
                )
            ));

            $response = $request->send();
            $json = $response->json();

            if (0 < count($json['results'])) {
                $data[$k]['id'] = $json['results'][0]['id'];
                $data[$k]['release_date'] = $json['results'][0]['release_date'];
                $data[$k]['original_title'] = $json['results'][0]['original_title'];
                $found[$k] = $data[$k];
            } else {
                $this->output->writeln($item['title'] . ' (' . $item['year'] . ') not found.');
            }
        }

        return $found;
    }

    private function getMoviesDetails($data)
    {
        $client = new Client($this->config['api_base_url']);
        foreach ($data as $k => $item) {
            if (!isset($item['id'])) {
                continue;
            }

            $request = $client->get('movie/' . $item['id'], array(), array(
                'query' => array(
                    'api_key' => $this->config['api_key'],
                    'append_to_response' => 'alternative_titles,credits,images,keywords,releases,trailers,translations,similar_movies,reviews,lists,changes',
                )
            ));

            $movieRaw = $request->send()->json();

            if ('en' !== $this->config['language']) {
                $request = $client->get('movie/' . $item['id'], array(), array(
                    'query' => array(
                        'api_key' => $this->config['api_key'],
                        'language' => $this->config['language'],
                    )
                ));

                $movieLocale = $request->send()->json();

                $movieRaw['title'] = $movieLocale['title'];
                $movieRaw['overview'] = $movieLocale['overview'];
            }

            $data[$k] = array_merge($data[$k], $movieRaw);
        }

        return $data;
    }

    private function makeXmlAndImage($data)
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../Resources/views');
        $twig = new \Twig_Environment($loader, array(
            'cache' => __DIR__ . '/../../../../app/cache',
        ));

        foreach ($data as $item) {
            $xml = $twig->render('themoviedb_v2.xml.twig', array(
                'movie' => $item,
                'config' => $this->config,
                'config_tmdb' => $this->config_tmdb,
            ));

            $fileInfo = pathinfo($item['path']);

            // -- Save XML file
            file_put_contents($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.xml', $xml);
            // -- Save Image file
            if (!isset($item['poster_path'])) {
                continue;
            }
            $imagePath = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.jpg';
            if (!$this->options['overwrite-image'] && file_exists($imagePath)) {
                continue;
            }
            file_put_contents(
                $imagePath,
                fopen($this->config_tmdb['images']['base_url'] . $this->config['poster_size'] . $item['poster_path'], 'r')
            );
        }
    }
}
