<?php

declare(strict_types = 1);

namespace App\Command;

use App\Service\Seeker;
use Cowsayphp\Cow;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package App\Command
 */
class SearchCompositionCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected static $defaultName = 'app:search_composition';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var Seeker
     */
    private $seekerComposition;

    /**
     * @param Seeker $seeker
     */
    public function __construct(Seeker $seeker)
    {
        parent::__construct($name = null);

        $this->seekerComposition = $seeker;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Search products composition from database')
        ;
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->seekerComposition->startSearching();

        $countOfGoodSearchComposition = $this->seekerComposition->getInfoAboutSearching();

        if ($countOfGoodSearchComposition !== 0) {
            $this->io->success(Cow::say(
                sprintf('Good search composition %d products', $countOfGoodSearchComposition)
            ));
        } else {
            $this->io->error(Cow::say('Bad import'));
        }
    }
}
