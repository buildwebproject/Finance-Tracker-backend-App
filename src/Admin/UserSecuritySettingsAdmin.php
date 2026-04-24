<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class UserSecuritySettingsAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'updatedAt';
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('user')
            ->add('appLockEnabled', null, ['label' => 'security_settings.fields.app_lock_enabled'])
            ->add('biometricEnabled', null, ['label' => 'security_settings.fields.biometric_enabled'])
            ->add('mpinUpdatedAt', null, ['label' => 'security_settings.fields.mpin_updated_at'])
            ->add('createdAt', null, ['label' => 'security_settings.fields.created_at'])
            ->add('updatedAt', null, ['label' => 'security_settings.fields.updated_at']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user', null, ['label' => 'security_settings.fields.user'])
            ->add('appLockEnabled', null, ['label' => 'security_settings.fields.app_lock_enabled', 'editable' => true])
            ->add('biometricEnabled', null, ['label' => 'security_settings.fields.biometric_enabled', 'editable' => true])
            ->add('hasMpin', null, ['label' => 'security_settings.fields.has_mpin'])
            ->add('mpinUpdatedAt', null, ['label' => 'security_settings.fields.mpin_updated_at'])
            ->add('updatedAt', null, ['label' => 'security_settings.fields.updated_at']);

        $list->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
            'translation_domain' => 'SonataAdminBundle',
            'actions' => [
                'show' => [],
                'edit' => [],
            ],
        ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, [
                'label' => 'security_settings.fields.user',
                'disabled' => $this->hasSubject() && null !== $this->getSubject()?->getId(),
            ])
            ->add('appLockEnabled', null, ['label' => 'security_settings.fields.app_lock_enabled'])
            ->add('biometricEnabled', null, ['label' => 'security_settings.fields.biometric_enabled']);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user', null, ['label' => 'security_settings.fields.user'])
            ->add('appLockEnabled', null, ['label' => 'security_settings.fields.app_lock_enabled'])
            ->add('biometricEnabled', null, ['label' => 'security_settings.fields.biometric_enabled'])
            ->add('hasMpin', null, ['label' => 'security_settings.fields.has_mpin'])
            ->add('mpinUpdatedAt', null, ['label' => 'security_settings.fields.mpin_updated_at'])
            ->add('createdAt', null, ['label' => 'security_settings.fields.created_at'])
            ->add('updatedAt', null, ['label' => 'security_settings.fields.updated_at']);
    }
}
