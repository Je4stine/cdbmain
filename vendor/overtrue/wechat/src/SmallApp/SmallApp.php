<?php
/**
 * SmallApp.php.
 *
 * @author    jijunhong <jijunhong@huayucc.com>
 * @copyright 2017 jijunhong <jijunhong@huayucc.com>
 *
 */

namespace EasyWeChat\SmallApp;

use EasyWeChat\Core\AbstractAPI;

/**
 * Class SmallApp.
 */
class SmallApp extends AbstractAPI
{
    const API_MODIFY_DOMAIN = 'https://api.weixin.qq.com/wxa/modify_domain';  //小程序服务器地址
    const API_BIND_TESTER = 'https://api.weixin.qq.com/wxa/bind_tester';  //绑定体验体验者
    const API_UNBIND_TESTER = 'https://api.weixin.qq.com/wxa/unbind_tester';  //绑定体验体验者
    const API_COMMIT = 'https://api.weixin.qq.com/wxa/commit';  //上传代码
    const API_GET_QRCODE = 'https://api.weixin.qq.com/wxa/get_qrcode';  //获取体验版二维码
    const API_GET_CATEGORY = 'https://api.weixin.qq.com/wxa/get_category';  //获取可选类目
    const API_GET_PAGE = 'https://api.weixin.qq.com/wxa/get_page';  //获取提交代码的页面配置
    const API_SUBMIT_AUDIT = 'https://api.weixin.qq.com/wxa/submit_audit';  //将代码包提交审核
    const API_GET_AUDITSTATUS = 'https://api.weixin.qq.com/wxa/get_auditstatus';  //查询指定版本审核状态
    const API_GET_LATSEST_AUDITSTATUS = 'https://api.weixin.qq.com/wxa/get_latest_auditstatus';  //查询最后一次审核状态
    const API_GET_RELEASE = 'https://api.weixin.qq.com/wxa/release';  //发布
    const API_CHANGE_VISITSTATUS = 'https://api.weixin.qq.com/wxa/change_visitstatus';//修改线上小程序可见状态

    const API_TEMPLATE_LIBRARY_LIST = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/list';  //获取模板库数据
    const API_TEMPLATE_LIBRARY_GET = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/get';  //获取模板库某个模板标题下关键词库
    const API_TEMPLATE_ADD = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/add';  //获取模板库某个模板标题下关键词库

    const API_TEMPLATE_LIST = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/list';  //获取帐号下已存在的模板列表
    const API_TEMPLATE_DEL = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/del';  //删除帐号下的某个模板

    const API_TEMPLATE_SEND = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send';  //发送模板消息

    const API_SET_WEAPPS_SUPPORT_VERSION = 'https://api.weixin.qq.com/cgi-bin/wxopen/setweappsupportversion';    //设置最低基础库版本

    const API_REVERT_CODE_RELEASE = 'https://api.weixin.qq.com/wxa/revertcoderelease';  //回退版本

    const API_UNDO_CODE_AUDIT = 'https://api.weixin.qq.com/wxa/undocodeaudit';  //小程序审核撤回

    /**
     * 修改服务器域名.
     * @param  string     $action          [操作类型]
     * @param  array|null $requestdomain   [资源请求地址]
     * @param  array|null $wsrequestdomain [ws地址]
     * @param  array|null $uploaddomain    [上传地址]
     * @param  array|null $downloaddomain  [下载地址]
     * @return  \EasyWeChat\Support\Collection
     */
    public function modify_domain($action ,$requestdomain = null,$wsrequestdomain = null,$uploaddomain = null,$downloaddomain = null)
    {
        $params = [
            'action' => $action,
            'requestdomain' => $requestdomain,
            'wsrequestdomain' => $wsrequestdomain,
            'uploaddomain' => $uploaddomain,
            'downloaddomain' => $downloaddomain,
        ];

        return $this->parseJSON('json', [self::API_MODIFY_DOMAIN, $params]);

    }

