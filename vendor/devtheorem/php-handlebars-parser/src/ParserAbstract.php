<?php

namespace DevTheorem\HandlebarsParser;

/*
 * This parser is based on code from Nikita Popov,
 * in turn based on a skeleton written by Moriyoshi Koizumi,
 * which in turn is based on work by Masato Bito.
 */

use DevTheorem\HandlebarsParser\Ast\BlockStatement;
use DevTheorem\HandlebarsParser\Ast\CloseBlock;
use DevTheorem\HandlebarsParser\Ast\ContentStatement;
use DevTheorem\HandlebarsParser\Ast\Expression;
use DevTheorem\HandlebarsParser\Ast\Hash;
use DevTheorem\HandlebarsParser\Ast\InverseChain;
use DevTheorem\HandlebarsParser\Ast\Literal;
use DevTheorem\HandlebarsParser\Ast\MustacheStatement;
use DevTheorem\HandlebarsParser\Ast\Node;
use DevTheorem\HandlebarsParser\Ast\OpenBlock;
use DevTheorem\HandlebarsParser\Ast\OpenHelper;
use DevTheorem\HandlebarsParser\Ast\OpenPartialBlock;
use DevTheorem\HandlebarsParser\Ast\PartialBlockStatement;
use DevTheorem\HandlebarsParser\Ast\PathExpression;
use DevTheorem\HandlebarsParser\Ast\PathSegment;
use DevTheorem\HandlebarsParser\Ast\Position;
use DevTheorem\HandlebarsParser\Ast\Program;
use DevTheorem\HandlebarsParser\Ast\SourceLocation;
use DevTheorem\HandlebarsParser\Ast\Statement;
use DevTheorem\HandlebarsParser\Ast\StripFlags;
use DevTheorem\HandlebarsParser\Ast\SubExpression;
use DevTheorem\HandlebarsParser\Phlexer\Token;

abstract class ParserAbstract
{
    private const SYMBOL_NONE = -1;

    protected Lexer $lexer;

    /*
     * The following members will be filled with generated parsing data:
     */

    /** @var int Size of $tokenToSymbol map */
    protected int $tokenToSymbolMapSize;
    /** @var int Size of $action table */
    protected int $actionTableSize;
    /** @var int Size of $goto table */
    protected int $gotoTableSize;

    /** @var int Symbol number signifying an invalid token */
    protected int $invalidSymbol;
    /** @var int Symbol number of error recovery token */
    protected int $errorSymbol;
    /** @var int Action number signifying default action */
    protected int $defaultAction;
    /** @var int Rule number signifying that an unexpected token was encountered */
    protected int $unexpectedTokenRule;

    protected int $YY2TBLSTATE;
    /** @var int Number of non-leaf states */
    protected int $numNonLeafStates;

    /** @var array<string, int> Map of Handlebars tokens to internal symbols */
    protected array $tokenMap;

    /** @var int[] Map of external symbols (static::T_*) to internal symbols */
    protected array $tokenToSymbol;
    /** @var string[] Map of symbols to their names */
    protected array $symbolToName;
    /** @var array<int, string> Names of the production rules (only necessary for debugging) */
    protected array $productions;

    /** @var int[] Map of states to a displacement into the $action table. The corresponding action for this
     *             state/symbol pair is $action[$actionBase[$state] + $symbol]. If $actionBase[$state] is 0, the
     *             action is defaulted, i.e. $actionDefault[$state] should be used instead. */
    protected array $actionBase;
    /** @var int[] Table of actions. Indexed according to $actionBase comment. */
    protected array $action;
    /** @var int[] Table indexed analogously to $action. If $actionCheck[$actionBase[$state] + $symbol] != $symbol
     *             then the action is defaulted, i.e. $actionDefault[$state] should be used instead. */
    protected array $actionCheck;
    /** @var int[] Map of states to their default action */
    protected array $actionDefault;
    /** @var array<\Closure|null> Semantic action callbacks */
    protected array $reduceCallbacks;

