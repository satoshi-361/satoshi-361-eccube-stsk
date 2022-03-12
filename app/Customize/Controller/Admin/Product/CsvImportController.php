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

namespace Customize\Controller\Admin\Product;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductImage;
use Eccube\Entity\ProductStock;
use Eccube\Entity\ProductTag;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ClassCategoryRepository;
use Eccube\Repository\DeliveryDurationRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\ProductImageRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\TagRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\CsvImportService;
use Eccube\Util\CacheUtil;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvImportController extends AbstractCsvImportController
{
    /**
     * @var DeliveryDurationRepository
     */
    protected $deliveryDurationRepository;

    /**
     * @var SaleTypeRepository
     */
    protected $saleTypeRepository;

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ClassCategoryRepository
     */
    protected $classCategoryRepository;

    /**
     * @var ProductImageRepository
     */
    protected $productImageRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    private $errors = [];

    protected $isSplitCsv = false;

    protected $csvFileNo = 1;

    protected $currentLineNo = 1;

    /**
     * CsvImportController constructor.
     *
     * @param DeliveryDurationRepository $deliveryDurationRepository
     * @param SaleTypeRepository $saleTypeRepository
     * @param TagRepository $tagRepository
     * @param CategoryRepository $categoryRepository
     * @param ClassCategoryRepository $classCategoryRepository
     * @param ProductImageRepository $productImageRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductRepository $productRepository
     * @param TaxRuleRepository $taxRuleRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param ValidatorInterface $validator
     *
     * @throws \Exception
     */
    public function __construct(
        DeliveryDurationRepository $deliveryDurationRepository,
        SaleTypeRepository $saleTypeRepository,
        TagRepository $tagRepository,
        CategoryRepository $categoryRepository,
        ClassCategoryRepository $classCategoryRepository,
        ProductImageRepository $productImageRepository,
        ProductStatusRepository $productStatusRepository,
        ProductRepository $productRepository,
        TaxRuleRepository $taxRuleRepository,
        BaseInfoRepository $baseInfoRepository,
        ValidatorInterface $validator
    ) {
        $this->deliveryDurationRepository = $deliveryDurationRepository;
        $this->saleTypeRepository = $saleTypeRepository;
        $this->tagRepository = $tagRepository;
        $this->categoryRepository = $categoryRepository;
        $this->classCategoryRepository = $classCategoryRepository;
        $this->productImageRepository = $productImageRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productRepository = $productRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->validator = $validator;
    }

    /**
     * 商品登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/product_csv_upload", name="admin_product_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_product.twig")
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function csvProduct(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $headers = $this->getProductCsvHeader();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->isSplitCsv = $form['is_split_csv']->getData();
                $this->csvFileNo = $form['csv_file_no']->getData();

                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('商品CSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $columnHeaders = $data->getColumnHeaders();

                    if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $size = count($data); 

                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $headerSize = count($columnHeaders);
                    $headerByKey = array_flip(array_map($getId, $headers));

                    $deleteImages = [];

                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        $line = $this->convertLineNo($data->key() + 1);
                        $this->currentLineNo = $line;
                        if ($headerSize != count($row)) {
                            $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        }

                        $Product = new Product();
                        $this->entityManager->persist($Product);

                        // if (StringUtil::isNotBlank($row[$headerByKey['name']]) && isset($row[$headerByKey['product_code']]) && StringUtil::isNotBlank($row[$headerByKey['product_code']])) {
                        //     $name = $row[$headerByKey['name']];
                        //     $product_code = $row[$headerByKey['product_code']];

                        //     $tempProduct = $this->productRepository->findOneBy(['name' => $name]);

                        //     if (is_null($tempProduct)) {
                        //         $Product = new Product();
                        //         $this->entityManager->persist($Product);
                        //     } else {
                        //         if ($tempProduct->getCodeMax() == $product_code)
                        //             $Product = $tempProduct;
                        //     }
                        // } else {
                        //     $Product = new Product();
                        //     $this->entityManager->persist($Product);
                        // }

                        $Product->setStatus($this->productStatusRepository->find(\Eccube\Entity\Master\ProductStatus::DISPLAY_SHOW));

                        if (StringUtil::isBlank($row[$headerByKey['name']])) {
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['name']]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, $headers);
                        } else {
                            $Product->setName(StringUtil::trimAll($row[$headerByKey['name']]));
                        }

                        if (isset($row[$headerByKey['note']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['note']])) {
                                $Product->setNote(StringUtil::trimAll($row[$headerByKey['note']]));
                            } else {
                                $Product->setNote(null);
                            }
                        }

                        if (isset($row[$headerByKey['description_detail']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['description_detail']])) {
                                if (mb_strlen($row[$headerByKey['description_detail']]) > $this->eccubeConfig['eccube_ltext_len']) {
                                    $message = trans('admin.common.csv_invalid_description_detail_upper_limit', [
                                        '%line%' => $line,
                                        '%name%' => $headerByKey['description_detail'],
                                        '%max%' => $this->eccubeConfig['eccube_ltext_len'],
                                    ]);
                                    $this->addErrors($message);

                                    return $this->renderWithError($form, $headers);
                                } else {
                                    $Product->setDescriptionDetail(StringUtil::trimAll($row[$headerByKey['description_detail']]));
                                }
                            } else {
                                $Product->setDescriptionDetail(null);
                            }
                        }

                        if (isset($row[$headerByKey['min_quantity']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['min_quantity']])) {
                                $Product->setMinQuantity(intval(StringUtil::trimAll($row[$headerByKey['min_quantity']])));
                            } else {
                                $Product->setMinQuantity(30);
                            }
                        }

                        if (isset($row[$headerByKey['size']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['size']])) {
                                $Product->setSize(StringUtil::trimAll($row[$headerByKey['size']]));
                            } else {
                                $Product->setSize(null);
                            }
                        }

                        if (isset($row[$headerByKey['packaging_form']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['packaging_form']])) {
                                $Product->setPackagingForm(StringUtil::trimAll($row[$headerByKey['packaging_form']]));
                            } else {
                                $Product->setPackagingForm(null);
                            }
                        }

                        if (isset($row[$headerByKey['material']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['material']])) {
                                $Product->setMaterial(StringUtil::trimAll($row[$headerByKey['material']]));
                            } else {
                                $Product->setMaterial(null);
                            }
                        }

                        if (isset($row[$headerByKey['print_range']])) {
                            if (StringUtil::isNotBlank($row[$headerByKey['print_range']])) {
                                $Product->setPrintRange(StringUtil::trimAll($row[$headerByKey['print_range']]));
                            } else {
                                $Product->setPrintRange(null);
                            }
                        }

                        // 商品画像登録
                        $this->createProductImage($row, $Product, $data, $headerByKey);

                        $this->entityManager->flush();

                        // 商品カテゴリ登録
                        $this->createProductCategory($row, $Product, $data, $headerByKey);

                        //タグ登録
                        $this->createProductTag($row, $Product, $data, $headerByKey);

                        $this->createProductClass($row, $Product, $data, $headerByKey);

                        if ($this->hasErrors()) {
                            return $this->renderWithError($form, $headers);
                        }
                        $this->entityManager->persist($Product);
                    }

                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();

                    log_info('商品CSV登録完了');
                    if (!$this->isSplitCsv) {
                        $message = 'admin.common.csv_upload_complete';
                        $this->session->getFlashBag()->add('eccube.admin.success', $message);
                    }

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * カテゴリ登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/category_csv_upload", name="admin_product_category_csv_import", methods={"GET", "POST"})
     * @Template("@admin/Product/csv_category.twig")
     */
    public function csvCategory(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();

        $headers = $this->getCategoryCsvHeader();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('カテゴリCSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $headerByKey = array_flip(array_map($getId, $headers));

                    $columnHeaders = $data->getColumnHeaders();
                    if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $size = count($data);
                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        /** @var $Category Category */
                        $Category = new Category();
                        if (isset($row[$headerByKey['id']]) && strlen($row[$headerByKey['id']]) > 0) {
                            if (!preg_match('/^\d+$/', $row[$headerByKey['id']])) {
                                $this->addErrors(($data->key() + 1).'行目のカテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                            $Category = $this->categoryRepository->find($row[$headerByKey['id']]);
                            if (!$Category) {
                                $this->addErrors(($data->key() + 1).'行目の更新対象のカテゴリIDが存在しません。新規登録の場合は、カテゴリIDの値を空で登録してください。');

                                return $this->renderWithError($form, $headers);
                            }
                            if ($row[$headerByKey['id']] == $row[$headerByKey['parent_category_id']]) {
                                $this->addErrors(($data->key() + 1).'行目のカテゴリIDと親カテゴリIDが同じです。');

                                return $this->renderWithError($form, $headers);
                            }
                        }

                        if (isset($row[$headerByKey['category_del_flg']]) && StringUtil::isNotBlank($row[$headerByKey['category_del_flg']])) {
                            if (StringUtil::trimAll($row[$headerByKey['category_del_flg']]) == 1) {
                                if ($Category->getId()) {
                                    log_info('カテゴリ削除開始', [$Category->getId()]);
                                    try {
                                        $this->categoryRepository->delete($Category);
                                        log_info('カテゴリ削除完了', [$Category->getId()]);
                                    } catch (ForeignKeyConstraintViolationException $e) {
                                        log_info('カテゴリ削除エラー', [$Category->getId(), $e]);
                                        $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $Category->getName()]);
                                        $this->addError($message, 'admin');

                                        return $this->renderWithError($form, $headers);
                                    }
                                }

                                continue;
                            }
                        }

                        if (!isset($row[$headerByKey['category_name']]) || StringUtil::isBlank($row[$headerByKey['category_name']])) {
                            $this->addErrors(($data->key() + 1).'行目のカテゴリ名が設定されていません。');

                            return $this->renderWithError($form, $headers);
                        } else {
                            $Category->setName(StringUtil::trimAll($row[$headerByKey['category_name']]));
                        }

                        $ParentCategory = null;
                        if (isset($row[$headerByKey['parent_category_id']]) && StringUtil::isNotBlank($row[$headerByKey['parent_category_id']])) {
                            if (!preg_match('/^\d+$/', $row[$headerByKey['parent_category_id']])) {
                                $this->addErrors(($data->key() + 1).'行目の親カテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }

                            /** @var $ParentCategory Category */
                            $ParentCategory = $this->categoryRepository->find($row[$headerByKey['parent_category_id']]);
                            if (!$ParentCategory) {
                                $this->addErrors(($data->key() + 1).'行目の親カテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                        }
                        $Category->setParent($ParentCategory);

                        // Level
                        if (isset($row['階層']) && StringUtil::isNotBlank($row['階層'])) {
                            if ($ParentCategory == null && $row['階層'] != 1) {
                                $this->addErrors(($data->key() + 1).'行目の親カテゴリIDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                            $level = StringUtil::trimAll($row['階層']);
                        } else {
                            $level = 1;
                            if ($ParentCategory) {
                                $level = $ParentCategory->getHierarchy() + 1;
                            }
                        }

                        $Category->setHierarchy($level);

                        if ($this->eccubeConfig['eccube_category_nest_level'] < $Category->getHierarchy()) {
                            $this->addErrors(($data->key() + 1).'行目のカテゴリが最大レベルを超えているため設定できません。');

                            return $this->renderWithError($form, $headers);
                        }

                        if ($this->hasErrors()) {
                            return $this->renderWithError($form, $headers);
                        }
                        $this->entityManager->persist($Category);
                        $this->categoryRepository->save($Category);
                    }

                    $this->entityManager->getConnection()->commit();
                    log_info('カテゴリCSV登録完了');
                    $message = 'admin.common.csv_upload_complete';
                    $this->session->getFlashBag()->add('eccube.admin.success', $message);

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * アップロード用CSV雛形ファイルダウンロード
     *
     * @Route("/%eccube_admin_route%/product/csv_template/{type}", requirements={"type" = "\w+"}, name="admin_product_csv_template", methods={"GET"})
     *
     * @param $type
     *
     * @return StreamedResponse
     */
    public function csvTemplate(Request $request, $type)
    {
        if ($type == 'product') {
            $headers = $this->getProductCsvHeader();
            $filename = 'product.csv';
        } elseif ($type == 'category') {
            $headers = $this->getCategoryCsvHeader();
            $filename = 'category.csv';
        } else {
            throw new NotFoundHttpException();
        }

        return $this->sendTemplateResponse($request, array_keys($headers), $filename);
    }

    /**
     * 登録、更新時のエラー画面表示
     *
     * @param FormInterface $form
     * @param array $headers
     * @param bool $rollback
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function renderWithError($form, $headers, $rollback = true)
    {
        if ($this->hasErrors()) {
            if ($rollback) {
                $this->entityManager->getConnection()->rollback();
            }
        }

        $this->removeUploadedFile();

        if ($this->isSplitCsv) {
            return $this->json([
                'success' => !$this->hasErrors(),
                'success_message' => trans('admin.common.csv_upload_line_success', [
                    '%from%' => $this->convertLineNo(2),
                    '%to%' => $this->currentLineNo, ]),
                'errors' => $this->errors,
                'error_message' => trans('admin.common.csv_upload_line_error', [
                    '%from%' => $this->convertLineNo(2), ]),
            ]);
        }

        return [
            'form' => $form->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
        ];
    }

    /**
     * 商品画像の削除、登録
     *
     * @param $row
     * @param Product $Product
     * @param CsvImportService $data
     * @param $headerByKey
     */
    protected function createProductImage($row, Product $Product, $data, $headerByKey)
    {
        if (!array_key_exists('product_image', $headerByKey))
            return;

        if (!isset($row[$headerByKey['product_image']])) {
            return;
        }
        if (StringUtil::isNotBlank($row[$headerByKey['product_image']])) {
            // 画像の削除
            $ProductImages = $Product->getProductImage();
            foreach ($ProductImages as $ProductImage) {
                $Product->removeProductImage($ProductImage);
                $this->entityManager->remove($ProductImage);
            }

            // 画像の登録
            $images = explode(',', $row[$headerByKey['product_image']]);

            $sortNo = 1;

            $pattern = "/\\$|^.*.\.\\\.*|\/$|^.*.\.\/\.*/";
            foreach ($images as $image) {
                $fileName = StringUtil::trimAll($image);

                // 商品画像名のフォーマットチェック
                if (strlen($fileName) > 0 && preg_match($pattern, $fileName)) {
                    $message = trans('admin.common.csv_invalid_image', ['%line%' => $data->key() + 1, '%name%' => $headerByKey['product_image']]);
                    $this->addErrors($message);
                } else {
                    // 空文字は登録対象外
                    if (!empty($fileName)) {
                        $ProductImage = new ProductImage();
                        $ProductImage->setFileName($fileName);
                        $ProductImage->setProduct($Product);
                        $ProductImage->setSortNo($sortNo);

                        $Product->addProductImage($ProductImage);
                        $sortNo++;
                        $this->entityManager->persist($ProductImage);
                    }
                }
            }
        }
    }

    /**
     * 商品カテゴリの削除、登録
     *
     * @param $row
     * @param Product $Product
     * @param CsvImportService $data
     * @param $headerByKey
     */
    protected function createProductCategory($row, Product $Product, $data, $headerByKey)
    {
        // カテゴリの削除
        $ProductCategories = $Product->getProductCategories();
        foreach ($ProductCategories as $ProductCategory) {
            $Product->removeProductCategory($ProductCategory);
            $this->entityManager->remove($ProductCategory);
            $this->entityManager->flush();
        }

        $print_icon = '';
        
        $print_icon_1 = 'print_1color.png';
        $print_icon_2 = 'print_2color.png';
        $print_icon_3 = 'print_3color.png';
        $print_daishi = 'print_daishi.png';
        $print_fullcolor = 'print_fullcolor.png';
        $print_haku_kata = 'print_haku_kata.png';
        $print_lazer = 'print_lazer.png';

        if (StringUtil::isNotBlank($row[$headerByKey['body_color']])) {
            $Product->setBodyColor($row[$headerByKey['body_color']]);

            $categories = explode('/', $row[$headerByKey['body_color']]);
            // $color_category_names = ['ホワイト', 'クリア', 'ブラック', 'ナチュラル', 'レッド', 'ピンク', 'ブルー', 'ネイビー', 'グリーン', 'イエロー', 
            //                         'オレンジ', 'パープル', 'ブラウン', 'ベージュ', 'シルバー・グレー', 'ゴールド'];

            $color_category_names = [];
            foreach($this->categoryRepository->findOneBy(['name' => '本体カラー'])->getChildren() as $item)
                array_push($color_category_names, $item->getName());

            $color_result = [];
            foreach($categories as $category) 
            foreach($color_category_names as $key => $color_name) {
                if (str_contains($category, $color_name) || str_contains($color_name, $category) ) {
                    array_push($color_result, $color_name);
                    break;
                } 
                else {
                    if ($key == count($color_category_names) - 1)
                        array_push($color_result, $category);
                }
            }
            array_unique($color_result);

            $sortNo = 1;
            $categoriesIdList = [];
            foreach ($color_result as $category) {
                $line = $data->key() + 1;
                $Category = $this->categoryRepository->findOneBy(['name' => $category]);

                if (!$Category) {
                    // $message = trans('admin.common.csv_invalid_not_found_target', [
                    //     '%line%' => $line,
                    //     '%name%' => $headerByKey['body_color'],
                    //     '%target_name%' => $category,
                    // ]);
                    // $this->addErrors($message);

                    // $Category = new \Eccube\Entity\Category();
                    // $Category->setName($category);
                    // $Category->setSortNo($sortNo);
                    // $Parent = $this->categoryRepository->findOneBy(['name' => '本体カラー']);
                    // $Category->setParent($Parent);
                    // $Category->setHierarchy($Parent->getHierarchy() + 1);

                    $message = trans('admin.common.csv_invalid_not_found_target', [
                        '%line%' => $line,
                        '%name%' => $headerByKey['body_color'],
                        '%target_name%' => $category,
                    ]);
                    $this->addErrors($message);
                } else {
                    foreach ($Category->getPath() as $ParentCategory) {
                        if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                            $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                            $this->entityManager->persist($ProductCategory);
                            $sortNo++;
                            $Product->addProductCategory($ProductCategory);
                            $categoriesIdList[$ParentCategory->getId()] = true;
                        }
                    }
                    if (!isset($categoriesIdList[$Category->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                        $sortNo++;
                        $this->entityManager->persist($ProductCategory);
                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$Category->getId()] = true;
                    }
                }
            }
        }

        if (isset($row[$headerByKey['price02']]) && StringUtil::isNotBlank($row[$headerByKey['price02']])) {
            $price02 = str_replace(',', '', $row[$headerByKey['price02']]);
            
            $price02 = str_replace('¥', '', $price02);
            $price02 = str_replace('￥', '', $price02);
            $errors = $this->validator->validate($price02, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $price_range = [0, 100, 150, 200, 250, 300, 350, 400, 500, 750, 1000, 1500, 999999];
                $price_category_name = '';
                $sortNo = 1;
        
                for ($i = 0; $i < count($price_range) - 1; $i++) {
                    if ($price02 >= $price_range[$i] && $price02 < $price_range[$i + 1]) {
                        if ($price_range[$i] == 0) $price_category_name .= '～';
                        else $price_category_name = $price_range[$i] . '円～';
        
                        if ($price_range[$i + 1] != 999999) $price_category_name .= ($price_range[$i + 1] - 1) . '円';
                        break;
                    }
                }
                $Category = $this->categoryRepository->findOneBy(['name' => $price_category_name]);     
                
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;
                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
                $this->addErrors($message);
            }
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
            $this->addErrors($message);
        }
        

        if (StringUtil::isNotBlank($row[$headerByKey['silk1']])) {
            // シルク１色の登録
            $silk1 = 'シルク1色'.'　'.$row[$headerByKey['silk1']];
            $print_icon = $print_icon_1;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $silk1]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['silk1'],
                    '%target_name%' => $silk1,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['silk2']])) {
            // シルク１色の登録
            $silk2 = 'シルク2色'.'　'.$row[$headerByKey['silk2']];
            $print_icon = $print_icon_2;
            $sortNo = 1;
            // $silk2 = 'シルク2色　B';
            $Category = $this->categoryRepository->findOneBy(['name' => $silk2]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['silk2'],
                    '%target_name%' => $silk2,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['silk3']])) {
            // シルク１色の登録
            $silk3 = 'シルク3色'.'　'.$row[$headerByKey['silk3']];
            $print_icon = $print_icon_3;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $silk3]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['silk3'],
                    '%target_name%' => $silk3,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['rsilk1']])) {
            // シルク１色の登録
            $rsilk1 = '回転シルク1色'.'　'.$row[$headerByKey['rsilk1']];
            $print_icon = $print_icon_1;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $rsilk1]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['rsilk1'],
                    '%target_name%' => $rsilk1,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['rsilk2']])) {
            // シルク１色の登録
            $rsilk2 = '回転シルク2色'.'　'.$row[$headerByKey['rsilk2']];
            $print_icon = $print_icon_2;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $rsilk2]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['rsilk2'],
                    '%target_name%' => $rsilk2,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['rsilk3']])) {
            // シルク１色の登録
            $rsilk3 = '回転シルク3色'.'　'.$row[$headerByKey['rsilk3']];
            $print_icon = $print_icon_3;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $rsilk3]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['rsilk3'],
                    '%target_name%' => $rsilk3,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['inkjet']])) {
            // シルク１色の登録
            $inkjet = 'インクジェット'.'　'.$row[$headerByKey['inkjet']];
            $print_icon = $print_fullcolor;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $inkjet]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['inkjet'],
                    '%target_name%' => $inkjet,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['sublimation']])) {
            // シルク１色の登録
            $sublimation = '昇華転写'.'　'.$row[$headerByKey['sublimation']];
            $print_icon = $print_fullcolor;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $sublimation]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['sublimation'],
                    '%target_name%' => $sublimation,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['stamping']])) {
            // シルク１色の登録
            $stamping = '素押し・箔押し'.'　'.$row[$headerByKey['stamping']];
            $print_icon = $print_haku_kata;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $stamping]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['stamping'],
                    '%target_name%' => $stamping,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['full_color']])) {
            // シルク１色の登録
            $full_color = '台紙フルカラー'.'　'.$row[$headerByKey['full_color']];
            $print_icon = $print_daishi;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $full_color]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['full_color'],
                    '%target_name%' => $full_color,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['thermal_size']])) {
            // シルク１色の登録
            $thermal_size = '熱転写Sサイズ'.'　'.$row[$headerByKey['thermal_size']];
            $print_icon = $print_fullcolor;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $thermal_size]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['thermal_size'],
                    '%target_name%' => $thermal_size,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['maximum_size']])) {
            // シルク１色の登録
            $maximum_size = '熱転写最大サイズ'.'　'.$row[$headerByKey['maximum_size']];
            $print_icon = $print_fullcolor;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $maximum_size]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['maximum_size'],
                    '%target_name%' => $maximum_size,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['pad']])) {
            // シルク１色の登録
            $pad = 'パッド1色'.'　'.$row[$headerByKey['pad']];
            $print_icon = $print_icon_1;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $pad]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['pad'],
                    '%target_name%' => $pad,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['laser']])) {
            // シルク１色の登録
            $laser = 'レーザー'.'　'.$row[$headerByKey['laser']];
            $print_icon = $print_lazer;
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $laser]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['laser'],
                    '%target_name%' => $laser,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['category']])) {
            // シルク１色の登録
            $category = $row[$headerByKey['category']];
            $sortNo = 1;

            $Category = $this->categoryRepository->findOneBy(['name' => $category]);
            if (!$Category) {
                $message = trans('admin.common.csv_invalid_not_found_target', [
                    '%line%' => $line,
                    '%name%' => $headerByKey['category'],
                    '%target_name%' => $category,
                ]);
                $this->addErrors($message);
            } else {
                foreach ($Category->getPath() as $ParentCategory) {
                    if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                        $this->entityManager->persist($ProductCategory);
                        $sortNo++;

                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }
                if (!isset($categoriesIdList[$Category->getId()])) {
                    $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                    $sortNo++;
                    $this->entityManager->persist($ProductCategory);
                    $Product->addProductCategory($ProductCategory);
                    $categoriesIdList[$Category->getId()] = true;
                }
            }
        }

        if (StringUtil::isNotBlank($row[$headerByKey['target']])) {
            // カテゴリの登録
            $categories = explode(',', $row[$headerByKey['target']]);
            $sortNo = 1;
            $categoriesIdList = [];
            foreach ($categories as $category) {
                $line = $data->key() + 1;
                $Category = $this->categoryRepository->findOneBy(['name' => $category]);

                if (!$Category) {
                    $message = trans('admin.common.csv_invalid_not_found_target', [
                        '%line%' => $line,
                        '%name%' => $headerByKey['target'],
                        '%target_name%' => $category,
                    ]);
                    $this->addErrors($message);
                } else {
                    foreach ($Category->getPath() as $ParentCategory) {
                        if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                            $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                            $this->entityManager->persist($ProductCategory);
                            $sortNo++;
                            $Product->addProductCategory($ProductCategory);
                            $categoriesIdList[$ParentCategory->getId()] = true;
                        }
                    }
                    if (!isset($categoriesIdList[$Category->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                        $sortNo++;
                        $this->entityManager->persist($ProductCategory);
                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$Category->getId()] = true;
                    }
                }
            }
        }
        
        if (StringUtil::isNotBlank($row[$headerByKey['scene']])) {
            $scene_category_names = ['営業マン配布粗品', '展示会・見本市', 'コンサート・ライブ', 'イベント・キャンペーン', 'セミナーイベント', 
            '創立・周年パーティー', '開店記念', 'スポーツ関連大会', 'オープンキャンバス関連', '卒業式', '同人関連', 'OEMノベルティ'];
            $scene_result = [];
            $category = $row[$headerByKey['scene']];
            $sortNo = 1;
            
            foreach($scene_category_names as $scene_name) {
                if (str_contains($category, $scene_name)) array_push($scene_result, $scene_name);
            }
            array_unique($scene_result);
            
            foreach($scene_result as $category) {
                $Category = $this->categoryRepository->findOneBy(['name' => $category]);
                if (!$Category) {
                    $message = trans('admin.common.csv_invalid_not_found_target', [
                        '%line%' => $line,
                        '%name%' => $headerByKey['scene'],
                        '%target_name%' => $category,
                    ]);
                    $this->addErrors($message);
                } else {
                    foreach ($Category->getPath() as $ParentCategory) {
                        if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                            $ProductCategory = $this->makeProductCategory($Product, $ParentCategory, $sortNo);
                            $this->entityManager->persist($ProductCategory);
                            $sortNo++;
    
                            $Product->addProductCategory($ProductCategory);
                            $categoriesIdList[$ParentCategory->getId()] = true;
                        }
                    }
                    if (!isset($categoriesIdList[$Category->getId()])) {
                        $ProductCategory = $this->makeProductCategory($Product, $Category, $sortNo);
                        $sortNo++;
                        $this->entityManager->persist($ProductCategory);
                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$Category->getId()] = true;
                    }
                }
            }
        }
        
        $this->entityManager->persist($Product);
        $this->entityManager->flush();
    }

    /**
     * タグの登録
     *
     * @param array $row
     * @param Product $Product
     * @param CsvImportService $data
     */
    protected function createProductTag($row, Product $Product, $data, $headerByKey)
    {
        if (!array_key_exists('product_tag', $headerByKey))
            return;

        if (!isset($row[$headerByKey['product_tag']])) {
            return;
        }
        // タグの削除
        $ProductTags = $Product->getProductTag();
        foreach ($ProductTags as $ProductTag) {
            $Product->removeProductTag($ProductTag);
            $this->entityManager->remove($ProductTag);
        }

        if (StringUtil::isNotBlank($row[$headerByKey['product_tag']])) {
            // タグの登録
            $tags = explode(',', $row[$headerByKey['product_tag']]);
            foreach ($tags as $tag_id) {
                $Tag = null;
                if (preg_match('/^\d+$/', $tag_id)) {
                    $Tag = $this->tagRepository->find($tag_id);

                    if ($Tag) {
                        $ProductTags = new ProductTag();
                        $ProductTags
                            ->setProduct($Product)
                            ->setTag($Tag);

                        $Product->addProductTag($ProductTags);

                        $this->entityManager->persist($ProductTags);
                    }
                }
                if (!$Tag) {
                    $message = trans('admin.common.csv_invalid_not_found_target', [
                        '%line%' => $data->key() + 1,
                        '%name%' => $headerByKey['product_tag'],
                        '%target_name%' => $tag_id,
                    ]);
                    $this->addErrors($message);
                }
            }
        }
    }

    /**
     * 商品規格分類1、商品規格分類2がnullとなる商品規格情報を作成
     *
     * @param $row
     * @param Product $Product
     * @param CsvImportService $data
     * @param $headerByKey
     * @param null $ClassCategory1
     * @param null $ClassCategory2
     *
     * @return ProductClass
     */
    protected function createProductClass($row, Product $Product, $data, $headerByKey, $ClassCategory1 = null, $ClassCategory2 = null)
    {
        // 規格分類1、規格分類2がnullとなる商品を作成
        $ProductClass = new ProductClass();
        $ProductClass->setProduct($Product);
        $ProductClass->setVisible(true);

        $ProductClass->setStockUnlimited(true);
        $ProductClass->setStock(null);
        $ProductClass->setSaleType($this->saleTypeRepository->find(\Eccube\Entity\Master\SaleType::SALE_TYPE_NORMAL));
        
        if (isset($row[$headerByKey['product_code']]) && StringUtil::isNotBlank($row[$headerByKey['product_code']])) {
            $ProductClass->setCode(StringUtil::trimAll($row[$headerByKey['product_code']]));
        } else {
            $ProductClass->setCode(null);
        }

        if (isset($row[$headerByKey['price01']]) && StringUtil::isNotBlank($row[$headerByKey['price01']])) {
            $price01 = str_replace(',', '', $row[$headerByKey['price01']]);
            $errors = $this->validator->validate($price01, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $price01 = str_replace('¥', '', $price01);
                $price01 = str_replace('￥', '', $price01);

                $ProductClass->setPrice01($price01);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price01']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['price02']]) && StringUtil::isNotBlank($row[$headerByKey['price02']])) {
            $price02 = str_replace(',', '', $row[$headerByKey['price02']]);
            $errors = $this->validator->validate($price02, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $price02 = str_replace('¥', '', $price02);
                $price02 = str_replace('￥', '', $price02);

                $ProductClass->setPrice02($price02);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
                $this->addErrors($message);
            }
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
            $this->addErrors($message);
        }

        $Product->addProductClass($ProductClass);
        $ProductStock = new ProductStock();
        $ProductClass->setProductStock($ProductStock);
        $ProductStock->setProductClass($ProductClass);

        if (!$ProductClass->isStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        $this->entityManager->persist($ProductClass);
        $this->entityManager->persist($ProductStock);

        return $ProductClass;
        
        /*--------------------------------------------------------------------------------------------------------------

        if (array_key_exists('product_tag', $headerByKey)) {
            $line = $data->key() + 1;
            if (isset($row[$headerByKey['sale_type']]) && StringUtil::isNotBlank($row[$headerByKey['sale_type']])) {
                if (preg_match('/^\d+$/', $row[$headerByKey['sale_type']])) {
                    $SaleType = $this->saleTypeRepository->find($row[$headerByKey['sale_type']]);
                    if (!$SaleType) {
                        $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                        $this->addErrors($message);
                    } else {
                        $ProductClass->setSaleType($SaleType);
                    }
                } else {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                    $this->addErrors($message);
                }
            } else {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                $this->addErrors($message);
            }
        }

        $ProductClass->setClassCategory1($ClassCategory1);
        $ProductClass->setClassCategory2($ClassCategory2);

        if (isset($row[$headerByKey['delivery_date']]) && StringUtil::isNotBlank($row[$headerByKey['delivery_date']])) {
            if (preg_match('/^\d+$/', $row[$headerByKey['delivery_date']])) {
                $DeliveryDuration = $this->deliveryDurationRepository->find($row[$headerByKey['delivery_date']]);
                if (!$DeliveryDuration) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['delivery_date']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setDeliveryDuration($DeliveryDuration);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['delivery_date']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['product_code']]) && StringUtil::isNotBlank($row[$headerByKey['product_code']])) {
            $ProductClass->setCode(StringUtil::trimAll($row[$headerByKey['product_code']]));
        } else {
            $ProductClass->setCode(null);
        }

        if (!isset($row[$headerByKey['stock_unlimited']])
            || StringUtil::isBlank($row[$headerByKey['stock_unlimited']])
            || $row[$headerByKey['stock_unlimited']] == (string) Constant::DISABLED
        ) {
            $ProductClass->setStockUnlimited(false);
            // 在庫数が設定されていなければエラー
            if (isset($row[$headerByKey['stock']]) && StringUtil::isNotBlank($row[$headerByKey['stock']])) {
                $stock = str_replace(',', '', $row[$headerByKey['stock']]);
                if (preg_match('/^\d+$/', $stock) && $stock >= 0) {
                    $ProductClass->setStock($stock);
                } else {
                    $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['stock']]);
                    $this->addErrors($message);
                }
            } else {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['stock']]);
                $this->addErrors($message);
            }
        } elseif ($row[$headerByKey['stock_unlimited']] == (string) Constant::ENABLED) {
            $ProductClass->setStockUnlimited(true);
            $ProductClass->setStock(null);
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['stock_unlimited']]);
            $this->addErrors($message);
        }

        if (isset($row[$headerByKey['sale_limit']]) && StringUtil::isNotBlank($row[$headerByKey['sale_limit']])) {
            $saleLimit = str_replace(',', '', $row[$headerByKey['sale_limit']]);
            if (preg_match('/^\d+$/', $saleLimit) && $saleLimit >= 0) {
                $ProductClass->setSaleLimit($saleLimit);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['sale_limit']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['price01']]) && StringUtil::isNotBlank($row[$headerByKey['price01']])) {
            $price01 = str_replace(',', '', $row[$headerByKey['price01']]);
            $errors = $this->validator->validate($price01, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice01($price01);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price01']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['price02']]) && StringUtil::isNotBlank($row[$headerByKey['price02']])) {
            $price02 = str_replace(',', '', $row[$headerByKey['price02']]);
            $errors = $this->validator->validate($price02, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice02($price02);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
                $this->addErrors($message);
            }
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
            $this->addErrors($message);
        }

        if ($this->BaseInfo->isOptionProductDeliveryFee()) {
            if (isset($row[$headerByKey['delivery_fee']]) && StringUtil::isNotBlank($row[$headerByKey['delivery_fee']])) {
                $delivery_fee = str_replace(',', '', $row[$headerByKey['delivery_fee']]);
                $errors = $this->validator->validate($delivery_fee, new GreaterThanOrEqual(['value' => 0]));
                if ($errors->count() === 0) {
                    $ProductClass->setDeliveryFee($delivery_fee);
                } else {
                    $message = trans('admin.common.csv_invalid_greater_than_zero',
                        ['%line%' => $line, '%name%' => $headerByKey['delivery_fee']]);
                    $this->addErrors($message);
                }
            }
        }

        $Product->addProductClass($ProductClass);
        $ProductStock = new ProductStock();
        $ProductClass->setProductStock($ProductStock);
        $ProductStock->setProductClass($ProductClass);

        if (!$ProductClass->isStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        $this->entityManager->persist($ProductClass);
        $this->entityManager->persist($ProductStock);

        return $ProductClass;
        ---------------------------------------------------------------------------------------------------- */
    }

    /**
     * 商品規格情報を更新
     *
     * @param $row
     * @param Product $Product
     * @param ProductClass $ProductClass
     * @param CsvImportService $data
     *
     * @return ProductClass
     */
    protected function updateProductClass($row, Product $Product, ProductClass $ProductClass, $data, $headerByKey)
    {
        $ProductClass->setProduct($Product);

        $line = $data->key() + 1;
        if (!isset($row[$headerByKey['sale_type']]) || $row[$headerByKey['sale_type']] == '') {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
            $this->addErrors($message);
        } else {
            if (preg_match('/^\d+$/', $row[$headerByKey['sale_type']])) {
                $SaleType = $this->saleTypeRepository->find($row[$headerByKey['sale_type']]);
                if (!$SaleType) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setSaleType($SaleType);
                }
            } else {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['sale_type']]);
                $this->addErrors($message);
            }
        }

        // 規格分類1、2をそれぞれセットし作成
        if (isset($row[$headerByKey['class_category1']]) && $row[$headerByKey['class_category1']] != '') {
            if (preg_match('/^\d+$/', $row[$headerByKey['class_category1']])) {
                $ClassCategory = $this->classCategoryRepository->find($row[$headerByKey['class_category1']]);
                if (!$ClassCategory) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category1']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setClassCategory1($ClassCategory);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category1']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['class_category2']]) && $row[$headerByKey['class_category2']] != '') {
            if (preg_match('/^\d+$/', $row[$headerByKey['class_category2']])) {
                $ClassCategory = $this->classCategoryRepository->find($row[$headerByKey['class_category2']]);
                if (!$ClassCategory) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category2']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setClassCategory2($ClassCategory);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['class_category2']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['delivery_date']]) && $row[$headerByKey['delivery_date']] != '') {
            if (preg_match('/^\d+$/', $row[$headerByKey['delivery_date']])) {
                $DeliveryDuration = $this->deliveryDurationRepository->find($row[$headerByKey['delivery_date']]);
                if (!$DeliveryDuration) {
                    $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['delivery_date']]);
                    $this->addErrors($message);
                } else {
                    $ProductClass->setDeliveryDuration($DeliveryDuration);
                }
            } else {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $headerByKey['delivery_date']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['product_code']]) && StringUtil::isNotBlank($row[$headerByKey['product_code']])) {
            $ProductClass->setCode(StringUtil::trimAll($row[$headerByKey['product_code']]));
        } else {
            $ProductClass->setCode(null);
        }

        if (!isset($row[$headerByKey['stock_unlimited']])
            || StringUtil::isBlank($row[$headerByKey['stock_unlimited']])
            || $row[$headerByKey['stock_unlimited']] == (string) Constant::DISABLED
        ) {
            $ProductClass->setStockUnlimited(false);
            // 在庫数が設定されていなければエラー
            if ($row[$headerByKey['stock']] == '') {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['stock']]);
                $this->addErrors($message);
            } else {
                $stock = str_replace(',', '', $row[$headerByKey['stock']]);
                if (preg_match('/^\d+$/', $stock) && $stock >= 0) {
                    $ProductClass->setStock($row[$headerByKey['stock']]);
                } else {
                    $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['stock']]);
                    $this->addErrors($message);
                }
            }
        } elseif ($row[$headerByKey['stock_unlimited']] == (string) Constant::ENABLED) {
            $ProductClass->setStockUnlimited(true);
            $ProductClass->setStock(null);
        } else {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['stock_unlimited']]);
            $this->addErrors($message);
        }

        if (isset($row[$headerByKey['sale_limit']]) && $row[$headerByKey['sale_limit']] != '') {
            $saleLimit = str_replace(',', '', $row[$headerByKey['sale_limit']]);
            if (preg_match('/^\d+$/', $saleLimit) && $saleLimit >= 0) {
                $ProductClass->setSaleLimit($saleLimit);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['sale_limit']]);
                $this->addErrors($message);
            }
        }

        if (isset($row[$headerByKey['price01']]) && $row[$headerByKey['price01']] != '') {
            $price01 = str_replace(',', '', $row[$headerByKey['price01']]);
            $errors = $this->validator->validate($price01, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice01($price01);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price01']]);
                $this->addErrors($message);
            }
        }

        if (!isset($row[$headerByKey['price02']]) || $row[$headerByKey['price02']] == '') {
            $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
            $this->addErrors($message);
        } else {
            $price02 = str_replace(',', '', $row[$headerByKey['price02']]);
            $errors = $this->validator->validate($price02, new GreaterThanOrEqual(['value' => 0]));
            if ($errors->count() === 0) {
                $ProductClass->setPrice02($price02);
            } else {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $headerByKey['price02']]);
                $this->addErrors($message);
            }
        }

        $ProductStock = $ProductClass->getProductStock();

        if (!$ProductClass->isStockUnlimited()) {
            $ProductStock->setStock($ProductClass->getStock());
        } else {
            // 在庫無制限時はnullを設定
            $ProductStock->setStock(null);
        }

        return $ProductClass;
    }

    /**
     * 登録、更新時のエラー画面表示
     */
    protected function addErrors($message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * 商品登録CSVヘッダー定義
     *
     * @return array
     */
    protected function getProductCsvHeader()
    {
        return [
            trans('品番') => [
                'id' => 'product_code',
                'description' => '品番',
                'required' => true,
            ],
            trans('admin.product.product_csv.product_name_col') => [
                'id' => 'name',
                'description' => 'admin.product.product_csv.product_name_description',
                'required' => true,
            ],
            trans('税込価格') => [
                'id' => 'price01',
                'description' => '税込価格',
                'required' => false,
            ],
            trans('税抜価格') => [
                'id' => 'price02',
                'description' => '税抜価格',
                'required' => true,
            ],
            trans('本体色') => [
                'id' => 'body_color',
                'description' => '本体色',
                'required' => true,
            ],
            trans('最小発注数') => [
                'id' => 'min_quantity',
                'description' => '最小発注数',
                'required' => false,
            ],
            trans('商品サイズ') => [
                'id' => 'size',
                'description' => '商品サイズ',
                'required' => false,
            ],
            trans('個装形態') => [
                'id' => 'packaging_form',
                'description' => '個装形態',
                'required' => false,
            ],
            trans('素材') => [
                'id' => 'material',
                'description' => '素材',
                'required' => false,
            ],
            trans('備考') => [
                'id' => 'note',
                'description' => '備考',
                'required' => false,
            ],
            trans('印刷・範囲') => [
                'id' => 'print_range',
                'description' => '印刷・範囲',
                'required' => false,
            ],
            trans('商品説明') => [
                'id' => 'description_detail',
                'description' => '商品説明',
                'required' => false,
            ],
            trans('シルク１色') => [
                'id' => 'silk1',
                'description' => 'シルク１色',
                'required' => false,
            ],
            trans('シルク２色') => [
                'id' => 'silk2',
                'description' => 'シルク２色',
                'required' => false,
            ],
            trans('シルク３色') => [
                'id' => 'silk3',
                'description' => 'シルク３色',
                'required' => false,
            ],
            trans('回転シルク1色') => [
                'id' => 'rsilk1',
                'description' => '回転シルク1色',
                'required' => false,
            ],
            trans('回転シルク2色') => [
                'id' => 'rsilk2',
                'description' => '回転シルク2色',
                'required' => false,
            ],
            trans('回転シルク3色') => [
                'id' => 'rsilk3',
                'description' => '回転シルク3色',
                'required' => false,
            ],
            trans('インクジェット') => [
                'id' => 'inkjet',
                'description' => 'インクジェット',
                'required' => false,
            ],
            trans('昇華転写') => [
                'id' => 'sublimation',
                'description' => '昇華転写',
                'required' => false,
            ],
            trans('素押し・箔押し') => [
                'id' => 'stamping',
                'description' => '素押し・箔押し',
                'required' => false,
            ],
            trans('台紙フルカラー') => [
                'id' => 'full_color',
                'description' => '台紙フルカラー',
                'required' => false,
            ],
            trans('熱転写Sサイズ') => [
                'id' => 'thermal_size',
                'description' => '熱転写Sサイズ',
                'required' => false,
            ],
            trans('熱転写最大サイズ') => [
                'id' => 'maximum_size',
                'description' => '熱転写最大サイズ',
                'required' => false,
            ],
            trans('パッド1色') => [
                'id' => 'pad',
                'description' => 'パッド1色',
                'required' => false,
            ],
            trans('レーザー') => [
                'id' => 'laser',
                'description' => 'レーザー',
                'required' => false,
            ],
            trans('カテゴリ') => [
                'id' => 'category',
                'description' => 'カテゴリ',
                'required' => false,
            ],
            trans('ターゲット') => [
                'id' => 'target',
                'description' => 'ターゲット',
                'required' => false,
            ],
            trans('目的シーン') => [
                'id' => 'scene',
                'description' => '目的シーン',
                'required' => false,
            ],
        ];
    }

    /**
     * カテゴリCSVヘッダー定義
     */
    protected function getCategoryCsvHeader()
    {
        return [
            trans('admin.product.category_csv.category_id_col') => [
                'id' => 'id',
                'description' => 'admin.product.category_csv.category_id_description',
                'required' => false,
            ],
            trans('admin.product.category_csv.category_name_col') => [
                'id' => 'category_name',
                'description' => 'admin.product.category_csv.category_name_description',
                'required' => true,
            ],
            trans('admin.product.category_csv.parent_category_id_col') => [
                'id' => 'parent_category_id',
                'description' => 'admin.product.category_csv.parent_category_id_description',
                'required' => false,
            ],
            trans('admin.product.category_csv.delete_flag_col') => [
                'id' => 'category_del_flg',
                'description' => 'admin.product.category_csv.delete_flag_description',
                'required' => false,
            ],
        ];
    }

    /**
     * ProductCategory作成
     *
     * @param \Eccube\Entity\Product $Product
     * @param \Eccube\Entity\Category $Category
     * @param int $sortNo
     *
     * @return ProductCategory
     */
    private function makeProductCategory($Product, $Category, $sortNo)
    {
        $ProductCategory = new ProductCategory();
        $ProductCategory->setProduct($Product);
        $ProductCategory->setProductId($Product->getId());
        $ProductCategory->setCategory($Category);
        $ProductCategory->setCategoryId($Category->getId());

        return $ProductCategory;
    }

    /**
     * @Route("/%eccube_admin_route%/product/csv_split", name="admin_product_csv_split", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function splitCsv(Request $request)
    {
        $this->isTokenValid();

        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dir = $this->eccubeConfig['eccube_csv_temp_realdir'];
            if (!file_exists($dir)) {
                $fs = new Filesystem();
                $fs->mkdir($dir);
            }

            $data = $form['import_file']->getData();
            $src = new \SplFileObject($data->getRealPath());
            $src->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);

            $fileNo = 1;
            $fileName = StringUtil::random(8);

            $dist = new \SplFileObject($dir.'/'.$fileName.$fileNo.'.csv', 'w');
            $header = $src->current();
            $src->next();
            $dist->fputcsv($header);

            $i = 0;
            while ($row = $src->current()) {
                $dist->fputcsv($row);
                $src->next();

                if (!$src->eof() && ++$i % $this->eccubeConfig['eccube_csv_split_lines'] === 0) {
                    $fileNo++;
                    $dist = new \SplFileObject($dir.'/'.$fileName.$fileNo.'.csv', 'w');
                    $dist->fputcsv($header);
                }
            }

            return $this->json(['success' => true, 'file_name' => $fileName, 'max_file_no' => $fileNo]);
        }

        return $this->json(['success' => false, 'message' => $form->getErrors(true, true)]);
    }

    /**
     * @Route("/%eccube_admin_route%/product/csv_split_import", name="admin_product_csv_split_import", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function importCsv(Request $request, CsrfTokenManagerInterface $tokenManager)
    {
        $this->isTokenValid();

        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $choices = $this->getCsvTempFiles();

        $filename = $request->get('file_name');
        if (!isset($choices[$filename])) {
            throw new BadRequestHttpException();
        }

        $path = $this->eccubeConfig['eccube_csv_temp_realdir'].'/'.$filename;
        $request->files->set('admin_csv_import', ['import_file' => new UploadedFile(
            $path,
            'import.csv',
            'text/csv',
            filesize($path),
            null,
            true
        )]);

        $request->setMethod('POST');
        $request->request->set('admin_csv_import', [
            Constant::TOKEN_NAME => $tokenManager->getToken('admin_csv_import')->getValue(),
            'is_split_csv' => true,
            'csv_file_no' => $request->get('file_no'),
        ]);

        return $this->forwardToRoute('admin_product_csv_import');
    }

    /**
     * @Route("/%eccube_admin_route%/product/csv_split_cleanup", name="admin_product_csv_split_cleanup", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function cleanupSplitCsv(Request $request)
    {
        $this->isTokenValid();

        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $files = $request->get('files', []);
        $choices = $this->getCsvTempFiles();

        foreach ($files as $filename) {
            if (isset($choices[$filename])) {
                unlink($choices[$filename]);
            } else {
                return $this->json(['success' => false]);
            }
        }

        return $this->json(['success' => true]);
    }

    protected function getCsvTempFiles()
    {
        $files = Finder::create()
            ->in($this->eccubeConfig['eccube_csv_temp_realdir'])
            ->name('*.csv')
            ->files();

        $choices = [];
        foreach ($files as $file) {
            $choices[$file->getBaseName()] = $file->getRealPath();
        }

        return $choices;
    }

    protected function convertLineNo($currentLineNo)
    {
        if ($this->isSplitCsv) {
            return ($this->eccubeConfig['eccube_csv_split_lines']) * ($this->csvFileNo - 1) + $currentLineNo;
        }

        return $currentLineNo;
    }
}
