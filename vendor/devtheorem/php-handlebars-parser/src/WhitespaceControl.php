<?php

namespace DevTheorem\HandlebarsParser;

use DevTheorem\HandlebarsParser\Ast\BlockStatement;
use DevTheorem\HandlebarsParser\Ast\CommentStatement;
use DevTheorem\HandlebarsParser\Ast\ContentStatement;
use DevTheorem\HandlebarsParser\Ast\MustacheStatement;
use DevTheorem\HandlebarsParser\Ast\PartialBlockStatement;
use DevTheorem\HandlebarsParser\Ast\PartialStatement;
use DevTheorem\HandlebarsParser\Ast\Program;
use DevTheorem\HandlebarsParser\Ast\Statement;

/**
 * Post-parse AST visitor that handles whitespace stripping.
 *
 * Implements the same logic as the reference JS parser:
 * https://github.com/handlebars-lang/handlebars-parser/blob/master/lib/whitespace-control.js
 */
class WhitespaceControl
{
    private bool $isRootSeen = false;

    public function __construct(
        private readonly bool $ignoreStandalone = false,
    ) {}

    public function accept(Program $program): Program
    {
        return $this->visitProgram($program);
    }

    /**
     * Dispatch to the appropriate visitor method for a statement node.
     */
    private function visitNode(Statement $node): ?StripInfo
    {
        if ($node instanceof BlockStatement || $node instanceof PartialBlockStatement) {
            return $this->visitBlock($node);
        }
        if ($node instanceof MustacheStatement) {
            return new StripInfo(
                open: $node->strip->open,
                close: $node->strip->close,
            );
        }
        if ($node instanceof CommentStatement || $node instanceof PartialStatement) {
            return new StripInfo(
                open: $node->strip->open,
                close: $node->strip->close,
                inlineStandalone: true,
            );
        }

        return null;
    }

    private function visitProgram(Program $program): Program
    {
        $doStandalone = !$this->ignoreStandalone;

        $isRoot = !$this->isRootSeen;
        $this->isRootSeen = true;

        $body = $program->body;

        for ($i = 0, $l = count($body); $i < $l; $i++) {
            $current = $body[$i];
            $strip = $this->visitNode($current);

            if ($strip === null) {
                continue;
            }

            $prevWS = $this->isPrevWhitespace($body, $i, $isRoot);
            $nextWS = $this->isNextWhitespace($body, $i, $isRoot);

            $openStandalone = $strip->openStandalone && $prevWS;
            $closeStandalone = $strip->closeStandalone && $nextWS;
            $inlineStandalone = $strip->inlineStandalone && $prevWS && $nextWS;

            if ($strip->close) {
                $this->omitRight($body, $i, true);
            }
            if ($strip->open) {
                $this->omitLeft($body, $i, true);
            }

            if ($doStandalone && $inlineStandalone) {
                $this->omitRight($body, $i);

                if ($this->omitLeft($body, $i)) {
                    // If we are on a standalone node, save the indent info for partials
                    if ($current instanceof PartialStatement) {
                        $previous = $body[$i - 1];
                        if (!$previous instanceof ContentStatement) {
                            throw new \Exception('Previous unexpectedly not a ContentStatement');
                        }

                        // Pull out the whitespace from the final line
                        preg_match('/([ \t]+$)/', $previous->original, $m);
                        $current->indent = $m[1] ?? '';
                    }
                }
            }
            if ($doStandalone && $openStandalone) {
                /** @var BlockStatement|PartialBlockStatement $current */
                $innerBody = ($current->program ?? $current->inverse ?? throw new \Exception('Missing program'))->body;
                $this->omitRight($innerBody);

                // Strip out the previous content node if it's whitespace only
                $this->omitLeft($body, $i);
            }
            if ($doStandalone && $closeStandalone) {
                // Always strip the next node
                $this->omitRight($body, $i);

                /** @var BlockStatement|PartialBlockStatement $current */
                $innerBody = ($current->inverse ?? $current->program)->body;
                $this->omitLeft($innerBody);
            }
        }

        return $program;
    }

