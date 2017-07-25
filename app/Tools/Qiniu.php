<?php
namespace App\Tools;

// 引入鉴权类
use Qiniu\Auth;
// 引入上传类
use Qiniu\Storage\UploadManager;
use Illuminate\Http\Request;

class Qiniu
{
    // 七牛云 Access Key 和 Secret Key
    protected $accessKey;

    protected $secretKey;
    // 要上传的空间
    protected $bucket;
    protected $custom;


    private $disk;

    public function __construct()
    {
        $this->accessKey = env('QINIU_ACCESS_KEY');
        $this->secretKey = env('QINIU_SECRET_KEY');
        $this->bucket = env('QINIU_BUCKET');
        $this->custom = env('QINIU_DOMAIN');
        $this->disk = \Storage::disk('qiniu');
    }

    /**
     * 获取本地上传资源 然后长传七牛
     * @param Request $request
     * @param $filename
     * @return bool|null
     */
    public function uploadImg(Request $request, $filename)
    {
        $file = $request->file($filename);
        if (!$file || !$file->isValid()) {
            return false;
        }
        // 要上传文件的本地路径
        $filePath = $file->getRealPath();
        $date = time();
        $key = 'cool/' . $date . '.' . $file->getClientOriginalExtension();
        return $this->put($filePath, $key);
    }

    /**
     *  文件上传至七牛
     * @param null $filePath
     * @param null $key
     * @return bool|null
     */
    public function put($filePath = null, $key = null)
    {
        // 构建鉴权对象
        $auth = new Auth($this->accessKey, $this->secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($this->bucket);

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return false;
        } else {
            return $key;
        }
    }

    /** 获取token
     * @return bool|null
     */
    public function getToken()
    {
        // 构建鉴权对象
        $auth = new Auth($this->accessKey, $this->secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($this->bucket);

        return $token;
    }

    /**
     * 获取七牛云 资源链接
     * @param $key_name (资源名称)
     * @return string
     */
    public function getimgurl($key_name)
    {
        $auth = new Auth($this->accessKey, $this->secretKey);

        $baseUrl = "http://" . $this->custom . "/" . $key_name;
        $signedUrl = $auth->privateDownloadUrl($baseUrl);// 获取 视频链接的接口
        return $signedUrl;

    }

}