<?php

namespace App\Tools;

use App\Services\Base;

class RedisCache
{

    /**
     * 获取人员信息
     *
     * @param $guid
     * @return array
     */
    public static function getPersonnelInfo($guid)
    {
        // 判断是否有人员GUID
        if (empty($guid)) return false;
        // REDIS是否存在
        $data = \Redis::hGetall(HASH_USER_INFO_ . $guid);

        // 不为空
        if (!empty($data)) {
            return $data;
        }

        // 获取人员信息
        $result = self::getUserInfo($guid);

        // 处理返回的数据
        $data = self::handleResult($result, 'userInfo');

        // 判断是否请求成功和数据
        if (!$data || empty($data)) return false;

        // 存入Redis
        \Redis::hmset(HASH_USER_INFO_ . $guid, $data);
        // 缓存24小时
        self::setExpiration(HASH_USER_INFO_ . $guid, 86400);
        // 返回数据
        return $data;
    }


    /**
     * 获取所有人员的GUID
     *
     * @param $guid
     * @return array|bool
     */
    public static function getPersonnelList($guid)
    {
        // 获取所有的链表
        $user_array = \Redis::lrange(LIST_USER_MSG_, 0, -1);

        if (!in_array($guid, $user_array)) {

            // 获取人员信息
            $result = self::getUserInfo($guid);

            // 处理返回的数据
            $data = self::handleResult($result, 'userInfo');

            // 判断是否请求成功和数据
            if (!$data || empty($data)) return false;

            // 存入Redis
            \Redis::lpush(LIST_USER_MSG_, $guid);
            \Redis::hmset(HASH_USER_INFO_ . $guid, $data);

            return \Redis::lrange(LIST_USER_MSG_, 0, -1);
        }

        return $user_array;
    }


    /**
     * 获取校区数据
     *
     * @param $type 默认为空 获取包括软删除的type = all 只获取软删除的type = trashed
     * @return array|mixed
     */
    public static function getCompany($type = '')
    {
        // 判断redis是否存在数据
        $company = \Redis::hGetall(HASH_CAMPUS_INFO_ . $type);

        // redis中存在返回数据
        if (!empty($company)) {
            return $company;
        }

        // 请求数据库
        $result = self::company(['status' => $type]);

        // 处理返回的数据
        $data = self::handleResult($result, 'res');

        // 判断是否请求成功和数据
        if (!$data || empty($data)) return false;

        // 存入redis
        $company = self::setHashData(HASH_CAMPUS_INFO_ . $type, $data, 'id', 'company');

        // 缓存24小时
        self::setExpiration(HASH_CAMPUS_INFO_ . $type, 86400);

        // 返回数据
        return $company;
    }


    /**
     * 获取全部标签表数据
     *
     * @param $type 1 education(学历), 2 resource（客户来源）, 3 emergency（紧急联系人）
     * @param $status 状态 1 正常 2 禁用 默认取全部
     * @return array
     */
    public static function getLabel($type, $status = null)
    {
        // 判断参数
        if (empty($type)) return false;

        // REDIS_KEY
        if (empty($status)) {
            $redis_key = HASH_LABELS_INFO_ . $type;
        } else {
            $redis_key = HASH_LABELS_INFO_ . $status . $type;
        }

        // 判断redis是否存在数据
        $label_type = \Redis::hGetall($redis_key);

        // redis中存在返回数据
        if (!empty($label_type)) {
            return $label_type;
        }

        // 请求服务器
        if (empty($status)) {
            $label_type = self::getLabelByType(['where' => ['type' => self::getLabelType($type)]]);
        } else {
            $label_type = self::getLabelByType(['where' => ['type' => self::getLabelType($type), 'status' => $status]]);
        }

        // 处理返回的数据
        $data = self::handleResult($label_type);

        // 判断是否请求成功和数据
        if (!$data || empty($data)) return false;

        // 存入redis
        $label = self::setHashData($redis_key, $data, 'id', 'name');
        // 设置过期时间
        self::setExpiration($redis_key, 86400);
        // 返回数据
        return $label;
    }


