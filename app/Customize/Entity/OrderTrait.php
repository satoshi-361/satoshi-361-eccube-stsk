<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
  * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @Eccube\Annotation\FormAppend(
     *     auto_render=false,
     *     type="\Symfony\Component\Form\Extension\Core\Type\NumberType",
     *     options={
     *          "required": false,
     *          "mapped": false,
     *          "label": false
     *     })
     */
    private $board_price;


    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @Eccube\Annotation\FormAppend(
     *     auto_render=false,
     *     type="\Symfony\Component\Form\Extension\Core\Type\NumberType",
     *     options={
     *          "required": false,
     *          "mapped": false,
     *          "label": false
     *     })
     */
    private $print_price;

    /**
     * Get board_price.
     *
     * @return board_price
     */
    public function getBoardPrice()
    {
        return $this->board_price;
    }

    /**
     * Set board_price.
     *
     * @param int $board_price
     *
     * @return this
     */
    public function setBoardPrice($board_price)
    {
        $this->board_price = $board_price;
        
        return $this;
    }

    /**
     * Get print_price.
     *
     * @return print_price
     */
    public function getPrintPrice()
    {
        return $this->print_price;
    }

    /**
     * Set print_price.
     *
     * @param int $print_price
     *
     * @return this
     */
    public function setPrintPrice($print_price)
    {
        $this->print_price = $print_price;
        
        return $this;
    }
}