<?php

namespace Leaf;

/**
 * 图片处理类
 *
 * 功能列表：
 * 1.生成缩略图 thumb
 * 2.剪裁并生成缩略图 thumbCut
 * 3.图片裁剪 cut
 * 4.图片加水印 waterMark
 * 5.转换图片格式 convert
 * 6.获取图片尺寸 getSize
 *
 * @author Zou Yiliang
 */
class Image
{
    /**
     * 缩放图片
     * @param string $filename 原图
     * @param string $dstName 生成的新文件名
     * @param int $maxWidth 最大宽度 px
     * @param int $maxHeight 最大高度 px
     * @return bool
     */
    public static function resize($filename, $dstName, $maxWidth, $maxHeight)
    {
        //创建原图画板
        if (($srcImg = self::createImage($filename)) == false) {
            return false;
        }

        //原图大小
        $x = imagesx($srcImg);
        $y = imagesy($srcImg);

        if ($x / $y > $maxWidth / $maxHeight) {
            //图片比较宽
            $m = $maxWidth;
            $n = $maxWidth * $y / $x;
        } else {
            //图片比较高
            $m = $maxHeight * $x / $y;
            $n = $maxHeight;
        }

        //目标图像画板
        $dstImg = imagecreatetruecolor($m, $n);

        //复制图片
        imagecopyresampled($dstImg, $srcImg,
            0, 0,             // 目标起始点
            0, 0,             // 原图起始点
            $m, $n,           // 目标宽高
            $x, $y);          // 原图宽高

        //释放资源
        imagedestroy($srcImg);

        $basePath = dirname($dstName);
        if (!file_exists($basePath)) {
            @mkdir($basePath, 0777, true);
            @chmod($basePath, 0777);
        }

        //保存到文件
        if (self::saveToFile($dstImg, $dstName)) {
            imagedestroy($dstImg); //释放资源
            return true;
        }
        return false;
    }

    /**
     * 生成缩略图
     *
     * @param string $filename 待处理的图片名
     * @param string $dstName 文件保存路径名
     * @param int $width 缩略图宽度
     * @param int $height 缩略图高度
     * @param int $fillColor 填充色 默认 0xFFFFFF
     * @return bool
     */
    public static function thumb($filename, $dstName, $width, $height, $fillColor = 0xFFFFFF)
    {
        //创建原图画板
        $src_img = self::createImage($filename);
        if ($src_img == false) {
            return false;
        }

        //目标图像画板
        $dst_img = imagecreatetruecolor($width, $height);

        //填充颜色
        $fillColor = imagecolorallocate($dst_img,
            (int)($fillColor % 0x1000000 / 0x10000),
            (int)($fillColor % 0x10000 / 0x100),
            $fillColor % 0x100);

        imagefill($dst_img, 0, 0, $fillColor);

        //获到源图片大小
        $x = imagesx($src_img); //最大x坐标值
        $y = imagesy($src_img); //最大y坐标值

        //计算图像比例
        $dst_x = 0;
        $dst_y = 0;
        if ($x / $y > $width / $height) {
            //如果图片比较宽,计算目标高度 (小图的高度)
            $h = $width * $y / $x;
            // (目标的高度-计算出的高度)/2,用为目标的y偏移量
            $dst_y = ($height - $h) / 2;
            //宽度使用指定要求缩放的宽度
            $w = $width;
        } else {
            $w = $x * $height / $y; //目标宽度 (小图的宽度)
            $h = $height;
            $dst_x = ($width - $w) / 2;
        }

        //复制图片
        imagecopyresampled($dst_img, $src_img,
            $dst_x, $dst_y,   // 目标起始点
            0, 0,             // 原图起始点
            $w, $h,           // 目标宽高
            $x, $y)           // 原图宽高
        ;

        //释放资源
        imagedestroy($src_img);

        $basePath = dirname($dstName);
        if (!file_exists($basePath)) {
            @mkdir($basePath, 0777, true);
            @chmod($basePath, 0777);
        }

        //保存到文件
        if (self::saveToFile($dst_img, $dstName)) {

            //释放资源
            imagedestroy($dst_img);

            return true;
        }
        return false;
    }

