<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;

final class WalletAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'createdAt';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->clearExcept(['list', 'show']);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id', null, ['label' => 'finance_wallet.fields.id'])
            ->add('user', null, ['label' => 'finance_wallet.fields.user'])
            ->add('name', null, ['label' => 'finance_wallet.fields.name'])
            ->add('createdAt', null, ['label' => 'finance_wallet.fields.created_at']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['label' => 'finance_wallet.fields.id'])
            ->add('user', null, ['label' => 'finance_wallet.fields.user'])
            ->add('name', null, ['label' => 'finance_wallet.fields.name'])
            ->add('startingBalance', null, ['label' => 'finance_wallet.fields.starting_balance'])
            ->add('currentBalance', null, ['label' => 'finance_wallet.fields.current_balance'])
            ->add('colorValue', null, ['label' => 'finance_wallet.fields.color_value'])
            ->add('iconCodePoint', null, ['label' => 'finance_wallet.fields.icon_code_point'])
            ->add('updatedAt', null, ['label' => 'finance_wallet.fields.updated_at']);

        $list->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
            'translation_domain' => 'SonataAdminBundle',
            'actions' => [
                'show' => [],
            ],
        ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id', null, ['label' => 'finance_wallet.fields.id'])
            ->add('user', null, ['label' => 'finance_wallet.fields.user'])
            ->add('name', null, ['label' => 'finance_wallet.fields.name'])
            ->add('startingBalance', null, ['label' => 'finance_wallet.fields.starting_balance'])
            ->add('currentBalance', null, ['label' => 'finance_wallet.fields.current_balance'])
            ->add('colorValue', null, ['label' => 'finance_wallet.fields.color_value'])
            ->add('iconCodePoint', null, ['label' => 'finance_wallet.fields.icon_code_point'])
            ->add('createdAt', null, ['label' => 'finance_wallet.fields.created_at'])
            ->add('updatedAt', null, ['label' => 'finance_wallet.fields.updated_at']);
    }
}

