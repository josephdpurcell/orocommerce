<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Oro\Bundle\CustomerBundle\Entity\AccountAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\AccountGroupAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class VisibilityPostSetDataListener extends AbstractVisibilityListener
{
    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $targetEntity = $form->getData();
        if (!is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }

        $this->setFormAllData($form);
        $this->setFormAccountGroupData($form);
        $this->setFormAccountData($form);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAllData(FormInterface $form)
    {
        $visibility = $this->findFormFieldData($form, 'all');

        if ($visibility instanceof VisibilityInterface) {
            $data = $visibility->getVisibility();
        } else {
            $data = call_user_func([$form->getConfig()->getOption('allClass'), 'getDefault'], $form->getData());
        }
        $form->get('all')->setData($data);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAccountGroupData(FormInterface $form)
    {
        $visibilities = $this->findFormFieldData($form, 'accountGroup');

        $data = array_map(function ($visibility) {
            /** @var VisibilityInterface|AccountGroupAwareInterface $visibility */
            return [
                'entity' => $visibility->getAccountGroup(),
                'data' => [
                    'visibility' => $visibility->getVisibility(),
                ],
            ];
        }, $visibilities);

        $form->get('accountGroup')->setData($data);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAccountData(FormInterface $form)
    {
        $visibilities = $this->findFormFieldData($form, 'account');

        $data = array_map(function ($visibility) {
            /** @var VisibilityInterface|AccountAwareInterface $visibility */
            return [
                'entity' => $visibility->getAccount(),
                'data' => [
                    'visibility' => $visibility->getVisibility(),
                ],
            ];
        }, $visibilities);

        $form->get('account')->setData($data);
    }
}