    /**
     * 剪裁并生成缩略图(先按比例切掉边上多余的部份)
     *
     * @param string $fromName 文件完整路径名
     * @param string $dstName 文件保存路径名
     * @param int $dstWidth 文件剪裁宽度
     * @param int $dstHeight 文件剪裁高度
     * @return bool
     */
    public static function thumbCut($fromName, $dstName, $dstWidth, $dstHeight)
    {
        //需要的宽高比例
        $newPercent = $dstWidth / $dstHeight; //例如 200/100

        //当前图片实际尺寸
        $imgArr = self::getSize($fromName);
        if ($imgArr == false) {
            return false;
        }

        //当前图片宽高比例
        $currentPercent = $imgArr['width'] / $imgArr['height'];  //例如  600/200    可 200/600

        //确定切割哪条边(分析图片太宽了，还是太高了)
        if ($currentPercent > $newPercent) {

            //太宽了 计算需要的宽度
            $w = $imgArr['height'] * $newPercent;

            //高度不变
            $h = $imgArr['height'];

            //切割点
            $x = (int)($imgArr['width'] - $w) / 2;
            $y = 0;

        } else {
            // 太高了 计算需要的高度
            $h = $imgArr['width'] / $newPercent;

            //宽度不变
            $w = $imgArr['width'];

            //切割点
            $y = (int)($imgArr['height'] - $h) / 2;
            $x = 0;
        }

        $basePath = dirname($dstName);
        if (!file_exists($basePath)) {
            @mkdir($basePath, 0777, true);
            @chmod($basePath, 0777);
        }

        if ($newPercent === $currentPercent) {
            @copy($fromName, $dstName);//不用剪裁
        } else {
            self::cut($fromName, $dstName, $x, $y, $w, $h);
        }

        //缩放
        return false !== self::thumb($dstName, $dstName, $dstWidth, $dstHeight);
    }

    /**
     * 图片裁剪
     *
     * @param string $from_name 待处理的图片名
     * @param string $dst_name 生成的目标文件
     * @param string $x
     * @param string $y
     * @param string $w
     * @param string $h
     * @return bool 成功返回文件名
     */
    public static function cut($from_name, $dst_name, $x, $y, $w, $h)
    {
        //创建原图画板
        $src_img = self::createImage($from_name);
        if ($src_img == false) {
            return false;
        }

        //目标图像 (画板)
        $dst_img = imagecreatetruecolor($w, $h);

        //填充为白色
        imagefill($dst_img, 0, 0, imagecolorallocate($dst_img, 255, 255, 255));

        //复制图片
        imagecopyresampled($dst_img, $src_img, 0, 0, $x, $y, $w, $h, $w, $h);

        //保存到文件
        if (self::saveToFile($dst_img, $dst_name)) {

            //释放资源
            imagedestroy($src_img);
            imagedestroy($dst_img);

            return true;
        }
        return false;
    }

