<?php

namespace Leaf\Validators;

use Leaf\Application;
use DateTime;

/**
 * 验证是否以指定格式表示的日期或时间
 */
class DateValidator extends BaseValidator
{
    /**
     * Here are some example values:
     *
     * ```php
     * 'm/d/Y' // the same date in PHP format
     * ```
     */
    public $format;
    /**
     * @var string the locale ID that is used to localize the date parsing.
     * This is only effective when the [PHP intl extension](http://php.net/manual/en/book.intl.php) is installed.
     */
    public $locale;
    /**
     * @var string the timezone to use for parsing date and time values.
     * This can be any value that may be passed to [date_default_timezone_set()](http://www.php.net/manual/en/function.date-default-timezone-set.php)
     * e.g. `UTC`, `Europe/Berlin` or `America/Chicago`.
     * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
     */
    public $timeZone;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            //$this->message = 'The format of {attribute} is invalid.';
            $this->message = '{attribute}格式无效';
        }
        if ($this->format === null) {
            $this->format = 'Y-m-d';
        }
        if ($this->locale === null) {
            //Application::$app['language'];
            $this->locale = 'zh-CN';
        }
        if ($this->timeZone === null) {
            $this->timeZone = Application::$app['timezone'];
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        return $this->parseDateValue($value) === false ? [$this->message, []] : null;
    }

    /**
     * Parses date string into UNIX timestamp
     *
     * @param string $value string representing date
     * @return boolean|integer UNIX timestamp or false on failure
     */
    protected function parseDateValue($value)
    {
        if (is_array($value)) {
            return false;
        }
        $format = $this->format;
        $date = DateTime::createFromFormat($format, $value, new \DateTimeZone($this->timeZone));
        $errors = DateTime::getLastErrors();
        if ($date === false || $errors['error_count'] || $errors['warning_count']) {
            return false;
        } else {
            // if no time was provided in the format string set time to 0 to get a simple date timestamp
            if (strpbrk($format, 'HhGgis') === false) {
                $date->setTime(0, 0, 0);
            }
            return $date->getTimestamp();
        }
    }
}
