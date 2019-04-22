<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @package App\Service
 */
class Seeker
{
    /**
     * @var int
     */
    const PRODUCT_BRAND = 0;

    /**
     * @var int
     */
    const PRODUCT_TITLE = 1;

    /**
     * @var int
     */
    const WORDS_COUNT_FOR_SAFE_SEARCH = 3;

    /**
     * @var string
     */
    private $siteForSearchingComposition = 'http://www.cosdna.com/eng';

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
        $this->entityManager = $entityManager;
        $this->parser = $parser;
    }

    public function startSearching()
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            $searchStatus = false;
            $riskIndicator = false;

            $wordsForGetRequest = $this->getProductSearchWords($product) ?? [];
            $requestLength = count($wordsForGetRequest);

            if ($requestLength === 1) {
                $riskIndicator = true;
            } elseif (!$requestLength) {
                continue;
            }

            while (!$searchStatus && ($requestLength > 0)) {
                $html = $this->parser->getDomFromUrl(
                    $this->siteForSearchingComposition,
                    sprintf(
                        '/product.php?q=%s+%s',
                        $product->getBrand(),
                        implode('+', $wordsForGetRequest)
                    )
                );

                $nodeElements = $html->find('.ProdName');

                if (count($nodeElements) > 0) {
                    $pageWithProductComposition = $nodeElements[0]->first('a')->getAttribute('href');

                    $htmlWithComposition = $this->parser->getDomFromUrl(
                        $this->siteForSearchingComposition,
                        '/' . $pageWithProductComposition
                    );

                    $compositionElements = $htmlWithComposition->find('tr[valign=top]');
                    $productComposition = [];

                    foreach ($compositionElements as $compositionElement) {
                        $productComposition[] = $compositionElement->first('td')->text();
                    }

                    if ($riskIndicator || count($wordsForGetRequest) < self::WORDS_COUNT_FOR_SAFE_SEARCH) {
                        $product->setRiskIndicator(true);
                    }

                    $product->setComposition($productComposition);
                    $this->entityManager->persist($product);

                    $searchStatus = true;
                } else {
                    $requestLength--;
                    unset($wordsForGetRequest[$requestLength]);
                }
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @return int
     */
    public function getInfoAboutSearching(): int
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        $countProductsWithComposition = 0;

        foreach ($products as $product) {
            if ($product->getComposition() !== []) {
                $countProductsWithComposition++;
            }
        }

        return $countProductsWithComposition;
    }

    /**
     * @param Product $product
     *
     * @return mixed
     */
    public function getProductSearchWords(Product $product)
    {
        preg_match_all('/\b([a-zA-Z]+)/', $product->getTitle(), $matches);

        return $matches[0];
    }
}
