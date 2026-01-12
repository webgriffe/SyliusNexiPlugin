<?php

declare(strict_types=1);

namespace Webgriffe\SyliusNexiPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class NexiConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, [
                'label' => 'webgriffe_sylius_nexi.form.gateway_configuration.alias',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('mac_key', TextType::class, [
                'label' => 'webgriffe_sylius_nexi.form.gateway_configuration.mac_key',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add(
                'sandbox',
                CheckboxType::class,
                [
                    'label' => 'webgriffe_sylius_nexi.form.gateway_configuration.sandbox',
                    'required' => false,
                ],
            )
        ;
    }
}
