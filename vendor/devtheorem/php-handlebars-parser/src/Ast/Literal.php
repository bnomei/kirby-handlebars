<?php

namespace DevTheorem\HandlebarsParser\Ast;

abstract class Literal extends Expression
{
    public function __construct(
        public string|int|float|bool|null $original,
        string $type,
        SourceLocation $loc,
    ) {
        parent::__construct($type, $loc);
    }
}
