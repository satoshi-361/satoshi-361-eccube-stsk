<?php

namespace Plugin\PrintPrice\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Plugin\PrintPrice\Entity\Config', false)) {
    /**
     * Config
     *
     * @ORM\Table(name="plg_print_price_config")
     * @ORM\Entity(repositoryClass="Plugin\PrintPrice\Repository\ConfigRepository")
     */
    class Config
    {
        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var int
         *
         * @ORM\Column(name="category_id", type="integer", nullable=true)
         */
        private $category_id;

        /**
         * @var int
         *
         * @ORM\Column(name="header_id", type="integer", nullable=true)
         */
        private $header_id;

        /**
         * @var string
         *
         * @ORM\Column(name="price", type="string", nullable=true)
         */
        private $price;

        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return int
         */
        public function getCategoryId()
        {
            return $this->category_id;
        }

        /**
         * @param int $category_id
         *
         * @return $this;
         */
        public function setCategoryId($category_id)
        {
            $this->category_id = $category_id;

            return $this;
        }
        
        /**
         * @return int
         */
        public function getHeaderId()
        {
            return $this->header_id;
        }

        /**
         * @param int $header_id
         *
         * @return $this;
         */
        public function setHeaderId($header_id)
        {
            $this->header_id = $header_id;

            return $this;
        }

        
        /**
         * @return string
         */
        public function getPrice()
        {
            return $this->price;
        }

        /**
         * @param string $price
         *
         * @return $this;
         */
        public function setPrice($price)
        {
            $this->price = $price;

            return $this;
        }
    }
}
