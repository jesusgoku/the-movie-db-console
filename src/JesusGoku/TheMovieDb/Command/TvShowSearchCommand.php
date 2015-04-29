<?php

namespace JesusGoku\TheMovieDb\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use JesusGoku\TheMovieDb\Service\TheTvDbService;
use JesusGoku\TheMovieDb\DependencyInjection\TheMovieDbConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;



/**
 * Class TvShowCommand
 * @package JesusGoku\TheMovieDb\Command
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class TvShowSearchCommand extends Command
{
    /** @var array */
    private $config;

    protected function configure()
    {
        $this
            ->setName('tvshow:search')
            ->setDescription('Search for TV Show')
            ->addArgument('query', InputArgument::REQUIRED, 'Series name to search')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // -- Load config
        $this->config = $this->getApplication()->getTheTvDbConfig();

        $tvShowService = new TheTvDbService($this->config['api_key'], $this->config['language']);

        $found = $tvShowService->search($input->getArgument('query'), 'es');

        /** @var Table $table */
        $table = $this->getHelper('table');

        $tableData = array();
        foreach ($found as $item) {
            $tableData[] = array(
                $item['id'],
                substr($item['name'], 0, 20),
                rtrim(substr($item['overview'], 0, 35)) . '...',
            );
        }

        $table
            ->setHeaders(array('ID', 'Title', 'Overview'))
            ->setRows($tableData)
        ;

        $table->render($output);
    }

}
