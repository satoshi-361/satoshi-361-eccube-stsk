<?php

declare(strict_types=1);

namespace Customize\Form\Extension\Front;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Front\ContactType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ContactTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ContactType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
      EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function getExtendedType(): string
    {
        return ContactType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [ContactType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product_name', TextType::class, [
                'required' => false,
            ])
            ->add('product_code', TextType::class, [
                'required' => false,
            ]);
    }
}