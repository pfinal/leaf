<?php

namespace Leaf\Provider;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Leaf\Session;

/**
 * 验证码
 *
 * $app->register(new \Leaf\Provider\CaptchaProvider());
 *
 * \Leaf\Route::get('captcha', function (\Leaf\Application $app) {
 *     //$captcha = new \Leaf\Provider\CaptchaProvider();
 *     //$captcha->width=120;
 *     //$captcha->create();
 *     return $app['captcha']->create();
 * });
 *
 * \Leaf\Route::any('show', function () {
 *     //<img src="/app.php/captcha" onclick="this.src='/app.php/captcha?refresh='+Math.random()" style="cursor:pointer;">
 *     return '<form method="post" action="' . \Leaf\Url::to('validate') . '"><img alt="captcha" src="' . \Leaf\Url::to('captcha') . '" onclick="this.src=\'' . \Leaf\Url::to('captcha') . '?refresh=\'+Math.random()" style="cursor:pointer;"><input name="captcha"> <button type="submit">Submit</button></form>';
 * });
 *
 * \Leaf\Route::post('validate', function (\Leaf\Application $app, \Leaf\Request $request) {
 *     if ($app['captcha']->validate($request->get('captcha'))) {
 *         return 'success';
 *     } else {
 *         return 'Verification code is invalid.';
 *     }
 * });
 */
class CaptchaProvider implements ServiceProviderInterface
{
    const REFRESH_GET_VAR = 'refresh';
    const SESSION_VAR_PREFIX = 'CaptchaCode';
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

    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['captcha'] = function () {
            $captcha = new self;
            return $captcha;
        };
    }

    /**
     * 直接输出图像
     */
    public function create()
    {
        self::header();

        if (isset($_GET[self::REFRESH_GET_VAR])) {
            $this->renderImage($this->getVerifyCode(true));
        } else {
            $this->renderImage($this->getVerifyCode());
        }
    }

    /**
     * 生成img标签
     * @return string
     */
    public function img()
    {
        ob_start();
        if (isset($_GET[self::REFRESH_GET_VAR])) {
            $this->renderImage($this->getVerifyCode(true));
        } else {
            $this->renderImage($this->getVerifyCode());
        }
        $img = ob_get_clean();

        //部份浏览器不兼容base64编码
        return '<img alt="captcha" src="data:image/png;base64,' . base64_encode($img) . '">';
    }

    public function generateValidationHash($code)
    {
        for ($h = 0, $i = strlen($code) - 1; $i >= 0; --$i)
            $h += ord($code[$i]);
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
            //更换验证码
            $this->getVerifyCode(true);
        }
        return false;
    }

    protected function generateVerifyCode()
    {
        if ($this->minLength < 3)
            $this->minLength = 3;
        if ($this->maxLength > 20)
            $this->maxLength = 20;
        if ($this->minLength > $this->maxLength)
            $this->maxLength = $this->minLength;
        $length = mt_rand($this->minLength, $this->maxLength);

        $letters = 'bcdfghjklmnpqrstvwxyz';
        $vowels = 'aeiou';
        $code = '';
        for ($i = 0; $i < $length; ++$i) {
            if ($i % 2 && mt_rand(0, 10) > 2 || !($i % 2) && mt_rand(0, 10) > 9)
                $code .= $vowels[mt_rand(0, 4)];
            else
                $code .= $letters[mt_rand(0, 20)];
        }

        return $code;
    }

    protected function getSessionKey()
    {
        return self::SESSION_VAR_PREFIX;
    }

    public function renderImage($code)
    {
        if ($this->backend === null && self::checkRequirements('imagick') || $this->backend === 'imagick') {
            $this->renderImageImagick((string)$code);
        } else if ($this->backend === null && self::checkRequirements('gd') || $this->backend === 'gd') {
            $this->renderImageGD((string)$code);
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

    public static function header()
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Transfer-Encoding: binary');
        header("Content-type: image/png");
    }

    /**
     * 检查扩展支持
     * @param string $extension 'gd', 'imagick' and null
     * @return boolean true if ImageMagick extension with PNG support or GD with FreeType support is loaded, otherwise false
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
            if (isset($imagickFormats) && in_array('PNG', $imagickFormats))
                return true;
            if (isset($gdInfo) && $gdInfo['FreeType Support'])
                return true;
        } elseif ($extension == 'imagick' && isset($imagickFormats) && in_array('PNG', $imagickFormats))
            return true;
        elseif ($extension == 'gd' && isset($gdInfo) && $gdInfo['FreeType Support'])
            return true;
        return false;
    }
}