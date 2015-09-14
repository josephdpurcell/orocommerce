<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;

class ProductRowType extends AbstractType
{
    const NAME = 'orob2b_product_row';

    const PRODUCT_SKU_FIELD_NAME = 'productSku';
    const PRODUCT_QUANTITY_FIELD_NAME = 'productQuantity';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $productSkuOptions = [
            'required' => false,
            'label' => 'orob2b.product.sku.label',
            'constraints' => [new NotBlank()]
        ];
        if ($options['validation_required']) {
            $productSkuOptions['constraints'][] = new ProductBySku();
        }

        $builder
            ->add(self::PRODUCT_SKU_FIELD_NAME, 'text', $productSkuOptions)
            ->add(
                self::PRODUCT_QUANTITY_FIELD_NAME,
                'number',
                [
                    'required' => false,
                    'label' => 'orob2b.product.quantity.label',
                    'constraints' => [new Decimal()]
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}