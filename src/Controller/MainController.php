<?php

declare(strict_types = 1);

namespace App\Controller;

use Cowsayphp\Cow;
use App\Service\Seeker;
use App\Service\HtmlParser;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var HtmlParser
     */
    private $parser;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param HtmlParser $parser
     * @param Seeker $seeker
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(HtmlParser $parser, Seeker $seeker, EntityManagerInterface $entityManager)
    {
        $this->seekerComposition = $seeker;
        $this->parser = $parser;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Response
     */
    public function index(): Response
    {
        foreach (HtmlParser::URLS_WITH_PRODUCTS as $url => $productCounter) {
            $this->parser->parseHtml($url, (int)($productCounter / HtmlParser::PRODUCTS_PER_PAGE) + 1);
        }

        $this->parser->saveProductsInDatabase($this->entityManager);

        return new Response(
            $this->cowResponseImage('search', 'Search products composition')
        );
    }

    /**
     * @return Response
     */
    public function search(): Response
    {
        $this->seekerComposition->startSearching();

        return new Response(
            $this->cowResponseImage('index', 'Parse products')
        );
    }

    /**
     * @param string $cowSpeech
     * @param string $routeNameForRedirect
     *
     * @return string
     */
    private function cowResponseImage(string $routeNameForRedirect, string $cowSpeech): string
    {
        $routeUrl = $this->generateUrl($routeNameForRedirect, [], UrlGeneratorInterface::ABSOLUTE_URL);

        return
            '<pre style="margin-left: 40vw">' .
            Cow::say(
                sprintf(
                    '<a href="%s">%s</a>',
                    $routeUrl,
                    $cowSpeech
                )
            ) .
            '<pre>'
        ;
    }
}
