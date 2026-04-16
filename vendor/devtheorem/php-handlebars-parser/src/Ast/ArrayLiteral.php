<?php

namespace DevTheorem\HandlebarsParser\Ast;

class ArrayLiteral extends Expression
{
    /**
     * @param Expression[] $items
     */
    public function __construct(
        public array $items,
        SourceLocation $loc,
    ) {
        parent::__construct('ArrayLiteral', $loc);
    }
}
