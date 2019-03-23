<?php
date_default_timezone_set("GMT");

//accessKeyId 和 accessSecrec 在 https://developer.godaddy.com/getstarted 申请 
define("accessKeyId", "");
define("accessSecrec", "");

$type = 'TXT';

$domainarray = GodaddyDns::getDomain($argv[1]);
//证书申请域名
$selfdomain  = ($domainarray[0] == "") ? $argv[2] : $argv[2].".".$domainarray[0];
//根域名
$domain      = $domainarray[1];

$obj = new GodaddyDns(accessKeyId, accessSecrec, $domain);

$data = $obj->GetDNSRecord($domain, $type);
$code = $data['httpCode'];
if ($code != 200) {
    echo 'code='.$code;
    echo '<br/>';
    echo $data['result'];
    exit;
}
$data_obj = json_decode($data['result']);
$count    = count($data_obj);
if ($count <= 0) {

    $r = $obj->CreateDNSRecord($domain, $selfdomain, $argv[3], $type);
} else {

    $r = $obj->UpdateDNSRecord($domain, $selfdomain, $argv[3], $type); //$domain,$name,$value,$recordType='TXT
}

class GodaddyDns
{
    private $accessKeyId  = null;
    private $accessSecrec = null;
    private $DomainName   = null;
    private $Host         = "";
    private $Path         = "";

    public function __construct($accessKeyId, $accessSecrec, $domain = "")
    {
        $this->accessKeyId  = $accessKeyId;
        $this->accessSecrec = $accessSecrec;
        $this->DomainName   = $domain;
    }
    /*
      根据域名返回主机名和二级域名
     */
    public static function getDomain($domain)
    {

        //常见根域名 【https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains】
        // 【http://www.seobythesea.com/2006/01/googles-most-popular-and-least-popular-top-level-domains/】

        $arr[] = ".co.jp";
        $arr[] = ".com.tw";
        $arr[] = ".net";
        $arr[] = ".com";
        $arr[] = ".com.cn";
        $arr[] = ".org";
        $arr[] = ".cn";
        $arr[] = ".gov";
        $arr[] = ".net.cn";
        $arr[] = ".io";
        $arr[] = ".top";
        $arr[] = ".me";
        $arr[] = ".int";
        $arr[] = ".edu";
        $arr[] = ".link";
        $arr[] = ".uk";
        $arr[] = ".hk";
 
        //二级域名
        $seconddomain = "";
        //子域名
        $selfdomain   = "";
        //根域名
        $rootdomain   = "";
        foreach ($arr as $k => $v) {
            $pos = stripos($domain, $v);
            if ($pos) {
                $rootdomain   = substr($domain, $pos);
                $s            = explode(".", substr($domain, 0, $pos));
                $seconddomain = $s[count($s) - 1].$rootdomain;
                for ($i = 0; $i < count($s) - 1; $i++)
                    $selfdomain .= $s[$i];
                break;
            }
        }
        //echo $seconddomain ;exit;
        if ($rootdomain == "") {
            $seconddomain = $domain;
            $selfdomain   = "";
        }
        return array($selfdomain, $seconddomain);
    }

    public function error($code, $str)
    {
        echo "操作错误:".$code.":".$str;
        exit;
    }

    private function curl($url, $header = '', $data = '', $method = 'get')
    {
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置提交的字符串
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array(
            'result' => $result,
            'httpCode' => $httpCode
        );
    }

    private function out($msg)
    {
        return json_decode($msg, true);
    }

    public function GetDNSRecord($domain, $recordType = 'TXT')
    {
        $url    = "https://api.godaddy.com/v1/domains/$domain/records/$recordType/_acme-challenge";
        $header = ['accept: application/json', 'authorization:sso-key '.$this->accessKeyId.':'.$this->accessSecrec];
        return $this->curl($url, $header);
    }

    public function UpdateDNSRecord($domain, $name, $value, $recordType = 'TXT')
    {
        $url    = "https://api.godaddy.com/v1/domains/$domain/records/$recordType/$name";
        $header = ['accept: application/json', 'Content-Type: application/json',
            'authorization:sso-key '.$this->accessKeyId.':'.$this->accessSecrec];
        $data   = array(
            array(
                'data' => $value,
                'name' => $name,
                'ttl' => 3600,
                'type' => $recordType)
        );
        return $this->curl($url, $header, json_encode($data), 'put');
    }

    public function CreateDNSRecord($domain, $name, $value, $recordType = 'TXT')
    {
        $url    = "https://api.godaddy.com/v1/domains/$domain/records";
        $header = ['accept: application/json', 'Content-Type: application/json',
            'authorization:sso-key '.$this->accessKeyId.':'.$this->accessSecrec];
        $data   = array(
            array(
                'data' => $value,
                'name' => $name,
                'ttl' => 3600,
                'type' => $recordType)
        );
        return $this->curl($url, $header, json_encode($data), 'PATCH');
    }
}
