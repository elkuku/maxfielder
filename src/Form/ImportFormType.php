<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 05.10.18
 * Time: 12:55
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

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
            // ->add('intelLink', null, ['required' => false])
            // ->add(
            //     'gpxRaw',
            //     TextareaType::class,
            //     [
            //         'attr'     => ['cols' => '30', 'rows' => '5'],
            //         'required' => false,
            //     ]
            // )
            // ->add(
            //     'csvRaw',
            //     TextareaType::class,
            //     [
            //         'attr'     => ['cols' => '30', 'rows' => '5'],
            //         'required' => false,
            //     ]
            // )
            // ->add(
            //     'idmcsvRaw',
            //     TextareaType::class,
            //     [
            //         'attr'     => ['cols' => '30', 'rows' => '5'],
            //         'required' => false,
            //     ]
            // )
            // ->add(
            //     'JsonRaw',
            //     TextareaType::class,
            //     [
            //         'attr'     => ['cols' => '30', 'rows' => '5'],
            //         'required' => false,
            //     ]
            // )
            // ->add(
            //     'OffleJson',
            //     TextareaType::class,
            //     [
            //         'attr'     => ['cols' => '30', 'rows' => '5'],
            //         'required' => false,
            //     ]
            // )
            // ->add(
            //     'multiexportcsv',
            //     TextareaType::class,
            //     [
            //         'attr'     => ['cols' => '30', 'rows' => '5'],
            //         'required' => false,
            //     ]
            // )
            ->add(
                'multiexportjson',
                TextareaType::class,
                [
                    'attr' => ['cols' => '30', 'rows' => '5'],
                    'required' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
