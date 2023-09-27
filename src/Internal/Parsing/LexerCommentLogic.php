<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\StringStream;

/**
 * Provides methods for tokenizing comments /  in the lexer.
 */
class LexerCommentLogic
{
    private readonly TokenEmitter $emitter;
    private readonly StringStream $str;

    /**
     * Constructor.
     *
     * @param StringStream $str     The string stream to be tokenized.
     * @param TokenEmitter $emitter The token emitter to receive the tokens.
     */
    public function __construct(StringStream $str, TokenEmitter $emitter)
    {
        $this->str = $str;
        $this->emitter = $emitter;
    }

    /**
     * Tokenizes in the bogus comment state.
     *
     * @param string $data The initial text data for the comment.
     */
    public function tokenizeBogusComment(string $data = ''): void
    {
        $pos = $this->str->findNextStr('>');
        if ($pos < 0) {
            $data .= $this->str->readToEnd();
        } else {
            $data .= $this->str->read($pos);
            $this->str->advance();
        }
        $this->emitter->emit(new CommentToken($data));

        // TODO
        if (!$this->str->hasNext()) {
            $this->emitter->emit(new EofToken());
        }
    }

    /**
     * Tokenizes in the CDATA section state.
     */
    public function tokenizeCdata(): void
    {
        $pos = $this->str->findNextStr(']]>');
        if ($pos < 0) { // CDATA without end
            $data = $this->str->readToEnd();
            $this->emitter->emit(new TextToken($data));
            $this->emitter->emit(new EofToken());
        } else {
            $data = $this->str->read($pos);
            $this->str->advance(3);
            $this->emitter->emit(new TextToken($data));
        }
    }

    /**
     * Tokenizes in the comment state.
     *
     * @param string $initData The initial data of the comment.
     */
    public function tokenizeComment(string $initData = ''): void
    {
        $pos = $this->str->findNextStr('>');
        if ($pos < 0) { // comment without end
            $data = $this->str->readToEnd();
            $this->emitter->emit(new CommentToken($initData . $data));
            $this->emitter->emit(new EofToken());
        } else {
            $data = $this->str->read($pos);
            $this->str->advance();
            if ($data === '' || $data === '-') { // <!--> or <!---> case
                $this->emitter->emit(new CommentToken($initData));
            } elseif (substr($data, -2) === '--') { // correct --> case
                $this->emitter->emit(new CommentToken($initData . substr($data, 0, -2)));
            } else { // stay in the comment state
                $initData .= $data . '>';
                $this->tokenizeComment($initData);
            }
        }
    }
}
