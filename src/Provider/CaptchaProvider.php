<?php

namespace Leaf\Provider;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Leaf\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 验证码
 *
 * 支持GD扩展或ImageMagick扩展
 *
 * //注册
 * $app->register(new \Leaf\Provider\CaptchaProvider());
 *
 * //生成验证码图片
 * \Leaf\Route::get('captcha', function (\Leaf\Application $app) {
 *     return $app['captcha']->create();
 * });
 *
 * //表单中使用验证码
 * \Leaf\Route::any('show', function () {
 *     $html = <<<TAG
 * <form method="post" action="{{ url('validate') }}">
 *     <img src="{{ url('captcha') }}" onclick="this.src='{{ url('captcha') }}?refresh='+Math.random()" style="cursor:pointer" alt="captcha">
 *     <input name="code">
 *     <button type="submit">提交</button>
 * </form>
 * TAG;
 *
 *     return View::renderText($html);
 * });
 *
 * //提交表单时验证输入是否正确
 * \Leaf\Route::post('validate', function (\Leaf\Application $app, \Leaf\Request $request) {
 *     if ($app['captcha']->validate($request->get('code'))) {
 *         // 'success';
 *     } else {
 *         // 'Verification code is invalid.';
 *     }
 * });
 *
 * //生成较难识别的验证码, 可使用第三方组件:
 *
 * //composer require gregwar/captcha
 *
 * //初始化验证码对象
 * $captcha = new \Gregwar\Captcha\CaptchaBuilder();
 *
 * //将验证码内容存入Session
 * \Leaf\Session::set('code', $captcha->getPhrase());
 *
 * //输出jpeg图片到浏览器
 * header('Content-type: image/jpeg');
 * $captcha->build()->output();
 *
 */
class CaptchaProvider implements ServiceProviderInterface
{
    public $testLimit = 3;             //尝试次数，默认3次错误更换验证码
    public $width = 85;
    public $height = 40;
    public $padding = 2;
    public $backColor = 0xFFFFFF;
    public $foreColor = 0x2040A0;
    public $transparent = true;        //背景透明
    public $minLength = 4;
    public $maxLength = 5;
    public $offset = -2;
    public $fontFile;
    public $fixedVerifyCode;
    public $backend;
    public $numberOnly = false;

