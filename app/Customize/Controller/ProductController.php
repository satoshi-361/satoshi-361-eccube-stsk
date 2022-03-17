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

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Front\OrderContactType;
use Eccube\Form\Type\Front\SampleOrderContactType;
use Eccube\Form\Type\Master\ProductListMaxType;
use Eccube\Form\Type\Master\ProductListOrderByType;
use Eccube\Form\Type\SearchProductType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\Master\ProductListMaxRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Eccube\Controller\AbstractController;
use Eccube\Repository\CustomerRepository;
use Eccube\Service\MailService;
use Eccube\Entity\CustomerAddress;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\Pref;
use Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Form\Type\Admin\OrderType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Common\Constant;
use Eccube\Service\OrderHelper;
use Eccube\Repository\OrderRepository;

class ProductController extends AbstractController
{
    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var AuthenticationUtils
     */
    protected $helper;
    
    /**
     * @var OrderNoProcessor
     */
    protected $orderNoProcessor;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var ProductListMaxRepository
     */
    protected $productListMaxRepository;

    private $title = '';

    /**
     * ProductController constructor.
     *
     * @param PurchaseFlow $orderPurchaseFlow
     * @param CustomerFavoriteProductRepository $customerFavoriteProductRepository
     * @param CartService $cartService
     * @param ProductRepository $productRepository
     * @param CategoryRepository $categoryRepository
     * @param CustomerRepository $customerRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param OrderRepository $orderRepository
     * @param AuthenticationUtils $helper
     * @param OrderHelper $orderHelper
     * @param ProductListMaxRepository $productListMaxRepository
     * @param OrderNoProcessor $orderNoProcessor
     */
    public function __construct(
        PurchaseFlow $orderPurchaseFlow,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        CustomerRepository $customerRepository,
        BaseInfoRepository $baseInfoRepository,
        OrderRepository $orderRepository,
        AuthenticationUtils $helper,
        MailService $mailService,
        OrderHelper $orderHelper,
        OrderNoProcessor $orderNoProcessor,
        ProductListMaxRepository $productListMaxRepository
    ) {
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->helper = $helper;
        $this->mailService = $mailService;
        $this->productListMaxRepository = $productListMaxRepository;
        $this->orderHelper = $orderHelper;
        $this->orderNoProcessor = $orderNoProcessor;
    }

    /**
     * 商品一覧画面.
     *
     * @Route("/products/list", name="product_list", methods={"GET"})
     * @Template("Product/list.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        // Doctrine SQLFilter
        if ($this->BaseInfo->isOptionNostockHidden()) {
            $this->entityManager->getFilters()->enable('option_nostock_hidden');
        }

        // handleRequestは空のqueryの場合は無視するため
        if ($request->getMethod() === 'GET') {
            $request->query->set('pageno', $request->query->get('pageno', ''));
        }

        // searchForm
        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createNamedBuilder('', SearchProductType::class);

        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_INITIALIZE, $event);

        /* @var $searchForm \Symfony\Component\Form\FormInterface */
        $searchForm = $builder->getForm();

        $searchForm->handleRequest($request);
        
