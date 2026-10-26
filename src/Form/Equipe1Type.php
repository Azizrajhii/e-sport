<?php

namespace App\Form;

use App\Entity\Equipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Equipe1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'équipe',
                'attr' => ['class' => 'form-control']
            ])
            ->add('maxMembers', IntegerType::class, [
                'label' => 'Membres max',
                'attr' => ['class' => 'form-control']
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo (PNG/JPEG)',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/png,image/jpeg'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipe::class,
        ]);
    }
}
