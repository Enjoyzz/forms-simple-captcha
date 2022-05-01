<?php

namespace Tests\Enjoys\Forms\Captcha\SimpleCaptcha;

use Enjoys\Forms\Captcha\SimpleCaptcha\SimpleCaptcha;
use Enjoys\Forms\Elements\Captcha;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\ServerRequestWrapper;
use Enjoys\Traits\Reflection;
use HttpSoft\Message\ServerRequest;
use Webmozart\Assert\InvalidArgumentException;

class DefaultsTest extends _TestCase
{
    use Reflection;


    public function setUp(): void
    {
        parent::setUp();
        $this->session->set(
            [
                'captcha_defaults' => 'testcode'
            ]
        );
    }

    public function tearDown(): void
    {
        $this->session->delete('captcha_defaults');
        parent::tearDown();
    }

    public function test1()
    {
        $captcha = new SimpleCaptcha();
        $captcha->setOption('foo', 'v_foo');
        $captcha->setOptions(
            [
                'bar' => 'v_bar',
                'baz' => 'v_baz'
            ]
        );

        $captcha_element = new Captcha($captcha);
        $captcha_element->baseHtml();


        $this->assertArrayHasKey('foo', $captcha->getOptions());
        $this->assertArrayHasKey('bar', $captcha->getOptions());
        $this->assertEquals('v_baz', $captcha->getOption('baz'));
        $this->assertEquals('text', $captcha_element->getAttribute('type')->getValueString());
        $this->assertEquals('off', $captcha_element->getAttribute('autocomplete')->getValueString());
    }


    public function testGenerateCode()
    {
        srand(0);
        $captcha = new SimpleCaptcha();
        $captcha->setOptions(
            [
                'size' => 5
            ]
        );

        $el = new Captcha($captcha);

        $method = $this->getPrivateMethod('Enjoys\Forms\Captcha\SimpleCaptcha\SimpleCaptcha', 'generateCode');
        $method->invokeArgs($captcha, [$el]);

        $this->assertEquals(5, \strlen($captcha->getCode()));
        $this->assertSame('o24ni', $captcha->getCode());
        $this->assertSame('o24ni', $this->session->get($el->getName()));
    }

    public function testGenerateCodeWithInvalidSizeOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $captcha = new SimpleCaptcha();
        $captcha->setOptions(
            [
                'size' => 'xl'
            ]
        );

        $method = $this->getPrivateMethod(SimpleCaptcha::class, 'generateCode');
        $method->invokeArgs($captcha, [new Captcha($captcha)]);
    }

    public function testGenerateCodeWithZeroSizeOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $captcha = new SimpleCaptcha();
        $captcha->setOptions(
            [
                'size' => 0
            ]
        );

        $method = $this->getPrivateMethod(SimpleCaptcha::class, 'generateCode');
        $method->invokeArgs($captcha, [new Captcha($captcha)]);
    }

    public function testCreateImgWithInvalidParams()
    {
        $this->expectError();
        $captcha = new SimpleCaptcha();
        $method = $this->getPrivateMethod(SimpleCaptcha::class, 'createImage');
        $method->invoke($captcha, 'test', 'x', 'y');
    }

    public function testCreateImgWithDefaultParams()
    {
        $captcha = new SimpleCaptcha();
        $method = $this->getPrivateMethod(SimpleCaptcha::class, 'createImage');
        $img = $method->invoke($captcha, 'test');
        $this->assertEquals(150, \imagesx($img));
        $this->assertEquals(50, \imagesy($img));
    }

    public function testCreateImg()
    {
        srand(0);

        $captcha = new SimpleCaptcha();
        $method = $this->getPrivateMethod(SimpleCaptcha::class, 'createImage');
        $img = $method->invoke($captcha, 'test', '200', '100');

        $this->assertEquals(200, \imagesx($img));
        $this->assertEquals(100, \imagesy($img));

        return $img;
    }


    /**
     * @depends testCreateImg
     */
    public function testGetBase64image($img)
    {
        $captcha = new SimpleCaptcha();
        $method = $this->getPrivateMethod(SimpleCaptcha::class, 'getBase64Image');

        $base64img = $method->invoke($captcha, $img);
        $result = \base64_decode($base64img);
        $size = \getimagesizefromstring($result);
        $this->assertEquals(200, $size[0]);
        $this->assertEquals(100, $size[1]);
    }


    public function testRenderWithInvalidParametersWidth()
    {
        $this->expectException(InvalidArgumentException::class);
        $captcha = new SimpleCaptcha();
        $captcha->setOptions([
            'width' => '100'
        ]);
        $element = new Captcha($captcha);
        $element->prepare();
        $element->baseHtml();
    }

    public function testRenderWithInvalidParametersHeight()
    {
        $this->expectException(InvalidArgumentException::class);
        $captcha = new SimpleCaptcha();
        $captcha->setOptions([
            'height' => '100'
        ]);
        $element = new Captcha($captcha);
        $element->prepare();
        $element->baseHtml();
    }

    public function testRenderWithDefaultsParametersHeightAndWidth()
    {
        srand(0);
        $element = new Captcha(new SimpleCaptcha());

        $element->prepare();

        $resultRenderer = $element->baseHtml();
        $this->assertStringContainsString(
            '<img alt="captcha image" src="data:image/jpeg;base64,',
            $resultRenderer
        );
        $this->assertStringContainsString(
            '" /><br /><input id="captcha_defaults" name="captcha_defaults" type="text" autocomplete="off">',
            $resultRenderer
        );
        preg_match_all('/"(.*?)"/i', $resultRenderer, $matches);
        $result = \base64_decode(\str_replace('data:image/jpeg;base64,', '', $matches[1][1]));
        $size = \getimagesizefromstring($result);
        $this->assertEquals(150, $size[0]);
        $this->assertEquals(50, $size[1]);
    }

    /**
     * @throws ExceptionRule
     */
    public function testRenderHtml()
    {
        $request = new ServerRequestWrapper(
            new ServerRequest(parsedBody: [
                'captcha_defaults' => 'testcode_fail'
            ], method: 'post')
        );

        $captcha = new SimpleCaptcha('code invalid');

        $element = new Captcha($captcha);
        $element->prepare();

        $element->validate();

        $html = $element->baseHtml();
        $this->assertEquals(6, \strlen($captcha->getCode()));
        $this->assertStringContainsString('img alt="captcha image" src="data:image/jpeg;base64,', $html);
        $this->assertStringContainsString(
            '<input id="captcha_defaults" name="captcha_defaults" type="text" autocomplete="off">',
            $html
        );
        $this->assertEquals('code invalid', $element->getRuleErrorMessage());
//        $this->assertStringContainsString('<p style="color: red">code invalid</p>', $html);
    }

    public function test_validate()
    {
        $request = new ServerRequestWrapper(
            new ServerRequest(queryParams: [
                'captcha_defaults' => 'testcode'
            ], method: 'gEt')
        );
        $captcha = new SimpleCaptcha();

        $element = new Captcha($captcha);
        $captcha->setRequest($request);
        $this->assertSame('captcha_defaults', $captcha->getName());
        $this->assertTrue($element->validate());

        $request = new ServerRequestWrapper(
            new ServerRequest(queryParams: [
                'captcha_defaults' => 'testcode_fail'
            ], method: 'get')
        );

        $captcha->setRequest($request);
        $this->assertFalse($element->validate());
    }
}
