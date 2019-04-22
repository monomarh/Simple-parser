<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Product;
use DiDom\Document;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @package App\Service
 */
class Analyzer
{
    /**
     * @var int
     */
    const LOW_RISK = 76;

    /**
     * @var int
     */
    const MEDIUM_RISK = 51;

    /**
     * @var string
     */
    private $siteForAnalyzingComposition = 'https://www.skincarisma.com/products/analyze';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var HtmlParser
     */
    private $parser;

    /**
     * @param EntityManagerInterface $entityManager
     * @param HtmlParser $parser
     */
    public function __construct(EntityManagerInterface $entityManager, HtmlParser $parser)
    {
        $this->entityManager = $entityManager;
        $this->parser = $parser;
    }

    public function analyze(): int
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            $productComposition = $product->getComposition();

            if (!$productComposition) {
                continue;
            }

            $wordsForGetRequest = '';
            foreach ($productComposition as $productComposite) {
                $wordsForGetRequest .= '%2C' . str_replace(' ', '+', $productComposite);
            }

            $html = $this->parser->getDomFromUrl(
                $this->siteForAnalyzingComposition,
                'product%5Bingredient%5D=' . $wordsForGetRequest
            );

            $this->findQuickNotes($product, $html);
            $this->setMatchingRisk($product, $html);
            $this->findNotableEffectAndIngredients($product, $html);
            $this->findIngredientsRelatedToSkinTypes($product, $html);

            $this->entityManager->persist($product);
        }
        $this->entityManager->flush();

        return 1;
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function findQuickNotes(Product $product, Document $html)
    {
        $quickNotes = [];

        $nodeElements = $html->find('.col-md-3.my-2');

        try {
            foreach ($nodeElements as $node) {
                if ($node->find('.fa.fa-check-square.green-icon.font-095')) {
                    $quickNotes[] = $node->find('span[class="semibold dotted-underline ml-1]')[0]->text();
                }
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

        if ($quickNotes !== []) {
            dump($quickNotes);
            $product->setQuickNotes($quickNotes);
        }
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function setMatchingRisk(Product $product, Document $html)
    {
        $nodeWithIngredientsNotFound = $html->find('.card.my-4');

        try {
            $stringWithIngredientsNotFound = $nodeWithIngredientsNotFound[0]->find('p')[4]->text();
            if ($stringWithIngredientsNotFound) {
                $ingredientsNotFound = $stringWithIngredientsNotFound  === 'none'
                    ? null : explode(',', $stringWithIngredientsNotFound);

                if ($ingredientsNotFound === null) {
                    return;
                }

                $matchingRisk = count($ingredientsNotFound) * 100 / count($product->getComposition());

                dump($matchingRisk);

                if ($matchingRisk <= self::MEDIUM_RISK) {
                    $product->setIngredientsMatchingRisk(false);
                } else {
                    $product->setIngredientsMatchingRisk(
                        $matchingRisk >= self::LOW_RISK ? null : true
                    );
                }
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function findNotableEffectAndIngredients(Product $product, Document $html)
    {
        $notableEffectsAndIngredients = [];

        try {
            $nodeWithNotableEffectAndIngredients = $html->find('.py-2.pr-2.pl-0');
            foreach ($nodeWithNotableEffectAndIngredients as $node) {
                $notableIngredients = [];
                $parentNode = $node->parent();
                if (($effect = $parentNode->find('span')) !== []) {
                    foreach ($parentNode->nextSiblings()[1]->find('.badge.badge-text.p-2.mb-1.mr-1') as $item) {
                        $notableIngredients[] = trim($item->text());
                    }
                    $notableEffectsAndIngredients[$effect[1]->find('b')[0]->text()] = $notableIngredients;
                }
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

        if ($notableEffectsAndIngredients !== []) {
            dump($notableEffectsAndIngredients);
            $product->setNotableEffectsAndIngredients($notableEffectsAndIngredients);
        }
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function findIngredientsRelatedToSkinTypes(Product $product, Document $html)
    {
        $relatedList = [];
        try {
            $nodeWithRelatedIngredients = $html->find('h3[class=font-080]')[2]->parent();
            $divsWithDataTag = $nodeWithRelatedIngredients->find('div.row');

            foreach ($divsWithDataTag as $node) {
                $skinType = $node->find('i')[0]->firstChild()->text();

                $hideHtml = new Document($node->getAttribute('data-original-title'));

                $listOfIngredients = $hideHtml->find('small');

                $goodList = [];
                foreach ($listOfIngredients[0]->find('b') as $ingredientName) {
                    $goodList[] = $ingredientName->text();
                }

                $badList = [];
                foreach ($listOfIngredients[1]->find('b') as $ingredientName) {
                    $badList[] = $ingredientName->text();
                }

                $relatedList[$skinType] = ['Good' => $goodList, 'Bad' => $badList];
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

        if ($relatedList !== []) {
            $product->setIngredientsRelatedToSkinTypes($relatedList);
        }
    }
}
