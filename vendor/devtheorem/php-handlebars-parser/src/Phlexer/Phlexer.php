<?php

namespace DevTheorem\HandlebarsParser\Phlexer;

use DevTheorem\HandlebarsParser\ErrorContext;

abstract class Phlexer
{
    public const INITIAL_STATE = 'INITIAL';

    /**
     * @var string[]
     */
    private array $states;
    private string $currentState;
    private string $text;
    private int $textLen;
    private int $cursor;

    /**
     * The current matched value
     */
    protected string $yytext;

    private int $line;
    private int $column;
    private int $preMatchLine;
    private int $preMatchColumn;

    /**
     * Per-state combined alternation patterns: /\G(?:(rule0)|(rule1)|...)/.
     * A single preg_match call against this pattern identifies the matching rule.
     *
     * @var array<string, string>
     */
    private array $combinedPatterns;

    /**
     * Rules grouped by start condition, parallel to $ruleGroupIndices.
     *
     * @var array<string, list<Rule>>
     */
    private array $rulesByState;

    /**
     * Per-state group indices: for each rule (same order as $rulesByState),
     * the index of its outer capturing group in the combined pattern's $matches array.
     *
     * @var array<string, list<int>>
     */
    private array $ruleGroupIndices;

    /**
     * @param Rule[] $rules
     */
    public function __construct(array $rules)
    {
        $byState = [];
        foreach ($rules as $rule) {
            foreach ($rule->startConditions as $state) {
                $byState[$state][] = $rule;
            }
        }

        // Build combined alternation patterns and group index maps per state.
        $this->rulesByState = $byState;
        $combinedPatterns = [];
        $ruleGroupIndices = [];

        foreach ($this->rulesByState as $state => $stateRules) {
            $parts = [];
            $groupIndex = 1; // group 0 is the full match; outer groups start at 1
            $indices = [];

            foreach ($stateRules as $rule) {
                $parts[] = '(' . $rule->pattern . ')';
                $indices[] = $groupIndex;
                // Each rule contributes: 1 outer group + its internal captures
                $groupIndex += 1 + $rule->captureCount;
            }

            $combinedPatterns[$state] = '/\G(?:' . implode('|', $parts) . ')/';
            $ruleGroupIndices[$state] = $indices;
        }

        $this->combinedPatterns = $combinedPatterns;
        $this->ruleGroupIndices = $ruleGroupIndices;
    }

    public function initialize(string $text): void
    {
        $this->states = [self::INITIAL_STATE];
        $this->currentState = self::INITIAL_STATE;
        $this->text = $text;
        $this->textLen = strlen($text);
        $this->cursor = 0;
        $this->yytext = '';
        $this->line = 1;
        $this->column = 0;
        $this->preMatchLine = 1;
        $this->preMatchColumn = 0;
    }

    /**
     * @return list<Token>
     */
    public function tokenize(string $text): array
    {
        $this->initialize($text);
        $tokens = [];

        while ($token = $this->getNextToken()) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    public function hasMoreTokens(): bool
    {
        return $this->cursor < $this->textLen;
    }

    public function getNextToken(): ?Token
    {
        while ($this->cursor < $this->textLen) {
            $this->preMatchLine = $this->line;
            $this->preMatchColumn = $this->column;

            $state = $this->currentState;

            if (!preg_match($this->combinedPatterns[$state], $this->text, $matches, PREG_UNMATCHED_AS_NULL, $this->cursor)) {
                $remaining = substr($this->text, $this->cursor);
                $context = ErrorContext::getErrorContext(substr($this->text, 0, $this->cursor), $remaining);
                throw new \Exception("Lexical error on line {$this->line}. Unrecognized text.\n{$context}");
            }

            $this->yytext = $matches[0] ?? '';
            $this->cursor += strlen($this->yytext);
            $this->advancePosition($this->yytext);

            // Find which rule matched: it's the first non-null outer group.
            $rules = $this->rulesByState[$state];
            $indices = $this->ruleGroupIndices[$state];
            $matchedRule = $rules[0]; // fallback; overwritten below

            foreach ($indices as $i => $groupIdx) {
                if ($matches[$groupIdx] !== null) {
                    $matchedRule = $rules[$i];
                    break;
                }
            }

            $tokenName = ($matchedRule->handler)();

            if ($tokenName !== null) {
                // If a token starts with a newline, report its position as the start of the next line
                if (isset($this->yytext[0]) && $this->yytext[0] === "\n") {
                    $this->preMatchLine += 1;
                    $this->preMatchColumn = 0;
                }

                return new Token($tokenName, $this->yytext, $this->preMatchLine, $this->preMatchColumn);
            }
        }

        return null;
    }

    /**
     * @return array{string, string}
     */
    public function getPositionContext(int $line, int $column): array
    {
        $lineNum = 1;
        $cursor = 0;

        while ($lineNum < $line && $cursor < $this->textLen) {
            if ($this->text[$cursor] === "\n") {
                $lineNum++;
            }
            $cursor++;
        }

        $cursor += $column;

        if ($lineNum !== $line || $cursor > $this->textLen) {
            throw new \Exception("Invalid position $line:$column");
        }

        return [
            substr($this->text, 0, $cursor),
            substr($this->text, $cursor),
        ];
    }

    private function advancePosition(string $text): void
    {
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] === "\n") {
                $this->line++;
                $this->column = 0;
            } else {
                $this->column++;
            }
        }
    }

    protected function pushState(string $state): void
    {
        $this->states[] = $state;
        $this->currentState = $state;
    }

    protected function popState(): void
    {
        array_pop($this->states);
        $lastKey = array_key_last($this->states);
        $this->currentState = $lastKey !== null ? $this->states[$lastKey] : self::INITIAL_STATE;
    }

    protected function topState(): string
    {
        return $this->currentState;
    }

    protected function rewind(int $length): void
    {
        $this->cursor -= $length;
        $this->line = $this->preMatchLine;
        $this->column = $this->preMatchColumn;
    }
}
