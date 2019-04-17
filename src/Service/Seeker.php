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
     * @var string
     */
    private $siteForSearchingComposition = 'http://www.cosdna.com/eng';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param KernelInterface $kernel
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(KernelInterface $kernel, EntityManagerInterface $entityManager)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:merge_all_csv',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $this->entityManager = $entityManager;
    }

    /**
     * @return int
     */
    public function startSearching(): int
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        $productCounter = 0;

        foreach ($products as $product) {
            if ($product->getComposition() !== []) {
                continue;
            }

            $searchStatus = false;
            $riskIndicator = false;
            $requestLength = 0;

            if (preg_match_all('/\b([a-zA-Z]+)/', $product->getTitle(), $matches)) {
                $requestLength = count($matches[0]);
                if ($requestLength === 1) {
                    $riskIndicator = true;
                } elseif ($requestLength === 0) {
                    continue;
                }
                $wordsForGetRequest = $matches[0];
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

                    dump(true);

                    $html = $this->getDomFromUrl('/' . $pageWithProductComposition);

                    $compositionElements = $html->find('tr[valign=top]');
                    $productComposition = [];

                    foreach ($compositionElements as $compositionElement) {
                        $productComposition[] = $compositionElement->first('td')->text();
                    }

                    $productCounter++;
                    if ($riskIndicator || count($wordsForGetRequest) < 3) {
                        $product->setRiskIndicator(false);
                    }

                    $product->setComposition($productComposition);
                    $this->entityManager->persist($product);

                    $searchStatus = true;
                } else {
                    $requestLength--;
                    unset($wordsForGetRequest[0]);
                }
            }

            $this->entityManager->flush();
        }

        return $productCounter;
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

        $load = Parser::getPage([
            'url' 	  => $url,
            'timeout' => 10,
        ]);

        return new Document($load['data']['content']);
    }
}
