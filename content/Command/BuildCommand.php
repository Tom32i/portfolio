<?php

namespace Content\Command;

use Exception;
use App\Kernel;
use Content\EventListener\SitemapListener;
use Content\Builder;
use Content\Builder\Sitemap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build Command
 */
class BuildCommand extends Command
{
    protected static $defaultName = 'content:build';

    /**
     * Static site builder
     *
     * @var Builder
     */
    private $builder;

    /**
     * Sitemap (optional)
     *
     * @var Sitemap
     */
    private $sitemap;

    public function __construct()
    {
        parent::__construct();

        $httpKernel = new Kernel('production', true);

        $httpKernel->boot();

        $this->builder = $httpKernel->getContainer()->get(Builder::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Build static website')
            ->setHelp('...')
            ->addArgument(
                'destination',
                InputArgument::OPTIONAL,
                'Full path to destination directory'
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'What should be used as domain name for absolute url generation?'
            )
            ->addOption(
                'scheme',
                null,
                InputOption::VALUE_REQUIRED,
                'What should be used as scheme for absolute url generation?'
            )
            ->addOption(
                'no-sitemap',
                null,
                InputOption::VALUE_NONE,
                'Don\'t build the sitemap'
            )
            ->addOption(
                'no-expose',
                null,
                InputOption::VALUE_NONE,
                'Don\'t expose the public directory after build'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($destination = $input->getArgument('destination')) {
            $this->builder->setDestination($destination);
        }

        if ($host = $input->getOption('host')) {
            $this->builder->setHost($host);
        }

        if ($scheme = $input->getOption('scheme')) {
            $this->builder->setScheme($scheme);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Building'));

        $this->builder->build(!$input->getOption('no-sitemap'), !$input->getOption('no-expose'));
    }
}
