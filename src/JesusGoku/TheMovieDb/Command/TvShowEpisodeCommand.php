<?php

namespace JesusGoku\TheMovieDb\Command;

use Symfony\Component\Console\Command\Command;
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
