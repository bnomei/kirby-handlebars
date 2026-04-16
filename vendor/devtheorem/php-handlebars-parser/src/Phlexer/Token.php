<?php

namespace DevTheorem\HandlebarsParser\Phlexer;

readonly class Token
{
    public function __construct(
        public string $name,
        public string $text,
        public int $line,
        public int $column,
    ) {}
}
