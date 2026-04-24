<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class FinanceCategoryAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
        $sortValues[DatagridInterface::SORT_BY] = 'name';
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id', null, ['label' => 'finance_category.fields.id'])
            ->add('name', null, ['label' => 'finance_category.fields.name'])
            ->add('isActive', null, ['label' => 'finance_category.fields.is_active'])
            ->add('createdAt', null, ['label' => 'finance_category.fields.created_at']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['label' => 'finance_category.fields.id'])
            ->add('name', null, ['label' => 'finance_category.fields.name'])
            ->add('iconName', null, [
                'label' => 'finance_category.fields.icon',
                'template' => 'admin/field/category_icon.html.twig',
            ])
            ->add('isActive', null, ['label' => 'finance_category.fields.is_active', 'editable' => true])
            ->add('createdAt', null, ['label' => 'finance_category.fields.created_at']);

        $list->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
            'translation_domain' => 'SonataAdminBundle',
            'actions' => [
                'edit' => [],
                'delete' => [],
            ],
        ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', null, [
                'label' => 'finance_category.fields.name',
                'required' => true,
            ])
            ->add('iconFile', VichImageType::class, [
                'label' => 'finance_category.fields.icon',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => true,
            ])
            ->add('isActive', null, [
                'label' => 'finance_category.fields.is_active',
                'required' => false,
            ]);
    }
}
