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
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($name = null);

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Parsing products from SPA websites')
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
        $urls = [
//            'https://shop.cravt.by/ukhod-11439-s' => 406,
//            'https://shop.cravt.by/ochishchenie-11448-s' => 171,
//            'https://shop.cravt.by/maski_dlya_litsa-11453-s' => 53,
//            'https://shop.cravt.by/ukhod_dlya_glaz-11454-s' => 91,
            'https://shop.cravt.by/ukhod_dlya_gub-11459-s' => 12
        ];
        $result = [];

        foreach ($urls as $url => $productCount) {
            /** @var HtmlParser $parser */
            $parser = new HtmlParser((string)$productCount);

            $result[] = $parser->parseHtml($url, (int)($productCount / HtmlParser::PRODUCTS_PER_PAGE) + 1);

            $parser->saveProducts();
            $parser->saveProductsInDatabase($this->entityManager);
        }

        if (!empty($result)) {
            $this->io->success(Cow::say(sprintf('Good import %d products', count($result))));
        } else {
            $this->io->error(Cow::say('Bad import'));
        }
    }
}
