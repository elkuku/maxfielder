<?php

namespace App\Form;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('agent_name')
            ->add(
                'lat',
                NumberType::class,
                [
                    // 'html5'    => true,
                    'required' => false,
                    'scale'    => 7,
                    'attr'     => [
                        'min'  => -90,
                        'max'  => 90,
                        'step' => 0.0000001,
                    ],
                ]
            )
            ->add(
                'lon',
                NumberType::class,
                [
                    // 'html5'    => true,
                    'required' => false,
                    'scale'    => 7,
                    'attr'     => [
                        'min'  => -90,
                        'max'  => 90,
                        'step' => 0.0000001,
                    ],
                ]
            )
            ->add('zoom')
        ->add('default_style', EnumType::class, ['class' => MapBoxStylesEnum::class])
        ->add('default_profile', EnumType::class, ['class' => MapBoxProfilesEnum::class])
        ;
    }
}