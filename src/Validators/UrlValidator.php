<?php
namespace Leaf\Validators;


/**
 * UrlValidator validates that the attribute value is a valid http or https URL.
 *
 * Note that this validator only checks if the URL scheme and host part are correct.
 * It does not check the rest part of a URL.
 */
class UrlValidator extends Validator
{
    /**
     * @var string the regular expression used to validate the attribute value.
     * The pattern may contain a `{schemes}` token that will be replaced
     * by a regular expression which represents the [[validSchemes]].
     */
    public $pattern = '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';
    /**
     * @var array list of URI schemes which should be considered valid. By default, http and https
     * are considered to be valid schemes.
     * 用于指定那些 URI 方案会被视为有效的数组。默认为 ['http', 'https']，代表 http 和 https URLs 会被认为有效。
     */
    public $validSchemes = ['http', 'https'];
    /**
     * @var string the default URI scheme. If the input doesn't contain the scheme part, the default
     * scheme will be prepended to it (thus changing the input). Defaults to null, meaning a URL must
     * contain the scheme part.
     * 若输入值没有对应的方案前缀，会使用的默认 URI 方案前缀。默认为 null，代表不修改输入值本身。
     */
    public $defaultScheme;
    /**
     * @var boolean whether validation process should take into account IDN (internationalized
     * domain names). Defaults to false meaning that validation of URLs containing IDN will always
     * fail. Note that in order to use IDN validation you have to install and enable `intl` PHP
     * extension, otherwise an exception would be thrown.
     * 验证过程是否应该考虑 IDN（internationalized domain names，国际化域名，也称多语种域名，比如中文域名）。默认为 false。要注意但是为使用 IDN 验证功能，请先确保安装并开启 intl PHP 扩展，不然会导致抛出异常。
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
            //$this->message = '{attribute} is not a valid URL.';
            $this->message = '{attribute}不是一个有效的URL';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        // make sure the length is limited to avoid DOS attacks
        if (is_string($value) && strlen($value) < 2000) {
            if ($this->defaultScheme !== null && strpos($value, '://') === false) {
                $value = $this->defaultScheme . '://' . $value;
            }

            if (strpos($this->pattern, '{schemes}') !== false) {
                $pattern = str_replace('{schemes}', '(' . implode('|', $this->validSchemes) . ')', $this->pattern);
            } else {
                $pattern = $this->pattern;
            }

            if ($this->enableIDN) {
                $value = preg_replace_callback('/:\/\/([^\/]+)/', function ($matches) {
                    return '://' . idn_to_ascii($matches[1]);
                }, $value);
            }

            if (preg_match($pattern, $value)) {
                return null;
            }
        }

        return [$this->message, []];
    }


}
