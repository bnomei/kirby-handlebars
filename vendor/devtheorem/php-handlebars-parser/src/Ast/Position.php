<?php

namespace DevTheorem\HandlebarsParser\Ast;

readonly class Position
{
    public function __construct(
        public int $line,
        public int $column,
    ) {}
}
