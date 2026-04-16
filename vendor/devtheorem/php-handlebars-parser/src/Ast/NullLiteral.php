<?php

namespace DevTheorem\HandlebarsParser\Ast;

class NullLiteral extends Literal
{
    public function __construct(
        public null $value,
        null $original,
        SourceLocation $loc,
    ) {
        parent::__construct($original, 'NullLiteral', $loc);
    }
}
