<?php declare(strict_types=1);

namespace DevTheorem\HandlebarsParser;

use DevTheorem\HandlebarsParser\Ast\ArrayLiteral;
use DevTheorem\HandlebarsParser\Ast\BooleanLiteral;
use DevTheorem\HandlebarsParser\Ast\CloseBlock;
use DevTheorem\HandlebarsParser\Ast\CommentStatement;
use DevTheorem\HandlebarsParser\Ast\ContentStatement;
use DevTheorem\HandlebarsParser\Ast\Hash;
use DevTheorem\HandlebarsParser\Ast\HashLiteral;
use DevTheorem\HandlebarsParser\Ast\HashPair;
use DevTheorem\HandlebarsParser\Ast\InverseChain;
use DevTheorem\HandlebarsParser\Ast\NullLiteral;
use DevTheorem\HandlebarsParser\Ast\NumberLiteral;
use DevTheorem\HandlebarsParser\Ast\OpenBlock;
use DevTheorem\HandlebarsParser\Ast\OpenHelper;
use DevTheorem\HandlebarsParser\Ast\OpenPartialBlock;
use DevTheorem\HandlebarsParser\Ast\PartialStatement;
use DevTheorem\HandlebarsParser\Ast\PathSegment;
use DevTheorem\HandlebarsParser\Ast\StringLiteral;
use DevTheorem\HandlebarsParser\Ast\SubExpression;
use DevTheorem\HandlebarsParser\Ast\UndefinedLiteral;

/* This is an automatically GENERATED file, which should not be manually edited.
 * Instead edit one of the following:
 *  * the grammar file grammar/handlebars.y
 *  * the skeleton file grammar/parser.template
 *  * the preprocessing script grammar/rebuildParser.php
 */
class Parser extends ParserAbstract
{
    public const YYERRTOK = 256;
    public const BOOLEAN = 257;
    public const CLOSE = 258;
    public const CLOSE_BLOCK_PARAMS = 259;
    public const CLOSE_RAW_BLOCK = 260;
    public const CLOSE_SEXPR = 261;
    public const CLOSE_UNESCAPED = 262;
    public const COMMENT = 263;
    public const CONTENT = 264;
    public const DATA = 265;
    public const END_RAW_BLOCK = 266;
    public const EQUALS = 267;
    public const ID = 268;
    public const INVALID = 269;
    public const INVERSE = 270;
    public const NULL = 271;
    public const NUMBER = 272;
    public const OPEN = 273;
    public const OPEN_BLOCK = 274;
    public const OPEN_BLOCK_PARAMS = 275;
    public const OPEN_ENDBLOCK = 276;
    public const OPEN_INVERSE = 277;
    public const OPEN_INVERSE_CHAIN = 278;
    public const OPEN_PARTIAL = 279;
    public const OPEN_PARTIAL_BLOCK = 280;
    public const OPEN_RAW_BLOCK = 281;
    public const OPEN_SEXPR = 282;
    public const OPEN_UNESCAPED = 283;
    public const PRIVATE_SEP = 284;
    public const SEP = 285;
    public const STRING = 286;
    public const UNDEFINED = 287;

    protected int $tokenToSymbolMapSize = 288;
    protected int $actionTableSize = 45;
    protected int $gotoTableSize = 49;

    protected int $invalidSymbol = 33;
    protected int $errorSymbol = 1;
    protected int $defaultAction = -32766;
    protected int $unexpectedTokenRule = 32767;

    protected int $YY2TBLSTATE = 28;
    protected int $numNonLeafStates = 71;

    protected array $symbolToName = array(
        "EOF",
        "error",
        "BOOLEAN",
        "CLOSE",
        "CLOSE_BLOCK_PARAMS",
        "CLOSE_RAW_BLOCK",
        "CLOSE_SEXPR",
        "CLOSE_UNESCAPED",
        "COMMENT",
        "CONTENT",
        "DATA",
        "END_RAW_BLOCK",
        "EQUALS",
        "ID",
        "INVERSE",
        "NULL",
        "NUMBER",
        "OPEN",
        "OPEN_BLOCK",
        "OPEN_BLOCK_PARAMS",
        "OPEN_ENDBLOCK",
        "OPEN_INVERSE",
        "OPEN_INVERSE_CHAIN",
        "OPEN_PARTIAL",
        "OPEN_PARTIAL_BLOCK",
        "OPEN_RAW_BLOCK",
        "OPEN_SEXPR",
        "OPEN_UNESCAPED",
        "PRIVATE_SEP",
        "SEP",
        "STRING",
        "UNDEFINED",
        "INVALID"
    );

