<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
  * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", nullable=true)
     */
    private $fax;
    
    /**
     * Get Fax
     * 
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param  string  $fax
     *
     * @return this
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
        
        return $this;
    }

    
    /**
     * @var string
     *
     * @ORM\Column(name="submit_state", type="string", nullable=true)
     */
    private $submit_state = '未入稿';
    
    /**
     * Get submit_state
     * 
     * @return string
     */
    public function getSubmitState()
    {
        return $this->submit_state;
    }

    /**
     * @param  string  submit_state
     *
     * @return this
     */
    public function setSubmitState($submit_state)
    {
        $this->submit_state = $submit_state;
        
        return $this;
    }
}