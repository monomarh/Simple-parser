<?php

declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 *
 * @package App\Entity
 */
class Product
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $brand;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $composition;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $riskIndicator;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $quickNotes;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ingredientsMatchingRisk;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $notableEffectsAndIngredients;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return array|null
     */
    public function getComposition(): ?array
    {
        return $this->composition;
    }

    /**
     * @param $composition
     */
    public function setComposition(array $composition): void
    {
        $this->composition = $composition;
    }

    /**
     * @return bool|null
     */
    public function getRiskIndicator(): ?bool
    {
        return $this->riskIndicator;
    }

    /**
     * @param bool $riskIndicator
     */
    public function setRiskIndicator(bool $riskIndicator)
    {
        $this->riskIndicator = $riskIndicator;
    }

    /**
     * @return array|null
     */
    public function getQuickNotes(): ?array
    {
        return $this->quickNotes;
    }

    /**
     * @param $quickNotes
     *
     * @return Product
     */
    public function setQuickNotes($quickNotes): self
    {
        $this->quickNotes = $quickNotes;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIngredientsMatchingRisk(): ?bool
    {
        return $this->ingredientsMatchingRisk;
    }

    /**
     * @param bool|null $ingredientsMatchingRisk
     *
     * @return Product
     */
    public function setIngredientsMatchingRisk(?bool $ingredientsMatchingRisk): self
    {
        $this->ingredientsMatchingRisk = $ingredientsMatchingRisk;

        return $this;
    }

    /**
     * @return array
     */
    public function getNotableEffectsAndIngredients(): array
    {
        return $this->notableEffectsAndIngredients;
    }

    /**
     * @param $notableEffectsAndIngredients
     *
     * @return Product
     */
    public function setNotableEffectsAndIngredients($notableEffectsAndIngredients): self
    {
        $this->notableEffectsAndIngredients = $notableEffectsAndIngredients;

        return $this;
    }
}
