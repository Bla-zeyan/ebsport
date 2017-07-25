<?php
namespace App\Tools;


/**
 * 自定义分页类,主要用于产生分页试图
 * Class Common
 * @package App\Library
 */
class CustomPage
{
    /**
     * @param $text
     * @return string
     */
    public function getActivePageWrapper($text)
    {
        return '<li><span>' . $text . '</span></li>';
    }


    /**
     * 获取当前页按钮的页面样式
     * @param $url
     * @param $page
     * @return string
     */
    public function getActivePageLinkWrapper($url, $page)
    {
        return '<li class="active"><a href="' . $url . '" style="background-color:#38f;border:1px solid #38f">' . $page . '</a></li>';
    }


    /**
     * 获取非当前页按钮的页面样式
     * @param $url
     * @param $page
     * @return string
     */
    public function getPageLinkWrapper($url, $page)
    {
        return '<li><a href="' . $url . '">' . $page . '</a></li>';
    }


    /**
     * 获取整个的分页样式
     * @param $nowPage 当前页
     * @param $totalPage 共多少页面
     * @param $baseUrl  当前url
     * @param $search   搜索
     * @param $count   一共多少条
     * @return string
     */
    public function getSelfPageView($nowPage, $totalPage, $baseUrl, $search = [], $count = null)
    {
        $pagePre = '<ul class="pagination">';

        if (!empty($count)) {
            $pagePre .= '<ol style="display: inline;margin-right:12px;margin-top:7px;float:left; clear: both"><span style="">共 ' . $count . ' 条</span></ol>';
        }

        $pageEnd = '</ul>';

        $pageLastStr = '';
        $pageNextStr = '';
        if ($nowPage <= 1) {
            $nowPage = 1;
            $pageLastStr = '<li class="disabled"><span>«</span></li>';
        }

        if ($nowPage > 1) {
            $lastSearchStr = $this->arrayToSearchStr(array_merge($search, ['nowPage' => 1, 'totalPage' => $totalPage]));
            $url = $baseUrl . '?' . $lastSearchStr;
            $pagePre .= '<li><span><a href=' . $url . '>首页</a></span></li>';
        }

        if ($nowPage >= $totalPage) {
            $nowPage = $totalPage;
            $pageNextStr = '<li class="disabled"><span>»</span></li>';
        }

        $search['totalPage'] = $totalPage;

        if (empty($pageLastStr)) {
            $lastPage = $nowPage - 1;
            $search['nowPage'] = $lastPage;
            $lastSearchStr = $this->arrayToSearchStr($search);
            $url = $baseUrl . '?' . $lastSearchStr;
            $pageLastStr = $this->getPageLinkWrapper($url, '«');
        }


        if (empty($pageNextStr)) {
            $pageNext = $nowPage + 1;
            $search['nowPage'] = $pageNext;
            $lastSearchStr = $this->arrayToSearchStr($search);
            $url = $baseUrl . '?' . $lastSearchStr;
            $pageNextStr = $this->getPageLinkWrapper($url, '»');
        }


        $pageTemp = '';
        $pageRange = $this->getPageRange($nowPage, $totalPage);
        $pageTemp .= $pageLastStr;
        foreach ($pageRange as $page) {
            $search['nowPage'] = $page;
            $searchStr = $this->arrayToSearchStr($search);
            $url = $baseUrl . '?' . $searchStr;
            if ($page == $nowPage) {
                $pageTemp .= $this->getActivePageLinkWrapper($url, $page);
            } else {
                $pageTemp .= $this->getPageLinkWrapper($url, $page);
            }
        }

        if ($nowPage < $totalPage) {
            $Str = $this->arrayToSearchStr(array_merge($search, ['nowPage' => $totalPage, 'totalPage' => $totalPage]));
            $url = $baseUrl . '?' . $Str;
            $pageNextStr .= '<li><span><a href=' . $url . '>末页</a></span></li>';
        }

        $pageTemp .= $pageNextStr;
        $pageView = $pagePre . $pageTemp . $pageEnd;
        return $pageView;
    }


    /**
     * 获取实际显示页面范围的范围
     * @param $nowPage
     * @param $totalPage
     * @return array
     */
    public function getPageRange($nowPage, $totalPage)
    {
        $returnArray = [];

        if ($totalPage <= 5) {
            for ($i = 1; $i <= $totalPage; $i++) {
                $returnArray[] = $i;
            }
        } else {
            $lengthLeft = $nowPage - 1;
            $lengthRight = $totalPage - $nowPage;

            if (($lengthLeft < 2) && ($lengthRight < 2)) {
                $returnArray = [];
            } elseif (($lengthLeft < 2) && ($lengthRight > 2)) {
                for ($i = 1; $i <= 5; $i++) {
                    $returnArray[] = $i;
                }
            } elseif (($lengthLeft > 2) && ($lengthRight < 2)) {
                $start = $totalPage - 4;
                for ($i = $start; $i <= $totalPage; $i++) {
                    $returnArray[] = $i;
                }
            } else {
                for ($i = $nowPage - 2; $i <= $nowPage + 2; $i++) {
                    $returnArray[] = $i;
                }
            }
        }

        return $returnArray;
    }


    /**
     * 将搜索的数组拼接成为url
     * 注意：PHP的内置函数http_build_query，会自动将没有值的参数清除，导致blade模板报错
     * @param $array
     * @return string
     */
    public function arrayToSearchStr($array)
    {
        $fields_string = '';

        reset($array);
        end($array);
        $lastKey = key($array);
        reset($array);

        foreach ($array as $key => $value) {
            if ($key != $lastKey) {
                $fields_string .= $key . '=' . $value . '&';
            } else {
                $fields_string .= $key . '=' . $value;
            }
        }
        rtrim($fields_string, '&');

        return $fields_string;
    }


    public function arrayToObject($e)
    {

        if (gettype($e) != 'array') return;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object')
                $e[$k] = (object)$this->arrayToObject($v);
        }
        return (object)$e;
    }

    public function objectToArray($e)
    {
        $e = (array)$e;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') return;
            if (gettype($v) == 'object' || gettype($v) == 'array')
                $e[$k] = (array)$this->objectToArray($v);
        }
        return $e;
    }
}