<?php

declare(strict_types = 1);

namespace App\Service;

use DiDom\Document;
use App\Entity\Product;
use JonnyW\PhantomJs\Client;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @package App\Service
 */
class HtmlParser
{
    /**
     * @var array
     */
    private $selectors = ['.product__brand', '.product__name'];

    /**
     * @var array
     */
    const URLS_WITH_PRODUCTS = [
//        'https://shop.cravt.by/ukhod-11439-s' => 406,
//        'https://shop.cravt.by/ochishchenie-11448-s' => 171,
//        'https://shop.cravt.by/maski_dlya_litsa-11453-s' => 53,
//        'https://shop.cravt.by/ukhod_dlya_glaz-11454-s' => 91,
        'https://shop.cravt.by/ukhod_dlya_gub-11459-s' => 12
    ];

    /**
     * @var int
     */
    const PRODUCTS_PER_PAGE = 24;

    /**
     * @var array
     */
    private $productsForSaving = [];

    /**
     * @var string
     */
    private $paginationOptions = '&offset=%d&limit=24';

    /**
     * @param array $selectors
     *
     * @return void
     */
    public function addSelectors(array $selectors): void
    {
        $this->selectors = [];
        if (!empty($selectors)) {
            foreach ($selectors as $selector) {
                $this->selectors[] = $selector;
            }
            $this->selectors = array_unique($this->selectors);
        }
    }

    /**
     * @param string $url
     * @param int $pagination
     *
     * @return array
     */
    public function parseHtml(string $url, int $pagination = 0): array
    {
        for ($pageNumber = 0; $pageNumber < $pagination; $pageNumber++) {
            $requiredFieldsFromHtml = [];

            /** @var Document $html */
            $html = new Document(
                $this->sendRequestAndReturnResponse(
                    $url,
                    sprintf($this->paginationOptions, $pageNumber * self::PRODUCTS_PER_PAGE)
                )
            );

            foreach ($this->selectors as $selector) {
                $requiredFieldsFromHtml[] = $html->find($selector);
            }

            foreach ($requiredFieldsFromHtml  as $productsSameFields) {
                foreach ($productsSameFields as $key => $productField) {
                    /** Skipping products that were hiding in the navbar */
                    if (preg_match('/^[0-5]{1}$/', (string)$key)) {
                        continue;
                    }

                    $this->productsForSaving[$key + $pageNumber * self::PRODUCTS_PER_PAGE][] = $productField->text();
                }
            }
        }
        return $this->productsForSaving;
    }

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @return void
     */
    public function saveProductsInDatabase(EntityManagerInterface $entityManager): void
    {
        foreach ($this->productsForSaving as $product) {
            $newProduct = new Product();

            $newProduct->setBrand($product[Seeker::PRODUCT_BRAND]);
            $newProduct->setTitle(str_replace(PHP_EOL, '', $product[Seeker::PRODUCT_TITLE]));

            $entityManager->persist($newProduct);
        }

        $entityManager->flush();
    }

    /**
     * @param string $url
     * @param string $parametrisesForGetRequest
     *
     * @return string
     */
    private function sendRequestAndReturnResponse(string $url, string $parametrisesForGetRequest = ''): string
    {
        /** @var Client $client */
        $client = Client::getInstance();

        $client->getEngine()->setPath(dirname(__DIR__) . '/../bin/phantomjs');

        $request = $client->getMessageFactory()->createRequest(
            $url . '?&' . $parametrisesForGetRequest,
            'GET'
        );

        $response = $client->getMessageFactory()->createResponse();

        $client->send($request, $response);

        return $response->getContent();
    }
}
