## 表单验证

一般说来，永远不应该信任从最终用户直接接收到的数据(如:表单、url参数、cookie等数据)，使用它们之前应始终先验证其可靠性。

### 1.一个简单的验证示例

```php
<?php
    use Leaf\Validator;
    
    $data = ['name' => 'Jack', 'age' => '10'];

    $rule = [
        ['name', 'string', 'length' => [2, 10]],
        ['age', 'integer', 'min' => 18, 'max' => 30]
    ];

    if (Validator::validate($data, $rule)) {
        echo '验证通过';
    } else {
        $error = Validator::getFirstError();
        echo $error; // age不能小于18
    }
```
如果同一条规则适合多个字段验证，只需要把第一个参数改为数组，例如 `[['name', 'remark'], 'string', 'length' => [2, 10]] `
 
如果某个值，仅被应用这些验证器：`required、trim 、compare、default、filter`，将无法通过验证。

$data数组中的每个值，都必须使用以下一个或多个验证器进行验证:

    'string', 'email', 'match', 'date', 'url', 'number', 'integer', 'double', 'boolean','in','exist', 'unique','image','safe'
   
默认情况下，当值为空时，大多数验证器，都将跳过，不对数据做验证，所以，对于必填项请务必使用required验证器，例如

```php
$data = ['email' => ''];
$rule = [
    ['email', 'required'], //当没有required验证器时，email为空也能通过验证
    ['email', 'email'],
]
```

需要验证的字段，都必须提供，否则将无法通过验证

```php
$data = ['name' => 'Jack',];

$rule = [
    ['name', 'string', 'length' => [2, 10]],
    ['age', 'integer'],  // 将提示age不存在，无法通过验证
];

$rule = [
    ['name', 'string', 'length' => [2, 10]],
    ['age', 'default', 'value' => 0], 
    ['age', 'integer'],// $data中，没有age，由于default验证器生效，将能通过验证，此时age为0
];
    
```

很多时候，我们需要自定义错误提示，指定message为你想要的内容

```php
$data = ['age' => '5岁'];

$rule = [
    ['age', 'integer', 'message' => '年龄必须是整数'],  //错误消息为:年龄必须是整数
];
```
有一些验证器支持更丰富的自定义消息

    number、double、integer:
        tooBig     '不能大于{max}'
        tooSmall   '不能小于{min}'
        
    string:
        tooShort   '最少需要{min}个字符长度'
        tooLong    '最长不能超过{max}个字符长度'
        notEqual   '要求{length}个字符长度'
 
如果想使用系统默认提示，并显示中文的字段名，允许传入第三个参数

```php
$data = ['age' => '5岁'];
$labels = ['age' => '年龄'];
$rule = [
    ['age', 'integer'],
];
if (Validator::validate($data, $rule, $labels)) {
    echo '验证通过';
} else {
    $error = Validator::getFirstError();
    echo $error; // 'age要求为整数类型' ==> '年龄要求为整数类型'
}
```

   
### 2.完整的验证器列表

    required    必填
    compare     比较
    default     默认值
    filter      滤镜
    trim        去首尾空格
    
    string      字符串
    email       电子邮箱
    match       正则匹配
    date        日期
    datetime    日期时间
    time        时间
    url         链接
    
    number      数字
    double      数字
    integer     整数
    mobile      手机号码
    
    boolean     Boolean
    in          范围
    unique      唯一
    exist       存在
    image       图片
    safe        标记为安全(不对数据做验证)
    inline      行内验证(使用匿名函数)
    
####required

```php
[
    // 检查 "username" 与 "password" 是否为空
    [['username', 'password'], 'required'],
]
```
该验证器检查输入值是否为空，还是已经提供了。
* requiredValue：所期望的输入值。若没设置，意味着输入不能为空。
* strict：检查输入值时是否检查类型。默认为 false。

当没有设置 requiredValue 属性时，若该属性为 true，验证器会检查输入值是否严格为 null；<br>
若该属性设为 false，该验证器会用一个更加宽松的规则检验输入值是否为空。<br>
当设置了 requiredValue 属性时，若该属性为 true，输入值与 requiredValue 的比对会同时检查数据类型。<br>
默认情况下，当输入项为空字符串，空数组，或 null 时，会被视为“空值”。你也可以通过配置isEmpty为一个回调函数，自行判断