    /**
     * 绑定体验者.
     *
     * @return \EasyWeChat\Support\Collection
     */
    public function bind_tester($wechatid)
    {
        return $this->parseJSON('json', [self::API_BIND_TESTER,['wechatid' => $wechatid]]);
    }

    /**
     * 解绑体验者.
     *
     * @return \EasyWeChat\Support\Collection
     */
    public function unbind_tester($wechatid)
    {
        return $this->parseJSON('json', [self::API_UNBIND_TESTER,['wechatid' => $wechatid]]);
    }

    /**
     * 上传代码
     * @param  string $template_id  [模板ID]
     * @param  string $ext_json     [ext_json]
     * @param  string $user_version [版本]
     * @param  string $user_desc    [版本介绍]
     * @return \EasyWeChat\Support\Collection
     */
    public function commit($template_id,$ext_json,$user_version,$user_desc)
    {
        $params = [
            'template_id' => $template_id,
            'ext_json' => $ext_json,
            'user_version' => $user_version,
            'user_desc' => $user_desc,
        ];
        return $this->parseJSON('json', [self::API_COMMIT,$params]);
    }

    /**
     * 获取体验版二维码
     * @return \EasyWeChat\Support\Collection
     */
    public function get_qrcode()
    {
        
        $access_token = $this->accessToken->getToken(true);

        $filename = self::API_GET_QRCODE.'?access_token='.$access_token;;
        $size = getimagesize($filename);
        $fp = fopen($filename, "rb");
        if ($size && $fp) {
            header("Content-type: {$size['mime']}");
            fpassthru($fp);
            exit;
        } else {
            // error
            $this->error = 'vendor/overtrue/wechat/src/SmallApp/SmallApp->get_qrcode()错误';
        }
    }

    /**
     * 获取可选类目
     * @return \EasyWeChat\Support\Collection
     */
    public function get_category()
    {
        return  $this->parseJSON('get', [self::API_GET_CATEGORY]);
    }

    /**
     * 获取提交代码的页面配置
     * @return \EasyWeChat\Support\Collection
     */
    public function get_page()
    {
        return  $this->parseJSON('get', [self::API_GET_PAGE]);
    }

    /**
     * 将代码包提交审核
     * @param  array  $item_list [提交审核项的一个列表]
     * @return \EasyWeChat\Support\Collection
     */
    public function submit_audit($item_list)
    {
        return $this->parseJSON('json', [self::API_SUBMIT_AUDIT,['item_list' => $item_list]]);
    }
    /**
     * 查询指定版本审核状态
     * @param  string  $auditid [提交审核时获取的审核ID]
     * @return \EasyWeChat\Support\Collection
     */
    public function get_auditstatus($auditid)
    {
        return $this->parseJSON('json', [self::API_GET_AUDITSTATUS,['auditid' => $auditid]]);
    }
    /**
     * 查询最新一次提交的审核状态
     * @return \EasyWeChat\Support\Collection
     */
    public function get_latest_auditstatus()
    {
        return $this->parseJSON('get', [self::API_GET_LATSEST_AUDITSTATUS,]);
    }
    /**
     * 发布已通过审核的小程序
     * @return \EasyWeChat\Support\Collection
     */
    public function release()
    {
        $data ='{}';
        return $this->parseJSON('json', [self::API_GET_RELEASE,$data]);
    }

    /**
     * 修改线上小程序可见状态
     * @param  string  $action [设置可访问状态，发布后默认可访问，close为不可见，open为可见]
     * @return \EasyWeChat\Support\Collection
     */
    public function change_visitstatus($action)
    {
        return $this->parseJSON('json', [self::API_CHANGE_VISITSTATUS,['action' => $action]]);
    }


