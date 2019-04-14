<?php

declare(strict_types = 1);

namespace App\Service;

use DiDom\Document;
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
    private $siteForSearchingCompostion = 'http://www.cosdna.com/eng/product.php';

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
//        $application = new Application($kernel);
//        $application->setAutoExit(false);
//
//        $input = new ArrayInput([
//            'command' => 'app:merge_all_csv',
//        ]);
//
//        $output = new BufferedOutput();
//        $application->run($input, $output);

        $this->file = new \SplFileObject(dirname(__DIR__) . '/../files/all.csv');
    }

    /**
     * @return array
     */
    public function startSearching(): array
    {
        $i = 0;
        foreach ($this->file as $product) {
            if ($this->file->eof()) { return []; }
            $searchStatus = false;
            $requestLength = 1;

            while (!$searchStatus || $requestLength) {
                $html = $this->getDomFromUrl('?q=MATIS+Repairing+eye+cream');

                $nodeElements = $html->find('.ProdName');
                if (count($nodeElements) > 0) {
                    $pageWithProductCompostition = $nodeElements[0]->first('a')->getAttribute('href');

                    $html = $this->getDomFromUrl('/' . $pageWithProductCompostition);

                    $compositionElements = $html->find('tr[valign=top]');
                    dump($compositionElements);

                    if (count($compositionElements) > 0) {
                        $productComposition = [];
                        foreach ($compositionElements as $compositionElement) {
                            $productComposition[] = $compositionElement->first('td')->text();
                            dump($productComposition);
                        }
                        die;

                        dump($productComposition);
                    } else {
                        throw new \Exception('Something get wrong');
                    }
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
        $url = $this->siteForSearchingCompostion . $getOptions;

        $load = Parser::getPage([
            'url' 	  => $url,
            'timeout' => 10,
        ]);

        return new Document($load['data']['content']);
    }
}
