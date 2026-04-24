<?php

declare(strict_types=1);

namespace App\Admin\Extension;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class UserAdminCurrencyExtension extends AbstractAdminExtension
{
    public function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('preferredCurrencyCode', null, ['label' => 'Preferred Currency Code']);
    }

    public function configureListFields(ListMapper $list): void
    {
        $list->add('preferredCurrencyCode', null, ['label' => 'Preferred Currency Code']);

        if (!$list->has(ListMapper::NAME_ACTIONS)) {
            return;
        }

        $keys = $list->keys();
        $keys = array_values(array_filter($keys, static fn (string $key): bool => 'preferredCurrencyCode' !== $key));

        $actionsIndex = array_search(ListMapper::NAME_ACTIONS, $keys, true);
        if (false === $actionsIndex) {
            $keys[] = 'preferredCurrencyCode';
            $list->reorder($keys);

            return;
        }

        array_splice($keys, $actionsIndex, 0, ['preferredCurrencyCode']);
        $list->reorder($keys);
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form->add('preferredCurrencyCode', null, [
            'label' => 'Preferred Currency Code',
            'required' => false,
            'help' => 'Use 3-letter ISO 4217 code, example: USD, INR, EUR.',
        ]);
    }

    public function configureShowFields(ShowMapper $show): void
    {
        $show->add('preferredCurrencyCode', null, ['label' => 'Preferred Currency Code']);
    }
}
