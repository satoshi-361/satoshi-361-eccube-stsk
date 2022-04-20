<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Entity\Product;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\Master\ProductStatusRepository;

class TopController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * ProductController constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductStatusRepository $productStatusRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/", name="homepage", methods={"GET"})
     * @Template("index.twig")
     */
    public function index(Request $request)
    {
        $Products = $this->productRepository->findAll();

	    $flag = $this->session->get('IS_ORDER_CONFIRMED');

        if ($flag == 'true') return $this->redirectToRoute('order_contact');
        
        $this->session->set('IS_ORDER_CONFIRMED', 'false');
        $rank = [];

        if ($this->productRepository->findOneBy(['name' => '人気ランキング']) != null) {
            $tempLank = $this->productRepository->findOneBy(['name' => '人気ランキング'])->getDescriptionDetail();
    
            foreach(explode(';', $tempLank) as $item) {
                array_push($rank, $this->productRepository->find(intval( explode(':', $item)[1] )));
            }
        }

        $ProductStatus = $this->productStatusRepository->find(ProductStatus::DISPLAY_SHOW);

        $qb = $this->productRepository->createQueryBuilder('p');
        $qb 
            ->andWhere('p.Status = 1')
            ->orderBy('p.create_date', 'DESC')
            ->setMaxResults(8);

        $NewItems = $qb->getQuery()->getResult();

        $Targets = $this->categoryRepository->findBy(['name' => 'ターゲット'])[0]->getChildren();
        $Scenes =  $this->categoryRepository->findBy(['name' => '目的・シーン'])[0]->getChildren();;

        return [
            'Products' => $Products,
            'ProductRanks' => $rank,
            'NewItems' => $NewItems,
            'Targets' => $Targets,
            'Scenes' => $Scenes,
        ];
    }
}
