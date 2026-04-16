<?php

namespace DevTheorem\HandlebarsParser\Ast;

class BlockStatement extends Statement
{
    /**
     * @param Expression[] $params
     */
    public function __construct(
        string $type,
        public PathExpression|Literal $path,
        public array $params,
        public ?Hash $hash,
        public ?Program $program,
        public ?Program $inverse,
        public StripFlags $openStrip,
        public ?StripFlags $inverseStrip,
        public ?StripFlags $closeStrip,
        SourceLocation $loc,
    ) {
        parent::__construct($type, $loc);
    }
}
