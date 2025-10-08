<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Template\Template;

final class HandlebarsTemplate extends Template
{
    private Handlebars $handlebars;

    /**
     * HandlebarsTemplate constructor.
     */
    public function __construct(string $name, string $type = 'html', string $defaultType = 'html')
    {
        $this->handlebars = new Handlebars;

        parent::__construct($name, $type, $defaultType);
    }

    /**
     * @codeCoverageIgnore
     */
    public function extension(): string
    {
        return strval(option('bnomei.handlebars.extension-input'));
    }

    public function file(): string
    {
        $name = $this->name();
        if (! $this->hasDefaultType()) {
            $name = $this->name().'.'.$this->type();
        }

        return $this->handlebars->file(
            $name
        );
    }

    public function render(array $data = []): string
    {
        $render = $this->handlebars->render(
            $this->name(),
            $data,
            $this->root(),
            $this->file(),
            true
        );

        return strval($render);
    }
}
