<?php

namespace DevTheorem\HandlebarsParser\Ast;

readonly class SourceLocation
{
    public function __construct(
        public Position $start,
        public Position $end,
    ) {}
}
