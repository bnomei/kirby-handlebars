<?php

namespace DevTheorem\HandlebarsParser;

readonly class StripInfo
{
    public function __construct(
        public bool $open,
        public bool $close,
        public bool $openStandalone = false,
        public bool $closeStandalone = false,
        public bool $inlineStandalone = false,
    ) {}
}
