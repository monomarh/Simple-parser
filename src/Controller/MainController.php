<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Service\HtmlParser;
use Cowsayphp\Cow;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class MainController extends AbstractController
{
    /**
     * @return Response
     */
    public function index(): Response
    {
        $urls = [
//            'https://shop.cravt.by/ukhod-11439-s' => 406,
//            'https://shop.cravt.by/ochishchenie-11448-s' => 171,
//            'https://shop.cravt.by/maski_dlya_litsa-11453-s' => 53,
//            'https://shop.cravt.by/ukhod_dlya_glaz-11454-s' => 91,
//            'https://shop.cravt.by/ukhod_dlya_gub-11459-s' => 12
        ];

        foreach ($urls as $url => $pagination) {
            $parser = new HtmlParser((string)$pagination);
            $parser->parseHtml($url, (int)($pagination / 24) + 1);
            $parser->saveInFile();
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