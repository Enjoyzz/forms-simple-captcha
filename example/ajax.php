<?php

declare(strict_types=1);

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Captcha\SimpleCaptcha\SimpleCaptcha;
use Enjoys\Forms\Element;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\Ruleable;
use Enjoys\Forms\Renderer\Html\HtmlRenderer;
use Enjoys\Session\Session;

require __DIR__ . '/../vendor/autoload.php';
try {
    $session = new Session();
    $form = new Form();
    $form->setAttribute(AttributeFactory::create('id', 'myForm'));
    $captcha = new SimpleCaptcha();
    $captcha->setOptions(
        [
            'size' => 3,
        ]

    );
    $form->captcha($captcha);
    $form->submit('submit1');

    if ($form->isSubmitted(false)) {
        try {
            if (!$form->isSubmitted()) {
                /** @var Element $element */
                $errors = array_filter(
                    array_map(function ($element) {
                        if ($element instanceof Ruleable && $element->isRuleError()) {
                            return $element->getRuleErrorMessage();
                        }
                        return null;
                    }, $form->getElements()),
                    function ($item) {
                        return !is_null($item);
                    }
                );

                if (!empty($errors)) {
                    throw new Exception(implode("<br>", $errors));
                }

                throw new Exception('Произошла ошибка: Форма не была отправлена');
            }
        } catch (Exception $e) {
            http_response_code(400);
        }
        exit;
    }

    $renderer = new HtmlRenderer($form);
    echo include __DIR__ . '/.assets.php';

    echo <<<HTML
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>
    <script src="https://malsup.github.io/jquery.form.js"></script>

    <script>
     $(document).ready(function () {
        $('#myForm').submit(function () {
            $(this).ajaxSubmit({
                type: "POST",
                data: $(this).serialize(),
                success: function () {
                    alert('success');
                },
                error: function () {
                    alert('error');
                }
            });
            return false;
        });
    });
    </script>
HTML;

    echo sprintf('<div class="container-fluid">%s</div>', $renderer->output());
    echo 'code: <b>' . $session->get($form->getElement($captcha->getName())->getName()) . '</b>';
} catch (Throwable $e) {
    print $e->getMessage();
}
