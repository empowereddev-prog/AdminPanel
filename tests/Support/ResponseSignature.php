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
    public static function of(int $status, mixed $payload): array
    {
        return [
            'status_code' => $status,
            'body' => self::shape($payload),
        ];
    }

    public static function shape(mixed $value): mixed
    {
        // A JSON object and a JSON array are different contracts to a typed
        // mobile client: {} decoded into a [Video] fails. Decoding assoc made
        // both arrive here as [], so `data: []` could silently become `data: {}`
        // and the gate saw no change. Objects therefore stay objects.
        if ($value instanceof \stdClass) {
            $fields = get_object_vars($value);

            if ($fields === []) {
                return '<object:empty>';
            }

            $shape = [];
            foreach ($fields as $key => $item) {
                $shape[$key] = self::shape($item);
            }
            ksort($shape);

            return $shape;
        }

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
