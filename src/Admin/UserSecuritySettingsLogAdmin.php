<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;

final class UserSecuritySettingsLogAdmin extends AbstractAdmin
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
            ->add('id')
            ->add('user')
            ->add('action', null, ['label' => 'security_log.fields.action'])
            ->add('ipAddress', null, ['label' => 'security_log.fields.ip_address'])
            ->add('createdAt', null, ['label' => 'security_log.fields.created_at']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user', null, ['label' => 'security_log.fields.user'])
            ->add('action', null, ['label' => 'security_log.fields.action'])
            ->add('ipAddress', null, ['label' => 'security_log.fields.ip_address'])
            ->add('details', null, ['label' => 'security_log.fields.details'])
            ->add('createdAt', null, ['label' => 'security_log.fields.created_at']);

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
            ->add('id')
            ->add('user', null, ['label' => 'security_log.fields.user'])
            ->add('action', null, ['label' => 'security_log.fields.action'])
            ->add('ipAddress', null, ['label' => 'security_log.fields.ip_address'])
            ->add('details', null, ['label' => 'security_log.fields.details'])
            ->add('createdAt', null, ['label' => 'security_log.fields.created_at']);
    }
}
