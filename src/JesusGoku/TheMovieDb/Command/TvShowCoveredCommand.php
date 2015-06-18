<?php

namespace JesusGoku\TheMovieDb\Command;

use JesusGoku\TheMovieDb\Service\TheTvDbService;
use JesusGoku\TheMovieDb\Service\TvShowServiceInterface;
use JesusGoku\TheMovieDb\Util\FileSystemScan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TvShowCoveredCommand
 * @package JesusGoku\TheMovieDb\Command
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class TvShowCoveredCommand extends Command
{
    /** @var array */
    private $config;

    /** @var FileSystemScan */
    private $fileSystemScan;

    /** @var TvShowServiceInterface */
    private $tvShowService;

    protected function configure()
    {
        $this
            ->setName('tvshow:covered')
            ->setDescription('Make xml to episodes')
            ->addArgument(
                'files',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Files to covered'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // -- Load config
        $this->config = $this->getApplication()->getTheTvDbConfig();

        // -- Arguments
        $files = $input->getArgument('files');

        $this->fileSystemScan = new FileSystemScan();
        $this->tvShowService = new TheTvDbService($this->config['api_key'], $this->config['language']);

        foreach ($files as $item) {
            $data = $this->processFilesInput(array($item));
            $data = $this->fileSystemScan->extractTvShowInfo($data);

            if (empty($data)) { continue; }

            if (is_dir($item)) {
                $this->makeMetadataFolder($data);
            } else {
                $data = array_filter(
                    array_map(array($this, 'processTvShow'), $data),
                    function ($e) { return null !== $e; }
                );
                $this->makeMetadata($data);
            }
        }
    }

    private function processFilesInput($filesInput)
    {
        $files = array();

        foreach ($filesInput as $item) {
            if (is_dir($item)) {
                $files = array_merge($files, $this->fileSystemScan->findTvShows($item));
            } else {
                $files[] = $item;
            }
        }

        return $files;
    }

    private function processTvShow($tvShowData)
    {
        $tvShow = $this->tvShowService->search($tvShowData['tvShow']);

        if (empty($tvShow)) {
            return;
        }

        $tvShow = $this->tvShowService->getTvShow($tvShow[0]['id']);
        $banners = $this->tvShowService->getBanners($tvShow['id']);

        $episode = $this->tvShowService->getEpisodeByDefault(
            $tvShow['id'],
            $tvShowData['season'],
            $tvShowData['episode']
        );

        $banner = isset($banners[TheTvDbService::BANNER_TYPE_SEASON][$episode['seasonNumber']][0])
            ? $banners[TheTvDbService::BANNER_TYPE_SEASON][$episode['seasonNumber']][0]['path']
            : null;

        return array(
            'input' => $tvShowData,
            'tvShow' => $tvShow,
            'episode' => $episode,
            'banner' => $banner
        );
    }

    private function makeMetadata($tvShows)
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../Resources/views');
        $twig = new \Twig_Environment($loader, array(
            'cache' => __DIR__ . '/../../../../app/cache',
            'debug' => true,
        ));
        $twig->addFilter(new \Twig_SimpleFilter('addZero', array($this, 'addZero')));

        foreach ($tvShows as $item) {
            $xml = $twig->render('episode.xml.twig', $item);
            $fileInfo = pathinfo($item['input']['path']);
            file_put_contents("{$fileInfo['dirname']}/{$fileInfo['filename']}.xml", $xml);

            if (null !== $item['banner']) {
                $this->tvShowService->saveBanner(
                    $item['banner'],
                    "{$fileInfo['dirname']}/{$fileInfo['filename']}.jpg"
                );
            }
        }
    }

    private function makeMetadataFolder($tvShows)
    {
        $found = $this->tvShowService->search($tvShows[0]['tvShow']);
        if (empty($found)) {
            return;
        }

        $tvShow = $this->tvShowService->getAll($found[0]['id']);

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../Resources/views');
        $twig = new \Twig_Environment($loader, array(
            'cache' => __DIR__ . '/../../../../app/cache',
            'debug' => true,
        ));
        $twig->addFilter(new \Twig_SimpleFilter('addZero', array($this, 'addZero')));

        foreach ($tvShows as $item) {
            if (!isset($tvShow['episodes'][$item['season']][$item['episode']])) {
                continue;
            }

            $xml = $twig->render('episode.xml.twig', array(
                'input' => $item,
                'tvShow' => $tvShow['tvShow'],
                'episode' => $tvShow['episodes'][$item['season']][$item['episode']],
            ));
            $fileInfo = pathinfo($item['path']);
            file_put_contents("{$fileInfo['dirname']}/{$fileInfo['filename']}.xml", $xml);

            if (isset($tvShow['banners'][TheTvDbService::BANNER_TYPE_SEASON][$item['season']][0])) {
                $this->tvShowService->saveBanner(
                    $tvShow['banners'][TheTvDbService::BANNER_TYPE_SEASON][$item['season']][0]['path'],
                    "{$fileInfo['dirname']}/{$fileInfo['filename']}.jpg"
                );
            }
        }
    }

    public function addZero($num, $length = 2)
    {
        return str_pad($num, $length, '0', STR_PAD_LEFT);
    }
}
