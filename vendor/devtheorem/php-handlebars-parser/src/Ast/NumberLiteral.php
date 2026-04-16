<?php

namespace DevTheorem\HandlebarsParser\Ast;

class NumberLiteral extends Literal
{
    public function __construct(
        public int|float $value,
        int|float $original,
        SourceLocation $loc,
    ) {
        parent::__construct($original, 'NumberLiteral', $loc);
    }
}
