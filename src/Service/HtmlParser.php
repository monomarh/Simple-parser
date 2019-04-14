<?php

declare(strict_types = 1);

namespace App\Service;

use DiDom\Document;
use JonnyW\PhantomJs\Client;

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
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var array
     */
    private $productsForSaving = [];

    /**
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        $fileNameWithExtension = dirname(__DIR__) . '/../files/' . $fileName . '.csv';

        $fp = fopen($fileNameWithExtension, 'wb');
        fclose($fp);

        $this->file = new \SplFileObject($fileNameWithExtension, 'wb');
    }

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
    public function parseHtml(string $url, int $pagination): array
    {
        for ($i = 0; $i < $pagination; $i++) {
            $requiredFieldsFromHtml = [];

            $html = new Document(
                $this->sendRequestAndReturnResponse(
                    $url, sprintf('&offset=%d&limit=24', $i * 24)
                )
            );

            foreach ($this->selectors as $selector) {
                $requiredFieldsFromHtml [] = $html->find($selector);
            }

            foreach ($requiredFieldsFromHtml  as $productsSameFields) {
                foreach ($productsSameFields as $key => $productField) {
                    if (preg_match('/^[0-5]{1}$/', (string)$key)) { continue; }

                    $this->productsForSaving[$key + $i * 24][] = $productField->text();
                }
            }

            return $this->productsForSaving;
        }
    }

    /**
     * @return void
     */
    public function saveInFile(): void
    {
        foreach($this->productsForSaving as $product) {
            $this->file->fputcsv($product);
        }
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
