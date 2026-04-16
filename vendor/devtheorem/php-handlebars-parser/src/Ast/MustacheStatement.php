<?php

namespace DevTheorem\HandlebarsParser\Ast;

class MustacheStatement extends Statement
{
    /**
     * @param Expression[] $params
     */
    public function __construct(
        string $type,
        public PathExpression|Literal|SubExpression $path,
        public array $params,
        public ?Hash $hash,
        public bool $escaped,
        public StripFlags $strip,
        SourceLocation $loc,
    ) {
        parent::__construct($type, $loc);
    }
}