    public function __construct($config = array())
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['captcha'] = function () use ($app) {
            $config = isset($app['captcha.config']) ? $app['captcha.config'] : array();
            $config += array('class' => 'Leaf\Provider\CaptchaProvider');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, array('config' => $config));
        };
    }

    /**
     * 直接输出图像
     * @return StreamedResponse
     */
    public function create($code = null)
    {
        $header = array(
            'Content-type' => 'image/png',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        );

        return new StreamedResponse(function () use ($code) {
            $this->renderImage(empty($code) ? $this->getVerifyCode(true) : $code);
        }, 200, $header);
    }

    /**
     * 返回 HTML inline base64
     * @return string
     */
    public function inline()
    {
        ob_start();

        $this->renderImage($this->getVerifyCode(true));

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }

    /**
     * 生成img标签
     * @return string
     */
    public function img()
    {
        //部份浏览器不兼容base64编码
        return '<img alt="captcha" src="' . self::inline() . '">';
    }

    public function generateValidationHash($code)
    {
        for ($h = 0, $i = strlen($code) - 1; $i >= 0; --$i) {
            $h += ord($code[$i]);
        }
        return $h;
    }

    /**
     * 生成验证码图片 返回随机字符串
     * @param bool|false $regenerate 重新生成
     * @return string
     */
    public function getVerifyCode($regenerate = false)
    {
        if ($this->fixedVerifyCode !== null) {
            return $this->fixedVerifyCode;
        }

        $name = $this->getSessionKey();

        if (Session::get($name) === null || $regenerate) {
            Session::set($name, $this->generateVerifyCode());
            Session::set($name . 'count', 1);
        }
        return Session::get($name);
    }

    /**
     * 验证
     * @param string $input
     * @param bool $caseSensitive
     * @return bool
     */
    public function validate($input, $caseSensitive = false)
    {
        $code = $this->getVerifyCode();
        $valid = $caseSensitive ? ($input === $code) : strcasecmp($input, $code) === 0;

        //验证成功，则清空Session中的验证码
        if ($valid) {
            Session::remove($this->getSessionKey());
            return true;
        }

        $name = $this->getSessionKey() . 'count';

        Session::set($name, Session::get($name) + 1);

        if (Session::get($name) > $this->testLimit && $this->testLimit > 0) {
            $this->getVerifyCode(true);
        }
        return false;
    }

    protected function generateVerifyCode()
    {
        if ($this->minLength < 3) {
            $this->minLength = 3;
        }
        if ($this->maxLength > 20) {
            $this->maxLength = 20;
        }
        if ($this->minLength > $this->maxLength) {
            $this->maxLength = $this->minLength;
        }
        $length = mt_rand($this->minLength, $this->maxLength);

        if ($this->numberOnly) {
            $a = '1' . str_repeat('0', $length - 1);
            $b = '9' . str_repeat('9', $length - 1);
            return mt_rand($a, $b);
        }

        $letters = 'bcdfghjklmnpqrstvwxyz';
        $vowels = 'aeiou';
        $code = '';
        for ($i = 0; $i < $length; ++$i) {
            if ($i % 2 && mt_rand(0, 10) > 2 || !($i % 2) && mt_rand(0, 10) > 9) {
                $code .= $vowels[mt_rand(0, 4)];
            } else {
                $code .= $letters[mt_rand(0, 20)];
            }
        }

        return $code;
    }

    protected function getSessionKey()
    {
        return 'CaptchaCode';
    }

    public function renderImage($code)
    {
        if ($this->backend === null && self::checkRequirements('imagick') || $this->backend === 'imagick') {
            $this->renderImageImagick((string)$code);
        } else if ($this->backend === null && self::checkRequirements('gd') || $this->backend === 'gd') {
            $this->renderImageGD((string)$code);
        } else {
            $this->renderImageOutFreeType((string)$code);
        }
    }

    protected function renderImageGD($code)
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        $backColor = imagecolorallocate($image,
            (int)($this->backColor % 0x1000000 / 0x10000),
            (int)($this->backColor % 0x10000 / 0x100),
            $this->backColor % 0x100);
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $backColor);
        imagecolordeallocate($image, $backColor);

        if ($this->transparent) {
            imagecolortransparent($image, $backColor);
        }

        $foreColor = imagecolorallocate($image,
            (int)($this->foreColor % 0x1000000 / 0x10000),
            (int)($this->foreColor % 0x10000 / 0x100),
            $this->foreColor % 0x100);

        if ($this->fontFile === null) {
            $this->fontFile = dirname(__FILE__) . '/Duality.ttf';
        }

        $length = strlen($code);
        $box = imagettfbbox(30, 0, $this->fontFile, $code);
        $w = $box[4] - $box[0] + $this->offset * ($length - 1);
        $h = $box[1] - $box[5];
        $scale = min(($this->width - $this->padding * 2) / $w, ($this->height - $this->padding * 2) / $h);
        $x = 10;
        $y = round($this->height * 27 / 40);

        for ($i = 0; $i < $length; ++$i) {
            $fontSize = (int)(rand(26, 32) * $scale * 0.8);
            $angle = rand(-10, 10);
            $letter = $code[$i];
            $box = imagettftext($image, $fontSize, $angle, $x, $y, $foreColor, $this->fontFile, $letter);
            $x = $box[2] + $this->offset;
        }

        imagecolordeallocate($image, $foreColor);
        imagepng($image);
        imagedestroy($image);
    }

    protected function renderImageImagick($code)
    {
        $backColor = new ImagickPixel('#' . dechex($this->backColor));
        $foreColor = new ImagickPixel('#' . dechex($this->foreColor));

        $image = new Imagick();
        $image->newImage($this->width, $this->height, $backColor);

        if ($this->fontFile === null) {
            $this->fontFile = dirname(__FILE__) . '/Duality.ttf';
        }

        $draw = new ImagickDraw();
        $draw->setFont($this->fontFile);
        $draw->setFontSize(30);
        $fontMetrics = $image->queryFontMetrics($draw, $code);

        $length = strlen($code);
        $w = (int)($fontMetrics['textWidth']) - 8 + $this->offset * ($length - 1);
        $h = (int)($fontMetrics['textHeight']) - 8;
        $scale = min(($this->width - $this->padding * 2) / $w, ($this->height - $this->padding * 2) / $h);
        $x = 10;
        $y = round($this->height * 27 / 40);

        for ($i = 0; $i < $length; ++$i) {
            $draw = new ImagickDraw();
            $draw->setFont($this->fontFile);
            $draw->setFontSize((int)(rand(26, 32) * $scale * 0.8));
            $draw->setFillColor($foreColor);
            $image->annotateImage($draw, $x, $y, rand(-10, 10), $code[$i]);
            $fontMetrics = $image->queryFontMetrics($draw, $code[$i]);
            $x += (int)($fontMetrics['textWidth']) + $this->offset;
        }

        $image->setImageFormat('png');
        echo $image;
    }

    protected function renderImageOutFreeType($code)
    {
        $width = $this->width;
        $height = $this->height;

        $image = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($image,
            (int)($this->backColor % 0x1000000 / 0x10000),
            (int)($this->backColor % 0x10000 / 0x100),
            $this->backColor % 0x100);

        $textColor = imagecolorallocate($image, 0, 0, 255);

        imagefill($image, 0, 0, $bg);

        imagestring($image, 5, 10, 5, $code, $textColor);

        $x1 = $width * rand(0, 20) / 100;
        $y1 = $height * rand(0, 100) / 100;

        $x2 = $width * rand(80, 100) / 100;
        $y2 = $height * rand(0, 90) / 100;

        imageline($image, $x1, $y1, $x2, $y2, $bg);

        imagepng($image);
        imagedestroy($image);
    }

    /**
     * 检查扩展支持
     * @param string $extension 'gd', 'imagick' and null
     * @return boolean 安装了ImageMagick扩展并支持PNG 或 安装了GD扩展并支持FreeType, 返回true, 否则返回false
     */
    public static function checkRequirements($extension = null)
    {
        if (extension_loaded('imagick')) {
            $imagick = new Imagick();
            $imagickFormats = $imagick->queryFormats('PNG');
        }

        if (extension_loaded('gd')) {
            $gdInfo = gd_info();
        }

        if ($extension === null) {
            if (isset($imagickFormats) && in_array('PNG', $imagickFormats)) {
                return true;
            }
            if (isset($gdInfo) && $gdInfo['FreeType Support']) {
                return true;
            }
        } elseif ($extension == 'imagick' && isset($imagickFormats) && in_array('PNG', $imagickFormats)) {
            return true;
        } elseif ($extension == 'gd' && isset($gdInfo) && $gdInfo['FreeType Support']) {
            return true;
        }

        return false;
    }
}
