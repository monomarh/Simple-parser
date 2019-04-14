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
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var string
     */
    private $siteForSearchingComposition = 'http://www.cosdna.com/eng/product.php';

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

        $this->file = new \SplFileObject(dirname(__DIR__) . '/../files/all.csv');
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function startSearching(): array
    {
        $i = 0;
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $searchStatus = false;
            $requestLength = 0;

            if (preg_match_all('/\b(\w+)/', $product->getTitle(), $matches)) {
                $requestLength = count($matches[0]);
                $wordsForGetRequest = $matches[0];
            }

            while (!$searchStatus && ($requestLength > 1)) {
                $html = $this->getDomFromUrl(
                    sprintf('?q=%s+%s', $product->getBrand(), implode('+', $wordsForGetRequest))
                );

                $nodeElements = $html->find('.ProdName');
                if (count($nodeElements) > 0) {
                    $pageWithProductComposition = $nodeElements[0]->first('a')->getAttribute('href');

                    $html = $this->getDomFromUrl('/' . $pageWithProductComposition);

                    $compositionElements = $html->find('tr[valign=top]');
                    dump($compositionElements);

                    if (count($compositionElements) > 0) {
                        $productComposition = [];
                        foreach ($compositionElements as $compositionElement) {
                            $productComposition[] = $compositionElement->first('td')->text();
                            dump($productComposition);
                              $searchStatus = true;
                        }
                        die;

                        dump($productComposition);
                    } else {
                        throw new \Exception('Something get wrong');
                    }
                } else {
                    $requestLength--;
                    unset($wordsForGetRequest[$requestLength]);
                }
            }

            return [];
        }
    }

    /**
     * @param string $getOptions
     *
     * @return Document
     */
    public function getDomFromUrl(string $getOptions)
    {
        $url = $this->siteForSearchingComposition . $getOptions;

        $load = Parser::getPage([
            'url' 	  => $url,
            'timeout' => 10,
        ]);

        return new Document($load['data']['content']);
    }
}