    /** @var int[] Map of non-terminals to a displacement into the $goto table. The corresponding goto state for this
     *             non-terminal/state pair is $goto[$gotoBase[$nonTerminal] + $state] (unless defaulted) */
    protected array $gotoBase;
    /** @var int[] Table of states to goto after reduction. Indexed according to $gotoBase comment. */
    protected array $goto;
    /** @var int[] Table indexed analogously to $goto. If $gotoCheck[$gotoBase[$nonTerminal] + $state] != $nonTerminal
     *             then the goto state is defaulted, i.e. $gotoDefault[$nonTerminal] should be used. */
    protected array $gotoCheck;
    /** @var int[] Map of non-terminals to the default state to goto after their reduction */
    protected array $gotoDefault;

    /** @var int[] Map of rules to the non-terminal on their left-hand side, i.e. the non-terminal to use for
     *             determining the state to goto after reduction. */
    protected array $ruleToNonTerminal;
    /** @var int[] Map of rules to the length of their right-hand side, which is the number of elements that have to
     *             be popped from the stack(s) on reduction. */
    protected array $ruleToLength;

    /*
     * The following members are part of the parser state:
     */

    /** @var mixed Temporary value containing the result of last semantic action (reduction) */
    protected mixed $semValue;
    /** @var mixed[] Semantic value stack (contains values of tokens and semantic action results) */
    protected array $semStack;
    /** @var int[] Token start position stack */
    protected array $tokenStartStack;
    /** @var int[] Token end position stack */
    protected array $tokenEndStack;

    /** @var int Error state, used to avoid error floods */
    protected int $errorState;

    /** @var Token[] Tokens for the current parse */
    protected array $tokens;
    /** @var int Current position in token array */
    protected int $tokenPos;

    /**
     * Initialize $reduceCallbacks map.
     */
    abstract protected function initReduceCallbacks(): void;

