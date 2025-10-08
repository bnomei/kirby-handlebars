<?php

declare(strict_types=1);

namespace Bnomei;

use Closure;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

final class LncFile
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $this->read($data);
    }

    public function source(): string
    {
        return A::get($this->data, 'source', '');
    }

    public function name(): string
    {
        return A::get($this->data, 'name', '');
    }

    public function target(): string
    {
        return A::get($this->data, 'target', '');
    }

    public function partial(): bool
    {
        return A::get($this->data, 'partial', false);
    }

    public function needsUpdate(): bool
    {
        return A::get($this->data, 'needsUpdate', false);
    }

    public function modified(): ?int
    {
        return A::get($this->data, 'modified');
    }

    public function toArray(): array
    {
        $copy = $this->data;

        foreach (['hbs', 'php'] as $remove) {
            if (A::get($copy, $remove)) {
                unset($copy[$remove]);
            }
        }

        return $copy;
    }

    public function read(array $data): array
    {
        $source = $data['source'];
        $target = A::get($data, 'target');

        $data['needsUpdate'] = false;
        if ($target && F::exists($target) === false) {
            $data['needsUpdate'] = true;
        } elseif ($source && $target && F::exists($target) && F::modified($source) > F::modified($target)) {
            $data['needsUpdate'] = true;
        } elseif ($source && F::modified($source) !== $data['modified']) {
            $data['needsUpdate'] = true;
        }

        return $data;
    }

    public function writePartial(): bool
    {
        if ($this->partial()) {
            $this->data['needsUpdate'] = false;

            return F::write($this->target(), ''); // touch
        }

        return false;
    }

    public function php(): Closure
    {
        $closure = A::get($this->data, 'php');
        if ($closure) {
            return $closure;
        }

        if ($this->target() && A::get($this->data, 'lnc') && F::exists($this->target())) {
            $php = require $this->target();

            $this->data['php'] = $php;
            $this->data['needsUpdate'] = false;

            return $php;
        }

        return fn (array $data) => '';
    }

    public function hbs(): string
    {
        $hbs = A::get($this->data, 'hbs');

        // lazy loading
        if (! $hbs && $this->source() && F::exists($this->source())) {
            $hbs = F::read($this->source());
            if ($hbs === false) {
                $this->data['hbs'] = '';
            } else {
                // fix fractal.build syntax
                $hbs = Str::replace($hbs, '{{> @', '{{> ');
                $hbs = Str::replace($hbs, ' this }}', '}}');
                $this->data['hbs'] = $hbs;
            }
        }

        return A::get($this->data, 'hbs');
    }
}
