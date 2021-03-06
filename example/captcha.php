<?php

declare(strict_types=1);

use Enjoys\Forms\Captcha\SimpleCaptcha\SimpleCaptcha;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Html\HtmlRenderer;

require __DIR__ . '/../vendor/autoload.php';

$session = new \Enjoys\Session\Session();
$form = new Form();
$captcha = new SimpleCaptcha();
$captcha->setOptions(
    [
//        'width' => 500,
//        'height' => 300
    ]

);
$form->captcha($captcha);
$renderer = new HtmlRenderer($form);
echo include __DIR__ . '/.assets.php';
echo sprintf('<div class="container-fluid">%s</div>', $renderer->output());
echo 'code: <b>' . $session->get($form->getElement($captcha->getName())->getName()) . '</b>';