#### compare

```php
[
    // 检查 "password" 是否与 "password_repeat" 的相同
    ['password', 'compare', 'compareValue' => $password_repeat],

    // 检查年龄是否大于等于 30
    ['age', 'compare', 'compareValue' => 30, 'operator' => '>='],
]
```
该验证器比较两个特定输入值之间的关系是否与 operator 属性所指定的相同。
* compareValue：用于与输入值相比较的常量值。
* operator：比较操作符。默认为 ==，意味着检查输入值是否与 compareValue 的值相等。该属性支持如下操作符:

```
==：检查两值是否相等。比对为非严格模式。
===：检查两值是否全等。比对为严格模式。
!=：检查两值是否不等。比对为非严格模式。
!==：检查两值是否不全等。比对为严格模式。
>：检查待测目标值是否大于给定被测值。
>=：检查待测目标值是否大于等于给定被测值。
<：检查待测目标值是否小于给定被测值。
<=：检查待测目标值是否小于等于给定被测值。
```

#### default

```php
[
    // 若 "country" 为空，则将其设为 "PRC"
    ['country', 'default', 'value' => 'PRC'],

    // 若 "from"为空，则给分配自今天起，3 天后的日期。
    ['from', 'default', 'value' => function ($value) {
        return date('Y-m-d', strtotime('+3 days'));
    }],
]
```
该验证器并不进行数据验证。而是当为空时分配默认值。
当输入数据是通过 HTML 表单，你经常会需要给空的输入项赋默认值。你可以通过调整 default 验证器来实现这一点。
* value：默认值，或一个返回默认值的 PHP Callable 对象（即回调函数）。它们会分配给检测为空的待测特性。
默认情况下，当输入项为空字符串，空数组，或 null 时，会被视为“空值”。你也可以通过配置isEmpty为一个回调函数，自行判断

注意：对于绝大多数验证器而言，若其skipOnEmpty 属性为默认值 true，则它们不会对空值进行任何处理。也就是当他们为空值时，相关验证会被直接略过。在 核心验证器 之中，只有default（默认值），filter（滤镜），required（必填），以及 trim（去首尾空格），这几个验证器会处理空输入。

#### filter

```php
[
    // 转为大写
    ['name', 'filter', 'filter' => 'strtoupper'],

    // 首字母大写，其余小写
    ['username', 'filter', 'filter' => function ($value) {
        return ucfirst(strtolower($value));
    }],
]
```
滤镜来执行复杂的数据过滤，该验证器并不进行数据验证。而是，给输入值应用一个滤镜修改。
* filter：用于定义滤镜的 PHP 回调函数。可以为全局函数名，匿名函数，或其他。该函数的样式必须是 `function ($value) { return $newValue; }`。该属性不能省略，必须设置。
* skipOnArray：是否在输入值为数组时跳过滤镜。默认为 false。请注意如果滤镜不能处理数组输入，你就应该把该属性设为 true。否则可能会导致 PHP Error 的发生。
技巧：如果你只是想要用 trim 处理下输入值，你可以直接用 trim 验证器的。

#### trim

```php
[
    // trim 掉 "username" 和 "email" 两侧的空格
    [['username', 'email'], 'trim'],
]
```
    该验证器并不进行数据验证。而是，trim 掉输入值两侧的多余空格。注意若该输入值为数组，那它会忽略掉该验证器。

#### string

```php
[
    // 检查 "username" 是否为长度 4 到 24 之间的字符串
    ['username', 'string', 'length' => [4, 24]],
]
```
该验证器检查输入值是否为特定长度的字符串。并检查特性的值是否为某个特定长度。
* length：指定待测输入字符串的长度限制。该属性可以被指定为以下格式之一：
1. 单个值，代表具体长度
2. 单元素数组：代表输入字符串的最小长度 (e.g. `[8]`)。这会重写 min 属性。
3. 包含两个元素的数组：代表输入字符串的最小和最大长度(e.g. `[8, 128]`)。 这会同时重写 min 和 max 属性。
* min：输入字符串的最小长度。若不设置，则代表不设下限。
* max：输入字符串的最大长度。若不设置，则代表不设上限。
* encoding：待测字符串的编码方式。若不设置，则使用应用自身的 `Application::$app['charset']` 属性值，该值默认为 UTF-8。

