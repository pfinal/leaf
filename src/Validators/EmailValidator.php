<?php

namespace Leaf\Validators;

/**
 * 邮箱验证器
 */
class EmailValidator extends Validator
{
    /**
     * @var string the regular expression used to validate the attribute value.
     * @see http://www.regular-expressions.info/email.html
     */
    public $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
    /**
     * @var string the regular expression used to validate email addresses with the name part.
     * This property is used only when [[allowName]] is true.
     * @see allowName
     */
    public $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
    /**
     * @var boolean whether to allow name in the email address (e.g. "John Smith <john.smith@example.com>"). Defaults to false.
     * 检查是否允许带名称的电子邮件地址 (e.g. 张三 <John.san@example.com>)。 默认为 false。
     * @see fullPattern
     */
    public $allowName = false;
    /**
     * @var boolean whether to check whether the email's domain exists and has either an A or MX record.
     * Be aware that this check can fail due to temporary DNS problems even if the email address is
     * valid and an email would be deliverable. Defaults to false.
     * 检查邮箱域名是否存在，且有没有对应的 A 或 MX 记录。不过要知道，有的时候该项检查可能会因为临时性 DNS 故障而失败，哪怕它其实是有效的。默认为 false。
     *
     */
    public $checkDNS = false;
    /**
     * @var boolean whether validation process should take into account IDN (internationalized domain
     * names). Defaults to false meaning that validation of emails containing IDN will always fail.
     * Note that in order to use IDN validation you have to install and enable `intl` PHP extension,
     * otherwise an exception would be thrown.
     *
     * 验证过程是否应该考虑 IDN（internationalized domain names，国际化域名，也称多语种域名，比如中文域名）。默认为 false。要注意但是为使用 IDN 验证功能，请先确保安装并开启 intl PHP 扩展，不然会导致抛出异常。
     *
     */
    public $enableIDN = false;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->enableIDN && !function_exists('idn_to_ascii')) {
            throw new \Exception('In order to use IDN validation intl extension must be installed and enabled.');
        }
        if ($this->message === null) {
            //$this->message = '{attribute} is not a valid email address.';
            $this->message = '{attribute}不是一个有效的邮箱地址';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        // make sure string length is limited to avoid DOS attacks
        if (!is_string($value) || strlen($value) >= 320) {
            $valid = false;
        } elseif (!preg_match('/^(.*<?)(.*)@(.*)(>?)$/', $value, $matches)) {
            $valid = false;
        } else {
            $domain = $matches[3];
            if ($this->enableIDN) {
                $value = $matches[1] . idn_to_ascii($matches[2]) . '@' . idn_to_ascii($domain) . $matches[4];
            }
            $valid = preg_match($this->pattern, $value) || $this->allowName && preg_match($this->fullPattern, $value);
            if ($valid && $this->checkDNS) {
                $valid = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            }
        }

        return $valid ? null : [$this->message, []];
    }

}
