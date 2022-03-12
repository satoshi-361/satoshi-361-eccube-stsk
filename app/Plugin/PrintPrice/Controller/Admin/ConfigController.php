<?php

namespace Plugin\PrintPrice\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PrintPrice\Form\Type\Admin\ConfigType;
use Plugin\PrintPrice\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\PrintPrice\Entity\Config as PrintPrice;
use Eccube\Repository\CategoryRepository;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(ConfigRepository $configRepository, CategoryRepository $categoryRepository)
    {
        $this->configRepository = $configRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/print_price/config", name="print_price_admin_config")
     * @Template("@PrintPrice/admin/config.twig")
     */
    public function index(Request $request)
    {
        $header = $this->configRepository->findBy(['category_id' => 0]);

        if (isset($_POST['flag'])) {
            $flag = $_POST['flag'];
            $price_data = $_POST['data'];

            switch($flag) {
                case 'flag_header_new':
                    $newHeader = new PrintPrice();
                    $newHeader->setCategoryId(0);
                    $header_id = count($header) + 1;
                    $newHeader->setHeaderId($header_id);
                    $newHeader->setPrice($price_data);

                    $this->entityManager->persist($newHeader);
                    $this->entityManager->flush();

                    break;

                case 'flag_price_new':
                    // $data = $price_data;
                    // $newHeader = new PrintPrice();
                    // $newHeader->setCategoryId(0);
                    // $header_id = count($header) + 1;
                    // $newHeader->setHeaderId($header_id);
                    // $newHeader->setPrice($data[0]);

                    // $this->entityManager->persist($newHeader);
                    // $this->entityManager->flush();

                    // $items = $data[1];

                    $items = $price_data;
                    foreach($items as $item) {
                        $newItem =  new PrintPrice();
                        $newItem->setCategoryId($item[0]);
                        $newItem->setHeaderId($item[1]);
                        $newItem->setPrice($item[2]);

                        $this->entityManager->persist($newItem);
                    }
                    $this->entityManager->flush();

                    break;

                case 'flag_price_edited':

                    $items = $price_data;
                    foreach($items as $item) {
                        $editedItem = $this->configRepository->findOneBy(['category_id' => $item[0], 'header_id' => $item[1]]);
                        
                        if (!is_null($editedItem)) {
                            $editedItem->setPrice($item[2]);
                            $this->entityManager->persist($editedItem);
                        }
                    }
                    $this->entityManager->flush();

                    break;

                case 'flag_header_delete':
                    $header_id = $_POST['header_id'];

                    foreach($this->configRepository->findBy(['header_id' => $header_id]) as $item)
                        $this->entityManager->remove($item);
                    $this->entityManager->flush();

                    break;
            }
        }
        $header = $this->configRepository->findBy(['category_id' => 0], ['header_id' => 'ASC']);
        $body = [];
        $printCategories = $this->categoryRepository->findOneBy(['name' => '印刷方法詳細'])->getChildren();

        foreach($printCategories as $iconCategories)
        foreach($iconCategories->getChildren() as $Category) {
            array_push($body, $this->configRepository->findBy(['category_id' => $Category->getId()], ['header_id' => 'ASC']));
        }

        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush();
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('print_price_admin_config');
        }

        return [
            'form' => $form->createView(),
            'header' => $header,
            'body' => array_reverse($body)
        ];
    }
}
