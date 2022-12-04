<?php

declare(strict_types=1);

namespace Enjoys\Forms\Captcha\SimpleCaptcha;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Element;
use Enjoys\Forms\Interfaces\CaptchaInterface;
use Enjoys\Forms\Interfaces\Ruleable;
use Enjoys\Forms\Traits\Request;
use Enjoys\Session\Session as Session;
use Enjoys\Traits\Options;
use Webmozart\Assert\Assert;

class SimpleCaptcha implements CaptchaInterface
{
    use Options;
    use Request;

    private string $code = '';
    private Session $session;
    private string $name = 'captcha_defaults';
    private ?string $ruleMessage = null;

    public function __construct(?string $message = null, array $options = [])
    {
        $this->setOptions($options);
        putenv('GDFONTPATH=' . realpath($this->getOption('gd_FontPath', __DIR__ . '/fonts')));

        $this->session = new Session();

        if (is_null($message)) {
            $message = 'Не верно введен код';
        }
        $this->setRuleMessage($message);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRuleMessage(): ?string
    {
        return $this->ruleMessage;
    }

    public function setRuleMessage(?string $message = null): void
    {
        $this->ruleMessage = $message;
    }

    /**
     * @psalm-suppress PossiblyNullReference
     * @param Ruleable&Element $element
     * @return bool
     */
    public function validate(Ruleable $element): bool
    {
        $method = $this->getRequest()->getMethod();
        $requestData = match (strtolower($method)) {
            'get' => $this->getRequest()->getQueryParams(),
            'post' => $this->getRequest()->getParsedBody(),
            default => []
        };

        $value = \getValueByIndexPath($element->getName(), $requestData);

        if ($this->session->get($element->getName()) !== $value) {
            $element->setRuleError($this->getRuleMessage());
            return false;
        }
        return true;
    }

    public function renderHtml(Element $element): string
    {
        $element->setAttributes(
            AttributeFactory::createFromArray([
                'type' => 'text',
                'autocomplete' => 'off'
            ])
        );

        $this->generateCode($element);

        $w = $this->getOption('width', 150);
        Assert::integer($w, 'Width parameter must be integer');

        $h = $this->getOption('height', 50);
        Assert::integer($h, 'Height parameter must be integer');

        $img = $this->createImage($this->getCode(), $w, $h);

        //dump($this->session->get($this->getName()));
        //  $html = '';

//        if ($this->element->isRuleError()) {
//            $html .= "<p style=\"color: red\">{$this->element->getRuleErrorMessage()}</p>";
//        }

        return sprintf(
            '<img alt="captcha image" src="data:image/jpeg;base64,%s" /><br /><input%s>',
            $this->getBase64Image($img),
            $element->getAttributesString()
        );
    }

    private function generateCode(Element $element): void
    {
        $max = $this->getOption('size', 6);

        Assert::notEq(0, $max);
        Assert::integer($max);

        $chars = $this->getOption('chars', 'qwertyuiopasdfghjklzxcvbnm1234567890');
        $size = strlen($chars) - 1;
        // Определяем пустую переменную, в которую и будем записывать символы.
        $code = '';
        // Создаём пароль.
        while ($max--) {
            $code .= $chars[rand(0, $size)];
        }
        $this->code = $code;
        $this->session->set(
            [
                $element->getName() => $this->code
            ]
        );
    }

    public function getCode(): string
    {
        return $this->code;
    }


    private function createImage(string $code, int $width = 150, int $height = 50): \GdImage
    {
        // Создаем пустое изображение
        $img = \imagecreatetruecolor($width, $height);

        $background_color = [\mt_rand(200, 255), \mt_rand(200, 255), \mt_rand(200, 255)];
        // Заливаем фон белым цветом
        $background = \imagecolorallocate($img, $background_color[0], $background_color[1], $background_color[2]);
        \imagefill($img, 0, 0, $background);


        // Накладываем защитный код
        $x = 0;
        $letters = \str_split($code);
        $figures = [50, 70, 90, 110, 130, 150, 170, 190, 210];

        foreach ($letters as $letter) {
            //Ориентир
            $h = 1;
            //Рисуем
            $color = \imagecolorallocatealpha(
                $img,
                $figures[\rand(0, \count($figures) - 1)],
                $figures[\rand(0, \count($figures) - 1)],
                $figures[\rand(0, \count($figures) - 1)],
                rand(10, 30)
            );


            // Формируем координаты для вывода символа
            if (empty($x)) {
                $x = (int)($width * 0.08);
            } else {
                $x = (int)($x + ($width * 0.8) / \count($letters) + \rand(0, (int)($width * 0.01)));
            }

//            if (rand(0, 1)) {
//                $y = (int)((($height * 1) / 2) + \rand(0, (int)($height * 0.1)));
//            } else {
//                $y = (int)((($height * 1) / 2) - \rand(0, (int)($height * 0.1)));
//            }

            $y = (int)($height - ($height / 4));


            // Изменяем регистр символа
            if (rand(0, 1)) {
                $letter = \strtoupper($letter);
            }

            // Выводим символ на изображение
            \imagefttext(
                $img,
                rand((int)($height / 2), (int)($height / 1.5)),
                rand(-30, 30),
                $x,
                $y,
                $color,
                $this->getOption('font', 'OhioKraft.otf'),
                $letter,
                [
                    'linespacing' => 2.5
                ]
            );
            $x++;
        }


        $linenum = rand((int)($height / 5), (int)($height / 4));
        for ($i = 0; $i < $linenum; $i++) {
            $color = imagecolorallocate($img, rand(0, 255), rand(0, 200), rand(0, 255));
            imageline($img, 0, rand(0, $height), $width, rand(0, $height), $color);
        }

        return $img;
    }


    private function getBase64Image(\GdImage $img): string
    {
        ob_start();
        imagejpeg($img);
        return base64_encode(ob_get_clean());
    }
}
