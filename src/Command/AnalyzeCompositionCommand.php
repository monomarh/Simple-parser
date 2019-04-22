<?php

declare(strict_types = 1);

namespace App\Command;

use App\Service\Analyzer;
use Cowsayphp\Cow;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package App\Command
 */
class AnalyzeCompositionCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected static $defaultName = 'app:analyze_composition';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var Analyzer
     */
    private $analyzer;

    /**
     * @param Analyzer $analyzer
     */
    public function __construct(Analyzer $analyzer)
    {
        parent::__construct($name = null);

        $this->analyzer = $analyzer;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Analyzing products composition from website')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->analyzer->analyze();

        if ($result !== []) {
            $this->io->success(Cow::say(sprintf('Good analyzing %d products composition', $result)));
        } else {
            $this->io->error(Cow::say('Bad analyzing'));
        }
    }
}
