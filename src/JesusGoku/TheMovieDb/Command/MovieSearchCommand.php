<?php

namespace JesusGoku\TheMovieDb\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use JesusGoku\TheMovieDb\Service\TheMovieDbService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use JesusGoku\TheMovieDb\DependencyInjection\TheMovieDbConfiguration;


/**
 * Class MovieSearchCommand
 * @package JesusGoku\TheMovieDb\Command
 *
 * @author Jesus Urrutia <jesus.urrutia@gmail.com>
 */
class MovieSearchCommand extends Command
{
    /** @var array */
    private $config;

    protected function configure()
    {
        $this
            ->setName('movie:search')
            ->setDescription('Search movie')
            ->addArgument('query', InputArgument::REQUIRED, 'Movie query to search')
            ->addOption('year', 'y', InputOption::VALUE_REQUIRED, 'Year of release movie')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Language to result (en, es)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // -- Load config
        $this->config = $this->getApplication()->getTheMovieDbConfig();

        // -- Search Movie
        $movieService = new TheMovieDbService($this->config['api_key']);
        $result = $movieService->search(
            $input->getArgument('query'),
            $input->getOption('year'),
            $input->getOption('language')
        );

        /** @var Table $table */
        $table = $this->getHelper('table');

        $tableData = array();
        foreach ($result as $item) {
            $tableData[] = array(
                $item['id'],
                $item['title'],
                $item['release_date'],
            );
        }

        $table
            ->setHeaders(array('ID', 'Title', 'Release Date'))
            ->setRows($tableData)
        ;

        $table->render($output);
    }
}
