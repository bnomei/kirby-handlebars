<?php

namespace DevTheorem\HandlebarsParser\Ast;

class ContentStatement extends Statement
{
    public bool $rightStripped = false;
    public bool $leftStripped = false;

    public function __construct(
        public string $value,
        public string $original,
        SourceLocation $loc,
    ) {
        parent::__construct('ContentStatement', $loc);
    }
}
