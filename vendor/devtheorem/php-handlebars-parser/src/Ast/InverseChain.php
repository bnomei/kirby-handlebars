<?php

namespace DevTheorem\HandlebarsParser\Ast;

readonly class InverseChain
{
    public function __construct(
        public StripFlags $strip,
        public Program $program,
        public bool $chain = false,
    ) {}
}
