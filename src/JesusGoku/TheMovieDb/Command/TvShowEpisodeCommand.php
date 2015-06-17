<?php

namespace JesusGoku\TheMovieDb\Command;

use JesusGoku\TheMovieDb\Service\TheTvDbService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class TvShowEpisodeCommand
 * @package JesusGoku\TheMovieDb\Command
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class TvShowEpisodeCommand extends Command
{
    private $config;

    protected function configure()
    {
        $this
            ->setName('tvshow:episode')
            ->setDescription('Get info for episode')
            ->addArgument('tvShowId', InputArgument::REQUIRED, 'The TVDB TV Show ID', null)
            ->addArgument('season', InputArgument::REQUIRED, 'Season number', null)
            ->addArgument('episode', InputArgument::REQUIRED, 'Episode number', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // -- Load config
        $this->config = $this->getApplication()->getTheTvDbConfig();

        // -- Arguments
        $tvShowId = $input->getArgument('tvShowId');
        $season = $input->getArgument('season');
        $episode = $input->getArgument('episode');

        $tvShowService = new TheTvDbService($this->config['api_key'], $this->config['language']);

        $episode = $tvShowService->getEpisodeByDefault($tvShowId, $season, $episode);

        /** @var Table $table */
        $table = $this->getHelper('table');

        $tableData = array();
        foreach ($episode as $k => $j) {
            if (is_array($j)) {
                $j = implode(', ', $j);
            }
            $tableData[] = array(
                $k,
                (strlen($j) > 58 ? rtrim(substr($j, 0, 55)) . '...' : $j),
            );
        }

        $table
            ->setHeaders(array('Field', 'Value'))
            ->setRows($tableData)
        ;

        $table->render($output);
    }
}
