<?php
namespace app\common\service;

use OSS\OssClient;
use OSS\Core\OssException;
use think\Log;

use function AlibabaCloud\Client\env;

class OssUpload
{
    var $accessKeyId;
    var $accessKeySecret;
    var $endpoint;
    var $bucket;
    var $object;
    var $ossClient;

    public function __construct()
    {
        // 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录RAM控制台创建RAM账号。
        $this->accessKeyId = config('oss.access_key_id');
        $this->accessKeySecret = config('oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $this->endpoint = config('oss.endpoint');
        // 设置存储空间名称。
        $this->bucket = config('oss.bucket');
        $this->ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
    }

    /**
     * TODO 上传文件
     * $filePath 本地文件路径加文件名包括后缀组成
     */
    public function upload($object, $filePath)
    {
        try {
            $ret = $this->ossClient->uploadFile($this->bucket, $object, $filePath);
            return ['code' => 1, 'msg' => 'ok', 'path' => $ret['info']['url']];
        } catch (OssException $e) {
            Log::ERROR('上传图片失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
        return ['code' => 0, 'msg' => 'error'];
    }

    /**
     * 删除文件
     */
    public function delete($object)
    {
        try {
            $ret = $this->ossClient->deleteObject($this->bucket, $object);
            return ['code' => 1, 'msg' => 'error'];
        } catch (OssException $e) {
            Log::ERROR('删除图片失败:' . $e->getMessage());
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
        return ['code' => 0, 'msg' => 'error'];
    }
}