#### email

```php
[
    // 检查 "email" 是否为有效的邮箱地址
    ['email', 'email'],
]
```
该验证器检查输入值是否为有效的邮箱地址。
* allowName：检查是否允许带名称的电子邮件地址 (e.g. `张三<John.san@example.com>`)。 默认为 false。
* checkDNS：检查邮箱域名是否存在，且有没有对应的 A 或 MX 记录。不过要知道，有的时候该项检查可能会因为临时性 DNS 故障而失败，哪怕它其实是有效的。默认为 false。
* enableIDN：验证过程是否应该考虑 IDN（internationalized domain names，国际化域名，也称多语种域名，比如中文域名）。默认为 false。要注意但是为使用 IDN 验证功能，请先确保安装并开启 intl PHP 扩展，不然会导致抛出异常。

#### match

```php
[
    // 检查 "username" 是否由字母开头，且只包含单词字符
    ['username', 'match', 'pattern' => '/^[a-z]\w*$/i']
]
```
该验证器检查输入值是否匹配指定正则表达式。
* pattern：用于检测输入值的正则表达式。该属性是必须的，若不设置则会抛出异常。
* not：是否对验证的结果取反。默认为 false，代表输入值匹配正则表达式时验证成功。如果设为 true，则输入值不匹配正则时返回匹配成功。

#### date

```php
[
    [['from', 'to'], 'date'],
]
```
该验证器检查输入值是否为适当格式的 date，time，或者 datetime
* format：待测的 日期/时间 格式。请参考 date_create_from_format() 相关的 PHP 手册了解设定格式字符串的更多细节。默认值为 'Y-m-d H:i:s'。

#### url

```php
[
    // 检查 "website" 是否为有效的 URL。若没有 URI 方案，则给 "website" 特性加 "http://" 前缀
    ['website', 'url', 'defaultScheme' => 'http'],
]
```
该验证器检查输入值是否为有效 URL。
* validSchemes：用于指定那些 URI 方案会被视为有效的数组。默认为 `['http', 'https']`，代表 http 和 https URLs 会被认为有效。
* defaultScheme：若输入值没有对应的方案前缀，会使用的默认 URI 方案前缀。默认为 null，代表不修改输入值本身。
* enableIDN：验证过程是否应该考虑 IDN（internationalized domain names，国际化域名，也称多语种域名，比如中文域名）。默认为 false。要注意但是为使用 IDN 验证功能，请先确保安装并开启 intl PHP 扩展，不然会导致抛出异常。

#### number

```php
[
    // 检查 "salary" 是否为浮点数
    ['salary', 'number'],
]
```
该验证器检查输入值是否为双精度浮点数。他等效于 double 验证器。
* max：上限值（含界点）。若不设置，则验证器不检查上限。
* min：下限值（含界点）。若不设置，则验证器不检查下限。

#### double

该验证器检查输入值是否为双精度浮点数。他等效于 number 验证器。
    
#### integer

```php
[
    // 检查 "age" 是否为整数
    ['age', 'integer'],
]
```
该验证器检查输入值是否为整形。
* max：上限值（含界点）。若不设置，则验证器不检查上限。
* min：下限值（含界点）。若不设置，则验证器不检查下限。

####boolean

```php
[
    // 检查 "selected" 是否为 0 或 1，无视数据类型
    ['selected', 'boolean'],

    // 检查 "deleted" 是否为布尔类型，即 true 或 false
    ['deleted', 'boolean', 'trueValue' => true, 'falseValue' => false, 'strict' => true],
]
```
该验证器检查输入值是否为一个布尔值。
* trueValue： 代表真的值。默认为 '1'。
* falseValue：代表假的值。默认为 '0'。
* strict：是否要求待测输入必须严格匹配 trueValue 或 falseValue。默认为 false。
注意：因为通过 HTML 表单传递的输入数据都是字符串类型，所以一般情况下你都需要保持 strict 属性为假。

