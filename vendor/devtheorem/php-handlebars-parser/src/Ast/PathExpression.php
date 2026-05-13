<?php

namespace DevTheorem\HandlebarsParser\Ast;

class PathExpression extends Expression
{
    public bool $this;

    /**
     * @param string[] $tail
     * @param (string | SubExpression)[] $parts
     */
    public function __construct(
        bool $this_,
        public bool $data,
        public int $depth,
        public SubExpression|string $head,
        public array $tail,
        public array $parts,
        public string $original,
        SourceLocation $loc,
    ) {
        $this->this = $this_;
        parent::__construct('PathExpression', $loc);
    }
}
