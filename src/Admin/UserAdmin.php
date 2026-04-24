<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\UserBundle\Admin\Model\UserAdmin as BaseUserAdmin;
use Sonata\UserBundle\Form\Type\RolesMatrixType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

final class UserAdmin extends BaseUserAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('username')
            ->add('fullName')
            ->add('email')
            ->add('authProvider')
            ->add('dateOfBirth')
            ->add('gender')
            ->add('googleEmail')
            ->add('twilioPhoneNumber')
            ->add('lastSocialLoginAt')
            ->add('enabled', null, ['editable' => true])
            ->add('createdAt');

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $list->add('impersonating', FieldDescriptionInterface::TYPE_STRING, [
                'virtual_field' => true,
                'template' => '@SonataUser/Admin/Field/impersonating.html.twig',
            ]);
        }

        $list->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
            'translation_domain' => 'SonataAdminBundle',
            'actions' => [
                'edit' => [],
            ],
        ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('username')
            ->add('fullName')
            ->add('email')
            ->add('authProvider')
            ->add('dateOfBirth')
            ->add('gender')
            ->add('profile')
            ->add('googleSubject')
            ->add('googleEmail')
            ->add('twilioPhoneNumber');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('username')
            ->add('fullName')
            ->add('email')
            ->add('authProvider')
            ->add('dateOfBirth')
            ->add('gender')
            ->add('profile')
            ->add('googleSubject')
            ->add('googleEmail')
            ->add('googleName')
            ->add('googlePictureUrl')
            ->add('googleEmailVerified')
            ->add('twilioPhoneNumber')
            ->add('twilioChannel')
            ->add('lastSocialLoginAt');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $emailRequired = true;
        $subject = $this->hasSubject() ? $this->getSubject() : null;
        if ($subject instanceof User && $subject->getAuthProvider() === 'twilio') {
            $emailRequired = false;
        }

        $form
            ->with('general', ['class' => 'col-md-4'])
                ->add('username')
                ->add('fullName', null, ['required' => false])
                ->add('email', null, ['required' => $emailRequired])
                ->add('dateOfBirth', DateType::class, [
                    'required' => false,
                    'widget' => 'single_text',
                ])
                ->add('gender', ChoiceType::class, [
                    'required' => false,
                    'choices' => [
                        'Male' => 'male',
                        'Female' => 'female',
                        'Other' => 'other',
                    ],
                    'placeholder' => 'Select gender',
                ])
                ->add('profile', TextareaType::class, [
                    'required' => false,
                ])
                ->add('plainPassword', PasswordType::class, [
                    'required' => (!$this->hasSubject() || null === $this->getSubject()->getId()),
                ])
                ->add('enabled', null)
            ->end()
            ->with('social_auth', ['class' => 'col-md-4'])
                ->add('authProvider', null, ['required' => false])
                ->add('googleSubject', null, ['required' => false])
                ->add('googleEmail', null, ['required' => false])
                ->add('googleName', null, ['required' => false])
                ->add('googlePictureUrl', null, ['required' => false])
                ->add('googleEmailVerified', null, ['required' => false])
                ->add('twilioPhoneNumber', null, ['required' => false])
                ->add('twilioChannel', null, ['required' => false])
                ->add('lastSocialLoginAt', null, ['required' => false])
            ->end()
            ->with('roles', ['class' => 'col-md-4'])
                ->add('realRoles', RolesMatrixType::class, [
                    'label' => false,
                    'multiple' => true,
                    'required' => false,
                ])
            ->end();
    }
}
