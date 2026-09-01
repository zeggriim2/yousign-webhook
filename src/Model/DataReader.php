<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Model;

use DateTimeImmutable;

/**
 * Tolerant readers shared by the payload models.
 *
 * Yousign (YouTrust) may add, reorder or reformat properties without a major
 * version bump, so every read degrades to null instead of failing.
 *
 * @internal
 */
final class DataReader
{
    /**
     * @param array<string, mixed> $data
     */
    public static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function bool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        if (null === $value) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function dateTime(array $data, string $key): ?DateTimeImmutable
    {
        $value = self::string($data, $key);

        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function map(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (!\is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $mapKey => $mapValue) {
            $map[(string) $mapKey] = $mapValue;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    public static function mapList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (!\is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (\is_array($item)) {
                $items[] = self::map(['item' => $item], 'item');
            }
        }

        return $items;
    }
}
