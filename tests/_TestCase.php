<?php

declare(strict_types=1);

namespace Tests\Enjoys\Forms\Captcha\SimpleCaptcha;

use Enjoys\Session\Session;
use PHPUnit\Framework\TestCase;

new Session();

class _TestCase extends TestCase
{
    protected Session $session;

    protected function setUp(): void
    {
        $this->session = new Session();
    }

    protected function tearDown(): void
    {
        $this->session->delete('csrf_secret');
        unset($this->session);
    }

    public function stringOneLine(string $input, bool $replaceTab = true): string
    {
        if ($replaceTab) {
            $input = str_replace(["\t", str_repeat(" ", 4)], "", $input);
        }

        return str_replace(["\r\n", "\r", "\n"], "", $input);
    }
}
