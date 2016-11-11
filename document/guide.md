# Leafphp 快速入门
===


## 编码规范

* PHP代码文件必须以 `<?php` 标签开始，不写PHP结束标记: `?>`
* PHP代码文件必须使用 不带BOM的 UTF-8 编码
* 命名空间与目录对应，文件名采用"类名.php"格式，目录大小写与命令空间相同，类名大小写与文件名相同(PSR-4规范)
* 类的命名必须遵循大写开头的驼峰命名规范(例如`UserController`)
* 方法名称如果是多个单词,必须遵循小写开头的驼峰命名规范(例如`createOrder`)
* 类中的常量所有字母都必须大写，多个单词间用下划线分隔
* 普通目录全小写格式，多个单词之间用中杠分隔(例如 `views/member-profile`)

## 基本概念约定

* 项目 project
* 应用 application
* 模块 bundle
* 控制器 controller
* 动用 action
* 路由 route


## 安装

    composer create-project pfinal/leafphp --prefer-dist

## 目录结构


* config 配置文件目录
    * app.php 应用配置文件
    * routes.php 路由
* runtime 运行时目录，存放缓存、日志、调式信息等，需要写权限
    * .gitignore 版本管理工具git忽略清单,请勿删除此文件
* views 视图文件目录
* document 文档
* src PHP源代码
* tests 测试
* vendor 第三方代码 composer管理,请勿手动修改该目录内容
* web 项目发布的根目录
    * static 第三方静态资源目录
    * themes 主题目录，项目的css、js、img等存放在此目录
    * index.php 前端控制器(MVC模式的入口文件)
    * assets 框架自动管理的资源文件,请勿手动修改该目录内容
    * temp 临时文件目录，需要写权限
    * uploads 文件上传目录，需要写权限
* .gitignore 版本管理工具git忽略清单
* console 命令行入口文件
* phpunit 单元测试入口文件

## 路由

下面以demo项目为例

在 `config/routes.php` 中定义路由
	
基本 GET 路由，浏览器访问 `http://localhost/demo/web`

```
use Leaf\Route;
Route::get('/', function(){
    return 'Hello Leafphp!';
});
```

只允许POST请求的路由，以POST方法访问 `http://localhost/demo/web/foo`

```
Route::post('foo', function(){
    return 'post only';
});
```

注册路由响应任意方式的HTTP请求，不对请求方法进行限制，post或get均可，浏览器访问 `http://localhost/demo/web/bar`

```
Route::any('bar', function(){
    return 'any';
});
```

基础路由参数 浏览器访问 `http://localhost/demo/web/user/1`

```
Route::get('user/:id', function($id){
    return 'user id: ' . $id;
});
```

方法注入，框架将自动注入`Leaf\Request`实例，浏览器访问 `http://localhost/demo/web/foo?name=leaf`


```
use Leaf\Request;
Route::any('foo', function(Request $request){
    $name = $request->get('name');
    return $name; // leaf
});
```

支持注入的对象

    Leaf\Request
    Leaf\Application

生成URL

```
use Leaf\Url;
$url = Url::to('foo');                 /demo/web/foo
$url = Url::to('foo', ['id' => 1]);    /demo/web/foo?id=1
$url = Url::to('foo', [], true);       http://localhost/demo/web/foo
```
如果访问时url中没有隐藏入口文件`index.php`,则生成的url也会自动包含`index.php`

## 请求与响应

### Request

```
use Leaf\Request;
Route::any('test', function (Request $request) {

    //GET POST PUT DELETE HEAD ...
    $method =  $request->getMethod();
    
	$bool = $request->isMethod('post')    // post request
	$bool = $request->isXmlHttpRequest()  // ajax request

	$id = $request->get('id');   // $_POST['id'] or  $_GET['id']
	$arr = $request->all();      // $_POST + $_GET

});
```

### Response

```
use Leaf\View;
use Leaf\Response;
use Leaf\Json;
Route::any('/', function(){

    //string
    return 'Hello Leaf!';
    return new Response('Hello Leaf!');
    
    //view
    return View::render('home.twig'); // 输出views/home.twig模板中内容
    
    //json
    return Json::render(['data' => 'SUCCESS']);
    return Json::renderWithTrue('SUCCESS');
    return Json::renderWithTrue('ERROR');
});
```

重定向

```
use Leaf\RedirectResponse;
$url = Url::to('foo');
return new RedirectResponse($url);

return new RedirectResponse('http://www.leafphp.com');
```

## 中间件

编写中间件类 `src/Middleware/TestMiddleware.php`

```
<?php
namespace Middleware;
use Leaf\Request;
class TestMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $id = $request->get('age');
        if ($id < 18) {
            return 'age error';
        }
        return $next($request);
    }
}
```

注册一个名为`test`的中间件

```
$app['test'] = 'Middleware\TestMiddleware';
```
为路由绑定这个`test`中间件

```
use Leaf\Request;
Route::any('info', function (Request $request) {
    return $request->get('age');
}, 'test');
```

浏览器访问 

    `http://localhost/demo/web/info?age=17`   被中间件拦截
    `http://localhost/demo/web/info?age=18`   请求将被通过

为一组路由绑定中间件 (绑定多个中间件，传入数组即可)

```
use Leaf\Request;
Route::group(['middleware' => 'test'], function () {

    Route::any('info', function (Request $request) {
        return $request->get('age');
    });

    //other route
});
```

如果希望中间件,在执行之后生效,可以使用下面的方式,
`$next()`返回值可能是`Symfony\Component\HttpFoundation\Response`对象或`string`

```
use Leaf\Request;
public function handle(Request $request, \Closure $next)
{
    $response = $next($request);
    // your code ...
    return $response;
}
```

