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

        $this->file = new \SplFileObject(dirname(__DIR__) . '/../files/all.csv');
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function startSearching(): array
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();

        $i = 0;
        foreach ($products as $product) {
            if ($i === 10) {return [];}
            $searchStatus = false;
            $requestLength = 0;

            if (preg_match_all('/\b(\w+)/', $product->getTitle(), $matches)) {
                $requestLength = count($matches[0]);
                $wordsForGetRequest = $matches[0];
            }

            while (!$searchStatus && ($requestLength > 1)) {
                $html = $this->getDomFromUrl(
                    sprintf(
                        '/product.php?q=%s+%s',
                        $product->getBrand(),
                        implode('+', $wordsForGetRequest))
                );

                $nodeElements = $html->find('.ProdName');
                if (count($nodeElements) > 0) {
                    $pageWithProductComposition = $nodeElements[0]->first('a')->getAttribute('href');

                    $html = $this->getDomFromUrl('/' . $pageWithProductComposition);

                    $compositionElements = $html->find('tr[valign=top]');
                    $productComposition = [];

                    foreach ($compositionElements as $compositionElement) {
                        $productComposition[] = $compositionElement->first('td')->text();
                    }

                    $product->setComposition($productComposition);
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();

                    $searchStatus = true;
                } else {
                    $requestLength--;
                    unset($wordsForGetRequest[$requestLength]);
                }
            }
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

        sleep(random_int(2, 9));

        $load = Parser::getPage([
            'url' 	  => $url,
            'timeout' => 10,
        ]);

        return new Document($load['data']['content']);
    }
}
