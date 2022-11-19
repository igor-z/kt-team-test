<?php

declare(strict_types=1);

namespace App\Admin;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

class ProductCategoryAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name', null, ['route' => ['name' => 'edit']])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'delete' => [],
                ],
            ]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
        $collection->get('list')
            ->setPath('/product-categories');
        $collection->get('create')
            ->setPath('/product-categories/create');
        $collection->get('edit')
            ->setPath('/product-categories/{id}/edit');
        $collection->get('delete')
            ->setPath('/product-categories/{id}/delete');
    }
}