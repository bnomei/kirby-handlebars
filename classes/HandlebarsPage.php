<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cms\Page;
use Kirby\Content\Field;

class HandlebarsPage extends Page implements HandlebarsData
{
    public static array $handlebarsData = [];

    public function handlebarsData(): array
    {
        $data = array_flip(array_map(static function ($value) { // @phpstan-ignore-line
            if (! is_string($value) && is_callable($value)) {
                $value = $value();
            }

            return $value ? strval($value) : null;
        }, static::$handlebarsData));

        foreach (array_keys($data) as $methodName) {
            $field = $this->{$methodName}();
            if (is_a($field, Field::class)) {
                $field = $field->value();
            }
            $data[$methodName] = $field;
        }

        return $data;
    }
}
