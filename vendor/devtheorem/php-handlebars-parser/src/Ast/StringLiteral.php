<?php

namespace DevTheorem\HandlebarsParser\Ast;

class StringLiteral extends Literal
{
    public function __construct(
        public string $value,
        string $original,
        SourceLocation $loc,
    ) {
        parent::__construct($original, 'StringLiteral', $loc);
    }
}
