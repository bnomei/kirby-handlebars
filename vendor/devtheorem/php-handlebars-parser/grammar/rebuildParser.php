<?php

/**
 * Code based on https://github.com/nikic/PHP-Parser/blob/master/grammar/rebuildParsers.php
 * by Nikita Popov.
 */

const LIB = '(?(DEFINE)
    (?<singleQuotedString>\'[^\\\\\']*+(?:\\\\.[^\\\\\']*+)*+\')
    (?<doubleQuotedString>"[^\\\\"]*+(?:\\\\.[^\\\\"]*+)*+")
    (?<string>(?&singleQuotedString)|(?&doubleQuotedString))
    (?<comment>/\*[^*]*+(?:\*(?!/)[^*]*+)*+\*/)
    (?<code>\{[^\'"/{}]*+(?:(?:(?&string)|(?&comment)|(?&code)|/)[^\'"/{}]*+)*+})
)';

const PARAMS = '\[(?<params>[^[\]]*+(?:\[(?&params)\][^[\]]*+)*+)\]';
const ARGS   = '\((?<args>[^()]*+(?:\((?&args)\)[^()]*+)*+)\)';

$grammarFile    = __DIR__ . '/handlebars.y';
$skeletonFile   = __DIR__ . '/parser.template';
$tmpGrammarFile = __DIR__ . '/tmp_parser.phpy';
$resultDir = __DIR__ . '/../src';
$kmyacc = __DIR__ . '/../vendor/bin/phpyacc';

$options = array_flip($argv);
$optionDebug = isset($options['--debug']);
$additionalArgs = $optionDebug ? '-t -v' : '';

echo "Building Handlebars parser.\n";

$grammarCode = file_get_contents($grammarFile);
$grammarCode = preprocessGrammar($grammarCode);
file_put_contents($tmpGrammarFile, $grammarCode);

$output = execCmd("$kmyacc $additionalArgs -m $skeletonFile -p Parser $tmpGrammarFile");

rename(__DIR__ . '/tmp_parser.php', "$resultDir/Parser.php");
unlink($tmpGrammarFile);

function execCmd(string $cmd): string
{
    $output = trim(shell_exec("$cmd 2>&1") ?? '');
    if ($output !== "") {
        echo "> " . $cmd . "\n";
        echo $output;
    }
    return $output;
}

function preprocessGrammar(string $code): string
{
    $code = resolveMacros($code);
    $code = resolveStackAccess($code);
    $code = str_replace('$this', '$self', $code);
    return $code;
}

function resolveStackAccess(string $code): string
{
    $code = preg_replace('/\$\d+/', '$this->semStack[$0]', $code);
    return preg_replace('/#(\d+)/', '$$1', $code);
}

function resolveMacros(string $code): string
{
    return preg_replace_callback(
        '~\b(?<!::|->)(?!array\()(?<name>[a-z][A-Za-z]++)' . ARGS . '~',
        function ($matches) {
            // recurse
            $matches['args'] = resolveMacros($matches['args']);

            $name = $matches['name'];
            $args = magicSplit(
                '(?:' . PARAMS . '|' . ARGS . ')(*SKIP)(*FAIL)|,',
                $matches['args']
            );

            if ('locInfo' === $name) {
                assertArgs(0, $args, $name);
                return '$this->locInfo($this->tokenStartStack[#1], $this->tokenEndStack[$stackPos])';
            }

            if ('init' === $name) {
                return '$$ = [' . implode(', ', $args) . ']';
            }

            if ('push' === $name) {
                assertArgs(2, $args, $name);

                return $args[0] . '[] = ' . $args[1] . '; $$ = ' . $args[0];
            }

            if ('pushNormalizing' === $name) {
                assertArgs(2, $args, $name);

                return 'if (' . $args[1] . ' !== null) { ' . $args[0] . '[] = ' . $args[1] . '; } $$ = ' . $args[0];
            }

            return $matches[0];
        },
        $code
    );
}

function assertArgs(int $num, array $args, string $name): void
{
    if ($num !== count($args)) {
        exit('Wrong argument count for ' . $name . '().');
    }
}

function regex(string $regex): string
{
    return '~' . LIB . '(?:' . str_replace('~', '\~', $regex) . ')~';
}

function magicSplit(string $regex, string $string): array
{
    $pieces = preg_split(regex('(?:(?&string)|(?&comment)|(?&code))(*SKIP)(*FAIL)|' . $regex), $string);

    foreach ($pieces as &$piece) {
        $piece = trim($piece);
    }

    if ($pieces === ['']) {
        return [];
    }

    return $pieces;
}