    /**
     * 根据ID获取数据
     *
     * @param $id
     * @return array|bool
     */
    public static function getSingleLabel($id)
    {
        if (empty($id)) {
            return false;
        }
        // 获取redis数据
        $label = \Redis::hgetAll(HASH_LABELS_ID_INFO_ . $id);

        // 判断是否存在
        if (empty($label)) {

            // 请求服务器
            $data = self::getLabelByType(['where' => ['id' => $id]]);

            // 处理返回的数据
            $label = self::handleResult($data);

            // 判断是否请求成功和数据
            if (!$label || empty($label)) return false;

            // 存入redis
            $label = self::setHashData(HASH_LABELS_ID_INFO_ . $id, $label, 'id', 'name');
            // 设置过期时间
            self::setExpiration(HASH_LABELS_ID_INFO_ . $id, 86400);

            // 返回数据
            return $label;

        }
        // 返回数据
        return $label;

    }

    /**
     * 获取班期名称
     *
     * @return array|bool|mixed
     */
    public static function getClass()
    {
        // 获取redis数据
        $class = \Redis::hgetAll(HASH_CLASS_INFO);

        // 判断是否存在
        if (empty($class)) {
            // 请求服务器
            $data = self::classRedis();

            // 处理返回的数据
            $class = self::handleResult($data);

            // 判断是否请求成功和数据
            if (!$class || empty($class)) return false;

            // 赋值没有班期名称的数据名称
            $class = self::recombinationClassName($class);

            // 存入redis
            $class = self::setHashData(HASH_CLASS_INFO, $class, 'cguid', 'name');

            // 设置过期时间
            self::setExpiration(HASH_CLASS_INFO, 86400);
            // 返回数据
            return $class;
        }

        // 返回数据
        return $class;
    }

    /**
     * 获取人才库的班期CGUID
     *
     * @param $class_guid
     * @return bool|mixed
     */
    public static function getClassName($class_guid)
    {
        // 选取redis六库
        \Redis::select(6);
        if (\Redis::exists(STRING_CLASS_NAME_ . $class_guid)) {
            return \Redis::get(STRING_CLASS_NAME_ . $class_guid);
        };

        // 请求服务器获取班期GUID
        $result = self::getClassGuid($class_guid);

        // 处理返回的数据
        $data = self::handleResult($result);

        // 判断是否请求会数据
        if (!is_array($data)) {
            return false;
        }

        // 判断班期信息
        if (!empty($class_names = self::getClass())) {
            // 判断
            $mark = in_array($data['cguid'], array_keys($class_names));

            if (!$mark) {
                return false;
            }
            if (!empty($calssname = $class_names[$data['cguid']])) {
                \Redis::set(STRING_CLASS_NAME_ . $class_guid, $calssname);
                return $calssname;
            }
        }
        return false;
    }

    /**
     * 获取学科数据
     *
     * @return array|mixed
     */
    public static function getSubject()
    {
        // 定义学科的键值
        $redis_key = STRING_SUBJECT_NAME;

        // 判断哈西中是否存在键
        if (\Redis::EXISTS($redis_key)) {
            // 取出健对应的值
            $subject = \Redis::hGETALL($redis_key);
        } else {

            // 请求的URL
            $url = config('resource.future.APP_URL') . '/crmapi/subject-list';
            $key = config('resource.future.APP_KEY');
            // 请求服务器
            $data = self::curl($url, [], $key);

            $data = json_decode($data, 1);

            // 处理返回的数据
            if (empty($data)) {
                return false;
            }

            if ($data['status'] !== 200) {
                return false;
            }

            $subject = $data['data'];

            // 存入redis
            $subject = self::setHashData($redis_key, $subject, 'id', 'name');
            // 设置过期时间
            self::setExpiration($redis_key, 86400);
        }

        // 返回数据
        return $subject;
    }

    /**
     * 根据班期类型更新缓存
     *
     * @param $school_id
     * @param $type
     * @param $subject_id
     * @param $is_all
     * @return array|bool|mixed
     */
    public static function getClassForSchool($school_id, $type, $subject_id, $is_all = null)
    {
        $redis_key = HASH_SCHOOL_CLASS_INFO_ . '-' . $school_id . '-' . $type . '-' . $subject_id . '-' . $is_all;

        // 判断key是否存在
        $mark = \Redis::EXISTS($redis_key);

        // 存在直接返回数据
        if ($mark) {
            return \Redis::hgetAll($redis_key);
        } else {
            // 判断是否取全部的班期
            if (empty($is_all)) {
                // 获取服务器数据
                $result = self::getClassByCompany(['school_id' => $school_id, 'certificate_id' => $type, 'subject_id' => $subject_id]);
            } else {
                $result = self::getClassByCompanyAll(['school_id' => $school_id, 'certificate_id' => $type, 'subject_id' => $subject_id]);
            }

            // 处理返回的数据
            $data = self::handleResult($result);

            // 判断是否请求成功和数据
            if (!$data || empty($data)) return false;

            // 赋值没有班期名称的数据名称
            $data = self::recombinationClassName($data);

            // 存入redis
            $class = self::setHashData($redis_key, $data, 'cguid', 'name');
            // 设置过期时间
            self::setExpiration($redis_key, 86400);
            // 返回数据
            return $class;
        }
    }

