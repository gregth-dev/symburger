<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //dd($options);

        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('image')
            ->add(
                'category',
                EntityType::class,
                [
                    'class' => Category::class,
                    'choices' => $options['categories'],
                    'choice_label' => 'name',
                    'label' => "Catégorie"
                ]
            )
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Ajouter une image',
                'label_attr' => ['data-browse' => 'Parcourir']
            ])
            ->add('productOrder', IntegerType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'categories' => null
        ]);
    }
}
