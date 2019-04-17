<?php

declare(strict_types = 1);

namespace App\Command;

use Cowsayphp\Cow;
use App\Service\HtmlParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Command
 */
class ParseProductsCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected static $defaultName = 'app:parse_products';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var HtmlParser
     */
    private $parser;

    /**
     * @param EntityManagerInterface $entityManager
     * @param HtmlParser $parser
     */
    public function __construct(EntityManagerInterface $entityManager, HtmlParser $parser)
    {
        parent::__construct($name = null);

        $this->entityManager = $entityManager;
        $this->parser = $parser;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Parsing products from SPA website')
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
        $result = [];

        foreach (HtmlParser::URLS_WITH_PRODUCTS as $url => $productCount) {
            $result[] = $this->parser->parseHtml(
                $url,
                (int)floor($productCount / HtmlParser::PRODUCTS_PER_PAGE) + 1
            );
        }

        $this->parser->saveProductsInDatabase($this->entityManager);

        if ($result !== []) {
            $this->io->success(Cow::say(sprintf('Good import from %d pages', count($result))));
        } else {
            $this->io->error(Cow::say('Bad import'));
        }
    }
}
