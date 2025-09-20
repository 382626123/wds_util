<?php
namespace Wds\Util;

class WdsWxMiniProgram
{
    protected  $AppId;
    protected  $AppSecret;
    protected  $ApiKey;
    /**
     * WxMiniProgram constructor.
     * @param array $config
     * 初始化
     */
    public function __construct(string $AppId, string $AppSecret, string $ApiKey)
    {
        $this->AppId = $AppId;
        $this->AppSecret = $AppSecret;
        $this->ApiKey = $ApiKey;
    }

    /**
     * @return mixed
     * 获取token token有时效 自己实现 用redis或者数据库缓存
     */
    private function getAccessToken()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->AppId&secret=$this->AppSecret";
        $res = json_decode(WdsUtil::httpRequest($url));
        if(!empty($res->access_token))
            return $res->access_token;
    }

    /**
     * @param $media_url
     * @param $openid
     * @param int $media_type
     * @param int $scene
     * @return false|mixed
     * 小程序素材审核
     */
    public function mediaCheckAsync($access_token,$media_url, $openid, int $media_type = 2, int $scene = 3){
        $url = "https://api.weixin.qq.com/wxa/media_check_async?access_token=$access_token";
        $row= [
            'media_url' => $media_url,
            'media_type' => $media_type,
            'version' => 2,
            'openid' => $openid,
            'scene' => $scene,
        ];
        return WdsUtil::httpRequest($url,'POST',$row);
    }

    /**
     * @param $content
     * @param $openid
     * @param int $scene  //场景枚举值1 资料；2 评论；3 论坛；4 社交日志
     * @return void
     * 文本内容安全识别
     * https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/sec-center/sec-check/msgSecCheck.html
     * 命中标签枚举值，100 正常；10001 广告；20001 时政；20002 色情；20003 辱骂；20006 违法犯罪；20008 欺诈；20012 低俗；20013 版权；21000 其他
     */
    public function msgSecCheck($access_token,$content, $openid, int $scene = 3):array{
        $url = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token=$access_token";

        $row= [
            'content' => $content,
            'version' => 2,
            'openid' => $openid,
            'scene' => $scene,
        ];

        $result =  WdsUtil::httpRequest($url,"POST",$row);

        if($result['errmsg'] == 'ok'){
            switch ($result['detail'][0]['label']) {
                case 100:
                    return ['status' => true, 'msg' => '正常'];
                case 10001:
                    return ['status' => false, 'msg' => '广告'];
                case 20001:
                    return ['status' => false, 'msg' => '时政'];
                case 20002:
                    return ['status' => false, 'msg' => '色情'];
                case 20003:
                    return ['status' => false, 'msg' => '辱骂'];
                case 20006:
                    return ['status' => false, 'msg' => '违法犯罪'];
                case 20008:
                    return ['status' => false, 'msg' => '欺诈'];
                case 20012:
                    return ['status' => false, 'msg' => '低俗'];
                case 20013:
                    return ['status' => false, 'msg' => '版权'];
                case 21000:
                    return ['status' => false, 'msg' => '其他'];
                default:
                    return ['status' => false, 'msg' => '未知标签值'];
            }
        }

        return ['status' => false ,'msg' => '请求失败'];
    }


    /**
     * @param $path
     * @param $imgDir static/code/  存放地址
     * @return string|void
     * 生成小程序二维码
     */
    public function createQRCode($access_token,$path,$imgDir = "static/code/")
    {
        $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$access_token";
        $row['path'] = $path;
        $row['width'] = 430;
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        //必须设置头消息
        $headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");

        $jpg =  WdsUtil::httpRequest($url,"POST",$row,$headers);

        //生成图片
        $filename= date('Y-m-d',time()) . date('His',time()) . mt_rand(1000,100000) .".jpg";///要生成的图片名字

        $file = fopen($imgDir.$filename,"w");//打开文件准备写入
        fwrite($file,$jpg);//写入
        fclose($file);//关闭
        $filePath = $imgDir.$filename;
        //图片是否存在
        if(file_exists($filePath))
            return  $filePath;
    }

    /**
     * @param $page
     * @param $scene
     * 生成小程序码，可接受页面参数较短，生成个数不受限。
     *最后返回图片地址
     */
    public function getwxacodeunlimit($access_token,$scene,$page,$is_hyaline = true)
    {
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$access_token";

        $row['scene'] = $scene;
        $row['page'] = $page;
        $row['width'] = 150;
        $row['auto_color'] = false;
        $row['is_hyaline'] = $is_hyaline; //是否需要透明

        //必须设置头消息
        $headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");

        $jpg =  WdsUtil::httpRequest($url,"POST",$row,$headers);
        //生成图片
        $imgDir = 'static/code/';
        $filename= date('Y-m-d',time()) . date('His',time()) . mt_rand(1000,100000) .".png";///要生成的图片名字

        $file = fopen($imgDir.$filename,"w");//打开文件准备写入
        fwrite($file,$jpg);//写入
        fclose($file);//关闭
        $filePath = $imgDir.$filename;
        //图片是否存在
        if(file_exists($filePath))
            return  $filePath;
    }


    /**
     * @param $access_token
     * @param $openId
     * @param $templateId
     * @param $data
     * @param $page
     * @param $miniprogramState
     * @return bool
     * 发送消息模板
     */
    public function sendTemplate($access_token,$openId,$templateId,$data,$page,$miniprogramState)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$access_token"; //小程序的订阅消息
        //文档https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/subscribe-message/subscribeMessage.send.html

        $row['touser'] = $openId;
        $row['template_id'] = $templateId;
        $row['miniprogram_state'] = $miniprogramState;
        $row['data'] = $data;
        $row['page'] = $page;
        $result = WdsUtil::httpRequest($url,"POST",$row);
        $result = WdsUtil::httpRequest($url,"POST",$row);

        if($result['errmsg'] == 'ok')
            return true;
        else
            return false;

    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @return int 成功0，失败返回对应的错误码
     */

    public function decryptData( $encryptedData, $iv,$session_key)
    {
        if (strlen($session_key) != 24) {
            return false;
        }
        $aesKey = base64_decode($session_key);

        if (strlen($iv) != 24) {
            return false;
        }
        $aesIV = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);

        $result= openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode( $result,true);
        if( $dataObj  == NULL )
        {
            return false;
        }
        if( $dataObj['watermark']['appid'] != $this->AppId )
        {
            return false;
        }
        return $dataObj;
    }

    /**
     *
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws \Exception
     * son数据，可直接填入js函数作为参数
     */
    public function GetPayParameters($UnifiedOrderResult)
    {
        if(!array_key_exists("appid", $UnifiedOrderResult) || !array_key_exists("prepay_id", $UnifiedOrderResult) || $UnifiedOrderResult['prepay_id'] == "")
        {
            throw new \Exception("参数错误");
        }
        $time = time().'';
        $SignParameters = [
            'appId'     => $this->AppId,
            'timeStamp' => "$time",
            'nonceStr'  => WdsUtil::getNonceStr(),
            'package'   => 'prepay_id='.$UnifiedOrderResult['prepay_id'],
            'signType'  => 'MD5'
        ];

        $data['paySign'] = $this->MakeSign($SignParameters);
        $data['timeStamp'] = "$time";
        $data['nonceStr'] = $SignParameters['nonceStr'];
        $data['package'] = "prepay_id=" . $UnifiedOrderResult['prepay_id'];
        $data['signType'] = 'MD5';
        return json_encode($data);
    }

    /**
     * @param $SignParameters
     * @return string
     * 生成签名
     */
    public function MakeSign($SignParameters):string
    {
        //签名步骤一：按字典序排序参数
        ksort($SignParameters);
        $string = WdsUtil::toUrlParams($SignParameters);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->ApiKey;
        //签名步骤三：MD5加密
        return md5($string);
    }

}