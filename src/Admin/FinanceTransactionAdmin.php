<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;

final class FinanceTransactionAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'occurredAt';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->clearExcept(['list', 'show']);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id', null, ['label' => 'finance_transaction.fields.id'])
            ->add('user', null, ['label' => 'finance_transaction.fields.user'])
            ->add('paymentType', null, ['label' => 'finance_transaction.fields.payment_type'])
            ->add('isIncome', null, ['label' => 'finance_transaction.fields.is_income'])
            ->add('financeCategory', null, ['label' => 'finance_transaction.fields.category_ref'])
            ->add('category', null, ['label' => 'finance_transaction.fields.category'])
            ->add('wallet', null, ['label' => 'finance_transaction.fields.wallet'])
            ->add('bankAccount', null, ['label' => 'finance_transaction.fields.bank_account'])
            ->add('occurredAt', null, ['label' => 'finance_transaction.fields.occurred_at'])
            ->add('createdAt', null, ['label' => 'finance_transaction.fields.created_at']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['label' => 'finance_transaction.fields.id'])
            ->add('user', null, ['label' => 'finance_transaction.fields.user'])
            ->add('amount', null, ['label' => 'finance_transaction.fields.amount'])
            ->add('isIncome', null, ['label' => 'finance_transaction.fields.is_income'])
            ->add('paymentType', null, ['label' => 'finance_transaction.fields.payment_type'])
            ->add('financeCategory', null, ['label' => 'finance_transaction.fields.category_ref'])
            ->add('category', null, ['label' => 'finance_transaction.fields.category'])
            ->add('wallet', null, ['label' => 'finance_transaction.fields.wallet'])
            ->add('bankAccount', null, ['label' => 'finance_transaction.fields.bank_account'])
            ->add('occurredAt', null, ['label' => 'finance_transaction.fields.occurred_at'])
            ->add('isSystemGenerated', null, ['label' => 'finance_transaction.fields.is_system_generated']);

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
            ->add('id', null, ['label' => 'finance_transaction.fields.id'])
            ->add('user', null, ['label' => 'finance_transaction.fields.user'])
            ->add('amount', null, ['label' => 'finance_transaction.fields.amount'])
            ->add('isIncome', null, ['label' => 'finance_transaction.fields.is_income'])
            ->add('paymentType', null, ['label' => 'finance_transaction.fields.payment_type'])
            ->add('financeCategory', null, ['label' => 'finance_transaction.fields.category_ref'])
            ->add('category', null, ['label' => 'finance_transaction.fields.category'])
            ->add('wallet', null, ['label' => 'finance_transaction.fields.wallet'])
            ->add('bankAccount', null, ['label' => 'finance_transaction.fields.bank_account'])
            ->add('note', null, ['label' => 'finance_transaction.fields.note'])
            ->add('occurredAt', null, ['label' => 'finance_transaction.fields.occurred_at'])
            ->add('isSystemGenerated', null, ['label' => 'finance_transaction.fields.is_system_generated'])
            ->add('sourceType', null, ['label' => 'finance_transaction.fields.source_type'])
            ->add('sourceId', null, ['label' => 'finance_transaction.fields.source_id'])
            ->add('createdAt', null, ['label' => 'finance_transaction.fields.created_at'])
            ->add('updatedAt', null, ['label' => 'finance_transaction.fields.updated_at']);
    }
}
