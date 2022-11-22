<?php

declare(strict_types=1);

namespace App\Admin;
use App\Entity\Weight;
use DomainException;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use ValueError;

class ProductAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name')
            ->add('weight')
            ->add('category')
            ->add('description');

        $form->get('weight')
            ->addModelTransformer(new CallbackTransformer(
                function ($weight) {
                    return (string) $weight;
                },
                function ($weight) {
                    try {
                        return Weight::fromString($weight);
                    } catch (ValueError|DomainException $e) {
                        throw new TransformationFailedException($e->getMessage());
                    }
                }
            ));
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name', null, ['route' => ['name' => 'edit']])
            ->add('weight', null, [
                'sortable' => true,
                'sort_field_mapping' => [
                    'fieldName' => 'weight.grams',
                ],
                'sort_parent_association_mappings' => [],
            ])
            ->add('category', null, [
                'sortable' => true,
                'associated_property' => 'name',
                'sort_field_mapping' => [
                    'fieldName' => 'name',
                ],
                'sort_parent_association_mappings' => [
                    ['fieldName' => 'category'],
                ],
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'delete' => [],
                ],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('category.name')
            ->add('weight_from', CallbackFilter::class, [
                'callback' => static function(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    try {
                        $weight = Weight::fromString($data->getValue());
                    } catch (DomainException|ValueError) {
                        return false;
                    }

                    $query->andWhere("$alias.weight.grams >= :weightFrom");
                    $query->setParameter('weightFrom', $weight->getGrams());

                    return true;
                },
                'field_type' => TextType::class,
                'advanced_filter' => false,
            ])
            ->add('weight_to', CallbackFilter::class, [
                'callback' => static function(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    try {
                        $weight = Weight::fromString($data->getValue());
                    } catch (DomainException|ValueError) {
                        return false;
                    }

                    $query->andWhere("$alias.weight.grams <= :weightTo");
                    $query->setParameter('weightTo', $weight->getGrams());

                    return true;
                },
                'field_type' => TextType::class,
                'advanced_filter' => false,
            ]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
        $collection->get('list')
            ->setPath('/products');
        $collection->get('create')
            ->setPath('/products/create');
        $collection->get('edit')
            ->setPath('/products/{id}/edit');
        $collection->get('delete')
            ->setPath('/products/{id}/delete');

        $collection->add('upload_import_file');
        $collection->get('upload_import_file')
            ->setPath('/products/upload-import-file');
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'products';
    }

    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $buttonList['import'] = ['template' => 'products/import_button.html.twig'];

        return $buttonList;
    }
}