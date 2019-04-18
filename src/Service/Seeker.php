<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Product;
use DiDom\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

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
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
                $html = $this->getDomFromUrl(
                    sprintf(
                        '/product.php?q=%s+%s',
                        $product->getBrand(),
                        implode('+', $wordsForGetRequest)
                    )
                );

                $nodeElements = $html->find('.ProdName');

                if (count($nodeElements) > 0) {
                    $pageWithProductComposition = $nodeElements[0]->first('a')->getAttribute('href');

                    $htmlWithComposition = $this->getDomFromUrl('/' . $pageWithProductComposition);

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
     * @param string $getOptions
     *
     * @return Document
     */
    public function getDomFromUrl(string $getOptions): Document
    {
        $url = $this->siteForSearchingComposition . $getOptions;

        try {
            sleep(random_int(2, 6));
        } catch (\Exception $e) {
            sleep(2);
        }

        $load = Curl::getPage([
            'url' 	  => $url,
            'timeout' => 10,
        ]);

        return new Document($load['data']['content']);
    }

    /**
     * @param Product $product
     *
     * @return mixed
     */
    private function getProductSearchWords(Product $product)
    {
        preg_match_all('/\b([a-zA-Z]+)/', $product->getTitle(), $matches);

        return $matches[0];
    }
}
