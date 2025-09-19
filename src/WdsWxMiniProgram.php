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
        $res = json_decode($this->httpRequest($url));
        $access_token = $res->access_token;
        return $access_token;
    }


    private function httpRequest(
        string $url,
        string $method = 'GET',
        array  $data = [],
        array  $headers = [],
        bool   $sslVerify = false
    )
    {
        // 初始化cURL
        $ch = curl_init();

        // 标准化请求方法（转为大写）
        $method = strtoupper($method);

        // 设置基础选项
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HTTPHEADER => $headers
        ];

        // 根据请求方法设置特定选项
        switch ($method) {
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
                break;

            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $options[CURLOPT_POSTFIELDS] = http_build_query($data, JSON_UNESCAPED_UNICODE);
                break;

            case 'GET':
                // GET请求将数据拼接到URL
                if (!empty($data)) {
                    $queryString = json_encode($data);
                    $options[CURLOPT_URL] = $url . (strpos($url, '?') === false ? '?' : '&') . $queryString;
                }
                break;

            // 可根据需要添加其他方法（DELETE等）
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
                }
                break;

            default:
                curl_close($ch);
                throw new \InvalidArgumentException("不支持的请求方法: {$method}");
        }

        // 设置cURL选项
        curl_setopt_array($ch, $options);

        // 执行请求
        $response = curl_exec($ch);

        // 检查cURL错误
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("请求失败: " . $error);
        }

        // 获取响应状态码
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 解析JSON响应
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("响应解析失败: " . json_last_error_msg());
        }

        // 根据状态码返回结果
        return $httpCode === 200 ? $responseData : false;
    }

}