<?php
namespace Wds\Util;

class WdsUtil{


    /**
     * @param int $length
     * @return string
     * @throws \Exception
     * 获取随机数
     */
    public static function uniqidReal(int $length = 13):string
    {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($length / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            throw new \Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $length);
    }


    /**
     * @param string $total 总金额
     * @param int $parts 分割次数
     * @param int $scale
     * @return array
     * 累进舍入算法 - 先均分再补偿最后一份
     */
    public static function progressiveRounding(string $total, int $parts, int $scale = 2): array
    {
        if ($parts <= 0) {
            throw new \InvalidArgumentException("分割次数必须大于 0");
        }

        // 初始均分（使用截断）
        $initialShare = bcdiv($total, (string)$parts, $scale);
        $results = array_fill(0, $parts, $initialShare);

        // 计算前 $parts-1 份的总和
        $sum = '0';
        for ($i = 0; $i < $parts - 1; $i++) {
            $sum = bcadd($sum, $results[$i], $scale);
        }

        // 最后一份使用总金额减去前面的累加值
        $results[$parts - 1] = bcsub($total, $sum, $scale);

        return $results;
    }

    /**
     * @return mixed
     * 获取用户ip
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * @param $ip
     * @return array|false
     * 根据ip获取i信息
     */
    public static function  getLocationByApi(string $ip)
    {
        // 方案1：使用IPIP.net的免费API（需注册获取Token）
        // $token = 'your_token_here'; // 注册后获取
        //  $url = "https://api.ipip.net/address?ip={$ip}&token={$token}";

        // 方案2：使用淘宝IP地址库（免费但信息简略）
        $url = "https://ip.taobao.com/outGetIpInfo?ip={$ip}&accessKey=alibaba-inc";


        // 发送HTTP请求
        $ch = curl_init();
        // 设置cURL选项
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true, // 允许重定向
            CURLOPT_SSL_VERIFYPEER => false, // 不验证SSL证书
            CURLOPT_SSL_VERIFYHOST => false, // 不验证证书中的域名
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        // 解析结果
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['code']) && $data['code'] == 0) {
                // 根据API返回格式调整
                return [
                    'country' => $data['data']['country'],
                    'region' => $data['data']['region'],
                    'city' => $data['data']['city'],
                    'isp' => $data['data']['isp']
                ];
            }
        }

        return false;
    }



    public static function httpRequest(
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

    /**
     * 格式化参数格式化成url参数
     */
    public static function toUrlParams($param): string
    {
        $buff = "";
        foreach ($param as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        return trim($buff, "&");
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return  string
     * 产生的随机字符串
     */
    public static function getNonceStr(int $length = 32):string
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return strtoupper($str);
    }
}