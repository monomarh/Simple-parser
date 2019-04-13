<?php

declare(strict_types = 1);

namespace App\Service;

use DiDom\Document;

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
     * @param array $selectors
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
     * @param string $productTypes
     * @param string $url
     * @param int $pagination
     *
     * @throws \Exception
     */
    public function parseHtml(string $productTypes, string $url, int $pagination): void
    {
        $fileName = $productTypes . '.csv';
        $fp = fopen(dirname(__DIR__) . '/../files/'. $fileName, 'wb');
        $elementsForImport = [];
        $i = 0;

        while ($i < $pagination) {
            $elements = [];

            $load = Parser::getPage([
                'url' 	  => $url . '?' . sprintf('&offset=%d&limit=24', $i * 24),
                'timeout' => 10,
            ]);

            $html = new Document($load['data']['content']);

            foreach ($this->selectors as $selector) {
                $elements[] = $html->find($selector);
                dump($html->find($selector));
            }

            foreach ($elements as $element) {
                foreach ($element as $key => $product) {
                    $elementsForImport[$key + $i * 24][] = $product->text();
                }
            }

            $i++;
        }

        foreach($elementsForImport as $product) {
            fputcsv($fp, $product);
        }

        fclose($fp);
    }
}