    /**
     * 图片加水印
     *
     * @param $filename string 原始图片文件名
     * @param $dstName string 生成的目标文件
     * @param $water string 水印图片
     * @param $pos int|array 水印位置  1右下  2 中中  3左上 传数组[x, y]
     * @return bool
     */
    public static function waterMark($filename, $dstName, $water, $pos = 1)
    {
        //图片画板
        $img = self::createImage($filename);
        $img_w = self::createImage($water);
        if ($img == false || $img_w == false) {
            return false;
        }

        //原图大小
        $src_x = imagesx($img);
        $src_y = imagesy($img);

        //获到水印图片的大小
        $x = imagesx($img_w);
        $y = imagesy($img_w);

        if (is_numeric($pos)) {
            //根据$pos来决定目标的位置
            switch ($pos) {
                case 1:
                    $w = $src_x - $x;
                    $h = $src_y - $y;
                    break;
                case 2:
                    $w = ($src_x - $x) / 2;
                    $h = ($src_y - $y) / 2;
                    break;
                default:
                    $w = 0;
                    $h = 0;
            }
        } else {
            $w = $pos[0];
            $h = $pos[1];
        }

        //将水印复制到另一个画板中
        imagecopyresampled($img, $img_w, $w, $h, 0, 0, $x, $y, $x, $y);

        //保存文件
        $result = self::saveToFile($img, $dstName);

        //释放资源
        imagedestroy($img);
        imagedestroy($img_w);

        return $result;
    }

    /**
     * 在图片上写字
     * @param $filename
     * @param $dstName
     * @param $letter
     * @param $x
     * @param $y
     * @param int $foreColor
     * @param string $fontFile 字体文件
     * @param int $fontSize
     * @return bool
     */
    public static function writeText($filename, $dstName, $letter, $x, $y, $foreColor = 0xFFFFFF, $fontFile = null, $fontSize = 12)
    {
        $image = self::createImage($filename);

        $foreColor = imagecolorallocate($image,
            (int)($foreColor % 0x1000000 / 0x10000),
            (int)($foreColor % 0x10000 / 0x100),
            (int)$foreColor % 0x100);

        if ($fontFile != null && function_exists('imagettftext')) {
            $angle = 0;
            imagettftext($image, $fontSize, $angle, $x, $y, $foreColor, $fontFile, $letter);
        } else {
            //mac的gd库默认缺少freetype时，可用imagestring，方便开发环境使用
            imagestring($image, 5, $x, $y, $letter, $foreColor);
        }

        //保存文件
        $result = self::saveToFile($image, $dstName);

        //释放资源
        imagedestroy($image);

        return $result;
    }

    /**
     * 转换图片格式 例如将1.png 转为 1.jpg
     *
     * @param $file
     * @param $saveFile
     * @return bool
     */
    public static function convert($file, $saveFile)
    {
        $img = self::createImage($file);
        if ($img == false) {
            return false;
        }
        if (self::saveToFile($img, $saveFile)) {
            imagedestroy($img);
            return true;
        }
        return false;
    }

    /**
     * 获取图片宽高
     *
     * @param $file
     * @return array|bool 返回数组 array('width'=>宽度,'height'=>高度);
     */
    public static function getSize($file)
    {
        $img = self::createImage($file);
        if ($img == false) {
            return false;
        }
        $x = imagesx($img);
        $y = imagesy($img);
        imagedestroy($img);
        return array('width' => $x, 'height' => $y);
    }


    /**
     * 保存img资源到文件
     *
     * @param $img
     * @param $file
     * @return bool
     */
    public static function saveToFile($img, $file)
    {
        $ext = strtolower(strrchr($file, '.'));
        switch ($ext) {
            case '.jpg':
            case '.jpeg':
                $fun = 'imagejpeg';
                break;
            case '.png':
                $fun = 'imagepng';
                break;
            case '.gif':
                $fun = 'imagegif';
                break;
            default:
                return false;
        }

        @mkdir(dirname($file), 0777, true);
        @chmod(dirname($file), 0777);

        //保存到文件
        return $fun($img, $file);
    }

    /**
     * 根据文件名，返回画板资源
     *
     * @param $filename
     * @return bool|resource
     */
    public static function createImage($filename)
    {
        $arr = @getimagesize($filename);

        if ($arr === false) {
            return false;
        }

        //1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
        switch ($arr[2]) {
            case 1:
                return imagecreatefromgif($filename);
            case 2:
                return imagecreatefromjpeg($filename);
            case 3:
                return imagecreatefrompng($filename);
            default:
                return false;
        }
    }
}
