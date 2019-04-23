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

            if (!$productComposition || $product->getQuickNotes()) {
                continue;
            }

            $wordsForGetRequest = '';
            foreach ($productComposition as $productComposite) {
                $wordsForGetRequest .= '%2C' . str_replace(' ', '+', $productComposite);
            }

            try {
                $html = $this->parser->getDomFromUrl(
                    $this->siteForAnalyzingComposition,
                    'product%5Bingredient%5D=' . $wordsForGetRequest
                );

                $this->findQuickNotes($product, $html);
                $this->setMatchingRisk($product, $html);
                $this->findNotableEffectAndIngredients($product, $html);
                $this->findIngredientsRelatedToSkinTypes($product, $html);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
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
        if ($product->getQuickNotes()) {
            return;
        }

        $quickNotes = [];
        $nodeElements = $html->find('.col-md-3.my-2');
        try {
            foreach ($nodeElements as $node) {
                if ($node->find('.fa.fa-check-square.green-icon.font-095')) {
                    $quickNotes[] = $node->find('span[class="semibold dotted-underline ml-1]')[0]->text();
                }
            }
        } catch (\Exception $e) {
            return;
        }

        if ($quickNotes !== []) {
            $product->setQuickNotes($quickNotes);
        }
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function setMatchingRisk(Product $product, Document $html)
    {
        if ($product->getIngredientsMatchingRisk()) {
            return;
        }

        $nodeWithIngredientsNotFound = $html->find('.card.my-4');
        try {
            $stringWithIngredientsNotFound = $nodeWithIngredientsNotFound[0]->find('p')[4]->text();
            if ($stringWithIngredientsNotFound) {
                $ingredientsNotFound = $stringWithIngredientsNotFound  === 'none'
                    ? null : explode(',', $stringWithIngredientsNotFound);

                if ($ingredientsNotFound === null) {
                    return;
                }

                $matchingRisk = 100 - count($ingredientsNotFound) * 100 / count($product->getComposition());

                if ($matchingRisk >= self::LOW_RISK) {
                    $product->setIngredientsMatchingRisk(null);
                } else {
                    $product->setIngredientsMatchingRisk($matchingRisk > self::MEDIUM_RISK);
                }
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function findNotableEffectAndIngredients(Product $product, Document $html)
    {
        if ($product->getNotableEffectsAndIngredients()) {
            return;
        }

        $notableEffectsAndIngredients = [];
        try {
            $nodeWithNotableEffectAndIngredients = $html->find('.py-2.pr-2.pl-0');
            foreach ($nodeWithNotableEffectAndIngredients as $node) {
                $notableIngredients = [];
                $parentNode = $node->parent();
                if ($parentNode && ($effect = $parentNode->find('span')) !== []) {
                    foreach ($parentNode->nextSiblings()[1]->find('.badge.badge-text.p-2.mb-1.mr-1') as $item) {
                        $notableIngredients[] = trim($item->text());
                    }
                    $notableEffectsAndIngredients[$effect[1]->find('b')[0]->text()] = $notableIngredients;
                }
            }
        } catch (\Exception $e) {
            return;
        }

        if ($notableEffectsAndIngredients !== []) {
            $product->setNotableEffectsAndIngredients($notableEffectsAndIngredients);
        }
    }

    /**
     * @param Product $product
     * @param Document $html
     */
    private function findIngredientsRelatedToSkinTypes(Product $product, Document $html)
    {
        if ($product->getDrySkin() || $product->getOilySkin() || $product->getSensitiveSkin()) {
            return;
        }

        $relatedList = [];
        try {
            $nodeWithRelatedIngredients = $html->find('h3[class=font-080]')[2]->parent();
            $divsWithDataTag = $nodeWithRelatedIngredients->find('div.row');

            $i = 0;
            foreach ($divsWithDataTag as $node) {
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

                $relatedList[$i++] = ['Good' => $goodList, 'Bad' => $badList];
            }
        } catch (\Exception $e) {
            return;
        }

        foreach ($relatedList as &$item) {
            if (array_key_exists('Good', $item) && $item['Good'] === []) {
                unset($item['Good']);
            }
            if (array_key_exists('Bad', $item) && $item['Bad'] === []) {
                unset($item['Bad']);
            }
        }

        unset($item);

        if ($relatedList !== []) {
            $product->setDrySkin($relatedList[0] ?? null);
            $product->setOilySkin($relatedList[1] ?? null);
            $product->setSensitiveSkin($relatedList[2] ?? null);
        }
    }
}
