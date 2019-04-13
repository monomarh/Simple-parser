<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Service\HtmlParser;
use Cowsayphp\Cow;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
    public function index(): Response
    {
        $parser = new HtmlParser();

        $urls = [
            'https://shop.cravt.by/ukhod-11439-s' => 406,
            'https://shop.cravt.by/ochishchenie-11448-s' => 171,
            'https://shop.cravt.by/maski_dlya_litsa-11453-s' => 53,
            'https://shop.cravt.by/ukhod_dlya_glaz-11454-s' => 91,
            'https://shop.cravt.by/ukhod_dlya_gub-11459-s' => 12
        ];

        foreach ($urls as $url => $pagination) {
            $parser->parseHtml((string)$pagination, $url, (int)($pagination / 24) + 1);
        }

        return new Response('<pre style="margin-left: 40vw">' . Cow::say('All good') . '<pre>');
    }
}