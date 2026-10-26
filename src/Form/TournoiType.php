<?php

namespace App\Form;

use App\Entity\Jeu;
use App\Entity\Tournoi;
use App\Form\DataTransformer\LocalDateTimeTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournoiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du tournoi',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: CS2 Tunis Major Open 2026'],
            ])
            ->add('jeu', EntityType::class, [
                'class' => Jeu::class,
                'choice_label' => 'nom',
                'label' => 'Jeu',
                'placeholder' => '-- Sélectionner un jeu --',
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Open, Qualifiers'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'En Attente',
                    'En cours' => 'En cours',
                    'Terminé' => 'Terminé',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('date_debut', TextType::class, [
                'mapped' => false,
                'label' => 'Date de début',
                'help' => 'Obligatoire. Format : AAAA-MM-JJ hh:mm (ex. 2026-06-15 10:00).',
                'attr' => [
                    'class' => 'form-control tournoi-datetime-text',
                    'placeholder' => '2026-06-15 10:00',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('date_fin', TextType::class, [
                'mapped' => false,
                'label' => 'Date de fin',
                'help' => 'Doit être postérieure à la date de début. Même format.',
                'attr' => [
                    'class' => 'form-control tournoi-datetime-text',
                    'placeholder' => '2026-06-22 18:00',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('dateInscriptionLimite', TextType::class, [
                'mapped' => false,
                'label' => 'Date limite d\'inscription',
                'required' => false,
                'help' => 'Optionnel. Laisser vide si aucune limite.',
                'attr' => [
                    'class' => 'form-control tournoi-datetime-text',
                    'placeholder' => '2026-06-14 23:59',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre max de participants',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 16'],
            ])
            ->add('cagnotte', NumberType::class, [
                'label' => 'Cagnotte (€)',
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 12000.00', 'step' => '0.01'],
            ])
            ->add('fraisInscription', NumberType::class, [
                'label' => 'Frais d\'inscription (€)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '0 pour gratuit'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
        ;

        $transformer = new LocalDateTimeTransformer();

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($transformer): void {
            $tournoi = $event->getData();
            if (!$tournoi instanceof Tournoi) {
                return;
            }

            if (null === $tournoi->getId() && null === $tournoi->getDateDebut() && null === $tournoi->getDateFin()) {
                $tournoi->applyDefaultSchedule();
            }

            $form = $event->getForm();
            $form->get('date_debut')->setData($transformer->transform($tournoi->getDateDebut()));
            $form->get('date_fin')->setData($transformer->transform($tournoi->getDateFin()));
            $form->get('dateInscriptionLimite')->setData($transformer->transform($tournoi->getDateInscriptionLimite()));
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($transformer): void {
            $tournoi = $event->getData();
            if (!$tournoi instanceof Tournoi) {
                return;
            }

            $form = $event->getForm();
            $parsed = $this->parseDateFields($form, $transformer);
            if (null === $parsed) {
                return;
            }

            $tournoi->setDateDebut($parsed['debut']);
            $tournoi->setDateFin($parsed['fin']);
            $tournoi->setDateInscriptionLimite($parsed['limite']);
        });
    }

    /**
     * @return array{debut: ?\DateTime, fin: ?\DateTime, limite: ?\DateTime}|null null si erreur de format
     */
    private function parseDateFields(FormInterface $form, LocalDateTimeTransformer $transformer): ?array
    {
        $rawDebut = $form->get('date_debut')->getData();
        $rawFin = $form->get('date_fin')->getData();
        $rawLimite = $form->get('dateInscriptionLimite')->getData();

        $ok = true;
        $debut = $fin = $limite = null;

        try {
            $debut = $transformer->reverseTransform(null !== $rawDebut ? (string) $rawDebut : '');
        } catch (TransformationFailedException $e) {
            $ok = false;
            $form->get('date_debut')->addError(new FormError($e->getMessage()));
        }

        try {
            $fin = $transformer->reverseTransform(null !== $rawFin ? (string) $rawFin : '');
        } catch (TransformationFailedException $e) {
            $ok = false;
            $form->get('date_fin')->addError(new FormError($e->getMessage()));
        }

        try {
            $limite = $transformer->reverseTransform(null !== $rawLimite ? (string) $rawLimite : '');
        } catch (TransformationFailedException $e) {
            $ok = false;
            $form->get('dateInscriptionLimite')->addError(new FormError($e->getMessage()));
        }

        return $ok ? ['debut' => $debut, 'fin' => $fin, 'limite' => $limite] : null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournoi::class,
        ]);
    }
}
