<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Settings\UserSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<UserSettings>
 */
class ProfileFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void
    {
        $builder
            ->add('agentName')
            ->add(
                'lat',
                NumberType::class,
                [
                    'required' => false,
                    'scale' => 7,
                    'attr' => [
                        'min' => -90,
                        'max' => 90,
                        'step' => 0.0000001,
                    ],
                ]
            )
            ->add(
                'lon',
                NumberType::class,
                [
                    'required' => false,
                    'scale' => 7,
                    'attr' => [
                        'min' => -90,
                        'max' => 90,
                        'step' => 0.0000001,
                    ],
                ]
            )
            ->add('zoom')
            ->add('mapboxApiKey')
            ->add(
                'defaultStyle',
                EnumType::class,
                [
                    'class' => MapBoxStylesEnum::class,
                    'choice_label' => fn(MapBoxStylesEnum $category): string => str_replace('_', ' ', $category->name),
                ]
            )
            ->add(
                'defaultProfile',
                EnumType::class,
                [
                    'class' => MapBoxProfilesEnum::class,
                ]
            )
            ->add(
                'mapProvider',
                EnumType::class,
                [
                    'class' => MapProvidersEnum::class,
                    'choice_label' => fn(MapProvidersEnum $element): string => ucfirst($element->name),
                ]
            );
    }
}
