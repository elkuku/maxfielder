<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('importImages', CheckboxType::class, [
                'required' => false,
            ])
            ->add('forceUpdate', CheckboxType::class, [
                'required' => false,
            ])
            ->add(
                'multiexportjson',
                TextareaType::class,
                [
                    'attr' => ['cols' => '30', 'rows' => '5'],
                    'required' => false,
                ]
            );
    }
}
