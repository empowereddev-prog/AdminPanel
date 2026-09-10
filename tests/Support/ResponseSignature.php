<?php

namespace Tests\Support;

/**
 * Reduces a JSON response to its shape: status code plus the recursive key
 * structure with value *types* rather than values.
 *
 * Phase 2 migrates every controller onto the shared envelope under an
 * additive-only constraint, so what must not drift is the shape the shipped app
 * parses - not ids, timestamps or message text.
 */
class ResponseSignature
{
    public static function of(int $status, ?array $payload): array
    {
        return [
            'status_code' => $status,
            'body' => self::shape($payload),
        ];
    }

    public static function shape(mixed $value): mixed
    {
        if (is_array($value)) {
            // A list: collapse to the shape of its first element, so row count
            // and ordering do not make the snapshot brittle.
            if ($value === [] || array_is_list($value)) {
                return $value === [] ? '<list:empty>' : ['<list>' => self::shape($value[0])];
            }

            $shape = [];
            foreach ($value as $key => $item) {
                $shape[$key] = self::shape($item);
            }
            ksort($shape);

            return $shape;
        }

        return match (true) {
            // Booleans keep their value: status:true and status:false are
            // different contracts, not the same shape.
            is_bool($value) => $value ? '<bool:true>' : '<bool:false>',
            is_int($value) => '<int>',
            is_float($value) => '<float>',
            is_string($value) => '<string>',
            is_null($value) => '<null>',
            default => '<' . gettype($value) . '>',
        };
    }
}
