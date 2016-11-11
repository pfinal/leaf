<?php

namespace Leaf;

/**
 * 分页类
 *
 * @property string limit 返回可用于MySQL LIMIT SQL语句
 * @property int offset
 * @property int itemCount 总记录数
 * @property int pageCount 总页数
 * @property int currentPage 当前页码
 * @property int pageSize 每页显示记录数
 * @author  Zou Yiliang
 */
class Pagination implements \JsonSerializable
{
    protected $itemCount;         //总记录数
    protected $currentPage;       //当前页码
    protected $offset;            //数据库查询的偏移量(查询开始的记录)
    protected $pageSize = 20;          //每页显示记录数
    protected $pageCount;         //总页数
    protected $prevPage;          //当前的上一页码
    protected $nextPage;          //当前的下一页码

    protected $url;               //访问当前action不带分页的url

    protected $pageVar = 'page';                   //分页get变量
    protected $className = 'pagination';           //样式名
    protected $maxButtonCount = 4;                 //当前页码两边最多显示多少个
    protected $prevPageLabel = '上一页';            //上一页按扭显示文本
    protected $nextPageLabel = '下一页';            //下一页按扭显示文本
    protected $selectedPageCssClass = 'active';
    protected $prevPageCssClass = 'prev-page';
    protected $nextPageCssClass = 'next-page';
    protected $disabledCssClass = 'disabled';
    protected $disabledTag = 'span';

    /**
     * 生成页码超链接
     *
     * $page->createLinks('article/index')
     *      article/index
     *      article/index         ?page=    2     &username=jack&status=10
     *
     * $page->createLinks('article/index', '/', '.html')
     *      article/index                        .html     ?username=jack&status=10
     *      article/index         /         2    .html     ?username=jack&status=10
     *
     * @param string $baseUrl
     * @param null $prefix 页码数字与$baseUrl之间的内容 默认为`?page=`
     * @param null $suffix 页码数字之后的内容，默认为空 示例: `.html`
     * @return string
     *      首页url   $baseUrl + $suffix + query_string($_SERVER['QUERY_STRING'])
     *      其它页面   $baseUrl + $prefix + $suffix + query_string
     */
    public function createLinks($baseUrl = '', $prefix = null, $suffix = null)
    {
        $this->url = $baseUrl;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        return $this->createPageLinks();
    }

    /**
     * 初始化
     */
    protected function init()
    {
        //计算总页数
        $this->pageCount = ceil($this->itemCount / $this->pageSize);

        //当前页码
        if (empty($this->currentPage) && isset($_REQUEST[$this->pageVar])) {
            $this->currentPage = intval($_REQUEST[$this->pageVar]);
        }
        $this->currentPage = intval($this->currentPage);

        //最小页码判断
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }

        //偏移量 (当前页-1)*每页条数
        $this->offset = ($this->currentPage - 1) * $this->pageSize;

        if ($this->currentPage > $this->pageCount) {
            $this->currentPage = $this->pageCount;
        }

        //上一页
        $this->prevPage = ($this->currentPage <= 1) ? 1 : $this->currentPage - 1;

