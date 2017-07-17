<?php

namespace Leaf\Validators;

/**
 * 图片验证器
 */
class ImageValidator extends BaseValidator
{
    /**
     * @var string 图片所在目录
     */
    public $directory;

    /**
     * @var integer 最少宽度px。默认为null不做限制
     * @see underWidth
     */
    public $minWidth;

    /**
     * @var integer 最大宽度px。默认为null不做限制
     * @see overWidth
     */
    public $maxWidth;

    /**
     * @var integer the minimum height in pixels.
     * Defaults to null, meaning no limit.
     * @see underHeight
     */
    public $minHeight;

    /**
     * @var integer the maximum width in pixels.
     * Defaults to null, meaning no limit.
     * @see overWidth
     */
    public $maxHeight;

    /**
     * @var string the error message used when the uploaded file is not an image.
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     */
    public $notImage;

    /**
     * @var string the error message used when the image is under [[minWidth]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {limit}: the value of [[minWidth]]
     */
    public $underWidth;

    /**
     * @var string the error message used when the image is over [[maxWidth]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {limit}: the value of [[maxWidth]]
     */
    public $overWidth;

    /**
     * @var string the error message used when the image is under [[minHeight]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {limit}: the value of [[minHeight]]
     */
    public $underHeight;

    /**
     * @var string the error message used when the image is over [[maxHeight]].
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {limit}: the value of [[maxHeight]]
     */
    public $overHeight;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->notImage === null) {
            $this->notImage = '{attribute}不是图片';
        }
        if ($this->underWidth === null) {
            $this->underWidth = '{attribute}宽度不够,至少需要{limit}px';
        }
        if ($this->underHeight === null) {
            $this->underHeight = '{attribute}高度不够,至少需要{limit}px';
        }
        if ($this->overWidth === null) {
            $this->overWidth = '{attribute}太宽,请勿超过{limit}px';
        }
        if ($this->overHeight === null) {
            $this->overHeight = '{attribute}太高,请勿超过{limit}px';
        }
    }

    /**
     * Validates an image file.
     */
    protected function validateValue(&$value)
    {
        if (!is_null($this->directory)) {
            $imageFile = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $value;
        } else {
            $imageFile = $value;
        }

        if (!file_exists($imageFile)) {
            return [$this->notImage, []];
        }

        if (false === ($imageInfo = getimagesize($imageFile))) {
            return [$this->notImage, []];
        }

        list($width, $height) = $imageInfo;

        if ($width == 0 || $height == 0) {
            return [$this->notImage, []];
        }

        if ($this->minWidth !== null && $width < $this->minWidth) {
            return [$this->underWidth, ['limit' => $this->minWidth]];
        }

        if ($this->minHeight !== null && $height < $this->minHeight) {
            return [$this->underHeight, ['limit' => $this->minHeight]];
        }

        if ($this->maxWidth !== null && $width > $this->maxWidth) {
            return [$this->overWidth, ['limit' => $this->maxWidth]];
        }

        if ($this->maxHeight !== null && $height > $this->maxHeight) {
            return [$this->overHeight, ['limit' => $this->maxHeight]];
        }

        return null;
    }
}
