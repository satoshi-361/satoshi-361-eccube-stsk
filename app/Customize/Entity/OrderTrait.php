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
     *          "label": false
     *     })
     */
    private $print_price;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @Eccube\Annotation\FormAppend(
     *     auto_render=false,
     *     type="\Symfony\Component\Form\Extension\Core\Type\TextType",
     *     options={
     *          "required": false,
     *          "label": false
     *     })
     */
    private $id_in_customer;

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

    /**
     * Get id_in_customer.
     *
     * @return id_in_customer
     */
    public function getIdInCustomer()
    {
        return $this->id_in_customer;
    }

    /**
     * Set id_in_customer.
     *
     * @param int $id_in_customer
     *
     * @return this
     */
    public function setIdInCustomer($id_in_customer)
    {
        $this->id_in_customer = $id_in_customer;
        
        return $this;
    }
}