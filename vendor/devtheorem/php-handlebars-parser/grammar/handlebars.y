%pure_parser

%token BOOLEAN
%token CLOSE
%token CLOSE_BLOCK_PARAMS
%token CLOSE_RAW_BLOCK
%token CLOSE_SEXPR
%token CLOSE_UNESCAPED
%token COMMENT
%token CONTENT
%token DATA
%token END_RAW_BLOCK
%token EQUALS
%token ID
%token INVALID
%token INVERSE
%token NULL
%token NUMBER
%token OPEN
%token OPEN_BLOCK
%token OPEN_BLOCK_PARAMS
%token OPEN_ENDBLOCK
%token OPEN_INVERSE
%token OPEN_INVERSE_CHAIN
%token OPEN_PARTIAL
%token OPEN_PARTIAL_BLOCK
%token OPEN_RAW_BLOCK
%token OPEN_SEXPR
%token OPEN_UNESCAPED
%token PRIVATE_SEP
%token SEP
%token STRING
%token UNDEFINED

%%

/*
 * Grammar should match https://github.com/handlebars-lang/handlebars-parser/blob/master/src/handlebars.yy
 * as of 2026-02-20. EBNF grammar has been converted to BNF.
 * Exception: the hash and array literal syntax (https://github.com/handlebars-lang/handlebars-parser/pull/15)
 * has been reverted for parity with the released version of Handlebars.js.
 */

program:
    statement_list { $$ = $this->prepareProgram($1); }
;

statement_list:
    statement_list statement { pushNormalizing($1, $2); }
  | /* empty */              { init(); }
;

statement:
    mustache     { $$ = $1; }
  | block        { $$ = $1; }
  | rawBlock     { $$ = $1; }
  | partial      { $$ = $1; }
  | partialBlock { $$ = $1; }
  | content      { $$ = $1; }
  | COMMENT {
        $$ = new CommentStatement(
            value: $this->stripComment($1),
            strip: $this->stripFlags($1, $1),
            loc: locInfo(),
        );
  };

content:
    CONTENT {
        $$ = new ContentStatement(
            value: $1,
            original: $1,
            loc: locInfo(),
        );
    };

content_list:
    content_list content { pushNormalizing($1, $2); }
  | /* empty */          { init(); }
;

rawBlock:
    openRawBlock content_list END_RAW_BLOCK {
        $$ = $this->prepareRawBlock($1, $2, $3, locInfo());
    };

openRawBlock:
    OPEN_RAW_BLOCK helperName expr_list optional_hash CLOSE_RAW_BLOCK {
        $$ = new OpenHelper($2, $3, $4);
    };

block:
    openBlock program optional_inverseChain closeBlock {
        $$ = $this->prepareBlock($1, $2, $3, $4, false, locInfo());
    }
  | openInverse program optional_inverseAndProgram closeBlock {
        $$ = $this->prepareBlock($1, $2, $3, $4, true, locInfo());
    }
;

openBlock:
    OPEN_BLOCK helperName expr_list optional_hash optional_blockParams CLOSE {
        $$ = new OpenBlock(
            open: $1,
            path: $2,
            params: $3,
            hash: $4,
            blockParams: $5,
            strip: $this->stripFlags($1, $6),
        );
    };

openInverse:
    OPEN_INVERSE helperName expr_list optional_hash optional_blockParams CLOSE {
        $$ = new OpenBlock(
            open: $1,
            path: $2,
            params: $3,
            hash: $4,
            blockParams: $5,
            strip: $this->stripFlags($1, $6),
        );
    };

openInverseChain:
    OPEN_INVERSE_CHAIN helperName expr_list optional_hash optional_blockParams CLOSE {
        $$ = new OpenBlock(
            open: $1,
            path: $2,
            params: $3,
            hash: $4,
            blockParams: $5,
            strip: $this->stripFlags($1, $6),
        );
    };

optional_inverseAndProgram:
    inverseAndProgram
  | /* empty */ { $$ = null; }
;

inverseAndProgram:
    INVERSE program {
        $$ = new InverseChain(
            strip: $this->stripFlags($1, $1),
            program: $2,
        );
    };

optional_inverseChain:
    inverseChain
  | /* empty */ { $$ = null; }
;

inverseChain:
    openInverseChain program optional_inverseChain {
        $inverse = $this->prepareBlock($1, $2, $3, $3, false, locInfo());
        $program = $this->prepareProgram([$inverse], $2->loc);
        $program->chained = true;

        $$ = new InverseChain($1->strip, $program, true);
  }
  | inverseAndProgram { $$ = $1; }
