<?php

namespace DevTheorem\HandlebarsParser\Ast;

readonly class OpenBlock extends OpenHelper
{
    /**
     * @param Expression[] $params
     * @param string[] $blockParams
     */
    public function __construct(
        public string $open,
        PathExpression|Literal $path,
        array $params,
        ?Hash $hash,
        public array $blockParams,
        public StripFlags $strip,
    ) {
        parent::__construct($path, $params, $hash);
    }
}
