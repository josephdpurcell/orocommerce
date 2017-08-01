<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\PromotionBundle\CouponGeneration\Coupon\CouponGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;

/**
 * Service that handles Coupon Generation operation request
 *
 * Gets CouponGenerationType form data (filled by user) and generates coupons based on it
 */
class CouponGenerationHandler
{
    /**
     * @var CouponGeneratorInterface
     */
    protected $generator;

    public function __construct(CouponGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Process Coupon Generation operation request
     *
     * @param FormInterface $form
     */
    public function process(FormInterface $form)
    {
        /** @var ActionData $actionData */
        $actionData = $form->getData();
        /** @var CouponGenerationOptions $options */
        $options = $actionData->get('couponGenerationOptions');
        $this->generator->generateAndSave($options);
    }
}