    /**
     * Creates a parser instance.
     */
    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
        $this->initReduceCallbacks();
        $this->tokenMap = $this->createTokenMap();
    }

    /**
     * Parses a Handlebars template into a node tree.
     */
    public function parse(string $code, bool $ignoreStandalone = false): Program
    {
        $this->lexer->initialize($code);
        $this->tokens = [];
        $result = $this->doParse();
        $result = (new WhitespaceControl($ignoreStandalone))->accept($result);

        // Clear out some of the interior state, so we don't hold onto unnecessary
        // memory between uses of the parser
        $this->tokenStartStack = [];
        $this->tokenEndStack = [];
        $this->semStack = [];
        $this->semValue = null;

        return $result;
    }

    private function readNextToken(): Token
    {
        $token = $this->lexer->getNextToken();

        if ($token === null) {
            if ($this->tokens) {
                $lastToken = end($this->tokens);
                $line = $lastToken->line;
                $column = $lastToken->column + strlen($lastToken->text);
            } else {
                $line = 0;
                $column = 0;
            }

            $token = new Token(Lexer::T_EOF, "\0", $line, $column);
        }

        $this->tokens[] = $token;

        return $token;
    }

    protected function doParse(): Program
    {
        // We start off with no lookahead-token
        $symbol = self::SYMBOL_NONE;
        $tokenValue = null;
        $this->tokenPos = -1;

        // Keep stack of start and end attributes
        $this->tokenStartStack = [];
        $this->tokenEndStack = [0];

        // Start off in the initial state and keep a stack of previous states
        $state = 0;
        $stateStack = [$state];

        // Semantic value stack (contains values of tokens and semantic action results)
        $this->semStack = [];

        // Current position in the stack(s)
        $stackPos = 0;

        $this->errorState = 0;

        for (; ;) {
            if ($this->actionBase[$state] === 0) {
                $rule = $this->actionDefault[$state];
            } else {
                if ($symbol === self::SYMBOL_NONE) {
                    $token = $this->tokens[++$this->tokenPos] ?? $this->readNextToken();
                    $tokenName = $token->name;

                    // Map the lexer token id to the internally used symbols.
                    $tokenValue = $token->text;
                    if (!isset($this->tokenMap[$tokenName])) {
                        throw new \RangeException(sprintf(
                            'The lexer returned an invalid token (name=%s, value=%s)',
                            $tokenName,
                            $tokenValue,
                        ));
                    }
                    $symbol = $this->tokenMap[$tokenName];
                }

                $idx = $this->actionBase[$state] + $symbol;
                if ((($idx >= 0 && $idx < $this->actionTableSize && $this->actionCheck[$idx] === $symbol)
                     || ($state < $this->YY2TBLSTATE
                         && ($idx = $this->actionBase[$state + $this->numNonLeafStates] + $symbol) >= 0
                         && $idx < $this->actionTableSize && $this->actionCheck[$idx] === $symbol))
                    && ($action = $this->action[$idx]) !== $this->defaultAction) {
                    /*
                     * >= numNonLeafStates: shift and reduce
                     * > 0: shift
                     * = 0: accept
                     * < 0: reduce
                     * = -YYUNEXPECTED: error
                     */
                    if ($action > 0) {
                        /* shift */
                        //$this->traceShift($symbol);

                        ++$stackPos;
                        $stateStack[$stackPos] = $state = $action;
                        $this->semStack[$stackPos] = $tokenValue;
                        $this->tokenStartStack[$stackPos] = $this->tokenPos;
                        $this->tokenEndStack[$stackPos] = $this->tokenPos;
                        $symbol = self::SYMBOL_NONE;

                        if ($this->errorState) {
                            --$this->errorState;
                        }

                        if ($action < $this->numNonLeafStates) {
                            continue;
                        }

                        /* $yyn >= numNonLeafStates means shift-and-reduce */
                        $rule = $action - $this->numNonLeafStates;
                    } else {
                        $rule = -$action;
                    }
                } else {
                    $rule = $this->actionDefault[$state];
                }
            }

            for (; ;) {
                if ($rule === 0) {
                    /* accept */
                    //$this->traceAccept();
                    /** @phpstan-ignore return.type */
                    return $this->semValue;
                }
                if ($rule !== $this->unexpectedTokenRule) {
                    /* reduce */
                    //$this->traceReduce($rule);

                    $ruleLength = $this->ruleToLength[$rule];
                    $callback = $this->reduceCallbacks[$rule];
                    if ($callback !== null) {
                        $callback($this, $stackPos);
                    } elseif ($ruleLength > 0) {
                        $this->semValue = $this->semStack[$stackPos - $ruleLength + 1];
                    }

                    /* Goto - shift nonterminal */
                    $lastTokenEnd = $this->tokenEndStack[$stackPos];
                    $stackPos -= $ruleLength;
                    $nonTerminal = $this->ruleToNonTerminal[$rule];
                    $idx = $this->gotoBase[$nonTerminal] + $stateStack[$stackPos];
                    if ($idx >= 0 && $idx < $this->gotoTableSize && $this->gotoCheck[$idx] === $nonTerminal) {
                        $state = $this->goto[$idx];
                    } else {
                        $state = $this->gotoDefault[$nonTerminal];
                    }

                    ++$stackPos;
                    $stateStack[$stackPos] = $state;
                    $this->semStack[$stackPos] = $this->semValue;
                    $this->tokenEndStack[$stackPos] = $lastTokenEnd;
                    if ($ruleLength === 0) {
                        // Empty productions use the start attributes of the lookahead token.
                        $this->tokenStartStack[$stackPos] = $this->tokenPos;
                    }
                } else {
                    /* error */
                    switch ($this->errorState) {
                        case 0:
                            $token = $this->tokens[$this->tokenPos];
                            throw new \Exception($this->getErrorMessage($symbol, $state, $token->line, $token->column));
                            // Break missing intentionally
                        case 1:
                        case 2:
                            $this->errorState = 3;

                            // Pop until error-expecting state uncovered
                            while (!(
                                (($idx = $this->actionBase[$state] + $this->errorSymbol) >= 0
                                    && $idx < $this->actionTableSize && $this->actionCheck[$idx] === $this->errorSymbol)
                                || ($state < $this->YY2TBLSTATE
                                    && ($idx = $this->actionBase[$state + $this->numNonLeafStates] + $this->errorSymbol) >= 0
                                    && $idx < $this->actionTableSize && $this->actionCheck[$idx] === $this->errorSymbol)
                            ) || ($action = $this->action[$idx]) === $this->defaultAction) { // Not totally sure about this
                                if ($stackPos <= 0) {
                                    // Could not recover from error
                                    throw new \Exception('Parse error: failed to recover from error');
                                }
                                $state = $stateStack[--$stackPos];
                                //$this->tracePop($state);
                            }

                            //$this->traceShift($this->errorSymbol);
                            ++$stackPos;
                            $stateStack[$stackPos] = $state = ($action ?? 0);

                            // We treat the error symbol as being empty, so we reset the end attributes
                            // to the end attributes of the last non-error symbol
                            $this->tokenStartStack[$stackPos] = $this->tokenPos;
                            $this->tokenEndStack[$stackPos] = $this->tokenEndStack[$stackPos - 1];
                            break;

                        case 3:
                            if ($symbol === 0) {
                                // Reached EOF without recovering from error
                                throw new \Exception('Parse error: reached EOF without recovering from error');
                            }

                            //$this->traceDiscard($symbol);
                            $symbol = self::SYMBOL_NONE;
                            break 2;
                    }
                }

                if ($state < $this->numNonLeafStates) {
                    break;
                }

                /* >= numNonLeafStates means shift-and-reduce */
                $rule = $state - $this->numNonLeafStates;
            }
        }
    }

    /**
     * Format error message including expected tokens.
     *
     * @param int $symbol Unexpected symbol
     * @param int $state State at time of error
     *
     * @return string Formatted error message
     */
    protected function getErrorMessage(int $symbol, int $state, int $line, int $column): string
    {
        $expected = $this->getExpectedTokens($state);
        $expectedString = "Expecting " . implode(', ', $expected) . ', got';

        [$before, $after] = $this->lexer->getPositionContext($line, $column);
        $context = ErrorContext::getErrorContext($before, $after);

        return "Parse error on line {$line}:\n{$context}\n{$expectedString} {$this->symbolToName[$symbol]}";
    }

    private function getNodeError(string $message, Node $node): string
    {
        $start = $node->loc->start;
        return "{$message} - {$start->line}:{$start->column}";
    }

    /**
     * Get limited number of expected tokens in given state.
     *
     * @return string[] Expected tokens. Returns a max of 10 items.
     */
    protected function getExpectedTokens(int $state): array
    {
        $expected = [];

        $base = $this->actionBase[$state];
        foreach ($this->symbolToName as $symbol => $name) {
            $idx = $base + $symbol;
            if ($idx >= 0 && $idx < $this->actionTableSize && $this->actionCheck[$idx] === $symbol
                || $state < $this->YY2TBLSTATE
                && ($idx = $this->actionBase[$state + $this->numNonLeafStates] + $symbol) >= 0
                && $idx < $this->actionTableSize && $this->actionCheck[$idx] === $symbol
            ) {
                if ($this->action[$idx] !== $this->unexpectedTokenRule
                    && $this->action[$idx] !== $this->defaultAction
                    && $symbol !== $this->errorSymbol
                ) {
                    if (count($expected) === 10) {
                        /* Too many expected tokens */
                        return $expected;
                    }

                    $expected[] = $name;
                }
            }
        }

        return $expected;
    }

    /**
     * The token map maps Handlebars token names
     * to the identifiers used by the Parser.
     *
     * @return array<string, int>
     */
    protected function createTokenMap(): array
    {
        $tokenMap = ['EOF' => 0]; // for sentinel token

        foreach ($this->symbolToName as $name) {
            if ($name === 'EOF' || $name === 'error') {
                continue;
            }
            $extSymbol = constant(static::class . '::' . $name);

            if (!is_int($extSymbol)) {
                $type = get_debug_type($extSymbol);
                throw new \Exception("Unexpected external ID type $type for token $name");
            }

            $intSymbol = $this->tokenToSymbol[$extSymbol];
            if ($intSymbol === $this->invalidSymbol) {
                continue;
            }
            $tokenMap[$name] = $intSymbol;
        }

        return $tokenMap;
    }

    /*
     * Tracing functions used for debugging the parser.
     */

    /*
    protected function traceNewState($state, $symbol): void {
        echo '% State ' . $state
            . ', Lookahead ' . ($symbol == self::SYMBOL_NONE ? '--none--' : $this->symbolToName[$symbol]) . "\n";
    }

    protected function traceRead($symbol): void {
        echo '% Reading ' . $this->symbolToName[$symbol] . "\n";
    }

    protected function traceShift($symbol): void {
        echo '% Shift ' . $this->symbolToName[$symbol] . "\n";
    }

    protected function traceAccept(): void {
        echo "% Accepted.\n";
    }

    protected function traceReduce($n): void {
        echo '% Reduce by (' . $n . ') ' . $this->productions[$n] . "\n";
    }

    protected function tracePop($state): void {
        echo '% Recovering, uncovered state ' . $state . "\n";
    }

    protected function traceDiscard($symbol): void {
        echo '% Discard ' . $this->symbolToName[$symbol] . "\n";
    }*/

    /*
     * Helper functions invoked by semantic actions
     * Based on https://github.com/handlebars-lang/handlebars-parser/blob/master/lib/helpers.js
     */

    /**
     * Get info for a node with the given start and end token positions.
     *
     * @param int $tokenStartPos Token position the node starts at
     * @param int $tokenEndPos Token position the node ends at
     */
    protected function locInfo(int $tokenStartPos, int $tokenEndPos): SourceLocation
    {
        $startToken = $this->tokens[$tokenStartPos];
        $endToken = $this->tokens[$tokenEndPos];

        $start = new Position($startToken->line, $startToken->column);
        $end = new Position($endToken->line, $endToken->column + strlen($endToken->text));

        return new SourceLocation($start, $end);
    }

    protected function id(string $token): string
    {
        if ($token !== '' && $token[0] === '[' && $token[-1] === ']') {
            return substr($token, 1, -1);
        } else {
            return $token;
        }
    }

    protected function stripFlags(string $open, string $close): StripFlags
    {
        return new StripFlags(($open[2] ?? '') === '~', $close[strlen($close) - 3] === '~');
    }

    protected function stripComment(string $comment): string
    {
        $comment = preg_replace('/^\\{\\{~?!-?-?/', '', $comment) ?? '';
        return preg_replace('/-?-?~?}}$/', '', $comment) ?? '';
    }

    /**
     * @param Statement[] $statements
     */
    protected function prepareProgram(array $statements, ?SourceLocation $loc = null): Program
    {
        if (!$loc) {
            if ($statements) {
                $firstLoc = $statements[0]->loc;
                $lastLoc = $statements[count($statements) - 1]->loc;
                $loc = new SourceLocation($firstLoc->start, $lastLoc->end);
            } else {
                $loc = new SourceLocation(new Position(0, 0), new Position(0, 0));
            }
        }

        return new Program($statements, [], $loc);
    }

    /**
     * @param Expression[] $params
     */
    protected function prepareMustache(
        PathExpression|Literal|SubExpression $path,
        array $params,
        ?Hash $hash,
        string $open,
        StripFlags $strip,
        SourceLocation $loc,
    ): MustacheStatement {
        $escapeFlag = $open[3] ?? $open[2] ?? '';
        $escaped = $escapeFlag !== '{' && $escapeFlag !== '&';
        $decorator = str_contains($open, '*');

        return new MustacheStatement(
            type: $decorator ? 'Decorator' : 'MustacheStatement',
            path: $path,
            params: $params,
            hash: $hash,
            escaped: $escaped,
            strip: $strip,
            loc: $loc,
        );
    }

    private function validateClose(OpenHelper $open, CloseBlock|string $close): void
    {
        if ($close instanceof CloseBlock) {
            $close = $close->path->original;
        }

        if ($open->path->original !== $close) {
            $msg = $this->getNodeError("{$open->path->original} doesn't match {$close}", $open->path);
            throw new \Exception($msg);
        }
    }

    /**
     * @param PathSegment[] $parts
     */
    protected function preparePath(bool $data, ?SubExpression $sexpr, array $parts, SourceLocation $loc): PathExpression
    {
        if ($data) {
            $original = '@';
        } else {
            $original = '';
        }

        $tail = [];
        $depth = 0;

        foreach ($parts as $segment) {
            $part = $segment->part;
            // If we have [] syntax then we do not treat path references as operators,
            // i.e. foo.[this] resolves to approximately context.foo['this']
            $isLiteral = $segment->original !== $part;
            $separator = $segment->separator;

            $partPrefix = $separator === '.#' ? '#' : '';

            $original .= ($separator ?? '') . $part;

            if (!$isLiteral && ($part === '..' || $part === '.' || $part === 'this')) {
                if ($tail !== []) {
                    $msg = $this->getNodeError("Invalid path: $original", new Node('', $loc));
                    throw new \Exception($msg);
                } elseif ($part === '..') {
                    $depth++;
                }
            } else {
                $tail[] = $partPrefix . $part;
            }
        }

        $head = $sexpr ?? array_shift($tail) ?? '';

        return new PathExpression(
            this_: str_starts_with($original, 'this.'),
            data: $data,
            depth: $depth,
            head: $head,
            tail: $tail,
            parts: $head !== '' ? [$head, ...$tail] : $tail,
            original: $original,
            loc: $loc,
        );
    }

    /**
     * @param ContentStatement[] $contents
     */
    protected function prepareRawBlock(OpenHelper $openRawBlock, array $contents, string $close, SourceLocation $loc): BlockStatement
    {
        $this->validateClose($openRawBlock, $close);
        $program = new Program($contents, [], $loc);
        $strip = new StripFlags(false, false);

        return new BlockStatement(
            type: 'BlockStatement',
            path: $openRawBlock->path,
            params: $openRawBlock->params,
            hash: $openRawBlock->hash,
            program: $program,
            inverse: null,
            openStrip: $strip,
            inverseStrip: null,
            closeStrip: $strip,
            loc: $loc,
        );
    }

    protected function prepareBlock(
        OpenBlock $openBlock,
        Program $program,
        ?InverseChain $inverseAndProgram,
        CloseBlock|InverseChain|null $close,
        bool $inverted,
        SourceLocation $loc,
    ): BlockStatement {
        if ($close instanceof CloseBlock) {
            $this->validateClose($openBlock, $close);
        }

        $decorator = str_contains($openBlock->open, '*');

        $program->blockParams = $openBlock->blockParams;

        $inverse = $inverseStrip = null;

        if ($inverseAndProgram) {
            if ($decorator) {
                throw new \Exception('Unexpected inverse block on decorator');
            }

            if ($inverseAndProgram->chain && $close) {
                $firstStmt = $inverseAndProgram->program->body[0];

                if (!$firstStmt instanceof BlockStatement && !$firstStmt instanceof PartialBlockStatement) {
                    throw new \Exception("Unexpected statement type: {$firstStmt->type}");
                }

                $firstStmt->closeStrip = $close->strip;
            }

            $inverseStrip = $inverseAndProgram->strip;
            $inverse = $inverseAndProgram->program;
        }

        if ($inverted) {
            $inverted = $inverse;
            $inverse = $program;
            $program = $inverted;
        }

        return new BlockStatement(
            type: $decorator ? 'DecoratorBlock' : 'BlockStatement',
            path: $openBlock->path,
            params: $openBlock->params,
            hash: $openBlock->hash,
            program: $program,
            inverse: $inverse,
            openStrip: $openBlock->strip,
            inverseStrip: $inverseStrip,
            closeStrip: $close?->strip,
            loc: $loc,
        );
    }

    protected function preparePartialBlock(
        OpenPartialBlock $open,
        Program $program,
        CloseBlock $close,
        SourceLocation $loc,
    ): PartialBlockStatement {
        $this->validateClose($open, $close);

        return new PartialBlockStatement(
            name: $open->path,
            params: $open->params,
            hash: $open->hash,
            program: $program,
            openStrip: $open->strip,
            closeStrip: $close->strip,
            loc: $loc,
        );
    }
}