## 控制器

控制器类通常以Controller作为后缀，例如 `src/Controller/SiteController.php`

任何PHP类均可作为控制器

```
<?php
namespace Controller;
class SiteController
{
    public function home()
    {
        return 'Hello Leafphp!';
    }
}
```

路由指向控制器中的方法

```
Route::get('home','Controller\SiteController@home');

```
浏览器访问  `http://localhost/demo/web/home`

如果需要包含多个字词，建议在 URI 中使用`中杠`来分隔,例如`user-profile`


使用注解语法注册路由,自动注册路由。class上的`Middleware`,将对整个控制器生效

```
<?php
// src/Controller/UserController.php
/**
 * @Middleware auth
 */
class UserController
{
    /**
     * @Route user/info
     * @Method get
     */
    public function info()
    {
        // your code ...
    }
}
```

添加注解路由 `Route::annotation('Controller\UserController');`

## 视图

支持twig和blade模板引擎,跟据后缀自动识别。

twig 
[http://twig.sensiolabs.org](http://twig.sensiolabs.org)

blade	[http://laravel.com/docs/5.1/blade](http://laravel.com/docs/5.1/blade)

```
use Leaf\View;

// views/index.twig
return View::render('home.twig', [
    'name' => 'Leaf',
]);

// views/index.blade.php
return View::render('home', [
    'name' => 'Leaf',
]);

```

将变量共享给所有模板

```
\Leaf\View::share('url', 'http://leafphp.com');
```

扩展twig模板 增加一个count函数,用于在模板中统计数组成员数量

```
$app->extend('twig', function ($twig, $app) {
    /** @var $twig \Twig_Environment */
    $twig->addFunction(new \Twig_SimpleFunction('count', function ($arr) use ($app) {
        return count($arr);
    }));
    return $twig;
});
```
twig中使用自定义的count函数

```
{{ count([1,3,5]) }} 或  {{ count({"id":1,"name":"Ethan"}) }}
```


## Bundle

Bundle可以更好的组织功能模块
 
目录结构

```
src/
    FooBundle/           Bundle总目录
        FooBundle.php    Bundle类文件
        Controller/      控制器
        resources/       资源目录
            routes.php   路由文件
            views/       视图目录
```
 
Bundle类,继承自`Leaf\Bundle`类即可,主要用于定位Bundle所在目录

```
// src/FooBundle/FooBundle.php
class FooBundle extends \Leaf\Bundle
{
}
```

注册Bundle

```
$app->registerBundle(new \FooBundle\FooBundle());
```

加载视图

Bundle的视图文件位于Bundle的resources/views目录中

```
return View::render('@FooBundle/home.twig');
```
顶级views目录中,与Bundle同名目录下的视图文件,将优先使用。
例如`/views/FooBundle/home.twig`,将替换`src/FooBundle/resources/views/home.twig`文件。

加载路由

Bundle注册后,Bundle的路由文件会自动加载,例如`FooBundle/resources/routes.php`。

自动生成Bundle

```
php console make:bundle
```

## 数据库

基本用法

    DB::getConnection()

    execute             执行SQL(INSERT、UPDATE、DELETE)
    query               执行SQL(SELECT)
    queryScalar         执行SQL,查询单一的值(SELECT COUNT)
    getLastInsertId     返回自增id
    beginTransaction    开启事务
    commit              提交事务
    rollBack            回滚事务
    getLastSql          最近执行的SQL

查询构造器

    DBQuery DB::table()

DBQuery返回数据的方法:

    findOne
    findByPk
    findOneBySql
    findAll
    findAllBySql
    count

DBQuery连惯操作方法，返回DBQuery对象:

    where
    whereIn
    limit
    offset
    orderBy
    asEntity
    lockForUpdate

分页

    $query = DB::select('user')->where($condition, $params);

    $page = new Pagination();
    //$page = Pagination::createFromCurrentNumber($p);
    $queryCount = clone $query;
    $page->itemCount = $queryCount->count();

    $query->limit($page->limit)->findAll();

## 系统服务

会话，调用Session相关方法时，会自动开启Session

```
use Leaf\Session;

//设置Session
Session::set('username', 'Jack');

//获取Session
$username = Session::get('username');

//删除Session
Session::remove('username');

//获取Session,如果不存在,使用`guest`作为默认值
$username = Session::get('username', 'guest');

//设置闪存数据
Session::setFlash('message', 'success');

//是否有闪存数据
$bool = Session::hasFlash('message');

//获取闪存数据,闪存数据获取后将自动删除
$message = Session::getFlash('message');
```
   
表单验证

```
$data = [
    'username' => 'jack',
    'email' => 'jack@b.c',
    'age' => '18',
    'info' => 'abc',
];

$rules = [
    [['username', 'email'], 'required'],
    [['username'], 'match', 'pattern' => '/\w{3,15}/'],
    [['info'], 'string', 'length' => [3, 10]],
    [['email'], 'email'],
    [['age'], 'compare', 'type' => 'number', 'operator' => '>=', 'compareValue' => 0],
    [['age'], 'compare', 'type' => 'number', 'operator' => '<=', 'compareValue' => 150],
];

$labels = [
    'username' => '用户名',
];

if (!Validator::validate($data, $rules, $labels)) {
    var_dump(Validator::getFirstError());
    var_dump(Validator::getErrors());
}
```

错误
	
    use Leaf\Exception\HttpException;

    throw new HttpException(400, '您访问的页面不存在');
    throw new HttpException(500, '服务器内部错误');

日志

    use Leaf\Log;
	
    Log::debug("日志内容");
    Log::info("日志内容");
    Log::warning("日志内容");
    Log::error("日志内容");


验证码

文件上传