    private function visitBlock(BlockStatement|PartialBlockStatement $block): StripInfo
    {
        if ($block->program) {
            $this->visitProgram($block->program);
        }
        if ($block instanceof BlockStatement && $block->inverse) {
            $this->visitProgram($block->inverse);
        }

        // Find the inverse program that is involved with whitespace stripping.
        $isBlockStatement = $block instanceof BlockStatement;
        $program = $isBlockStatement
            ? ($block->program ?? $block->inverse)
            : $block->program;

        $inverse = ($isBlockStatement && $block->program && $block->inverse)
            ? $block->inverse
            : null;
        $firstInverse = $inverse;
        $lastInverse = $inverse;

        if ($inverse !== null && $inverse->chained && $inverse->body[0] instanceof BlockStatement) {
            $firstInverse = $inverse->body[0]->program;

            // Walk the inverse chain to find the last inverse that is actually in the chain.
            while ($lastInverse?->chained) {
                $lastInverseBlockStatement = $lastInverse->body[array_key_last($lastInverse->body)];
                if ($lastInverseBlockStatement instanceof BlockStatement) {
                    $lastInverse = $lastInverseBlockStatement->program;
                }
            }
        }

        $strip = new StripInfo(
            open: $block->openStrip->open,
            close: $block->closeStrip->close ?? false,

            // Determine the standalone candidacy. Basically flag our content as being
            // possibly standalone so our parent can determine if we actually are standalone.
            openStandalone: $program !== null && $this->isNextWhitespace($program->body),
            closeStandalone: $this->isPrevWhitespace(
                ($firstInverse ?? $program)->body ?? [],
            ),
        );

        if ($block->openStrip->close && $program !== null) {
            $this->omitRight($program->body, null, true);
        }

        if ($inverse !== null) {
            /** @var BlockStatement $block */
            $inverseStrip = $block->inverseStrip;

            if ($inverseStrip?->open && $program !== null) {
                $this->omitLeft($program->body, null, true);
            }

            if ($inverseStrip?->close && $firstInverse !== null) {
                $this->omitRight($firstInverse->body, null, true);
            }
            if ($block->closeStrip?->open && $lastInverse !== null) {
                $this->omitLeft($lastInverse->body, null, true);
            }

            // Find standalone else statements
            if (
                !$this->ignoreStandalone
                && $program !== null
                && $firstInverse !== null
                && $this->isPrevWhitespace($program->body)
                && $this->isNextWhitespace($firstInverse->body)
            ) {
                $this->omitLeft($program->body);
                $this->omitRight($firstInverse->body);
            }
        } elseif ($block->closeStrip?->open && $program !== null) {
            $this->omitLeft($program->body, null, true);
        }

        return $strip;
    }

    /**
     * Check if the node to the left of position i is whitespace-only on the current line.
     *
     * @param Statement[] $body
     */
    private function isPrevWhitespace(array $body, ?int $i = null, bool $isRoot = false): bool
    {
        if ($i === null) {
            $i = count($body);
        }

        // Nodes that end with newlines are considered whitespace (but are special-cased for strip operations)
        $prev = $body[$i - 1] ?? null;
        $sibling = $body[$i - 2] ?? null;

        if ($prev === null) {
            return $isRoot;
        }

        if ($prev instanceof ContentStatement) {
            $pattern = ($sibling || !$isRoot) ? '/\r?\n\s*?$/' : '/(^|\r?\n)\s*?$/';
            return (bool) preg_match($pattern, $prev->original);
        }

        return false;
    }

    /**
     * Check if the node to the right of position i is whitespace-only on the current line.
     *
     * @param Statement[] $body
     */
    private function isNextWhitespace(array $body, ?int $i = null, bool $isRoot = false): bool
    {
        if ($i === null) {
            $i = -1;
        }

        $next = $body[$i + 1] ?? null;
        $sibling = $body[$i + 2] ?? null;

        if ($next === null) {
            return $isRoot;
        }

        if ($next instanceof ContentStatement) {
            $pattern = ($sibling || !$isRoot) ? '/^\s*?\r?\n/' : '/^\s*?(\r?\n|$)/';
            return (bool) preg_match($pattern, $next->original);
        }

        return false;
    }

    /**
     * Marks the node to the right of the position as omitted.
     * I.e. {{foo}}' ' will mark the ' ' node as omitted.
     *
     * If $i is null, then the first child will be marked as such.
     *
     * If $multiple is true then all whitespace will be stripped out until
     * non-whitespace content is met.
     *
     * @param Statement[] $body
     */
    private function omitRight(array $body, ?int $i = null, bool $multiple = false): void
    {
        $current = $body[$i === null ? 0 : $i + 1] ?? null;

        if (
            !$current instanceof ContentStatement
            || (!$multiple && $current->rightStripped)
        ) {
            return;
        }

        $original = $current->value;
        $current->value = ($multiple
            ? preg_replace('/^\s+/', '', $current->value)
            : preg_replace('/^[ \t]*\r?\n?/', '', $current->value)) ?? '';
        $current->rightStripped = ($current->value !== $original);
    }

    /**
     * Marks the node to the left of the position as omitted.
     * I.e. ' '{{foo}} will mark the ' ' node as omitted.
     *
     * If $i is null then the last child will be marked as such.
     *
     * If $multiple is true then all whitespace will be stripped out until
     * non-whitespace content is met.
     *
     * @param Statement[] $body
     */
    private function omitLeft(array $body, ?int $i = null, bool $multiple = false): bool
    {
        $current = $body[$i === null ? count($body) - 1 : $i - 1] ?? null;

        if (
            !$current instanceof ContentStatement
            || (!$multiple && $current->leftStripped)
        ) {
            return false;
        }

        // We omit the last node if it's whitespace only and not preceded by a non-content node.
        $original = $current->value;
        $current->value = ($multiple
            ? preg_replace('/\s+$/', '', $current->value)
            : preg_replace('/[ \t]+$/', '', $current->value)) ?? '';
        $current->leftStripped = ($current->value !== $original);

        return $current->leftStripped;
    }
}
