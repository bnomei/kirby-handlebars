<?php

namespace DevTheorem\HandlebarsParser\Phlexer;

readonly class Rule
{
    /**
     * @var string[]
     */
    public array $startConditions;

    /**
     * Number of capturing groups (non-special '(') in the pattern.
     * Used when building per-state combined alternation patterns
     * to correctly compute which match group corresponds to this rule.
     */
    public int $captureCount;

    /**
     * @param string[] $startConditions,
     * @param \Closure(): ?string $handler
     */
    public function __construct(
        array $startConditions,
        public string $pattern,
        public \Closure $handler,
    ) {
        $this->startConditions = $startConditions ?: [Phlexer::INITIAL_STATE];
        // Count capturing '(' — those not preceded by '\' and not followed by '?'
        $count = preg_match_all("/(?<!\\\\)\((?!\?)/", $this->pattern);
        $this->captureCount = is_int($count) ? $count : 0;
    }
}
