<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
  * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="page_title", type="string", nullable=true)
     */
    private $page_title;

    /**
     * @var string
     *
     * @ORM\Column(name="size", type="string", nullable=true)
     */
    private $size;
    
    /**
     * @var string
     *
     * @ORM\Column(name="body_color", type="string", nullable=true)
     */
    private $body_color;

    /**
     * @var string
     *
     * @ORM\Column(name="packaging_form", type="string", nullable=true)
     */
    private $packaging_form;

    /**
     * @var string
     *
     * @ORM\Column(name="material", type="string", nullable=true)
     */
    private $material;

    /**
     * @var int
     *
     * @ORM\Column(name="price_range", type="integer", nullable=true)
     */
    private $price_range;

    /**
     * @var string
     *
     * @ORM\Column(name="print_range", type="string", nullable=true)
     */
    private $print_range;
    
    /**
     * @var int
     *
     * @ORM\Column(name="min_quantity", type="integer", nullable=true)
     */
    private $min_quantity = 30;

    /**
     * @var string
     *
     * @ORM\Column(name="template_file", type="string", nullable=true)
     */
    private $template_file;
    
    /**
     * Get Page Title
     * 
     * @return string
     */
    public function getPageTitle()
    {
        return $this->page_title;
    }

    /**
     * @param  string  $page_title
     *
     * @return this
     */
    public function setPageTitle($page_title)
    {
        $this->page_title = $page_title;
        
        return $this;
    }

    /**
     * Get Size
     * 
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param  string  $size
     *
     * @return this
     */
    public function setSize($size)
    {
        $this->size = $size;
        
        return $this;
    }

    /**
     * Get Body Color
     * 
     * @return string
     */
    public function getBodyColor()
    {
        return $this->body_color;
    }

    /**
     * @param  string  $body_color
     *
     * @return this
     */
    public function setBodyColor($body_color)
    {
        $this->body_color = $body_color;
        
        return $this;
    }
    
    /**
     * Get Packaging Form
     * 
     * @return string
     */
    public function getPackagingForm()
    {
        return $this->packaging_form;
    }

    /**
     * @param  string  $packaging_form
     *
     * @return this
     */
    public function setPackagingForm($packaging_form)
    {
        $this->packaging_form = $packaging_form;
        
        return $this;
    }
    
    /**
     * Get Material
     * 
     * @return string
     */
    public function getMaterial()
    {
        return $this->material;
    }

    /**
     * @param  string  $material
     *
     * @return this
     */
    public function setMaterial($material)
    {
        $this->material = $material;
        
        return $this;
    }
    
    /**
     * Get Price Range
     * 
     * @return int
     */
    public function getPriceRange()
    {
        return $this->price_range;
    }

    /**
     * @param  int  $price_range
     *
     * @return this
     */
    public function setPriceRange($price_range)
    {
        $this->price_range = $price_range;
        
        return $this;
    }

    /**
     * Get Print Range
     * 
     * @return string
     */
    public function getPrintRange()
    {
        return $this->print_range;
    }

    /**
     * @param  string  $print_range
     *
     * @return this
     */
    public function setPrintRange($print_range)
    {
        $this->print_range = $print_range;
        
        return $this;
    }

    /**
     * Get Min Quantity
     * 
     * @return int
     */
    public function getMinQuantity()
    {
        return $this->min_quantity;
    }

    /**
     * @param  int  $min_quantity
     *
     * @return this
     */
    public function setMinQuantity($min_quantity)
    {
        $this->min_quantity = $min_quantity;
        
        return $this;
    }

    /**
     * Get template_file
     * 
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->template_file;
    }

    /**
     * @param  string  $template_file
     *
     * @return this
     */
    public function setTemplateFile($template_file)
    {
        $this->template_file = $template_file;
        
        return $this;
    }
}