    protected array $tokenToSymbol = array(
            0,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,   33,   33,   33,   33,
           33,   33,   33,   33,   33,   33,    1,    2,    3,    4,
            5,    6,    7,    8,    9,   10,   11,   12,   13,   32,
           14,   15,   16,   17,   18,   19,   20,   21,   22,   23,
           24,   25,   26,   27,   28,   29,   30,   31
    );

    protected array $action = array(
           81,    0,  120,  130,  129,  100,  102,  104,   99,   11,
           17,  119,  125,   18,   89,   12,   13,   19,   90,   14,
           35,   91,   86,  101,   85,  127,  124,  109,   82,   21,
           20,   41,   16,    0,    0,   52,   15,  134,  133,  118,
          123,  126,   44,    0,   59
    );

    protected array $actionCheck = array(
            8,    0,    4,   28,   29,    3,    3,    3,    3,   17,
           18,   13,    2,   21,    3,   23,   24,   25,    3,   27,
           10,    3,    5,    7,   11,   15,   16,    6,    9,   20,
           22,   19,   12,   -1,   -1,   13,   26,   13,   13,   13,
           30,   31,   13,   -1,   14
    );

    protected array $actionBase = array(
            0,   -8,   29,   29,   29,   29,   29,   29,   29,   29,
           29,   24,   24,   24,   24,   24,   24,   24,   24,   24,
           24,   24,    8,    8,  -25,  -25,  -25,   13,  -25,  -25,
           -2,   30,   12,   12,   12,   24,    9,   24,    9,    9,
           22,   26,    1,   25,   20,    2,    3,    4,   17,   16,
            5,   21,   20,   11,   15,   18,    0,    0,    0,    0,
            0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
            0,    0,   19,   10,   10,   10,   10,   10,   10,   10,
           10,   10,   10,   10,   10,   10,   10,   10,   10,   10,
           10,   10,   10,   30,   30,    0,    0,    0,   19
    );

    protected array $actionDefault = array(
            3,    1,   41,   41,   41,   41,   41,   41,   41,   41,
           41,32767,32767,32767,32767,32767,32767,32767,32767,32767,
        32767,32767,   25,   25,   37,   61,32767,32767,   57,   60,
        32767,   22,   46,   46,   46,32767,32767,32767,32767,32767,
           39,32767,32767,32767,   63,32767,32767,32767,32767,32767,
        32767,32767,32767,32767,32767,32767,    3,    3,    3,    3,
            3,   13,   35,   35,   35,   35,   35,   35,   35,   35,
           35
    );

    protected array $goto = array(
           32,   33,   46,   47,   48,   49,   51,   34,    3,    4,
            5,    6,    7,    8,    9,   10,   62,   65,   66,   68,
           69,  115,   97,   63,   64,   67,   70,   50,   26,   26,
           26,   26,   26,   22,   31,   36,   94,   23,   37,   28,
           37,   29,   54,   55,   83,   87,   88,   92,  114
    );

    protected array $gotoCheck = array(
           14,   14,   14,   14,   14,   14,   14,   14,   13,   13,
           13,   13,   13,   13,   13,   13,   24,   24,   24,   24,
           24,   24,   16,   12,   12,   12,   12,   12,   26,   26,
           26,   26,   26,    1,    1,    1,    1,    1,   35,   34,
           35,   34,   20,   20,    9,   17,   17,   22,   29
    );

    protected array $gotoBase = array(
            0,  -23,    0,    0,    0,    0,    0,    0,    0,   17,
            0,    0,    6,  -55,   -3,    0,   -1,    7,    0,    0,
            9,    0,   16,    0,    5,    0,   11,    0,    0,    8,
            0,    0,    0,    0,    4,   14
    );

    protected array $gotoDefault = array(
        -32768,   42,    1,   73,   75,   76,   77,   78,   79,   80,
           27,   61,  107,    2,   45,   56,   38,  103,   57,   39,
           53,   60,   98,   95,  105,   58,   24,  111,   40,  113,
          116,   30,  121,  122,   25,   43
    );

    protected array $ruleToNonTerminal = array(
            0,    1,    2,    2,    3,    3,    3,    3,    3,    3,
            3,    9,   10,   10,    6,   11,    5,    5,   15,   18,
           21,   19,   19,   22,   16,   16,   23,   23,   17,    4,
            4,    7,    8,   25,   13,   13,   24,   24,   26,   27,
           14,   14,   28,   28,   29,   20,   20,   31,   31,   30,
           12,   12,   12,   12,   12,   12,   12,   33,   35,   35,
           32,   32,   34,   34
    );

