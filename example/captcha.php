<?php

declare(strict_types=1);

use Enjoys\Forms\Captcha\SimpleCaptcha\SimpleCaptcha;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Html\HtmlRenderer;

require __DIR__ . '/../vendor/autoload.php';

$form = new Form();
$captcha = new SimpleCaptcha();
$form->captcha($captcha);
$renderer = new HtmlRenderer($form);
echo include __DIR__.'/.assets.php';
echo sprintf('<div class="container-fluid">%s</div>', $renderer->output());
