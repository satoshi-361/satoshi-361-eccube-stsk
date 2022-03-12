<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
  * @EntityExtension("Eccube\Entity\Category")
 */
trait CategoryTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="is_visible", type="integer", nullable=true)
     */
    private $is_visible = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", nullable=true)
     */
    private $image;
    
    /**
     * @var string
     *
     * @ORM\Column(name="image1", type="string", nullable=true)
     */
    private $image1;
    
    /**
     * Get Image
     * 
     * @return int
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param  int  $image
     *
     * @return this
     */
    public function setImage($image)
    {
        $this->image = $image;
        
        return $this;
    }    

    /**
     * Get Image1
     * 
     * @return int
     */
    public function getImage1()
    {
        return $this->image1;
    }

    /**
     * @param  int  $image1
     *
     * @return this
     */
    public function setImage1($image1)
    {
        $this->image1 = $image1;
        
        return $this;
    }

    /**
     * Get is_visible
     * 
     * @return int
     */
    public function isVisible()
    {
        return $this->is_visible;
    }

    /**
     * @param  int  $is_visible
     *
     * @return this
     */
    public function setVisible($is_visible)
    {
        $this->is_visible = $is_visible;
        
        return $this;
    }
}