    /**
     * 获取模板库数据
     * @param  integer $offset [offset和count用于分页，表示从offset开始，拉取count条记录，offset从0开始，count最大为20。]
     * @param  integer $count  [offset和count用于分页，表示从offset开始，拉取count条记录，offset从0开始，count最大为20。]
     * @return \EasyWeChat\Support\Collection
     */
    public function template_library_list($offset=0, $count=5)
    {
        $params = [
            'offset' => $offset,
            'count' => $count,
        ];
        return $this->parseJSON('json', [self::API_TEMPLATE_LIBRARY_LIST,$params]);
    }
    /**
     * 获取模板库某个模板标题下关键词库
     * @param  string $id [模板标题id，可通过接口获取，也可登录小程序后台查看获取]
     * @return \EasyWeChat\Support\Collection
     */
    public function template_library_get($id)
    {
        return $this->parseJSON('json', [self::API_TEMPLATE_LIBRARY_GET,['id' => $id]]);
    }
    /**
     * 组合模板并添加至帐号下的个人模板库
     * @param  string $id [模板标题id]
     * @param  array $keyword_id_list  [开发者自行组合好的模板关键词列表，关键词顺序可以自由搭配（例如[3,5,4]或[4,5,3]），最多支持10个关键词组合]
     * @return \EasyWeChat\Support\Collection
     */
    public function template_add($id,$keyword_id_list)
    {
        $params = [
            'id' => $id,
            'keyword_id_list' => $keyword_id_list,
        ];
        return $this->parseJSON('json', [self::API_TEMPLATE_ADD,$params]);
    }

    /**
     * 获取帐号下已存在的模板列表
     * @param  integer $offset [offset和count用于分页，表示从offset开始，拉取count条记录，offset从0开始，count最大为20。]
     * @param  integer $count  [offset和count用于分页，表示从offset开始，拉取count条记录，offset从0开始，count最大为20。]
     * @return \EasyWeChat\Support\Collection
     */
    public function template_list($offset=0, $count=5)
    {
        $params = [
            'offset' => $offset,
            'count' => $count,
        ];
        return $this->parseJSON('json', [self::API_TEMPLATE_LIST,$params]);
    }

    /**
     * 删除帐号下的某个模板
     * @param  string $template_id [要删除的模板id]
     * @return \EasyWeChat\Support\Collection
     */
    public function template_del($template_id)
    {
        return $this->parseJSON('json', [self::API_TEMPLATE_DEL,['template_id' => $template_id]]);
    }
    /**
     * 发送模板消息
     * @param  string $template_id [要删除的模板id]
     * @return 
     */
    /**
     *  发送模板消息
     * @param  [string] $touser           [接收者（用户）的 openid]
     * @param  [string] $template_id      [所需下发的模板消息的id]
     * @param  [string] $form_id          [表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id]
     * @param  [array] $data              [模板内容，不填则下发空模板]
     * @param  [string] $page             [点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。]
     * @param  [string] $color            [模板内容字体的颜色，不填默认黑色]
     * @param  [string] $emphasis_keyword [模板需要放大的关键词，不填则默认无放大]
     * @return \EasyWeChat\Support\Collection
     */
    public function template_send($touser,$template_id,$form_id,$data = null,$page = null,$color = null,$emphasis_keyword = null)
    {
        $params = [
            'touser' => $touser,
            'template_id' => $template_id,
            'form_id' => $form_id,
            'data' => $data,
        ];
        $page?$params['page'] = $page:'';
        $color?$params['color'] = $color:'';
        $emphasis_keyword?$params['emphasis_keyword'] = $emphasis_keyword:'';

        return $this->parseJSON('json', [self::API_TEMPLATE_SEND,$params]);
        //save_log('../log/equipment',json_encode($res));
        //return $res;
    }

    /**
     * 设置最低基础库版本
     * @return [type] [description]
     */
    public function setweappsupportversion($version)
    {
        return $this->parseJSON('json', [self::API_SET_WEAPPS_SUPPORT_VERSION,['version' => $version]]);
    }

    /**
     * 小程序版本回退
     * @return \EasyWeChat\Support\Collection
     */
    public function revert_code_release()
    {
        return $this->parseJSON('get', [self::API_REVERT_CODE_RELEASE,]);
    }
    

    /**
     * 小程序审核撤回
     * @return \EasyWeChat\Support\Collection
     */
    public function undo_code_audit()
    {
        return $this->parseJSON('get', [self::API_UNDO_CODE_AUDIT,]);
    }


}
