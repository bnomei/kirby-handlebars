<?php

namespace DevTheorem\HandlebarsParser\Ast;

class Program extends Node
{
    /**
     * @param Statement[] $body
     * @param string[] $blockParams
     */
    public function __construct(
        public array $body,
        public array $blockParams,
        SourceLocation $loc,
        public bool $chained = false,
    ) {
        parent::__construct('Program', $loc);
    }
}