;

closeBlock:
    OPEN_ENDBLOCK helperName CLOSE {
        $$ = new CloseBlock($2, $this->stripFlags($1, $3));
    };

mustache:
    // Parsing out the '&' escape token at AST level saves ~500 bytes after min due to the removal of one parser node.
    // This also allows for handler unification as all mustache node instances can utilize the same handler
    // See https://github.com/handlebars-lang/handlebars-parser/blob/master/lib/parse.js
    OPEN expr expr_list optional_hash CLOSE {
        $$ = $this->prepareMustache(
            path: $2,
            params: $3,
            hash: $4,
            open: $1,
            strip: $this->stripFlags($1, $5),
            loc: locInfo(),
        );
    }
  | OPEN_UNESCAPED expr expr_list optional_hash CLOSE_UNESCAPED {
        $$ = $this->prepareMustache(
            path: $2,
            params: $3,
            hash: $4,
            open: $1,
            strip: $this->stripFlags($1, $5),
            loc: locInfo(),
        );
    }
;

partial:
    OPEN_PARTIAL expr expr_list optional_hash CLOSE {
        $$ = new PartialStatement(
            name: $2,
            params: $3,
            hash: $4,
            indent: '',
            strip: $this->stripFlags($1, $5),
            loc: locInfo(),
        );
    };

partialBlock:
    openPartialBlock program closeBlock {
        $$ = $this->preparePartialBlock(
            open: $1,
            program: $2,
            close: $3,
            loc: locInfo(),
        );
    };

openPartialBlock:
    OPEN_PARTIAL_BLOCK expr expr_list optional_hash CLOSE {
        $$ = new OpenPartialBlock(
            path: $2,
            params: $3,
            hash: $4,
            strip: $this->stripFlags($1, $5),
        );
    };

expr_list:
    expr_list expr { pushNormalizing($1, $2); }
  | /* empty */    { init(); }
;

expr:
    helperName { $$ = $1; }
  | sexpr { $$ = $1; }
;

sexpr:
    OPEN_SEXPR expr expr_list optional_hash CLOSE_SEXPR {
        $$ = new SubExpression(
            path: $2,
            params: $3,
            hash: $4,
            loc: locInfo(),
        );
    }
;

hash:
    non_empty_hashSegment_list {
        $$ = new Hash($1, locInfo());
    };

optional_hash:
    hash
  | /* empty */ { $$ = null; }
;

non_empty_hashSegment_list:
    hashSegment { init($1); }
  | non_empty_hashSegment_list hashSegment { pushNormalizing($1, $2); }
;

hashSegment:
    ID EQUALS expr {
        $$ = new HashPair(
            key: $this->id($1),
            value: $3,
            loc: locInfo(),
        );
    };

optional_blockParams:
    blockParams
  | /* empty */ { init(); }
;

non_empty_ID_list:
    ID { init($1); }
  | non_empty_ID_list ID { pushNormalizing($1, $2); }
;

blockParams:
    OPEN_BLOCK_PARAMS non_empty_ID_list CLOSE_BLOCK_PARAMS {
        $$ = array_map($this->id(...), $2);
    };

helperName:
    path { $$ = $1; }
  | dataName { $$ = $1; }
  | STRING { $$ = new StringLiteral($1, $1, locInfo()); }
  | NUMBER { $$ = new NumberLiteral($1 + 0, $1 + 0, locInfo()); }
  | BOOLEAN { $$ = new BooleanLiteral($1 === 'true', $1 === 'true', locInfo()); }
  | UNDEFINED { $$ = new UndefinedLiteral(null, null, locInfo()); }
  | NULL { $$ = new NullLiteral(null, null, locInfo()); }
;

dataName:
    DATA pathSegments {
        $$ = $this->preparePath(
            data: true,
            sexpr: null,
            parts: $2,
            loc: locInfo(),
        );
    };

sep:
    SEP { $$ = $1; }
  | PRIVATE_SEP { $$ = $1; }
;

path:
    sexpr sep pathSegments {
        $$ = $this->preparePath(
            data: false,
            sexpr: $1,
            parts: $3,
            loc: locInfo(),
        );
    }
  | pathSegments {
        $$ = $this->preparePath(
            data: false,
            sexpr: null,
            parts: $1,
            loc: locInfo(),
        );
    }
;

pathSegments:
    pathSegments sep ID {
        push($1, new PathSegment($this->id($3), $3, $2));
    }
  | ID {
        init(new PathSegment($this->id($1), $1, null));
    }
;

%%