#### in

```php
[
    // 检查 "level" 是否为 1、2 或 3 中的一个
    ['level', 'in', 'range' => [1, 2, 3]],
]
```
该验证器检查输入值是否存在于给定列表的范围之中。
* range：用于检查输入值的给定值列表。
* strict：输入值与给定值直接的比较是否为严格模式（也就是类型与值都要相同，即全等）。默认为 false。
* not：是否对验证的结果取反。默认为 false。当该属性被设置为 true，验证器检查输入值是否不在给定列表内。
* allowArray：是否接受输入值为数组。当该值为 true 且输入值为数组时，数组内的每一个元素都必须在给定列表内存在，否则返回验证失败。

#### unique

```php
[
     // 检查grade在config表中value字段唯一,附加过滤条件是config表的id字段不等于1
     ['grade', 'unique', 'table' => 'config', 'field' => 'value', 'filter' => ['id != ?', [1]] ],
]
```

该验证器检查输入值是否在某表字段中唯一。
* filter：用于检查输入值唯一性必然会进行数据库查询，而该属性为用于进一步筛选该查询的过滤条件。
译注：exist 和 unique 验证器的机理和参数都相似，有点像一体两面的阴和阳。

他们的区别是 exist 要求找得到；而 unique 正相反，要求键所代表的的属性不能在其值所代表字段中被找到。
从另一个角度来理解：他们都会在验证的过程中执行数据库查询，查询的条件即为where $v=$k 
(假设 一对键值对为 $k => $v)。unique 要求查询的结果数 $count==0，而 exist 则要求查询的结果数 $count>0
最后别忘了，unique 验证器不存在 allowArray 属性哦。

#### exist

```php
[
    //SELECT COUNT(*) FROM `pre_config` WHERE (`name`='grade') AND (`value`='A')
    ['grade', 'exist', 'table' => 'config', 'field' => 'value', 'filter' => ['name=?', ['grade']]],

]
```
该验证器检查输入值是否在某表字段中存在。
* filter：用于检查输入值存在性必然会进行数据库查询，而该属性为用于进一步筛选该查询的过滤条件。可以为代表额外查询条件的字符串或数组
* allowArray：是否允许输入值为数组。默认为 false。若该属性为 true 且输入值为数组，则数组的每个元素都必须在目标字段中存在。值得注意的是，若设为多元素数组来验证被测值在多字段中的存在性时，该属性不能设置为 true。
译注：exist 和 unique 验证器的机理和参数都相似，有点像一体两面的阴和阳。

他们的区别是 exist 要求找得到；而 unique 正相反，要求键所代表的的属性不能在其值所代表字段中被找到。
从另一个角度来理解：他们都会在验证的过程中执行数据库查询，查询的条件即为where $v=$k 
(假设 一对键值对为 $k => $v)。unique 要求查询的结果数 $count==0，而 exist 则要求查询的结果数 $count>0
最后别忘了，unique 验证器不存在 allowArray 属性哦。

#### image

```php
[
    // 检查 "photo" 是否为适当尺寸的有效图片(photo为位于directory中的文件名)
    ['photo', 'image', 'directory' => $uploads, 'minWidth' => 50, 'maxWidth' => 220]
]
```
该验证器检查输入值是否为代表有效的图片文件。它还支持以下为图片检验而设的额外属性：
* minWidth：图片的最小宽度。默认为 null，代表无下限。
* maxWidth：图片的最大宽度。默认为 null，代表无上限。
* minHeight：图片的最小高度。 默认为 null，代表无下限。
* maxHeight：图片的最大高度。默认为 null，代表无上限。

#### inline

```php
[
    // 验证身份证格式
    ['idcard', function($idcard){
         //composer require pfinal/identity-card
         if (!\PFinal\IdentityCard\IDCard::validate($idcard)) { 
             return '{attribute}验证未通过';
         }
    }],
]

```


#### safe

```php
[
    // 标记 "description" 为安全特性
    ['description', 'safe'],
]
```
该验证器并不进行数据验证。而是把一个特性标记为安全特性。标记为安全(不对数据做验证)

`2015-12-31 23:43`