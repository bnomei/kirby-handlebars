<?php

namespace DevTheorem\HandlebarsParser\Ast;

class PartialStatement extends Statement
{
    /**
     * @param Expression[] $params
     */
    public function __construct(
        public PathExpression|SubExpression|Literal $name,
        public array $params,
        public ?Hash $hash,
        public string $indent,
        public StripFlags $strip,
        SourceLocation $loc,
    ) {
        parent::__construct('PartialStatement', $loc);
    }
}
