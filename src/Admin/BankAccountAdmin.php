<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;

final class BankAccountAdmin extends AbstractAdmin
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
            ->add('id', null, ['label' => 'finance_bank.fields.id'])
            ->add('user', null, ['label' => 'finance_bank.fields.user'])
            ->add('bankName', null, ['label' => 'finance_bank.fields.bank_name'])
            ->add('nickname', null, ['label' => 'finance_bank.fields.nickname'])
            ->add('isDefault', null, ['label' => 'finance_bank.fields.is_default'])
            ->add('createdAt', null, ['label' => 'finance_bank.fields.created_at']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['label' => 'finance_bank.fields.id'])
            ->add('user', null, ['label' => 'finance_bank.fields.user'])
            ->add('bankName', null, ['label' => 'finance_bank.fields.bank_name'])
            ->add('nickname', null, ['label' => 'finance_bank.fields.nickname'])
            ->add('startingBalance', null, ['label' => 'finance_bank.fields.starting_balance'])
            ->add('currentBalance', null, ['label' => 'finance_bank.fields.current_balance'])
            ->add('isDefault', null, ['label' => 'finance_bank.fields.is_default'])
            ->add('updatedAt', null, ['label' => 'finance_bank.fields.updated_at']);

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
            ->add('id', null, ['label' => 'finance_bank.fields.id'])
            ->add('user', null, ['label' => 'finance_bank.fields.user'])
            ->add('bankName', null, ['label' => 'finance_bank.fields.bank_name'])
            ->add('nickname', null, ['label' => 'finance_bank.fields.nickname'])
            ->add('startingBalance', null, ['label' => 'finance_bank.fields.starting_balance'])
            ->add('currentBalance', null, ['label' => 'finance_bank.fields.current_balance'])
            ->add('isDefault', null, ['label' => 'finance_bank.fields.is_default'])
            ->add('createdAt', null, ['label' => 'finance_bank.fields.created_at'])
            ->add('updatedAt', null, ['label' => 'finance_bank.fields.updated_at']);
    }
}

