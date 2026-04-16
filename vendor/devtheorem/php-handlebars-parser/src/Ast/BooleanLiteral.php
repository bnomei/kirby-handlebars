<?php

namespace DevTheorem\HandlebarsParser\Ast;

class BooleanLiteral extends Literal
{
    public function __construct(
        public bool $value,
        bool $original,
        SourceLocation $loc,
    ) {
        parent::__construct($original, 'BooleanLiteral', $loc);
    }
}
