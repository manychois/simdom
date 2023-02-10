<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

final class InsertionMode
{
    public const INITIAL = 'Initial';
    public const BEFORE_HTML = 'BeforeHtml';
    public const BEFORE_HEAD = 'BeforeHead';
    public const IN_HEAD = 'InHead';
    public const AFTER_HEAD = 'AfterHead';
    public const IN_BODY = 'InBody';
    public const AFTER_BODY = 'AfterBody';
    public const AFTER_AFTER_BODY = 'AfterAfterBody';
}