    /**
     * 获取班期类型
     *
     * @return array|bool
     */
    public static function getClassByType()
    {
        $redis_key = HASH_CLASS_TYPE;
        // 判断key是否存在
        $mark = \Redis::EXISTS($redis_key);

        // 存在直接返回数据
        if ($mark) {
            $class_type = \Redis::hgetAll($redis_key);
            return array_except($class_type, 3);
        } else {

            // 获取服务器数据
            $result = self::getClassTypes();

            // 处理返回的数据
            $data = self::handleResult($result);

            // 判断是否请求成功和数据
            if (!$data || empty($data)) return false;

            // 存入redis
            \Redis::hmset($redis_key, $data);
            // 设置过期时间
            self::setExpiration($redis_key, 86400);

            return array_except($data, 3);
        }
    }

    /**
     * 获取班期类型数据
     *
     * @return array|bool
     */
    public static function getClassesType()
    {
        // 定义班期类型的键值
        $redis_key = STRING_CLASS_TYPE_NAME;

        // 判断哈西中是否存在键
        if (\Redis::EXISTS($redis_key)) {

            // 取出健对应的值
            $class_type = \Redis::hGETALL($redis_key);
        } else {

            // 请求的URL
            $url = config('resource.future.APP_URL') . '/crmapi/class-type';
            $key = config('resource.future.APP_KEY');

            // 请求服务器
            $data = self::curl($url, [], $key);
            $data = json_decode($data, 1);

            // 处理返回的数据
            if (empty($data)) {
                return false;
            }

            // 如果请求成功 存缓存
            if ($data['ServerNo'] === 'SN200') {

                // 存入redis
                $class_type = \Redis::Hmset($redis_key, $data['ResultData']);
                self::setExpiration($redis_key, 86400);

                // 存缓存成功
                if ($class_type === 'OK') {

                    // 返回数据
                    return $data['ResultData'];
                }

            }
        }
        return $class_type;
    }

    /**
     *  获取人员信息
     *
     * @param $guid
     * @return boonl
     */
    public static function getUserInfo($guid)
    {
        $url = config('resource.hrs.APP_URL') . '/getWorkerInfo';
        $key = config('resource.hrs.APP_FINANCE_KEY');
        // 请求服务器
        return self::curl($url, ['guid' => $guid], $key);
    }

    /**
     * 请求校区数据
     *
     * @param array $param
     * @return bool
     */
    public static function company($param = [])
    {
        $url = config('resource.hrs.APP_URL') . '/api_company_type';
        $key = config('resource.hrs.APP_FINANCE_KEY');

        // 请求服务器
        return self::curl($url, $param, $key);
    }

    /**
     * 根据TYPE值获取Label数据
     *
     * @param $param
     * @return bool
     */
    public static function getLabelByType($param = [])
    {
        // 请求的URL
        $url = config('resource.server.APP_URL') . '/label/type';
        $key = config('resource.server.APP_KEY');

        // 请求服务器
        return self::curl($url, $param, $key);
    }

    /**
     * 班期缓存
     *
     * @param $param
     * @return bool
     */
    public static function classRedis($param = [])
    {
        // 请求的URL
        $url = config('resource.future.APP_URL') . '/class/all';
        $key = config('resource.future.APP_KEY');

        // 请求服务器
        return self::curl($url, $param, $key);
    }

    /**
     * 获取班期GUID
     *
     * @param $param
     * @return bool
     */
    public static function getClassGuid($guid, $param = [])
    {
        // 请求的URL
        $url = config('resource.server.APP_URL') . '/rel_class/' . $guid;
        $key = config('resource.server.APP_KEY');

        // 请求服务器
        return self::curl($url, $param, $key);
    }

    /**
     * 通过校区ID和班期类型获取班期请求人才库获取没有开班的班期
     *
     * @param $param
     * @return bool
     */
    public static function getClassByCompany($param = [])
    {
        // 请求的URL
        $url = config('resource.future.APP_URL') . '/without/open/class';
        $key = config('resource.future.APP_KEY');

        // 请求服务器
        return self::curl($url, $param, $key);
    }