    protected array $ruleToLength = array(
            1,    1,    2,    0,    1,    1,    1,    1,    1,    1,
            1,    1,    2,    0,    3,    5,    4,    4,    6,    6,
            6,    1,    0,    2,    1,    0,    3,    1,    3,    5,
            5,    5,    3,    5,    2,    0,    1,    1,    5,    1,
            1,    0,    1,    2,    3,    1,    0,    1,    2,    3,
            1,    1,    1,    1,    1,    1,    1,    2,    1,    1,
            3,    1,    3,    1
    );

    protected function initReduceCallbacks(): void {
        $this->reduceCallbacks = [
            0 => null,
            1 => static function ($self, $stackPos) {
                 $self->semValue = $self->prepareProgram($self->semStack[$stackPos-(1-1)]); 
            },
            2 => static function ($self, $stackPos) {
                 if ($self->semStack[$stackPos-(2-2)] !== null) { $self->semStack[$stackPos-(2-1)][] = $self->semStack[$stackPos-(2-2)]; } $self->semValue = $self->semStack[$stackPos-(2-1)]; 
            },
            3 => static function ($self, $stackPos) {
                 $self->semValue = []; 
            },
            4 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            5 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            6 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            7 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            8 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            9 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            10 => static function ($self, $stackPos) {
                
        $self->semValue = new CommentStatement(
            value: $self->stripComment($self->semStack[$stackPos-(1-1)]),
            strip: $self->stripFlags($self->semStack[$stackPos-(1-1)], $self->semStack[$stackPos-(1-1)]),
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos]),
        );
  
            },
            11 => static function ($self, $stackPos) {
                
        $self->semValue = new ContentStatement(
            value: $self->semStack[$stackPos-(1-1)],
            original: $self->semStack[$stackPos-(1-1)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            12 => static function ($self, $stackPos) {
                 if ($self->semStack[$stackPos-(2-2)] !== null) { $self->semStack[$stackPos-(2-1)][] = $self->semStack[$stackPos-(2-2)]; } $self->semValue = $self->semStack[$stackPos-(2-1)]; 
            },
            13 => static function ($self, $stackPos) {
                 $self->semValue = []; 
            },
            14 => static function ($self, $stackPos) {
                
        $self->semValue = $self->prepareRawBlock($self->semStack[$stackPos-(3-1)], $self->semStack[$stackPos-(3-2)], $self->semStack[$stackPos-(3-3)], $self->locInfo($self->tokenStartStack[$stackPos-(3-1)], $self->tokenEndStack[$stackPos]));
    
            },
            15 => static function ($self, $stackPos) {
                
        $self->semValue = new OpenHelper($self->semStack[$stackPos-(5-2)], $self->semStack[$stackPos-(5-3)], $self->semStack[$stackPos-(5-4)]);
    
            },
            16 => static function ($self, $stackPos) {
                
        $self->semValue = $self->prepareBlock($self->semStack[$stackPos-(4-1)], $self->semStack[$stackPos-(4-2)], $self->semStack[$stackPos-(4-3)], $self->semStack[$stackPos-(4-4)], false, $self->locInfo($self->tokenStartStack[$stackPos-(4-1)], $self->tokenEndStack[$stackPos]));
    
            },
            17 => static function ($self, $stackPos) {
                
        $self->semValue = $self->prepareBlock($self->semStack[$stackPos-(4-1)], $self->semStack[$stackPos-(4-2)], $self->semStack[$stackPos-(4-3)], $self->semStack[$stackPos-(4-4)], true, $self->locInfo($self->tokenStartStack[$stackPos-(4-1)], $self->tokenEndStack[$stackPos]));
    
            },
            18 => static function ($self, $stackPos) {
                
        $self->semValue = new OpenBlock(
            open: $self->semStack[$stackPos-(6-1)],
            path: $self->semStack[$stackPos-(6-2)],
            params: $self->semStack[$stackPos-(6-3)],
            hash: $self->semStack[$stackPos-(6-4)],
            blockParams: $self->semStack[$stackPos-(6-5)],
            strip: $self->stripFlags($self->semStack[$stackPos-(6-1)], $self->semStack[$stackPos-(6-6)]),
        );
    
            },
            19 => static function ($self, $stackPos) {
                
        $self->semValue = new OpenBlock(
            open: $self->semStack[$stackPos-(6-1)],
            path: $self->semStack[$stackPos-(6-2)],
            params: $self->semStack[$stackPos-(6-3)],
            hash: $self->semStack[$stackPos-(6-4)],
            blockParams: $self->semStack[$stackPos-(6-5)],
            strip: $self->stripFlags($self->semStack[$stackPos-(6-1)], $self->semStack[$stackPos-(6-6)]),
        );
    
            },
            20 => static function ($self, $stackPos) {
                
        $self->semValue = new OpenBlock(
            open: $self->semStack[$stackPos-(6-1)],
            path: $self->semStack[$stackPos-(6-2)],
            params: $self->semStack[$stackPos-(6-3)],
            hash: $self->semStack[$stackPos-(6-4)],
            blockParams: $self->semStack[$stackPos-(6-5)],
            strip: $self->stripFlags($self->semStack[$stackPos-(6-1)], $self->semStack[$stackPos-(6-6)]),
        );
    
            },
            21 => null,
            22 => static function ($self, $stackPos) {
                 $self->semValue = null; 
            },
            23 => static function ($self, $stackPos) {
                
        $self->semValue = new InverseChain(
            strip: $self->stripFlags($self->semStack[$stackPos-(2-1)], $self->semStack[$stackPos-(2-1)]),
            program: $self->semStack[$stackPos-(2-2)],
        );
    
            },
            24 => null,
            25 => static function ($self, $stackPos) {
                 $self->semValue = null; 
            },
            26 => static function ($self, $stackPos) {
                
        $inverse = $self->prepareBlock($self->semStack[$stackPos-(3-1)], $self->semStack[$stackPos-(3-2)], $self->semStack[$stackPos-(3-3)], $self->semStack[$stackPos-(3-3)], false, $self->locInfo($self->tokenStartStack[$stackPos-(3-1)], $self->tokenEndStack[$stackPos]));
        $program = $self->prepareProgram([$inverse], $self->semStack[$stackPos-(3-2)]->loc);
        $program->chained = true;

        $self->semValue = new InverseChain($self->semStack[$stackPos-(3-1)]->strip, $program, true);
  
            },
            27 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            28 => static function ($self, $stackPos) {
                
        $self->semValue = new CloseBlock($self->semStack[$stackPos-(3-2)], $self->stripFlags($self->semStack[$stackPos-(3-1)], $self->semStack[$stackPos-(3-3)]));
    
            },
            29 => static function ($self, $stackPos) {
                
        $self->semValue = $self->prepareMustache(
            path: $self->semStack[$stackPos-(5-2)],
            params: $self->semStack[$stackPos-(5-3)],
            hash: $self->semStack[$stackPos-(5-4)],
            open: $self->semStack[$stackPos-(5-1)],
            strip: $self->stripFlags($self->semStack[$stackPos-(5-1)], $self->semStack[$stackPos-(5-5)]),
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(5-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            30 => static function ($self, $stackPos) {
                
        $self->semValue = $self->prepareMustache(
            path: $self->semStack[$stackPos-(5-2)],
            params: $self->semStack[$stackPos-(5-3)],
            hash: $self->semStack[$stackPos-(5-4)],
            open: $self->semStack[$stackPos-(5-1)],
            strip: $self->stripFlags($self->semStack[$stackPos-(5-1)], $self->semStack[$stackPos-(5-5)]),
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(5-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            31 => static function ($self, $stackPos) {
                
        $self->semValue = new PartialStatement(
            name: $self->semStack[$stackPos-(5-2)],
            params: $self->semStack[$stackPos-(5-3)],
            hash: $self->semStack[$stackPos-(5-4)],
            indent: '',
            strip: $self->stripFlags($self->semStack[$stackPos-(5-1)], $self->semStack[$stackPos-(5-5)]),
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(5-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            32 => static function ($self, $stackPos) {
                
        $self->semValue = $self->preparePartialBlock(
            open: $self->semStack[$stackPos-(3-1)],
            program: $self->semStack[$stackPos-(3-2)],
            close: $self->semStack[$stackPos-(3-3)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(3-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            33 => static function ($self, $stackPos) {
                
        $self->semValue = new OpenPartialBlock(
            path: $self->semStack[$stackPos-(5-2)],
            params: $self->semStack[$stackPos-(5-3)],
            hash: $self->semStack[$stackPos-(5-4)],
            strip: $self->stripFlags($self->semStack[$stackPos-(5-1)], $self->semStack[$stackPos-(5-5)]),
        );
    
            },
            34 => static function ($self, $stackPos) {
                 if ($self->semStack[$stackPos-(2-2)] !== null) { $self->semStack[$stackPos-(2-1)][] = $self->semStack[$stackPos-(2-2)]; } $self->semValue = $self->semStack[$stackPos-(2-1)]; 
            },
            35 => static function ($self, $stackPos) {
                 $self->semValue = []; 
            },
            36 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            37 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            38 => static function ($self, $stackPos) {
                
        $self->semValue = new SubExpression(
            path: $self->semStack[$stackPos-(5-2)],
            params: $self->semStack[$stackPos-(5-3)],
            hash: $self->semStack[$stackPos-(5-4)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(5-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            39 => static function ($self, $stackPos) {
                
        $self->semValue = new Hash($self->semStack[$stackPos-(1-1)], $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos]));
    
            },
            40 => null,
            41 => static function ($self, $stackPos) {
                 $self->semValue = null; 
            },
            42 => static function ($self, $stackPos) {
                 $self->semValue = [$self->semStack[$stackPos-(1-1)]]; 
            },
            43 => static function ($self, $stackPos) {
                 if ($self->semStack[$stackPos-(2-2)] !== null) { $self->semStack[$stackPos-(2-1)][] = $self->semStack[$stackPos-(2-2)]; } $self->semValue = $self->semStack[$stackPos-(2-1)]; 
            },
            44 => static function ($self, $stackPos) {
                
        $self->semValue = new HashPair(
            key: $self->id($self->semStack[$stackPos-(3-1)]),
            value: $self->semStack[$stackPos-(3-3)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(3-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            45 => null,
            46 => static function ($self, $stackPos) {
                 $self->semValue = []; 
            },
            47 => static function ($self, $stackPos) {
                 $self->semValue = [$self->semStack[$stackPos-(1-1)]]; 
            },
            48 => static function ($self, $stackPos) {
                 if ($self->semStack[$stackPos-(2-2)] !== null) { $self->semStack[$stackPos-(2-1)][] = $self->semStack[$stackPos-(2-2)]; } $self->semValue = $self->semStack[$stackPos-(2-1)]; 
            },
            49 => static function ($self, $stackPos) {
                
        $self->semValue = array_map($self->id(...), $self->semStack[$stackPos-(3-2)]);
    
            },
            50 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            51 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            52 => static function ($self, $stackPos) {
                 $self->semValue = new StringLiteral($self->semStack[$stackPos-(1-1)], $self->semStack[$stackPos-(1-1)], $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos])); 
            },
            53 => static function ($self, $stackPos) {
                 $self->semValue = new NumberLiteral($self->semStack[$stackPos-(1-1)] + 0, $self->semStack[$stackPos-(1-1)] + 0, $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos])); 
            },
            54 => static function ($self, $stackPos) {
                 $self->semValue = new BooleanLiteral($self->semStack[$stackPos-(1-1)] === 'true', $self->semStack[$stackPos-(1-1)] === 'true', $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos])); 
            },
            55 => static function ($self, $stackPos) {
                 $self->semValue = new UndefinedLiteral(null, null, $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos])); 
            },
            56 => static function ($self, $stackPos) {
                 $self->semValue = new NullLiteral(null, null, $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos])); 
            },
            57 => static function ($self, $stackPos) {
                
        $self->semValue = $self->preparePath(
            data: true,
            sexpr: null,
            parts: $self->semStack[$stackPos-(2-2)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(2-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            58 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            59 => static function ($self, $stackPos) {
                 $self->semValue = $self->semStack[$stackPos-(1-1)]; 
            },
            60 => static function ($self, $stackPos) {
                
        $self->semValue = $self->preparePath(
            data: false,
            sexpr: $self->semStack[$stackPos-(3-1)],
            parts: $self->semStack[$stackPos-(3-3)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(3-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            61 => static function ($self, $stackPos) {
                
        $self->semValue = $self->preparePath(
            data: false,
            sexpr: null,
            parts: $self->semStack[$stackPos-(1-1)],
            loc: $self->locInfo($self->tokenStartStack[$stackPos-(1-1)], $self->tokenEndStack[$stackPos]),
        );
    
            },
            62 => static function ($self, $stackPos) {
                
        $self->semStack[$stackPos-(3-1)][] = new PathSegment($self->id($self->semStack[$stackPos-(3-3)]), $self->semStack[$stackPos-(3-3)], $self->semStack[$stackPos-(3-2)]); $self->semValue = $self->semStack[$stackPos-(3-1)];
    
            },
            63 => static function ($self, $stackPos) {
                
        $self->semValue = [new PathSegment($self->id($self->semStack[$stackPos-(1-1)]), $self->semStack[$stackPos-(1-1)], null)];
    
            },
        ];
    }
}
