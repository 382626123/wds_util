<?php
namespace Wds\Util;

class MyUtil{

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
}