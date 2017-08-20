<?php

namespace JesusGoku\TheMovieDb\Command;

use Guzzle\Http\Client;
use JesusGoku\TheMovieDb\Service\TheMovieDbService;
use JesusGoku\TheMovieDb\Util\FileSystemScan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class MovieCommand extends Command
{
    /** @var array */
    private $config;

    /** @var array */
    private $config_tmdb;

    /** @var array */
    private $options;

    /** @var OutputInterface */
    private $output;

    /** @var FileSystemScan */
    private $fileSystemScan;

    /** @var TheMovieDbService */
    private $tmdbService;

    protected function configure()
    {
        $this
            ->setName('movie:covered')
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
            ->addOption(
                'kodi-only',
                null,
                InputOption::VALUE_NONE,
                'Generate Kodi only'
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
            'kodi-only' => $input->getOption('kodi-only'),
        );
        $this->output = $output;


        // -- Load config
        $this->config = $this->getApplication()->getTheMovieDbConfig();

        // -- Load The Movie DB Configuration
        $client = new Client($this->config['api_base_url']);
        $request = $client->get('configuration', array(), array(
            'query' => array(
                'api_key' => $this->config['api_key'],
            )
        ));
        $this->config_tmdb = $request->send()->json();

        // -- Load utils
        $this->fileSystemScan = new FileSystemScan(array(
            'extensions' => $formats,
        ));
        // -- TheMovieDBService
        $this->tmdbService = new TheMovieDbService(
            $this->config['api_key'],
            $this->config['language']
        );

        // -- Load files
        $files = $this->processFilesInput($filesInput, $formats);

        // -- Parse file names
        $data = $this->fileSystemScan->extractMoviesInfo($files);

        // -- Search for files
        $data = $this->searchMovies($data);

        // -- Get movie details
        $data = $this->getMoviesDetails($data);

        // -- Make XML
        $this->makeXmlAndImage($data);
    }

    private function processFilesInput($filesInput)
    {
        $files = array();

        foreach ($filesInput as $item) {
            if (is_dir($item)) {
                $files = array_merge($files, $this->fileSystemScan->findMovies($item));
            } else {
                $files[] = $item;
            }
        }

        return $files;
    }

    private function searchMovies($data)
    {
        $found = array();
        foreach ($data as $k => $item) {
            usleep(300000);  // -- Prevent rate limit (40 request / 10 seconds)
            // -- Verify if movie has metadata
            $fileInfo = pathinfo($item['path']);
            if (!$this->options['overwrite-xml'] && file_exists($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.xml')) {
                continue;
            }

            $result = $this->tmdbService->search($item['title'], $item['year']);
            if (!empty($result)) {
                $temp = array(
                    'id' => $result[0]['id'],
                    'release_date' => $result[0]['release_date'],
                    'original_title' => $result[0]['original_title'],
                );

                $found[$k] = array_merge($data[$k], $temp);
            } else {
                $this->output->writeln($item['title'] . ' (' . $item['year'] . ') not found.');
            }
        }

        return $found;
    }

    private function getMoviesDetails($data)
    {
        foreach ($data as $k => $item) {
            usleep(300000);  // -- Prevent rate limit (40 request / 10 seconds)
            if (!isset($item['id'])) {
                continue;
            }
            $movieRaw = $this->tmdbService->getMovieDetail($item['id'], 'en');

            if ('en' !== $this->config['language']) {
                $movieLocale = $this->tmdbService->getMovieDetail($item['id'], null, array(
                    'append_to_response' => '',
                ));

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
            $fileInfo = pathinfo($item['path']);

            // -- Save XML file
            if (!$this->options['kodi-only']) {
                $xml = $twig->render('themoviedb_v2.xml.twig', array(
                    'movie' => $item,
                    'config' => $this->config,
                    'config_tmdb' => $this->config_tmdb,
                ));
                file_put_contents($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.xml', $xml);
            }

            $kodiNfo = $twig->render('kodi_movie.nfo.twig', array(
                'movie' => $item,
                'config' => $this->config,
                'config_tmdb' => $this->config_tmdb,
            ));
            file_put_contents($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.nfo', $kodiNfo);


//            file_put_contents($fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.json', json_encode($item, JSON_PRETTY_PRINT));

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