        // paginator
        $searchData = $searchForm->getData();
        $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);

        $event = new EventArgs(
            [
                'searchData' => $searchData,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_SEARCH, $event);
        $searchData = $event->getArgument('searchData');

        $query = $qb->getQuery()
            ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);

        /** @var SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $query,
            !empty($searchData['pageno']) ? $searchData['pageno'] : 1,
            !empty($searchData['disp_number']) ? $searchData['disp_number']->getId() : $this->productListMaxRepository->findOneBy([], ['sort_no' => 'ASC'])->getId()
        );

        $ids = [];
        foreach ($pagination as $Product) {
            $ids[] = $Product->getId();
        }
        $ProductsAndClassCategories = $this->productRepository->findProductsWithSortedClassCategories($ids, 'p.id');

        // addCart form
        $forms = [];
        foreach ($pagination as $Product) {
            /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
            $builder = $this->formFactory->createNamedBuilder(
                '',
                AddCartType::class,
                null,
                [
                    'product' => $ProductsAndClassCategories[$Product->getId()],
                    'allow_extra_fields' => true,
                ]
            );
            $addCartForm = $builder->getForm();

            $forms[$Product->getId()] = $addCartForm->createView();
        }

        // 表示件数
        $builder = $this->formFactory->createNamedBuilder(
            'disp_number',
            ProductListMaxType::class,
            null,
            [
                'required' => false,
                'allow_extra_fields' => true,
            ]
        );
        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_DISP, $event);

        $dispNumberForm = $builder->getForm();

        $dispNumberForm->handleRequest($request);

        // ソート順
        $builder = $this->formFactory->createNamedBuilder(
            'orderby',
            ProductListOrderByType::class,
            null,
            [
                'required' => false,
                'allow_extra_fields' => true,
            ]
        );
        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_ORDER, $event);

        $orderByForm = $builder->getForm();

        $orderByForm->handleRequest($request);

        $Category = $searchForm->get('category_id')->getData();

        return [
            'subtitle' => $this->getPageTitle($searchData),
            'pagination' => $pagination,
            'search_form' => $searchForm->createView(),
            'disp_number_form' => $dispNumberForm->createView(),
            'order_by_form' => $orderByForm->createView(),
            'forms' => $forms,
            'Category' => $Category,
        ];
    }

    /**
     * 商品詳細画面.
     *
     * @Route("/products/detail/{id}", name="product_detail", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("Product/detail.twig")
     * @ParamConverter("Product", options={"repository_method" = "findWithSortedClassCategories"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return array
     */
    public function detail(Request $request, Product $Product)
    {
        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_INITIALIZE, $event);

        $is_favorite = false;
        if ($this->isGranted('ROLE_USER')) {
            $Customer = $this->getUser();
            $is_favorite = $this->customerFavoriteProductRepository->isFavorite($Customer, $Product);
        }

	    $id = $Product->getId();

	    $arr = $this->session->get('check_product.product');
	    $arr[] = $id;
	    $arr = array_unique($arr);
	    $max = $this->eccubeConfig['CHECK_PRODUCT_MAX'];
	    $arr = array_slice($arr, (- $max), $max);

	    $this->session->set('check_product.product', $arr);

        $productId =  $this->session->get('check_product.product') ?: array();
		$CheckProducts = array();
		foreach ($productId as $id) {
			$tempProduct = $this->productRepository->find($id);
			if(!is_null($tempProduct) && $tempProduct->getStatus()->getId() === ProductStatus::DISPLAY_SHOW) {
				$CheckProducts[] = $tempProduct;
			}
		}

        return [
            'title' => $this->title,
            'subtitle' => $Product->getName(),
            'form' => $builder->getForm()->createView(),
            'Product' => $Product,
            'is_favorite' => $is_favorite,
            'CheckProducts' => $CheckProducts
        ];
    }

    /**
     * お気に入り追加.
     *
     * @Route("/products/add_favorite/{id}/{category_id}/{name}", name="product_add_favorite", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function addFavorite(Request $request, Product $Product, $category_id = 0, $name = '')
    {
        $this->checkVisibility($Product);

        $event = new EventArgs(
            [
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_FAVORITE_ADD_INITIALIZE, $event);

        if ($this->isGranted('ROLE_USER')) {
            $Customer = $this->getUser();
            $this->customerFavoriteProductRepository->addFavorite($Customer, $Product);
            $this->session->getFlashBag()->set('product_detail.just_added_favorite', $Product->getId());

            $event = new EventArgs(
                [
                    'Product' => $Product,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_FAVORITE_ADD_COMPLETE, $event);

            if ($category_id != 0)
                return $this->redirectToRoute('product_list', ['category_id' => intval($category_id)]);
            else if ( $name != '' )
                return $this->redirectToRoute('product_list', ['name' => $name]);
            else return $this->redirectToRoute('product_detail', ['id' => $Product->getId()]);
        } else {
            // 非会員の場合、ログイン画面を表示
            //  ログイン後の画面遷移先を設定
            $this->setLoginTargetPath($this->generateUrl('product_add_favorite', ['id' => $Product->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
            $this->session->getFlashBag()->set('eccube.add.favorite', true);

            $event = new EventArgs(
                [
                    'Product' => $Product,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_FAVORITE_ADD_COMPLETE, $event);

            return $this->redirectToRoute('mypage_login');
        }
    }

    /**
     * カートに追加.
     *
     * @Route("/products/add_cart/{id}", name="product_add_cart", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function addCart(Request $request, Product $Product)
    {
        // エラーメッセージの配列
        $errorMessages = [];
        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_CART_ADD_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            throw new NotFoundHttpException();
        }

        $addCartData = $form->getData();

        log_info(
            'カート追加処理開始',
            [
                'product_id' => $Product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
            ]
        );

        // カートへ追加
        $this->cartService->addProduct($addCartData['product_class_id'], $addCartData['quantity']);

        // 明細の正規化
        $Carts = $this->cartService->getCarts();
        foreach ($Carts as $Cart) {
            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
            // 復旧不可のエラーが発生した場合は追加した明細を削除.
            if ($result->hasError()) {
                $this->cartService->removeProduct($addCartData['product_class_id']);
                foreach ($result->getErrors() as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
            foreach ($result->getWarning() as $warning) {
                $errorMessages[] = $warning->getMessage();
            }
        }

        $this->cartService->save();

        log_info(
            'カート追加処理完了',
            [
                'product_id' => $Product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
            ]
        );

        $event = new EventArgs(
            [
                'form' => $form,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_CART_ADD_COMPLETE, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        if ($request->isXmlHttpRequest()) {
            // ajaxでのリクエストの場合は結果をjson形式で返す。

            // 初期化
            $done = null;
            $messages = [];

            if (empty($errorMessages)) {
                // エラーが発生していない場合
                $done = true;
                array_push($messages, trans('front.product.add_cart_complete'));
            } else {
                // エラーが発生している場合
                $done = false;
                $messages = $errorMessages;
            }

            return $this->json(['done' => $done, 'messages' => $messages]);
        } else {
            // ajax以外でのリクエストの場合はカート画面へリダイレクト
            foreach ($errorMessages as $errorMessage) {
                $this->addRequestError($errorMessage);
            }

            return $this->redirectToRoute('cart');
        }
    }

    /**
     * ページタイトルの設定
     *
     * @param  array|null $searchData
     *
     * @return str
     */
    protected function getPageTitle($searchData)
    {
        if (isset($searchData['name']) && !empty($searchData['name'])) {
            return trans('front.product.search_result');
        } elseif (isset($searchData['category_id']) && $searchData['category_id']) {
            return $searchData['category_id']->getName();
        } else {
            return trans('front.product.all_products');
        }
    }

    /**
     * 閲覧可能な商品かどうかを判定
     *
     * @param Product $Product
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    protected function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            // if ($this->BaseInfo->isOptionNostockHidden()) {
            //     if (!$Product->getStockFind()) {
            //         return false;
            //     }
            // }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * @Route("/order/contact", name="order_contact", methods={"POST", "GET"})
     * 
     * @Template("Order/index.twig")
     */
    public function orderContact(Request $request, CsrfTokenManagerInterface $tokenManager)
    {
        if ($request->getMethod() === 'POST') {
            $request->getSession()->set('productName', $_POST['order']['productName']);
            $request->getSession()->set('productPrice', $_POST['order']['productPrice']);
            $request->getSession()->set('totalQuantity', $_POST['order']['totalQuantity']);
            $request->getSession()->set('printingFee', $_POST['order']['printingFee']);
            $request->getSession()->set('printingFeeQuantity', $_POST['order']['printingFeeQuantity']); 
            $request->getSession()->set('dataPlacementFee', $_POST['order']['dataPlacementFee']);
            $request->getSession()->set('shipmentFee', $_POST['order']['shipmentFee']);
            $request->getSession()->set('zeinuki', $_POST['order']['zeinuki']);
            $request->getSession()->set('tax', $_POST['order']['tax']);
            $request->getSession()->set('totalAmount', $_POST['order']['totalAmount']);
            $request->getSession()->set('userName', $_POST['order']['userName']);
            
            $cTemp = ''; 
            if (isset($_POST['quantity'])) {
                $Colors = $_POST['quantity'];
                foreach($Colors as $key => $quantity)
                    $cTemp .= $key . ': ' . $quantity . '個, ';
                $request->getSession()->set('colors',   $cTemp); 
            } else if (isset($_POST['order']['colors'])) {
                $request->getSession()->set('colors',   $_POST['order']['colors']); 
            }
        }
        if ($request->getSession()->get('IS_ORDER_CONFIRMED') == 'false' || $request->getSession()->get('IS_ORDER_CONFIRMED') == null) {
            if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
                $request->getSession()->set('IS_ORDER_CONFIRMED', 'true');
                return $this->redirectToRoute('mypage_login');
            }
        }

        $Order = [];
        $Order['productName'] = $request->getSession()->get('productName');
        $Order['productPrice'] = $request->getSession()->get('productPrice');
        $Order['totalQuantity'] = $request->getSession()->get('totalQuantity');
        $Order['printingFee'] = $request->getSession()->get('printingFee');
        $Order['printingFeeQuantity'] = $request->getSession()->get('printingFeeQuantity');
        $Order['dataPlacementFee'] = $request->getSession()->get('dataPlacementFee');
        $Order['shipmentFee'] = $request->getSession()->get('shipmentFee');
        $Order['zeinuki'] = $request->getSession()->get('zeinuki');
        $Order['tax'] = $request->getSession()->get('tax');
        $Order['totalAmount'] = $request->getSession()->get('totalAmount');
        $Order['userName'] = $request->getSession()->get('userName');
        $Order['colors'] = $request->getSession()->get('colors');

        $builder = $this->formFactory->createBuilder(OrderContactType::class);

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
                [
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'kana01' => $user->getKana01(),
                    'kana02' => $user->getKana02(),
                    'postal_code' => $user->getPostalCode(),
                    'pref' => $user->getPref(),
                    'addr01' => $user->getAddr01(),
                    'addr02' => $user->getAddr02(),
                    'phone_number' => $user->getPhoneNumber(),
                    'email' => $user->getEmail(),
                ]
            );
        }

        // FRONT_CONTACT_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    $additionalAddress = $request->get('additional');
                    $saved_address = $request->get('saved_address');
                    if ($saved_address != null)
                        $CustomerAddress = $this->getDoctrine()->getRepository(CustomerAddress::class)->find($saved_address);
                    else $CustomerAddress = null;

                    return $this->render(
                        'Order/confirm.twig',
                        [
                            'form' => $form->createView(),
                            'Order' => $Order,
                            'additional' => $additionalAddress,
                            'CustomerAddress' => $CustomerAddress
                        ]
                    );

                case 'complete':
                    $Customer = $form->getData();
                    $Order = $request->get('order'); 
                    $additionalAddress = $request->get('additional');
                    $saved_address = $request->get('saved_address');
                    if ($saved_address != null)
                        $CustomerAddress = $this->getDoctrine()->getRepository(CustomerAddress::class)->find($saved_address);
                    else $CustomerAddress = null;

                    $customer_id = $this->getUser() ? $this->getUser()->getId() : '';

                    $Shipping = [];
                    if ($saved_address != null) {
                        $Shipping = [
                            'name' => [
                                'name01' => $CustomerAddress->getName01(),
                                'name02' =>  $CustomerAddress->getName02(),
                            ],
                            'kana' => [
                                'kana01' =>  $CustomerAddress->getKana01(),
                                'kana02' =>  $CustomerAddress->getKana02(),
                            ],
                            'postal_code' =>  $CustomerAddress->getPostalCode(),
                            'address' => [
                                'pref' =>  strval($CustomerAddress->getPref()->getId()),
                                'addr01' =>  $CustomerAddress->getAddr01(),
                                'addr02' =>  $CustomerAddress->getAddr02(),
                            ],
                            'phone_number' =>  $CustomerAddress->getPhoneNumber(),
                            'company_name' => $CustomerAddress->getCompanyName(),
                            'tracking_number' => '',
                            'Delivery' => '1',
                            'note' => '',
                            'shipping_delivery_date' => [
                              'year' => '',
                              'month' => '',
                              'day' => ''
                            ],
                            'DeliveryTime' => '',
                        ];
                    } else if ($additionalAddress != null) {
                        $Shipping = [
                            'name' => [
                                'name01' =>  $additionalAddress['name']['name01'],
                                'name02' =>  $additionalAddress['name']['name02'],
                            ],
                            'kana' => [
                                'kana01' =>  $additionalAddress['kana']['kana01'],
                                'kana02' =>  $additionalAddress['kana']['kana02'],
                            ],
                            'postal_code' =>  $additionalAddress['postal_code'],
                            'address' => [
                                'pref' =>  $additionalAddress['address']['pref'],
                                'addr01' =>  $additionalAddress['address']['addr01'],
                                'addr02' =>  $additionalAddress['address']['addr02'],
                            ],
                            'phone_number' =>  $additionalAddress['phone_number'],
                            'company_name' => '',
                            'tracking_number' => '',
                            'Delivery' => '1',
                            'note' => '',
                            'shipping_delivery_date' => [
                              'year' => '',
                              'month' => '',
                              'day' => ''
                            ],
                            'DeliveryTime' => '',
                        ];
                    } else if ($customer_id != '') {                        
                        $User = $this->getUser();
                        $Shipping = [
                            'name' => [
                                'name01' => $User->getName01(),
                                'name02' =>  $User->getName02(),
                            ],
                            'kana' => [
                                'kana01' =>  $User->getKana01(),
                                'kana02' =>  $User->getKana02(),
                            ],
                            'postal_code' =>  $User->getPostalCode(),
                            'address' => [
                                'pref' =>  $User->getPref()->getId(),
                                'addr01' =>  $User->getAddr01(),
                                'addr02' =>  $User->getAddr02(),
                            ],
                            'phone_number' =>  $User->getPhoneNumber(),
                            'company_name' => $User->getCompanyName(),
                            'tracking_number' => '',
                            'Delivery' => '1',
                            'note' => '',
                            'shipping_delivery_date' => [
                            'year' => '',
                            'month' => '',
                            'day' => ''
                            ],
                            'DeliveryTime' => '',
                        ];
                    } else {
                        $Shipping = [
                            'name' => [
                                'name01' =>  $Customer['name01'],
                                'name02' =>  $Customer['name02'],
                            ],
                            'kana' => [
                                'kana01' =>  $Customer['kana01'],
                                'kana02' =>  $Customer['kana02'],
                            ],
                            'postal_code' =>  $Customer['postal_code'],
                            'address' => [
                                'pref' =>  strval($Customer['pref']->getId()),
                                'addr01' =>  $Customer['addr01'],
                                'addr02' =>  $Customer['addr02'],
                            ],
                            'phone_number' =>  $Customer['phone_number'],
                            'company_name' => '',
                            'tracking_number' => '',
                            'Delivery' => '1',
                            'note' => '',
                            'shipping_delivery_date' => [
                              'year' => '',
                              'month' => '',
                              'day' => ''
                            ],
                            'DeliveryTime' => '',
                        ];
                    }

                    $OrderItems = [];

                    $Product = $this->productRepository->findOneBy(['name' => $Order['productName']]);

                    if (isset($Product)) {
                        array_push($OrderItems, [
                            "product_name" => $Product->getName(),
                            "ProductClass" => strval($Product->getProductClasses()[0]->getId()),
                            "order_item_type" => '1',
                            "price" => $Product->getPrice02Min(),
                            "quantity" => $Order['totalQuantity'],
                            "color" => $Order['colors'],
                            "tax_type" => '1',
                            "tax_rate" => '0'
                        ]);
                    }
                    if ($Order['printingFee'] != '') {
                        array_push($OrderItems, [
                            "product_name" => '版代',
                            "ProductClass" => '',
                            "order_item_type" => '3',
                            "price" => $Order['printingFee'],
                            "quantity" => '1',
                            "tax_type" => '1',
                            "tax_rate" => '0'
                        ]);
                    }
                    if ($Order['dataPlacementFee'] != '') {
                        array_push($OrderItems, [
                            "product_name" => '印刷代',
                            "ProductClass" => '',
                            "order_item_type" => '3',
                            "price" => $Order['dataPlacementFee'],
                            "quantity" => '1',
                            "tax_type" => '1',
                            "tax_rate" => '0'
                        ]);
                    }
                    if ($Order['tax'] != '') {
                        array_push($OrderItems, [
                            "product_name" => '消費税',
                            "ProductClass" => '',
                            "order_item_type" => '3',
                            "price" => $Order['tax'],
                            "quantity" => '1',
                            "tax_type" => '1',
                            "tax_rate" => '0'
                        ]);
                    }
                    if ($Order['shipmentFee'] != '') {
                        array_push($OrderItems, [
                            "product_name" => '送料',
                            "ProductClass" => '',
                            "order_item_type" => '5',
                            "price" => $Order['shipmentFee'],
                            "quantity" => '1',
                            "tax_type" => '1',
                            "tax_rate" => '0'
                        ]);
                    }

                    $request->request->remove('order');
                    $request->request->remove('contact');
                    $request->request->remove('mode');

                    $request->request->set('order', [
                        Constant::TOKEN_NAME => $tokenManager->getToken('order')->getValue(),
                        'return_link' => '',
                        'Payment' => '4',
                        'Customer' => $customer_id,
                        'name' => [
                            'name01' => $Customer['name01'],
                            'name02' => $Customer['name02'],
                        ],
                        'kana' => [
                            'kana01' => $Customer['kana01'],
                            'kana02' => $Customer['kana02'],
                        ],
                        'postal_code' => $Customer['postal_code'],
                        'address' => [
                            'pref' => strval($Customer['pref']->getId()),
                            'addr01' => $Customer['addr01'],
                            'addr02' => $Customer['addr02'],
                        ],
                        'email' => $Customer['email'],
                        'phone_number' => $Customer['phone_number'],
                        'company_name' => '',
                        'board_price' => $Order['dataPlacementFee'],
                        'print_price' => $Order['printingFee'],
                        'message' =>  $Customer['contents'],
                        'Shipping' => $Shipping,
                        'OrderItems' => $OrderItems,
                        'use_point' => '0',
                        'note' => ''
                    ]);

                    $request->setMethod('POST');
                    $this->forwardToRoute('order_regist');

                    $this->mailService->sendCstmOrderMail($Customer, $Order, $additionalAddress, $CustomerAddress);
                    $request->getSession()->set('IS_ORDER_CONFIRMED', 'false');

                    return $this->render('Order/complete.twig');
            }
        }

        return [
            'form' => $form->createView(),
            'Order' => $Order
        ];
    }

    /**
     * @Route("/order/sample/contact/{product_id}", name="sample_order_contact")
     * 
     * @Template("Order/sample.twig")
     */
    public function SampleOrderContact(Request $request, $product_id = null)
    {
        $builder = $this->formFactory->createBuilder(SampleOrderContactType::class);

        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
                [
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'kana01' => $user->getKana01(),
                    'kana02' => $user->getKana02(),
                    'postal_code' => $user->getPostalCode(),
                    'pref' => $user->getPref(),
                    'addr01' => $user->getAddr01(),
                    'addr02' => $user->getAddr02(),
                    'phone_number' => $user->getPhoneNumber(),
                    'email' => $user->getEmail(),
                ]
            );
        }
        // FRONT_CONTACT_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE, $event);
        
        if (!is_null($product_id)) {
            $Product = $this->productRepository->find($product_id);
            
            $has_class = $Product->hasProductClass();
            if (!$has_class) {
                $ProductClasses = $Product->getProductClasses();
                foreach ($ProductClasses as $pc) {
                    if (!is_null($pc->getClassCategory1())) {
                        continue;
                    }
                    if ($pc->isVisible()) {
                        $ProductClass = $pc;
                        break;
                    }
                }
                $builder->setData([
                    'product_name' => $Product->getName(),
                    'product_code' => $ProductClass->getCode(),
                    'price' => $Product->getPrice02Min()
                ]);
            }
        }

        // FRONT_CONTACT_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render(
                        'Order/sample_confirm.twig',
                        [
                            'form' => $form->createView()
                        ]
                    );

                case 'complete':
                    $data = $form->getData();
                    $this->mailService->sendSampleOrderMail($data);

                    return $this->render('Order/complete.twig');
            }
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * 受注登録.
     *
     * @Route("/order/regist", name="order_regist", methods={"GET", "POST"})
     * 
     */
    public function registeOrder(Request $request, RouterInterface $router)
    {
        $TargetOrder = null;
        $OriginOrder = null;

        // 空のエンティティを作成.
        $TargetOrder = new Order();
        $TargetOrder->addShipping((new Shipping())->setOrder($TargetOrder));

        $preOrderId = $this->orderHelper->createPreOrderId();
        $TargetOrder->setPreOrderId($preOrderId);

        // 編集前の受注情報を保持
        $OriginOrder = clone $TargetOrder;
        $OriginItems = new ArrayCollection();
        foreach ($TargetOrder->getOrderItems() as $Item) {
            $OriginItems->add($Item);
        }

        $builder = $this->formFactory->createBuilder(OrderType::class, $TargetOrder);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'OriginOrder' => $OriginOrder,
                'TargetOrder' => $TargetOrder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $purchaseContext = new PurchaseContext($OriginOrder, $OriginOrder->getCustomer());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form['OrderItems']->isValid()) {
                $event = new EventArgs(
                    [
                        'builder' => $builder,
                        'OriginOrder' => $OriginOrder,
                        'TargetOrder' => $TargetOrder,
                        'PurchaseContext' => $purchaseContext,
                    ],
                    $request
                );
                $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_PROGRESS, $event);
    
                $flowResult = $this->purchaseFlow->validate($TargetOrder, $purchaseContext);
    
                if ($flowResult->hasWarning()) {
                    foreach ($flowResult->getWarning() as $warning) {
                        $this->addWarning($warning->getMessage());
                        print_r($warning->getMessage());
                    }
                }
    
                if ($flowResult->hasError()) {
                    foreach ($flowResult->getErrors() as $error) {
                        $this->addError($error->getMessage());
                    }
                }

                if (!$flowResult->hasError()) {
                    try {
                        $this->purchaseFlow->prepare($TargetOrder, $purchaseContext);
                        $this->purchaseFlow->commit($TargetOrder, $purchaseContext);
                    } catch (PurchaseException $e) {
                        $this->addError($e->getMessage());
                    }
                    $OldStatus = $OriginOrder->getOrderStatus();
                    $NewStatus = $TargetOrder->getOrderStatus();
                    // ステータスが変更されている場合はステートマシンを実行.
                    if ($TargetOrder->getId() && $OldStatus->getId() != $NewStatus->getId()) {
                        // 発送済に変更された場合は, 発送日をセットする.
                        if ($NewStatus->getId() == OrderStatus::DELIVERED) {
                            $TargetOrder->getShippings()->map(function (Shipping $Shipping) {
                                if (!$Shipping->isShipped()) {
                                    $Shipping->setShippingDate(new \DateTime());
                                }
                            });
                        }
                        // ステートマシンでステータスは更新されるので, 古いステータスに戻す.
                        $TargetOrder->setOrderStatus($OldStatus);
                        try {
                            // FormTypeでステータスの遷移チェックは行っているのでapplyのみ実行.
                            $this->orderStateMachine->apply($TargetOrder, $NewStatus);
                        } catch (ShoppingException $e) {
                            $this->addError($e->getMessage());
                        }
                    }

                    $this->entityManager->persist($TargetOrder);
                    $this->entityManager->flush();
                    foreach ($OriginItems as $Item) {
                        if ($TargetOrder->getOrderItems()->contains($Item) === false) {
                            $this->entityManager->remove($Item);
                        }
                    }
                    $this->entityManager->flush();
                    // 新規登録時はMySQL対応のためflushしてから採番
                    $this->orderNoProcessor->process($TargetOrder, $purchaseContext);
                    $this->entityManager->flush();
                    // 会員の場合、購入回数、購入金額などを更新
                    if ($Customer = $TargetOrder->getCustomer()) {
                        $this->orderRepository->updateOrderSummary($Customer);
                        $this->entityManager->flush();
                    }
                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'OriginOrder' => $OriginOrder,
                            'TargetOrder' => $TargetOrder,
                            'Customer' => $Customer,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE, $event);
                    $this->addSuccess('admin.common.save_complete');
                    log_info('受注登録完了', [$TargetOrder->getId()]);
                    if ($returnLink = $form->get('return_link')->getData()) {
                        try {
                            // $returnLinkはpathの形式で渡される. pathが存在するかをルータでチェックする.
                            $pattern = '/^'.preg_quote($request->getBasePath(), '/').'/';
                            $returnLink = preg_replace($pattern, '', $returnLink);
                            $result = $router->match($returnLink);
                            // パラメータのみ抽出
                            $params = array_filter($result, function ($key) {
                                return 0 !== \strpos($key, '_');
                            }, ARRAY_FILTER_USE_KEY);
                            // pathからurlを再構築してリダイレクト.
                            return $this->redirectToRoute($result['_route'], $params);
                        } catch (\Exception $e) {
                            // マッチしない場合はログ出力してスキップ.
                            log_warning('URLの形式が不正です。');
                        }
                    }
                    return new Response('true');
                }
            }
        } 

        return new Response('false');
    }
}
