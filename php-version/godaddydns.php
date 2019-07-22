<?php
date_default_timezone_set("GMT");

$dir = dirname(dirname(__FILE__));
#根域名列表文件，如果自己的根域名不存在该文件中，可自行添加
$domainfile = $dir . DIRECTORY_SEPARATOR . "domain.ini";

# 第一个参数是 action，代表 (add/clean) 
# 第二个参数是域名 
# 第三个参数是主机名（第三个参数+第二个参数组合起来就是要添加的 TXT 记录）
# 第四个参数是 TXT 记录值
# 第五个参数是 APPKEY
# 第六个参数是 APPTOKEN

echo "域名 API 调用开始\n";
print_r($argv);
if (count($argv) < 7) {
    echo "参数有误\n";
    exit;
}
echo $argv[1]."-".$argv[2]."-".$argv[3]."-".$argv[4]."-".$argv[5]."-".$argv[6]."\n";

$domainarray = GodaddyDns::getDomain($argv[2]);
$selfdomain  = ($domainarray[0] == "") ? $argv[3] : $argv[3].".".$domainarray[0];

/*

  $obj = new GodaddyDns($argv[5], $argv[6], $domainarray[1]);
  $data = $obj->getDomains();
  $data_obj = json_decode($data['result']);
  $code = $data['httpCode'];
  test :php  godaddydns.php add yudadan.com  v k
 */

$obj = new GodaddyDns($argv[5], $argv[6], $domainarray[1]);

switch ($argv[1]) {
    case "clean":
        //api 不包含该操作
        break;

    case "add":
        //$data     = $obj->GetDNSRecord($domainarray[1], $selfdomain);
        //$data_obj = json_decode($data['result']);
        //$count    = count($data_obj);
        //if ($count > 0) {

        //    $data = $obj->UpdateDNSRecord($domainarray[1], $selfdomain, $argv[4]);
        //} else {
            $data = $obj->CreateDNSRecord($domainarray[1], $selfdomain, $argv[4]);
        //}
        if ($data["httpCode"] != 200) {
            $message = json_decode($data["result"], true);
            echo "域名处理失败-".$message["message"];
            exit;
        }
        break;
}

echo "域名 API 调用结束\n";

//    $r = $obj->UpdateDNSRecord($domain, $selfdomain, $argv[3], $type);

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

	global $domainfile;
	$tmp = file($domainfile);
	$arr = array();
	foreach ($tmp as $k=>$v) {
		$v = trim($v);
		if ($v!="")
			$arr[]= "." . $v;
	}

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
                    $selfdomain .= $s[$i] . ".";
		$selfdomain = substr($selfdomain,0,strlen($selfdomain)-1);
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

    public function getDomains()
    {

        $url    = "https://api.godaddy.com/v1/domains";
        $header = ['accept: application/json', 'authorization:sso-key '.$this->accessKeyId.':'.$this->accessSecrec];
        return $this->curl($url, $header);
    }

    public function delRecords($domain)
    {

        $url    = "https://api.godaddy.com/v1/domains/$domain";
        $header = ['accept: application/json', 'Content-Type: application/json',
            'authorization:sso-key '.$this->accessKeyId.':'.$this->accessSecrec];

        return $this->curl($url, $header, '', 'delete');
    }

    public function GetDNSRecord($domain, $record, $recordType = 'TXT')
    {
        $url    = "https://api.godaddy.com/v1/domains/$domain/records/$recordType/$record";
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
