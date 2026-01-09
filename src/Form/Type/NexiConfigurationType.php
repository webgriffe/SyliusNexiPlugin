<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class NexiConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, ['label' => 'Merchant Alias'])
            ->add('mac_key', TextType::class, ['label' => 'Mac Key'])
            ->add(
                'sandbox',
                ChoiceType::class,
                [
                    'choices' => [
                        'Yes' => true,
                        'No' => false,
                    ],
                    'label' => 'Sanbox Mode Enabled',
                ],
            )
        ;
    }
}
