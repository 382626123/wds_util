<?php
namespace Wds\Util;

/**
 *环信
 */
class WdsHuanxin
{
    private string $client_id;

    private string $base_url;

    private string $client_secret;

    /**
     * @param string $client_id
     * @param string $base_url  //http://a1.easemob.com/1187250806225242/demo/  环信的请求域名 需要/结尾
     * @param string $client_secret
     *
     */
    public function __construct(string $client_id,string $base_url,string $client_secret){

        $this->base_url = $base_url;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }


    /**
     * @return mixed
     * 获取token 使用吧token缓存起来
     * https://doc.easemob.com/document/server-side/easemob_app_token.html
     * 返回值
     * access_token    String    有效的 Token 字符串。
     * expires_in    Long    Token 有效时间，单位为秒，在有效期内不需要重复获取。
     * application    String    当前 App 的 UUID 值。
     */
    public function getToken(){

        // 请求 URL
        $url = $this->base_url.'token';
        // 请求头
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        // 请求数据
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'ttl' => 1024000
        ];
        return WdsUtil::httpRequest($url, "GET",$data, $headers);
    }

    /**
     * @param $userInfo
     * @return false|mixed
     * 创建用户
     */
    public function createUser($appToken,$userInfo){
        try {
            $url = $this->base_url.'users';
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $appToken  // 拼接 Token 到认证头
            ];

            // 请求数据
            $data = [
                'username' => $userInfo['username'],
                'password' => $userInfo['password']
            ];

            $result = WdsUtil::httpRequest($url,'POST',$data,$headers);

            if($result == false){
                return false;
            }
            return $result;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $data
     * @return array|bool
     * 修改用户信息
     * https://doc.easemob.com/document/server-side/userprofile.html#%E8%AE%BE%E7%BD%AE%E7%94%A8%E6%88%B7%E5%B1%9E%E6%80%A7
     */
    public function updateUserInfo($appToken,$data){
        $url = $this->base_url.'metadata/user/'.$data['hx_username'];

        $headers = [
            'Authorization: Bearer ' . $appToken  // 拼接 Token 到认证头
        ];
        // 请求数据
        $data = [
            'nickname' => $data['nickname'],
            'avatarurl' => $data['avatarurl'],
//            1：男；- 2：女；- 0：未知。
            'gender' => $data['gender'],
            'birth' => $data['birth'],
        ];
        $result = WdsUtil::httpRequest($url,'PUT',$data,$headers);

        if($result == false){
            return false;
        }
        return $result;
    }

    /**
     * @param string $groupName 群组名称，最大长度为 128 字符。
     * * @param string $owner 群主的用户 ID。
     * * @param string $avatar 群组头像的 URL，最大长度为 1024 字符。
     * * @param string $description 群组描述，最大长度为 512 字符。
     * * @param string $public 是否是公开群。公开群可以被搜索到，用户可以申请加入公开群；私有群无法被搜索到，因此需要群主或群管理员添加，用户才可以加入。
     * * - true：公开群；
     * * - false：私有群。
     * * 创建群组
     * 123456
     *
     * @return false|mixed
     */
    public function chatGroups($appToken,$data){
        $url = $this->base_url.'chatgroups';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $appToken  // 拼接 Token 到认证头
        ];

        $row = [
            'groupname' => $data['groupname'],
            'avatar' => $data['avatar'],
            'public' => true,
            'description' => $data['description'],
            'owner' => $data['owner']
        ];

        $result = WdsUtil::httpRequest($url,"GET", $row, $headers);
        if($result == false){
            return false;
        }
        return $result;

    }

    /**
     * @param $data
     * @return array|bool
     * 修改群组信息
     */
    public function updateGroups($appToken,$data){
        $url = $this->base_url.'chatgroups/'.$data['group_id'];

        $headers = [
            'Authorization: Bearer ' . $appToken  // 拼接 Token 到认证头
        ];

        // 请求数据
        $row = [
            'groupname' => $data['groupname'],
            'description' => $data['description'],
            'public' => $data['public'],
            'avatar' => $data['avatar'],
        ];
        $result = WdsUtil::httpRequest($url,'PUT',$row,$headers);

        if($result == false){
            return false;
        }
        return $result;
    }
}