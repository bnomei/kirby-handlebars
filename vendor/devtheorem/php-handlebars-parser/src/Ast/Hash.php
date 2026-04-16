<?php

namespace DevTheorem\HandlebarsParser\Ast;

class Hash extends Node
{
    /**
     * @param HashPair[] $pairs
     */
    public function __construct(
        public array $pairs,
        SourceLocation $loc,
    ) {
        parent::__construct('Hash', $loc);
    }
}
