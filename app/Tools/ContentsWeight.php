<?php
namespace App\Tools;

use App\Services\FaqServices\FaqService;

/**
 * 热度排序
 *
 * @author wangzeyan
 */
class ContentsWeight
{
    // 键
    protected static $minscore = 10;

    protected static function keyExists($key)
    {
        return \Redis::exists($key);
    }

    /**
     * 添加文章热度
     *
     * @param $guid
     * @return float
     */
    public static function contentHot($guid)
    {
        $key = SORTEDSETS_CONTENT_GUID_;
        $score = \Redis::zscore($key, $guid);
        if ($score) {
            $score = \Redis::zincrby($key, 1, $guid);
        } else {
            \Redis::zadd($key, 1, $guid);
            $score = \Redis::zscore($key, $guid);
        }
        return $score;
    }

    /**
     * 获取最热文章的guid
     *
     * @param $number
     * @return array
     */
    public static function getHotContents($number)
    {
        $key = SORTEDSETS_CONTENT_GUID_;
        $status = self::keyExists($key);
        if ($status) {
            return \Redis::zRevRange($key, 0, $number - 1, ['withscores' => TRUE]);
        } else {
            $data = new FaqService();
            $data = $data->CommentHeat();
            foreach ($data as $k => $v) {
                \Redis::zadd($key, $v, $k);
            }
            return \Redis::zRevRange($key, 0, $number - 1, ['withscores' => TRUE]);
        }

    }

    /**
     * 添加问题热度
     *
     * @param $guid
     * @return float
     */
    public static function problemHot($problem)
    {
        $key = SORTEDSETS_PROBLEM_PROBLEM_;
        $score = \Redis::zscore($key, $problem);
        if ($score) {
            $score = \Redis::zincrby($key, 1, $problem);
        } else {
            \Redis::zadd($key, 1, $problem);
            $score = \Redis::zscore($key, $problem);
        }
        return $score;
    }

    /**
     * 获取热度问题
     *
     * @param $number
     * @return array
     */
    public static function getHotProblem($number)
    {
        $key = SORTEDSETS_PROBLEM_PROBLEM_;
        $status = self::keyExists($key);
        if ($status) {
            return \Redis::zRevRange($key, 0, $number - 1, ['withscores' => TRUE]);
        } else {
            $data = new FaqService();
            $problem = $data->getProblemFollow();
            foreach ($problem as $v) {
                \Redis::zadd($key, $v['follow'], $v['problem']);
            }
            $title = $data->getContentField(['field' => ['follow', 'title'], 'param' => ['status' => 1]]);
            foreach ($title as $key => $value) {
                \Redis::zadd($key, $value, $key);
            }
            return \Redis::zRevRange($key, 0, $number - 1, ['withscores' => TRUE]);
        }
    }

    /**
     * 获取有效的问题
     * @return array
     */
    public static function getEffectiveProblem()
    {
        $key = SORTEDSETS_PROBLEM_PROBLEM_;
        $problem = \Redis::zRevRange($key, 0, -1, ['withscores' => TRUE]);
        $score = max($problem);
        $data = \Redis::zrangebyscore($key, self::$minscore, $score, ['withscores' => TRUE]);
        return $data;
    }

    /**
     * 清除无效的问题缓存
     * @return int
     */
    public static function delInvalidProblem()
    {
        $key = SORTEDSETS_PROBLEM_PROBLEM_;
        return \Redis::zRemRangeByScore($key, 0, self::$minscore);
    }

}