    /**
     * 通过校区ID和班期类型获取班期请求人才库获取全部的班期
     *
     * @param $param
     * @return bool
     */
    public static function getClassByCompanyAll($param = [])
    {
        // 请求的URL
        $url = config('resource.future.APP_URL') . '/class/all';
        $key = config('resource.future.APP_KEY');

        // 请求服务器
        return self::curl($url, array_filter($param), $key);
    }

    /**
     * 获取班期类型
     *
     * @param $param
     * @return bool
     */
    public static function getClassTypes($param = [])
    {
        // 请求的URL
        $url = config('resource.future.APP_URL') . '/crmapi/class-type';
        $key = config('resource.future.APP_KEY');

        // 请求服务器
        return self::curl($url, $param, $key);
    }

    /**
     * curl请求
     *
     * @param $param
     * @return bool
     */
    public static function curl($url, $param, $key, $ispost = 0)
    {
        $base = new Base();
        // 请求服务器
        return $base->scurl($url, $param, $key, $ispost);
    }

    /**
     * 处理返回的数据
     *
     * @param $result 返回的数据
     * @param string $res_key 返回数据的KEY
     * @return array or bool
     */
    public static function handleResult($result, $res_key = '')
    {
        if (empty($result)) return false;
        $data = json_decode($result, 1);

        if ($data['ServerNo'] != 'SN200' && $data['ServerNo'] != '200') {
            return false;
        }

        if (empty($res_key)) {
            return $data['ResultData'];
        }

        return $data['ResultData'][$res_key];
    }

    /**
     * 储存redis数据
     *
     * @param $redis_key
     * @param $data
     * @param $key
     * @param $val
     * @return mixed
     */
    public static function setHashData($redis_key, $data, $key, $val)
    {
        $hash_data = collect($data)->reduce(function ($hash_data, $value) use ($key, $val) {
            $hash_data[$value[$key]] = $value[$val];
            return $hash_data;
        }, []);
        \Redis::hmset($redis_key, $hash_data);
        return $hash_data;
    }

    /**
     * 根据字段名获取对应的label的TYPE
     *
     * @param $type
     * @return mixed
     */
    public static function getLabelType($type)
    {
        return config('dictionary.' . $type . '.id');
    }

    /**
     * 获取全部地区缓存
     *
     * @return array|bool|mixed
     */
    public static function getDistrictById()
    {
        // 定义学科的键值
        $redis_key = STRING_DISTRICT_NAME_;

        // 判断哈西中是否存在键
        if (\Redis::EXISTS($redis_key)) {

            // 取出健对应的值
            $district = \Redis::hGETALL($redis_key);
        } else {

            // 请求的URL
            $url = config('resource.server.APP_URL') . '/district';
            $key = config('resource.server.APP_KEY');

            // 请求服务器
            $data = self::curl($url, [], $key);
            $data = json_decode($data, 1);

            // 处理返回的数据
            if (empty($data)) {
                return false;
            }

            if ($data['ServerNo'] !== "SN200") {
                return false;
            }

            $district = $data['ResultData']['res'];

            // 存入redis
            $district = self::setHashData($redis_key, $district, 'id', 'name');
        }

        // 返回数据
        return $district;
    }

    /**
     * 清除人员缓存
     *
     * @param $guid
     * @return bool
     *
     * @author wangzeyan
     */
    public static function updatePersonnelCache($guid)
    {
        // 判断是否有人员GUID
        if (empty($guid)) return false;

        // 删除列表中的元素
        \Redis::lrem(LIST_USER_MSG_, 0, $guid);

        // 删除hash缓存
        \Redis::del(HASH_USER_INFO_ . $guid);

    }

    /**
     * 重组班期名称
     *
     * @param $class
     * @return mixed
     */
    public static function recombinationClassName($class)
    {
        // 获取所有学科
        $subjects = self::getSubject();

        // 请求缓存班期类型
        $class_type = self::getClassesType();

        // 班期名称重组
        foreach ($class as &$value) {
            if (!isset($value['alias'])) {
                $value['name'] = collect($class_type)->get($value['certificate_id'])
                    . collect($subjects)->get($value['subject_id'])
                    . $value['class_id'] . '期';
            } else {
                $value['name'] = $value['alias'];
            }
        }
        return $class;
    }

