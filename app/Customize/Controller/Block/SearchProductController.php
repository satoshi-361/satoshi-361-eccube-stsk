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

namespace Customize\Controller\Block;

use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\SearchProductBlockType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Repository\CategoryRepository;

class SearchProductController extends AbstractController
{
    /**
     * @var RequestStack
     */
    protected $requestStack;
    
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    public function __construct(RequestStack $requestStack, CategoryRepository $categoryRepository
    ) {
        $this->requestStack = $requestStack;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/block/search_product", name="block_search_product", methods={"GET"})
     * @Route("/block/search_product_sp", name="block_search_product_sp", methods={"GET"})
     * @Template("Block/search_product.twig")
     */
    public function index(Request $request)
    {
        $builder = $this->formFactory
            ->createNamedBuilder('', SearchProductBlockType::class)
            ->setMethod('GET');

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_BLOCK_SEARCH_PRODUCT_INDEX_INITIALIZE, $event);

        $request = $this->requestStack->getMasterRequest();

        $form = $builder->getForm();
        $form->handleRequest($request);

        return [
            'form' => $form->createView(),
        ];
    }

    
    /**
     * @Route("/block/search_product__by_category", name="block_search_product_by_category", methods={"GET"})
     * @Route("/block/search_product_sp_by_category", name="block_search_product_sp_by_category", methods={"GET"})
     * @Template("Block/sidebar_search.twig")
     */
    public function byCategory(Request $request)
    {
        $Parents = $this->categoryRepository->getList(null, false);

        $Targets = [];
        $Manufacturers = [];
        $PrintMethods = [];
        $Colors = [];
        $Scenes = [];
        $Categories = [];

        foreach($Parents as $item) {
            if ( $item->getName() == 'ターゲット' )
                $Targets = $item->getChildren();
            else if ( $item->getName() == '製造メーカー' )
                $Manufacturers = $item->getChildren();
            else if ( $item->getName() == '印刷方法詳細' ) {
                foreach ($item->getChildren() as $pItem)
                    array_push($PrintMethods, $pItem);
            }
            else if ( $item->getName() == '本体カラー' )
                $Colors = $item->getChildren();
            else if ( $item->getName() == '目的・シーン' )
                $Scenes = $item->getChildren();
            else if ( $item->getName() == 'カテゴリ' )
                $Categories = $item->getChildren();
            else if ( $item->getName() == '価格帯' )
                $SearchPrices = $item->getChildren();
        }

        return [
            'Targets' => $Targets,
            'Manufacturers' => $Manufacturers,
            'PrintMethods' => $PrintMethods,
            'Colors' => $Colors,
            'Scenes' => $Scenes,
            'Categories' => $Categories,
            'SearchPrices' => $SearchPrices
        ];
    }
}
