<?php

namespace app\common\logic;

use app\common\service\AmazonOss;
use Qcloud\cos\Api;
use think\Request;
use OSS\OssClient;
use OSS\Core\OssException;
/**
 * 上传
 * @package app\common\logic
 */
class Upload extends Common
{

    public function _initialize()
    {
        parent::_initialize();

    }

    /**
     * 上传图片
     * @param $name
     * @return array
     */
    function uploadImage($name)
    {
        $file = Request::instance()->file($name);
        if (!$file) {
            return ['code' => 0, 'msg' => lang('请上传图片')];
        }
        $allow = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!$file->checkMime($allow)) {
            return ['code' => 0, 'msg' => lang('只能上传jpg、png、gif文件')];
        }

        $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
        $name = time() . mt_rand(1000, 9999) . ".{$extension}";
        $folder = date("Ym") . "/";
        $path = ROOT_PATH . 'public/uploads/' . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $info = $file->move($path, $name);

//		$stream = file_get_contents($file->getpathName());
//		// 调用工具类上传
//		$img_url = AmazonOss::upload($name, $stream);

        $params = [
//			'path' => $img_url,
            'path' => str_replace("\\", "/", $folder . $name),
            'create_time' => time()
        ];
        if ($id = $this->db->name('upload_files')->insertGetId($params)) {
            return ['code' => 1, 'msg' => $id, 'path' => $params['path']];
        }
        return ['code' => 0, 'msg' => lang('上传失败，请稍后重试')];
    }

    function moveImage($id, $folder, $size = [])
    {
        $info = $this->db->name('upload_files')->where(['id' => $id])->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang('上传图片失败，请重新上传')];
        }

        $local_src = ROOT_PATH . 'public/uploads/'.$info['path'];

//		$content = file_get_contents($local_src);
//		file_put_contents($local_src, $content);
        //crop
        !isset($size['0']) && $size['0'] = 1000;
        !isset($size['1']) && $size['1'] = 1000;
        $image = \think\Image::open($local_src);
        $image->thumb($size['0'], $size['1'])->save($local_src);

        $stream = file_get_contents($local_src);
        // 调用工具类上传
        $result = AmazonOss::upload($info['path'], $stream);
        if (!$result) {
            return ['code' => 0, 'msg' => lang('上传图片失败，请重新上传')];
        }
        $info['status'] = 1;
        $this->db->name('upload_files')->update($info);
        return ['code' => 1, 'picture' => $result, 'file' => $info['path']];
    }

    function uploadFileCloud($file = '', $folder='')
    {
        $object  = $file;
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
        $filePath = ROOT_PATH . 'public/uploads/' .$file;
        $stream = file_get_contents($filePath);
        // 调用工具类上传
        AmazonOss::upload($object, $stream);

        return $object;
    }



    public function aliyunOssConfig(  ) {
        // 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录 https://ram.console.aliyun.com 创建RAM账号。
        $accessKeyId = "LTAI4FqGXKpvJsRSMmcL9Lu4";
        $accessKeySecret = "96i2VSjoxpfSw8UpO5p2ceRKTkZim0";
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = "http://oss-eu-central-1.aliyuncs.com";

        return new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    }

    function uploadFileCloud123($file = '', $folder='')
    {
//		$file = Request::instance()->file('file');
//
//		$extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
//		$name = time() . mt_rand(1000, 9999) . ".{$extension}";
//		$folder = date("Ym") . "/";
//		$path = ROOT_PATH . 'public/uploads/' . $folder;
//		$info = $file->move($path, $name);


        // 存储空间名称
        $bucket= "sweden";
        // 文件名称
        $object  = $file;
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
//		$filePath = ROOT_PATH . 'public/uploads/' . $folder .'/'.$file;
        $filePath = ROOT_PATH . 'public/uploads/' .$file;
        try{
            $ossClient = $this->aliyunOssConfig();

            $ret = $ossClient->uploadFile($bucket, $object, $filePath);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return false;
        }

        if (isset($ret['oss-request-url']) && !empty($ret['oss-request-url'])) {
            $path = $ret['oss-request-url'];  //获取资源文件路径（包含了APPID和bucketname）
            $res['code'] = '1';
            $res['picture'] = $object;  //资源文件路径（需要前面添加上域名地址才可以使用）
            $res['url'] = $path;   //资源文件URL
            //如果不用OSS，那么状态为已使用的文件不能删除
            return $object;
        }
        return false;
    }

    /**
     * 上传视频
     * @param $name
     * @return array
     */
    function uploadVideo($name)
    {
        $file = Request::instance()->file($name);
        if (!$file) {
            return ['code' => 0, 'msg' => lang('请上传视频')];
        }
        $allow = ['video/mp4', 'video/ogg', 'video/flv', 'video/avi', 'video/wmv', 'video/rmvb'];
        if (!$file->checkMime($allow)) {
            return ['code' => 0, 'msg' => lang('只能上传mp4、ogg、flv、avi、wmv、rmvb文件')];
        }

        $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
        $name = time() . mt_rand(1000, 9999) . ".{$extension}";
        $folder = date("Ym") . "/";
        $path = ROOT_PATH . 'public/uploads/' . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $info = $file->move($path, $name);

        $params = [
            'path' => str_replace("\\", "/", $folder . $name),
            'create_time' => time()
        ];
        if ($id = $this->db->name('upload_files')->insertGetId($params)) {
            return ['code' => 1, 'msg' => $id, 'path' => $params['path']];
        }
        return ['code' => 0, 'msg' => lang('上传失败，请稍后重试')];
    }

    function deleteFile($file)
    {
        $ossClient = $this->aliyunOssConfig();

        // 存储空间名称
        $bucket= "go-battery-cl";
        $res = $ossClient->deleteObject($bucket, $file);
    }


    function useFile($id, $folder = '')
    {
        $info = $this->db->name('upload_files')->where(['id' => $id])->find();
        if (!$info) {
            return ['code' => 0, 'msg' => lang('上传图片失败，请重新上传')];
        }
        $path =  ROOT_PATH . 'public/uploads/' . $info['path'];
        $this->db->name('upload_files')->where(['id'=>$id])->update(['status' => 1]);
        return ['code' => 1,
            'file' => $info['path'],
            'size' => filesize($path),
            'path' => ROOT_PATH . 'public/uploads/' . $info['path'],
//            'qrcode' => config('qcloudurl')."/".$info['path']
            'qrcode' => config('website')."/uploads/".$info['path']
        ];
    }

    function statFile($file){
        $cosApi = new Api(config('Qcloud'));
        $bucket = config('Qcloud.bucket');
        $ret = $cosApi->stat($bucket, $file);
        echo '<pre>';
        print_r($ret);exit;
    }


    /**
     * 微信图片
     * @param $media_id
     * @return array
     */
    function wxImage($media_id)
    {
        $name = time() . mt_rand(1000, 9999) . ".jpg";
        $folder = date("Ym") . "/";
        $path = ROOT_PATH . 'public/uploads/' . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $result = callWechat('Media',$this->oCode)->getMedia($media_id);
        if($result){
            @file_put_contents($path.$name,$result);
            $params = [
                'path' => str_replace("\\", "/", $folder . $name),
                'create_time' => time()
            ];
            if ($id = $this->db->name('upload_files')->insertGetId($params)) {
                return ['code' => 1, 'msg' => $id];
            }
        }
        return ['code' => 0, 'msg' => lang('上传失败，请稍后重试')];
    }
}