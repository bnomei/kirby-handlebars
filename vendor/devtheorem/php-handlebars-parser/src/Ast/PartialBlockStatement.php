<?php

namespace DevTheorem\HandlebarsParser\Ast;

class PartialBlockStatement extends Statement
{
    /**
     * @param Expression[] $params
     */
    public function __construct(
        public PathExpression|Literal $name,
        public array $params,
        public ?Hash $hash,
        public Program $program,
        public StripFlags $openStrip,
        public StripFlags $closeStrip,
        SourceLocation $loc,
    ) {
        parent::__construct('PartialBlockStatement', $loc);
    }
}