        //下一页
        $this->nextPage = ($this->currentPage == $this->pageCount) ? $this->pageCount : $this->currentPage + 1;
    }

    /**
     * 生成页码超链接
     * @return string
     */
    protected function createPageLinks()
    {
        $this->init();

        if ($this->pageCount <= 1) {
            return '';
        }

        //当前页码的位置
        $cur = $this->maxButtonCount + 1;

        $buttonCount = $this->maxButtonCount * 2 + 1;

        //开始数字
        if ($this->currentPage <= $cur || $this->pageCount <= $buttonCount) {
            $ctrl_begin = 1;
        } else if ($this->currentPage > $this->pageCount - $buttonCount) {
            $ctrl_begin = $this->pageCount - $buttonCount + 1;
        } else {
            $ctrl_begin = $this->currentPage - $cur;
        }

        //结束数字
        $ctrl_end = $ctrl_begin + $buttonCount - 1;

        //不能大于总页数
        if ($ctrl_end > $this->pageCount) {
            $ctrl_end = $this->pageCount;
        }

        $ctrl_num_html = "";
        for ($i = $ctrl_begin; $i <= $ctrl_end; $i++) {
            if ($i == $this->currentPage) {
                //当前页，不加超链接
                $ctrl_num_html .= "<{$this->disabledTag} class='{$this->selectedPageCssClass}' >{$i}</{$this->disabledTag}>";
            } else {
                $url = $this->createPageLink($i);
                $ctrl_num_html .= "<a href='{$url}'>{$i}</a>";
            }
        }

        //判断是否需要加上省略号
        if ($ctrl_begin != 1) {
            $url = $this->createPageLink(1);
            $ctrl_num_html = "<a href='{$url}'>1</a><{$this->disabledTag}>...</{$this->disabledTag}>" . $ctrl_num_html;
        }
        if ($ctrl_end != $this->pageCount) {
            $url = $this->createPageLink($this->pageCount);
            $ctrl_num_html .= "<{$this->disabledTag}>...</{$this->disabledTag}><a href='{$url}'>{$this->pageCount}</a>";
        }

        //上一页
        if ($this->currentPage == 1) {
            $prev = "<{$this->disabledTag} class='{$this->prevPageCssClass} {$this->disabledCssClass}'>{$this->prevPageLabel}</{$this->disabledTag}>";
        } else {
            $url = $this->createPageLink($this->prevPage);
            $prev = "<a class='{$this->prevPageCssClass}' href='{$url}'>{$this->prevPageLabel}</a>";
        }

        //下一页
        if ($this->currentPage == $this->pageCount) {
            $next = "<{$this->disabledTag} class='{$this->nextPageCssClass} {$this->disabledCssClass}'>{$this->nextPageLabel}</{$this->disabledTag}>";
        } else {
            $url = $this->createPageLink($this->nextPage);
            $next = "<a class='{$this->nextPageCssClass}' href='{$url}'>{$this->nextPageLabel}</a>";
        }

        //控制翻页链接
        $html = "<span class=\"{$this->className}\">";
        $html .= $prev . ' ';
        $html .= $ctrl_num_html;
        $html .= ' ' . $next;
        $html .= "</span>";
        return $html;
    }

    protected function createPageLink($num)
    {
        if ($num == 1) {
            return $this->appendSuffix($this->url);
        }

        $prefix = $this->prefix;
        if ($prefix === null) {
            $s = strpos($this->url, '?') === false ? '?' : '&';
            $prefix = $s . urlencode($this->pageVar) . '=';
        }

        return $this->appendSuffix($this->url . $prefix . $num);
    }

    /**
     * @return string
     */
    protected function appendSuffix($url)
    {
        $url .= $this->suffix;

        //拼接query参数
        $get = isset($_GET) ? $_GET : array();
        if ($this->prefix === null) {
            unset($get[$this->pageVar]);//去除page参数
        }
        if (count($get) > 0) {
            $s = strpos($url, '?') === false ? '?' : '&';
            $url .= $s . http_build_query($get);
        }
        return $url;
    }

    /**
     * 实现JsonSerializable接口，方便转为json时自定义数据。
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'itemCount' => $this->itemCount,//总记录数
            'currentPage' => $this->currentPage,//当前页码
            'offset' => $this->offset, //数据库查询的偏移量(查询开始的记录)
            'pageSize' => $this->pageSize,//每页显示记录数
            'pageCount' => $this->pageCount,//总页数
            'prevPage' => $this->prevPage,//当前的上一页码
            'nextPage' => $this->nextPage,//当前的下一页码
        );
    }

    public function __get($name)
    {
        $this->init();
        switch ($name) {
            case 'limit':
                return $this->offset . ', ' . $this->pageSize;
            default:
                return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    public function config($config)
    {
        if (is_array($config)) {
            foreach ($config as $k => $v) {
                $this->$k = $v;
            }
        }
    }
}

/*
.pagination {
	padding:3px; margin:3px; text-align:center;
}
.pagination a,.pagination span {
	border:#dddddd 1px solid;
	text-decoration:none;
	color:#666666; padding: 5px 10px; margin-right:4px;
}
.pagination a:hover {
	border: #a0a0a0 1px solid;
}
.pagination .active {
	font-weight:bold; background-color:#f0f0f0;
}
.pagination .disabled {
	border:#f3f3f3 1px solid;
	color:#aaaaaa;
}
*/