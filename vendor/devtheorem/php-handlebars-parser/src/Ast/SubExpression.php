<?php

namespace DevTheorem\HandlebarsParser\Ast;

class SubExpression extends Expression
{
    /**
     * @param Expression[] $params
     */
    public function __construct(
        public SubExpression|PathExpression|Literal $path,
        public array $params,
        public ?Hash $hash,
        SourceLocation $loc,
    ) {
        parent::__construct('SubExpression', $loc);
    }
}
