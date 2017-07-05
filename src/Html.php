<?php

namespace Leaf;

/**
 * 提供用于创建HTML视图的辅助方法
 * @author  Zou Yiliang
 * @since   1.0
 */
class Html
{
    public static $renderSpecialAttributesValue = true;
    public static $beforeRequiredLabel = '';
    public static $afterRequiredLabel = ' <span class="required">*</span>';

    /**
     * 把特殊的字符编码为HTML实体
     * @param $text string 要被编码的数据
     * @return string
     */
    public static function encode($text, $charset = 'UTF-8')
    {
        return htmlspecialchars($text, ENT_QUOTES, $charset);
    }

    /**
     * 生成的下拉列表
     * @param string $name 下拉列表的名称
     * @param string $select 被选中的值
     * @param array $list 用来生成列表选项的数据(值=>显示)，也可以是二维组。如果是二维，则需要指定$options参数中的value和display
     * @param $options array('value'=>'用哪项作为值','display'=>'用哪一项作为显示内容'   class=>'类名','prompt'=>array(0=>'请选择'))  prompt也可以是字符串，则option 的value为空
     * @return string
     */
    public static function dropDownList($name, $select, $list = array(), $options = array())
    {
        $options['name'] = $name;

        if (!isset($options['id'])) {
            $options['id'] = self::getIdByName($name);
        }

        $temp = $options;
        if (array_key_exists('prompt', $temp)) {
            unset($temp['prompt']);
        }
        if (array_key_exists('value', $temp)) {
            unset($temp['value']);
        }
        if (array_key_exists('display', $temp)) {
            unset($temp['display']);
        }

        $html = "<select " . self::renderAttributes($temp) . ">";

        if (isset($options['prompt'])) {

            if (is_array($options['prompt'])) {
                $option_value = key($options['prompt']);
                $option_display = current($options['prompt']);
            } else {
                $option_value = '';
                $option_display = (string)$options['prompt'];
            }

            $html .= "<option value='" . $option_value . "'>" . $option_display . "</option>";

            unset($options['prompt']);
        }

        foreach ($list as $k => $v) {

            if (is_array($v) || is_object($v)) {
                $val = $v[$options['value']];
                $dis = $v[$options['display']];
            } else {
                $val = $k;
                $dis = $v;
            }

            if (!strcmp($select, $val)) {
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }

            $html .= "<option value='{$val}' {$selected}>{$dis}</option>";
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * 生成的单选按钮列表
     * @param string $name string单选按钮列表的名称
     * @param string $select 被选中的单选按钮
     * @param  array $data 用于生成单选按钮列表的值-标签对。值将自动被HTML编码，然而标签不会。
     * @param array $htmlOptions 附加的HTML选项。选项将会应用于每个单选按钮上，下面这些特定的选项是被认可的：
     *            template: 字符串，指定如何渲染每个复选框。 其中"{input}"将被生成的单选按钮标签取代，而"{label}"会替换为相应的复选框标签的label。
     *                      默认为{inputInLabel} ，表示input被包含在label中
     *            separator: 字符串，分隔生成的单选按钮的字符串。默认为空白
     *            labelOptions: 数组，指定为列表中的每个标签渲染的附加的HTML属性。
     * @return string
     */
    public static function radioButtonList($name, $select, $data, $htmlOptions = array())
    {
        $template = isset($htmlOptions['template']) ? $htmlOptions['template'] : '{inputInLabel}';//{input} {label}
        $separator = isset($htmlOptions['separator']) ? $htmlOptions['separator'] : "";
        $container = isset($htmlOptions['container']) ? $htmlOptions['container'] : 'span';
        unset($htmlOptions['template'], $htmlOptions['separator'], $htmlOptions['container']);

        $labelOptions = isset($htmlOptions['labelOptions']) ? $htmlOptions['labelOptions'] : array();
        unset($htmlOptions['labelOptions']);

        $items = array();
        $baseID = isset($htmlOptions['baseID']) ? $htmlOptions['baseID'] : self::getIdByName($name);
        unset($htmlOptions['baseID']);
        $id = 0;

        foreach ($data as $value => $label) {
            $checked = !strcmp($value, $select);
            $htmlOptions['value'] = $value;
            $htmlOptions['id'] = $baseID . '_' . $id++;
            $option = self::radioButton($name, $checked, $htmlOptions);
            $inputInLabel = self::label($option . $label, $htmlOptions['id'], $labelOptions);
            $label = self::label($label, $htmlOptions['id'], $labelOptions);
            $items[] = strtr($template, array('{input}' => $option, '{label}' => $label, '{inputInLabel}' => $inputInLabel));
        }

        if (empty($container)) {
            return implode($separator, $items);
        } else {
            return self::tag($container, array('id' => $baseID), implode($separator, $items));
        }
    }


    /**
     * 生成复选框列表
     * @param $name string 复选框列表的名称
     * @param string|array $select 复选框列表的选中项，可以是一个单独的被选中项的字符串，也可以是多个选中项的数组
     * @param array $data 用于生成复选框列表的值-标签对。值将自动被HTML编码，然而标签不会。
     * @param array $htmlOptions 附加的HTML选项 。选项将会应用于每个复选框上，下面这些特定的选项是被认可的：
     *            template: 字符串，指定如何渲染每个复选框。默认为"{input} {label}"， 其中"{input}"将被生成的复选框标签取代，而"{label}"会替换为相应的复选框标签的label。
     *            separator: 字符串，分隔生成的复选框的字符串。
     *            labelOptions: 数组，指定为列表中的每个标签渲染的附加的HTML属性。
     * @return string
     */
    public static function checkBoxList($name, $select, $data, $htmlOptions = array())
    {
        $template = isset($htmlOptions['template']) ? $htmlOptions['template'] : '{inputInLabel}'; //{input} {label}
        $separator = isset($htmlOptions['separator']) ? $htmlOptions['separator'] : "";

        unset($htmlOptions['template'], $htmlOptions['separator']);

        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }

        unset($htmlOptions['checkAll'], $htmlOptions['checkAllLast']);

        $labelOptions = isset($htmlOptions['labelOptions']) ? $htmlOptions['labelOptions'] : array();
        unset($htmlOptions['labelOptions']);

        $items = array();
        $baseID = self::getIdByName($name);
        $id = 0;
        $checkAll = true;

        foreach ($data as $value => $label) {
            $checked = !is_array($select) && !strcmp($value, $select) || is_array($select) && in_array($value, $select);
            $checkAll = $checkAll && $checked;
            $htmlOptions['value'] = $value;
            $htmlOptions['id'] = $baseID . '_' . $id++;
            $option = self::checkBox($name, $checked, $htmlOptions);
            $inputInLabel = self::label($option . $label, $htmlOptions['id'], $labelOptions);
            $label = self::label($label, $htmlOptions['id'], $labelOptions);
            $items[] = strtr($template, array('{input}' => $option, '{label}' => $label, '{inputInLabel}' => $inputInLabel));
        }

        return self::tag('span', array('id' => $baseID), implode($separator, $items));
    }

    /**
     * 生成单选按钮
     * @param  string $name 按钮的名称
     * @param $checked boolean 单选按钮是否被选中状态
     * @param $htmlOptions array 附加的HTML属性
     * @return string
     */
    public static function radioButton($name, $checked = false, $htmlOptions = array())
    {
        if ($checked) {
            $htmlOptions['checked'] = 'checked';
        } else {
            unset($htmlOptions['checked']);
        }

        $value = isset($htmlOptions['value']) ? $htmlOptions['value'] : 1;

        if (array_key_exists('uncheckValue', $htmlOptions)) {
            $uncheck = $htmlOptions['uncheckValue'];
            unset($htmlOptions['uncheckValue']);
        } else {
            $uncheck = null;
        }

        if ($uncheck !== null) {
            if (isset($htmlOptions['id']) && $htmlOptions['id'] !== false) {
                $uncheckOptions = array('id' => $htmlOptions['id']);
            } else {
                $uncheckOptions = array('id' => false);
            }
            $hidden = self::hiddenField($name, $uncheck, $uncheckOptions);
        } else {
            $hidden = '';
        }

        return $hidden . self::inputField('radio', $name, $value, $htmlOptions);
    }

    /**
     * 生成一个复选框
     * @param string $name 复选框的名称
     * @param boolean $checked 复选框是否为被选中状态
     * @param array $htmlOptions 附加的HTML属性
     * @return string
     */
    public static function checkBox($name, $checked = false, $htmlOptions = array())
    {
        if ($checked) {
            $htmlOptions['checked'] = 'checked';
        } else {
            unset($htmlOptions['checked']);
        }

        $value = isset($htmlOptions['value']) ? $htmlOptions['value'] : 1;

        if (array_key_exists('uncheckValue', $htmlOptions)) {
            $uncheck = $htmlOptions['uncheckValue'];
            unset($htmlOptions['uncheckValue']);
        } else {
            $uncheck = null;
        }

        if ($uncheck !== null) {
            if (isset($htmlOptions['id']) && $htmlOptions['id'] !== false) {
                $uncheckOptions = array($htmlOptions['id']);
            } else {
                $uncheckOptions = array('id' => false);
            }
            $hidden = self::hiddenField($name, $uncheck, $uncheckOptions);
        } else {
            $hidden = '';
        }

        return $hidden . self::inputField('checkbox', $name, $value, $htmlOptions);
    }

    public static function label($label, $for, $htmlOptions = array())
    {
        if ($for === false) {
            unset($htmlOptions['for']);
        } else {
            $htmlOptions['for'] = $for;
        }

        if (isset($htmlOptions['required'])) {
            if ($htmlOptions['required']) {
                if (isset($htmlOptions['class'])) {
                    $htmlOptions['class'] .= ' required';
                } else {
                    $htmlOptions['class'] = 'required';
                }
                $label = self::$beforeRequiredLabel . $label . self::$afterRequiredLabel;
            }
            unset($htmlOptions['required']);
        }
        return self::tag('label', $htmlOptions, $label);
    }

    public static $closeSingleTags = true;

    public static function tag($tag, $htmlOptions = array(), $content = false, $closeTag = true)
    {
        $html = '<' . $tag . self::renderAttributes($htmlOptions);
        if ($content === false) {
            return $closeTag && self::$closeSingleTags ? $html . ' />' : $html . '>';
        } else {
            return $closeTag ? $html . '>' . $content . '</' . $tag . '>' : $html . '>' . $content;
        }
    }

    public static function hiddenField($name, $value = '', $htmlOptions = array())
    {
        return self::inputField('hidden', $name, $value, $htmlOptions);
    }

    /**
     * 生成的input标签
     * @param $type string input标签类型(例如 'text', 'radio')
     * @param $name string input标签名称
     * @param $value string input标签值
     * @param $htmlOptions array 额外的HTML标签的属性
     * @return string
     */
    protected static function inputField($type, $name, $value, $htmlOptions)
    {
        $htmlOptions['type'] = $type;
        $htmlOptions['value'] = $value;
        $htmlOptions['name'] = $name;

        if (!isset($htmlOptions['id'])) {
            $htmlOptions['id'] = self::getIdByName($name);
        } elseif ($htmlOptions['id'] === false) {
            unset($htmlOptions['id']);
        }
        return self::tag('input', $htmlOptions);
    }


    public static function getIdByName($name)
    {
        return str_replace(array('[]', '][', '[', ']', ' '), array('', '_', '_', '', '_'), $name);
    }

    public static function renderAttributes($htmlOptions)
    {
        static $specialAttributes = array(
            'async' => 1,
            'autofocus' => 1,
            'autoplay' => 1,
            'checked' => 1,
            'controls' => 1,
            'declare' => 1,
            'default' => 1,
            'defer' => 1,
            'disabled' => 1,
            'formnovalidate' => 1,
            'hidden' => 1,
            'ismap' => 1,
            'loop' => 1,
            'multiple' => 1,
            'muted' => 1,
            'nohref' => 1,
            'noresize' => 1,
            'novalidate' => 1,
            'open' => 1,
            'readonly' => 1,
            'required' => 1,
            'reversed' => 1,
            'scoped' => 1,
            'seamless' => 1,
            'selected' => 1,
            'typemustmatch' => 1,
        );

        if ($htmlOptions === array()) {
            return '';
        }

        $html = '';
        if (isset($htmlOptions['encode'])) {
            $raw = !$htmlOptions['encode'];
            unset($htmlOptions['encode']);
        } else {
            $raw = false;
        }

        foreach ($htmlOptions as $name => $value) {
            if (isset($specialAttributes[$name])) {
                if ($value) {
                    $html .= ' ' . $name;
                    if (self::$renderSpecialAttributesValue) {
                        $html .= '="' . $name . '"';
                    }
                }
            } elseif ($value !== null) {
                $html .= ' ' . $name . '="' . ($raw ? $value : self::encode($value)) . '"';
            }
        }

        return $html;
    }

    /**
     *
     * @param string $text 链接主体。它不会被编码。因此，如图像标签那样的HTML代码也会被通过
     * @param string $url
     * @param array $htmlOptions 附加的HTML属性
     * @return string
     */
    public static function link($text, $url = '#', $htmlOptions = array())
    {
        if ($url !== '') {
            $htmlOptions['href'] = $url;
        }
        return self::tag('a', $htmlOptions, $text);
    }

    public static function textField($name, $value = '', $htmlOptions = array())
    {
        return self::inputField('text', $name, $value, $htmlOptions);
    }

    /**
     * @param $name
     * @param string $value
     * @param array $htmlOptions
     * @return string
     */
    public static function passwordField($name, $value = '', $htmlOptions = array())
    {
        return self::inputField('password', $name, $value, $htmlOptions);
    }

    /**
     * 注意,需要设置form的enctype为'multipart/form-data',提交后,才能使用$_FILES[$name]获取上传文件
     * @param $name
     * @param string $value
     * @param array $htmlOptions
     * @return string
     */
    public static function fileField($name, $value = '', $htmlOptions = array())
    {
        return self::inputField('file', $name, $value, $htmlOptions);
    }

    public static function textArea($name, $value = '', $htmlOptions = array())
    {
        $htmlOptions['name'] = $name;

        if (!isset($htmlOptions['id'])) {
            $htmlOptions['id'] = self::getIdByName($name);
        } elseif ($htmlOptions['id'] === false) {
            unset($htmlOptions['id']);
        }

        return self::tag('textarea', $htmlOptions, isset($htmlOptions['encode']) && !$htmlOptions['encode'] ? $value : self::encode($value));
    }
}