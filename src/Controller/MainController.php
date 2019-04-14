<?php

declare(strict_types = 1);

namespace App\Controller;

use Cowsayphp\Cow;
use App\Service\Seeker;
use App\Service\HtmlParser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @package App\Controller
 */
class MainController extends AbstractController
{
    /**
     * @var Seeker
     */
    private $seekerComposition;

    /**
     * @param Seeker $seeker
     */
    public function __construct(Seeker $seeker)
    {
        $this->seekerComposition = $seeker;
    }

    /**
     * @return Response
     */
    public function index(): Response
    {
        $urls = [
            'https://shop.cravt.by/ukhod-11439-s' => 406,
            'https://shop.cravt.by/ochishchenie-11448-s' => 171,
            'https://shop.cravt.by/maski_dlya_litsa-11453-s' => 53,
            'https://shop.cravt.by/ukhod_dlya_glaz-11454-s' => 91,
            'https://shop.cravt.by/ukhod_dlya_gub-11459-s' => 12
        ];

        foreach ($urls as $url => $productCounter) {
            $parser = new HtmlParser((string)$productCounter);
            $parser->parseHtml($url, (int)($productCounter / HtmlParser::PRODUCTS_PER_PAGE) + 1);
            $parser->saveProducts();
            $parser->saveProductsInDatabase($this->getDoctrine()->getManager());
        }

        return new Response(
            '<pre style="margin-left: 40vw">' .
            Cow::say(sprintf(
                '<a href="%s">Search products composition</a>',
                $this->generateUrl('search', [], UrlGeneratorInterface::ABSOLUTE_URL))
            ) .
            '<pre>'
        );
    }

    /**
     * @return Response
     */
    public function search(): Response
    {
        $result = $this->seekerComposition->startSearching();

        return new Response(
            '<pre style="margin-left: 40vw">' .
            Cow::say(sprintf(
                    '<a href="%s">Parse products</a>',
                    $this->generateUrl('index', [], UrlGeneratorInterface::ABSOLUTE_URL))
            ) .
            '<pre>'
        );
    }
}