    /**
     * 获取所有咨询师的guid
     *
     * @return array|bool
     */
    public static function getCounselor()
    {
        // 定义咨询师的哈希键
        $redis_key = HASH_COUNSELOR_GUID_;

        // 判断哈西中是否存在键
        if (\Redis::EXISTS($redis_key)) {
            // 取出健对应的值
            $district = \Redis::hGETALL($redis_key);
        } else {

            // 请求的URL
            $url = config('resource.server.APP_URL') . '/counselo';
            $key = config('resource.server.APP_KEY');
            // 请求服务器
            $data = self::curl($url, [], $key);
            $data = json_decode($data, 1);

            // 处理返回的数据
            if (empty($data)) {
                return false;
            }

            if ($data['ServerNo'] !== "SN200") {
                return false;
            }
            $district = $data['ResultData']['res'];

            // 存入redis
            \Redis::hmset($redis_key, $district);
        }

        // 返回数据
        return $district;
    }

    /**
     * 获取单个班期的详细信息
     *
     * @param $class
     * @return  array | bool
     */
    public static function getClassInfo($class)
    {
        // REDIS的KEY
        $redis_key = HASH_CLASS_INFO . $class;

        // REDIS中存在数据则返回
        if (\Redis::hgetall($redis_key)) {
            return \Redis::hgetall($redis_key);
        }

        // URL
        $url = config('resource.future.APP_URL') . '/crmapi/class-by-cguid';
        // KEY
        $key = config('resource.future.APP_KEY');
        // 请求教务系统获取班期数据
        $result = self::curl($url, ['cguid' => $class], $key);

        // 编码
        $data = json_decode($result, 1);

        // 获取班期数据失败
        if (empty($data) || collect($data)->get('ServerNo') != 'SN200') {
            return false;
        }

        // 获取返回的数据
        $class = collect($data)->get('ResultData');
        // 重组班期姓名
        $class = collect(RedisCache::recombinationClassName([$class]))->first();

        // 储存REDIS
        \Redis::hmset($redis_key, $class);
        // 设置过去时间
        self::setExpiration($redis_key, 86400);

        // 返回数据
        return $class;
    }

    /**
     * 获取全部班期
     *
     * @return array|bool|mixed|string
     */
    public static function getAllClass()
    {
        // 获取redis数据
        $class = \Redis::get(STRING_CLASS_INFO);

        // 判断是否存在
        if (empty($class)) {
            // 请求服务器
            $data = self::classRedis();

            // 处理返回的数据
            $class = self::handleResult($data);

            // 判断是否请求成功和数据
            if (!$class || empty($class)) return false;

            // 处理数据
            $class = collect($class)->reduce(function ($data, $value) {
                $data[collect($value)->get('cguid')] = $value;
                return $data;
            }, []);

            // 存入redis
            \Redis::set(STRING_CLASS_INFO, json_encode($class));

            // 设置过期时间
            self::setExpiration(STRING_CLASS_INFO, 86400);

            // 返回数据
            return $class;
        }

        // 返回数据
        return json_decode($class, 1);
    }


    /**
     * 设置REDIS的过期时间
     *
     * @param $redis_key
     * @param $time
     */
    public static function setExpiration($redis_key, $time)
    {
        \Redis::EXPIRE($redis_key, $time);
    }

    /**
     * 获取用户消息缓存
     *
     * @param $guid
     * @return array|bool
     */
    public static function userMessagesList($guid)
    {
        // REDIS的KEY
        $redis_key = SORTEDSETS_MESSAGES_USER_ . $guid;
        \Redis::select(6);
        // 判断哈西中是否存在键
        if (\Redis::EXISTS($redis_key)) {
            // 取出健对应的值
            return \Redis::zRevRange($redis_key, 0, -1, ['withscores' => TRUE]);
        } else {
            return false;
        }
    }

    /**
     * 操作用户消息
     *
     * @param $guid
     * @param $message
     * @param $operation
     * @return int
     */
    public static function userMessagesOperation($guid, $message, $operation)
    {
        // REDIS的KEY
        $redis_key = SORTEDSETS_MESSAGES_USER_ . $guid;
        \Redis::select(6);

        // 判断什么操作（true：添加，false：删除）
        if ($operation) {
            $score = \Redis::zscore($redis_key, $message);
            if (!$score) {
                \Redis::zadd($redis_key, $score, $message);
            }
            return \Redis::zincrby($redis_key, 1, $message);
        } else {
            return \Redis::zRem($redis_key, $message);
        }
    }

}
