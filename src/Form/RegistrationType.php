<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Nom obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 100, 'minMessage' => 'Minimum 3 caractères']),
                ],
            ])
            ->add('email', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email obligatoire']),
                    new Assert\Email(['message' => 'Email invalide']),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'first_options' => ['label' => false],
                'second_options' => ['label' => false],
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Mot de passe obligatoire']),
                    new Assert\Length(['min' => 8, 'minMessage' => 'Minimum 8 caractères']),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*[0-9]).+$/',
                        'message' => 'Au moins 1 majuscule + 1 chiffre'
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
