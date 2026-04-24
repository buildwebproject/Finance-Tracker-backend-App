<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Country;
use App\Service\CountryDataProvider;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class CountryAdmin extends AbstractAdmin
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
            ->add('search', CallbackFilter::class, [
                'label' => 'country.filters.search',
                'show_filter' => true,
                'callback' => [$this, 'filterBySearch'],
            ], [
                'field_type' => TextType::class,
                'field_options' => [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'India / IN / +91',
                    ],
                ],
            ])
            ->add('name')
            ->add('iso2Code')
            ->add('iso3Code')
            ->add('dialCode')
            ->add('currencyCode')
            ->add('currencyIcon')
            ->add('isActive');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('flagEmoji', null, [
                'label' => 'country.fields.flag',
                'template' => 'admin/field/country_flag.html.twig',
            ])
            ->add('name', null, [
                'label' => 'country.fields.name',
                'sortable' => true,
            ])
            ->add('iso2Code', null, ['label' => 'country.fields.iso2'])
            ->add('iso3Code', null, ['label' => 'country.fields.iso3'])
            ->add('dialCode', null, ['label' => 'country.fields.dial_code', 'sortable' => true])
            ->add('currencyCode', null, ['label' => 'country.fields.currency_code', 'sortable' => true])
            ->add('currencyIcon', null, ['label' => 'country.fields.currency_icon'])
            ->add('isActive', null, ['label' => 'country.fields.is_active', 'editable' => true])
            ->add('createdAt', null, ['label' => 'country.fields.created_at']);

        $list->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
            'translation_domain' => 'SonataAdminBundle',
            'actions' => [
                'edit' => [],
            ],
        ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', null, [
                'label' => 'country.fields.name',
                'required' => true,
            ])
            ->add('iso2Code', null, [
                'label' => 'country.fields.iso2',
                'required' => true,
                'help' => 'country.help.iso2',
            ])
            ->add('iso3Code', null, [
                'label' => 'country.fields.iso3',
                'required' => false,
            ])
            ->add('dialCode', null, [
                'label' => 'country.fields.dial_code',
                'required' => true,
                'help' => 'country.help.dial_code',
            ])
            ->add('flagEmoji', null, [
                'label' => 'country.fields.flag',
                'required' => false,
                'disabled' => true,
                'help' => 'country.help.flag',
            ])
            ->add('currencyCode', null, [
                'label' => 'country.fields.currency_code',
                'required' => false,
                'disabled' => true,
                'help' => 'country.help.currency_code',
            ])
            ->add('currencyIcon', null, [
                'label' => 'country.fields.currency_icon',
                'required' => false,
                'disabled' => true,
                'help' => 'country.help.currency_icon',
            ])
            ->add('isActive', null, [
                'label' => 'country.fields.is_active',
                'required' => false,
            ]);
    }

    protected function preValidate(object $object): void
    {
        if (!$object instanceof Country) {
            return;
        }

        $object->setFlagEmoji(CountryDataProvider::generateFlagEmoji($object->getIso2Code()));
    }

    protected function prePersist(object $object): void
    {
        $this->preValidate($object);
    }

    protected function preUpdate(object $object): void
    {
        $this->preValidate($object);
    }

    public function filterBySearch(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if (!$data->hasValue()) {
            return false;
        }

        $search = trim((string) $data->getValue());
        if ('' === $search) {
            return false;
        }

        $parameter = sprintf('country_search_%d', $query->getUniqueParameterId());
        $searchLike = '%'.mb_strtolower($search).'%';
        $queryBuilder = $query->getQueryBuilder();

        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->like(sprintf('LOWER(%s.name)', $alias), ':'.$parameter),
                $queryBuilder->expr()->like(sprintf('LOWER(%s.iso2Code)', $alias), ':'.$parameter),
                $queryBuilder->expr()->like(sprintf('LOWER(%s.iso3Code)', $alias), ':'.$parameter),
                $queryBuilder->expr()->like(sprintf('LOWER(%s.dialCode)', $alias), ':'.$parameter),
                $queryBuilder->expr()->like(sprintf('LOWER(%s.currencyCode)', $alias), ':'.$parameter),
                $queryBuilder->expr()->like(sprintf('LOWER(%s.currencyIcon)', $alias), ':'.$parameter),
            ))
            ->setParameter($parameter, $searchLike);

        return true;
    }
}
