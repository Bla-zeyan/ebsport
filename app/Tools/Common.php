<?php

namespace App\Tools;

use Flc\Alidayu\Requests\AlibabaAliqinFcSmsNumSend;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Uuid;
use Flc\Alidayu\Client;
use Flc\Alidayu\App;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;


class Common
{

    /**
     * 验证码生成
     *
     */

    public static function captcha()
    {
        $phrase = new PhraseBuilder;
        // 设置验证码位数
        $code = $phrase->build(4);
        // 生成验证码图片的Builder对象,配置相应属性
        $builder = new CaptchaBuilder($code, $phrase);
        // 设置背景颜色
        $builder->setBackgroundColor(220, 210, 230);
        $builder->setMaxAngle(25);
        $builder->setMaxBehindLines(0);
        $builder->setMaxFrontLines(0);
        // 可以设置图片宽高及字体
        $builder->build($width = 100, $height = 40, $font = null);
        // 获取验证码的内容
        $phrase = $builder->getPhrase();
        // 把内容存入session
        Session::put('code', $phrase);
        // 生成图片
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        return $builder->output();
    }


    /**
     * 返回uuid
     * @return string
     */
    public static function getUuid()
    {
        $uuid = Uuid::uuid1();
        return $uuid->getHex();
    }

    /**
     *  获取本月第一天和最后一天
     * @param $date
     * @return array
     */
    public static function getMonth($date)
    {
        $firstday = date("Y-m-01", strtotime($date));
        $lastday = date("Y-m-d", strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }


    /**
     *  获取上个月第一天和最后一天
     * @param $date
     * @return array
     */
    public static function getlastMonthDays($date)
    {
        $timestamp = strtotime($date);
        $firstday = date('Y-m-01', strtotime(date('Y', $timestamp) . '-' . (date('m', $timestamp) - 1) . '-01'));
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }


    /**
     *  获取下个月第一天和最后一天
     * @param $date
     * @return array
     */
    public static function getNextMonthDays($date)
    {
        $timestamp = strtotime($date);
        $arr = getdate($timestamp);
        if ($arr['mon'] == 12) {
            $year = $arr['year'] + 1;
            $month = $arr['mon'] - 11;
            $firstday = $year . '-0' . $month . '-01';
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        } else {
            $firstday = date('Y-m-01', strtotime(date('Y', $timestamp) . '-' . (date('m', $timestamp) + 1) . '-01'));
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        }
        return array($firstday, $lastday);
    }

    /**
     * 判断并返回当前页码
     * @param  array $data
     * @param  ingeter
     * @return mixed(false | array)
     * @author 郭鹏超
     */
    public static function getNowPage($nowPage, $count)
    {
        if (empty($count)) return false;
        $totalPage = ceil($count / PAGENUM);
        if ($nowPage < 1) $nowPage = 1;
        if ($nowPage > $totalPage) $nowPage = $totalPage;
        return ['nowPage' => (int)$nowPage, 'totalPage' => (int)$totalPage];
    }

    /**
     * 用户注册生成随机串
     * @param  int 生成长度
     * @return string 生成的字条串
     */
    public static function random($length)
    {
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 生成手机验证码
     * @param $phone
     * @return bool
     */
    public static function phoneCode($phone)
    {
        // 配置信息
        $config = [
            'app_key' => env('ALISMS_KEY'),
            'app_secret' => env('ALISMS_SECRETKEY'),
        ];
        $client = new Client(new App($config));
        $req = new  AlibabaAliqinFcSmsNumSend;
        $number = rand(100000, 999999);
        $req->setRecNum("$phone")
            ->setSmsParam([
                'number' => $number
            ])
            ->setSmsFreeSignName('IT源代码平台')
            ->setSmsTemplateCode('SMS_6390462');
        $resp = $client->execute($req);
        dd($resp);
        if (property_exists($resp, 'result')) {
            \Redis::sEtex('STRING_CODE_' . $phone, 600, $number);
            return $number;
        } else {
            return false;
        }
    }

    /** 获取一周的时间戳
     *
     * @return array
     */
    public static function getWeekTime()
    {

        // 获取一周中每天的时间戳
        $time = strtotime(date('Y-m-d'));
        // 一周的时间 从今天开始
        $everyDateStart = [];
        for ($i = 1; $i <= 7; $i++) {
            $everyDateStart[] = ($i - 1) * 86400 + $time;
        }
        return $everyDateStart;
    }


    /** 单条储存 适用 后接ID
     *
     * @param $list_key
     * @param $hash_key
     * @param $data
     * @param $mark
     */
    public static function pushInfo($list_key, $hash_key, $data, $mark)
    {
        \Redis::rpush($list_key, $mark);
        \Redis::hMset($hash_key, $data);
    }

    /** 获取Redis列表数据
     *
     * @param $list_key
     * @return array
     */
    public static function getListData($list_key)
    {
        return \Redis::lrange($list_key, 0, -1);

    }

    /** 获取HASH数据
     *
     * @param $hash_key
     * @return array
     */
    public static function getHashData($hash_key)
    {
        return \Redis::hGetall($hash_key);
    }


    /**
     * 处理返回的数据
     *
     * @param $result
     * @param string $res_key
     * @return bool
     */
    public static function handleResult($result, $res_key = '')
    {
        // 是否请求成功
        if (empty($result)) {
            return false;
        }

        // 编码
        $data = json_decode($result, 1);

        // 判断
        if ($data['ServerNo'] != 'SN200') {
            return false;
        }

        // 返回数据
        if (empty($res_key)) {
            return $data['ResultData'];
        }
        return $data['ResultData'][$res_key];
    }

    /**
     * 加密
     *
     * @param $data
     * @return string
     */
    public static function encryption($data)
    {
        // 参数
        $param = collect($data)->get('param');
        // 录入人的GUID
        $guid = collect($data)->get('guid');

        // 随机字符串
        $token = '8o6q4Fiwercf9xnu3RvcG5D6cfHrbHFK';
        // 数组
        $hashs = [
            [0, 5, 9, 15, 22, 28],
            [5, 8, 19, 25, 30, 31],
            [20, 58, 31, 3, 85, 8],
            [25, 31, 0, 9, 13, 17],
            [29, 2, 96, 17, 25, 26],
            [10, 17, 18, 29, 2, 3],
            [5, 18, 15, 17, 18, 22],
            [8, 29, 22, 27, 21, 26],
        ];

        // 根据TOKEN获取数组中的数字组成字符串
        $strs = substr($token, 2, 1);
        $strs .= substr($token, 5, 1);
        $strs .= substr($token, 8, 1);
        // 对字符串进行哈希处理
        $code = hexdec($strs);
        // 并且对8取取
        $str1 = $code % 8;
        // 取数组中的一组
        $arr = $hashs["$str1"];
        // token
        $m = null;
        foreach ($arr as $v) {
            $m .= substr($token, $v, 1);
        }

        // 返回
        return md5($guid . $param . $m);
    }

    /**
     * 解密
     *
     * @param $data
     * @return bool
     */
    public static function decrypt($data)
    {
        // token生成
        $mark = self::encryption($data);
        // 验证TOKEN
        if (collect($data)->get('token') != $mark) {
            return false;
        }
        // 验证通过
        return true;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public static function createLinkstring($para)
    {
        $arg = '';
        while ((list ($key, $val) = each($para)) == true) {
            $arg .= $key . '=' . $val . '&';
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 密码加密
     *
     * @param string $string
     * @param string $skey
     * @return mixed
     */
    public static function encode($string = '', $skey = '35fb8d0581d84d5fb109ae5ed9c5bf61')
    {
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key < $strCount && $strArr[$key] .= $value;
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
    }

    /**
     * 密码解密
     *
     * @param string $string
     * @param string $skey
     * @return string
     */
    public static function decode($string = '', $skey = '35fb8d0581d84d5fb109ae5ed9c5bf61')
    {
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }

    /**
     * 邮件发送
     * @param $name  收件人姓名
     * @param $to    收件人邮箱
     * @param $title 邮件标题
     * @param $url   链接地址
     * @param string $blade 邮箱模板
     * @return bool
     */
    public static function sendEmail($name, $to, $title, $url, $blade = 'register')
    {
        // 邮件发送
        $flag = Mail::send('email.' . $blade, ['name' => $name, 'url' => $url], function ($message) use ($to, $title) {
            // 发送
            $message->to($to)->subject('【Microlanguage】' . $title);
        });
        // 判断发送结果
        if ($flag) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 数组转换对象
     *
     * @param $e 数组
     * @return object|void
     */
    public static function arrayToObject($e)
    {

        if (gettype($e) != 'array') return;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object')
                $e[$k] = (object)self::arrayToObject($v);
        }
        return (object)$e;
    }

    /**
     * 对象转换数组
     *
     * @param $e StdClass对象实例
     * @return array|void
     */
    public static function objectToArray($e)
    {
        $e = (array)$e;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') return;
            if (gettype($v) == 'object' || gettype($v) == 'array')
                $e[$k] = (array)self::objectToArray($v);
        }
        return $e;
    }

    /**
     * 计算年龄
     * @param $time 出生日对应的时间戳
     * @return bool|int|string
     */
    public static function getAge($time)
    {
        $year_diff = date('Y') - date('Y', $time);
        $mon_diff = date('m') - date('m', $time);
        if ($year_diff == 0) {
            return 0;
        } else {
            if ($mon_diff >= 0) {
                return $year_diff;
            } else {
                return $year_diff - 1;
            }
        }
    }

}
