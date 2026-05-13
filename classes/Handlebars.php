<?php

declare(strict_types=1);

namespace Bnomei;

use Closure;
use Exception;
use Kirby\Cms\Page;
use Kirby\Content\Field;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Query\Query;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use Stringable;
use Throwable;

final class Handlebars
{
    private LncFiles $lncFiles;

    private array $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'extension-output' => option('bnomei.handlebars.extension-output'),
            'extension-input' => option('bnomei.handlebars.extension-input'),
            'queries' => option('bnomei.handlebars.queries'),
            'resolve-content-queries' => option('bnomei.handlebars.resolve-content-queries', false),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if ($call instanceof Closure) {
                $this->options[$key] = $call();
            }
        }

        $this->lncFiles = LncFiles::singleton($this->options);
    }

    public function option(?string $key = null): mixed
    {
        if ($key) {
            return A::get($this->options, $key);
        }

        return $this->options;
    }

    public function name(string $file): string
    {
        return str_replace('@', '', basename($file, '.'.strval($this->option('extension-input'))));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function file(string $name): string
    {
        return $this->lncFiles->hbsFile($name);
    }

    private function array_map_recursive(array $arr, Closure $fn): array
    {
        return array_map(function ($item) use ($fn) {
            return is_array($item) ? $this->array_map_recursive($item, $fn) : $fn($item);
        }, $arr);
    }

    /*
     * PHP array_merge_recursive creates arrays where there
     * where none when merging.
     */
    private function array_merge_recursive(array $array, array $merge): array
    {
        foreach ($merge as $key => $value) {
            if (array_key_exists($key, $array) && is_array($array[$key])) {
                $array[$key] = $this->array_merge_recursive($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    private function normalizeQueryValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeQueryValue($item), $value);
        }

        if ($value instanceof Field) {
            return $value->value();
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return $value;
    }

    private function resolveConfiguredQuery(string $query, array $params): mixed
    {
        try {
            return $this->normalizeQueryValue(Query::factory($query)->resolve($params));
        } catch (Throwable) {
            return null;
        }
    }

    private function queryData(string $query, mixed $value): array
    {
        $result = $value;
        foreach (array_reverse(explode('.', $query)) as $key) {
            $result = [$key => $result];
        }

        return $result;
    }

    public function addQueries(array $data, array $params = []): array
    {
        $queries = $this->option('queries');
        if (! is_array($queries)) {
            $queries = [];
        }

        // add queries from options
        foreach ($queries as $query) {
            $query = strval($query);
            if ($query === '') {
                continue;
            }

            $result = $this->queryData(
                $query,
                $this->resolveConfiguredQuery($query, $params)
            );
            $data = $this->array_merge_recursive($data, $result);
        }

        return $data;
    }

    public function resolveQueries(array $data, array $params): array
    {
        $resolveContentQueries = $this->option('resolve-content-queries') === true;

        // resolve queries in data
        return $this->array_map_recursive($data, function ($value) use ($params, $resolveContentQueries) {
            $value = $this->normalizeQueryValue($value);

            if (is_string($value) && Str::contains($value, '{{') && Str::contains($value, '}}')) {
                if ($resolveContentQueries) {
                    $value = Str::template($value, $params);
                }
            }

            return $value;
        });
    }

    public function modelData(array $data, ?Page $page): array
    {
        if (! $page) {
            return $data;
        }

        /** @var HandlebarsPage|Page $page */
        $modelData = $page->handlebarsData(); // @phpstan-ignore-line
        if (! is_array($modelData)) {
            return $data;
        }

        return $this->array_merge_recursive($data, $modelData);
    }

    public function fieldsToValue(array $data): array
    {
        return array_map(static function ($object) {
            if ($object && is_object($object) && is_a($object, 'Kirby\Cms\Field')) {
                return $object->value();
            }

            return $object;
        }, $data);
    }

    public function handlebars(string $template, array $data): string
    {
        return $this->lncFiles->precompiledTemplate($template)($data);
    }

    public function render(string $name, array $data = [], ?string $root = null, ?string $file = null, bool $return = false): string
    {
        $template = $this->name($file ?? $name);

        $params = [
            'kirby' => A::get($data, 'kirby'),
            'site' => A::get($data, 'site'),
            'page' => A::get($data, 'page'),
        ];

        $data = $this->addQueries($data, $params);
        $data = $this->modelData($data, $params['page']);
        $data = $this->resolveQueries($data, $params);
        $data = $this->fieldsToValue($data);

        return $this->handlebars($template, $data);
    }

    public function flush(): void
    {
        try {
            $this->lncFiles->flush();
        } catch (Exception $e) {
            //
        }
    }
}
