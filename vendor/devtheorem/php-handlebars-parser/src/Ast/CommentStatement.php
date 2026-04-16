<?php

namespace DevTheorem\HandlebarsParser\Ast;

class CommentStatement extends Statement
{
    public function __construct(
        public string $value,
        public StripFlags $strip,
        SourceLocation $loc,
    ) {
        parent::__construct('CommentStatement', $loc);
    }
}
