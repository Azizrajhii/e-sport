<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Affiche / saisit une date-heure au format fixe AAAA-MM-JJ hh:mm (sans extension intl).
 */
final class LocalDateTimeTransformer implements DataTransformerInterface
{
    public const FORMAT = 'Y-m-d H:i';

    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Valeur de date invalide.');
        }

        return $value->format(self::FORMAT);
    }

    public function reverseTransform(mixed $value): ?\DateTime
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new TransformationFailedException('Format de date invalide.');
        }

        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $dt = \DateTime::createFromFormat(self::FORMAT, $value);
        if ($dt instanceof \DateTime) {
            return $dt;
        }

        throw new TransformationFailedException(
            'Date invalide. Utilisez le format AAAA-MM-JJ hh:mm (ex. 2026-06-15 14:30).'
        );
    }
}
