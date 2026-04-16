<?php

namespace DevTheorem\HandlebarsParser\Ast;

class Node
{
    public function __construct(
        public string $type,
        public SourceLocation $loc,
    ) {}
}
