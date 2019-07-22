<?php
date_default_timezone_set("GMT");

$dir = dirname(dirname(__FILE__));
#根域名列表文件，如果自己的根域名不存在该文件中，可自行添加
$domainfile = $dir . DIRECTORY_SEPARATOR . "domain.ini";

/*
  $obj = new AliDns("LTAIkLV6coSSKklZ", "YEGDVHQV4oBC6AGQM9BWaHStUtNE5M", "simplehttps.com1");
  $data = $obj->DescribeDomainRecords();
  if ($data["httpcode"]!=200) {
  echo "aly dns 域名获取失败-" . $data["Code"] . ":" . $data["Message"];
  }
  //显示所有
  //$data = $obj->DescribeDomainRecords();

  //增加解析
  //$data= $obj->AddDomainRecord("TXT", "test", "test");

  //修改解析
  //$data = $obj->UpdateDomainRecord("3965724468724736","TXT", "test", "test2");

  //删除解析
  //$data = $obj->DescribeDomainRecords();
  //$data = $data["DomainRecords"]["Record"];
  //if (is_array($data)) {
  //foreach ($data as $v) {
  //if ($v["RR"] == "test") {
  //$obj->DeleteDomainRecord($v["RecordId"]);
  //}
  //}
  //}
 */


/*
  example:

  php alydns.php add  "simplehttps.com" "dnsv" "dnsk"  APPKEY APPTOKEN
 */

########## 配合 cerbot 运行
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

$domainarray = AliDns::getDomain($argv[2]);
$selfdomain  = ($domainarray[0] == "") ? $argv[3] : $argv[3].".".$domainarray[0];

$obj = new AliDns($argv[5], $argv[6], $domainarray[1]);

switch ($argv[1]) {
case "clean":
	$data = $obj->DescribeDomainRecords();
	$data = $data["DomainRecords"]["Record"];
	if (is_array($data)) {
		foreach ($data as $v) {
			if ($v["RR"] == $selfdomain) {
				$data = $obj->DeleteDomainRecord($v["RecordId"]);
				if ($data["httpcode"] != 200) {
					echo "aly dns 域名删除失败-".$data["Code"].":".$data["Message"];
					exit;
				}
			}
		}
	}
	break;

case "add":
	$data = $obj->AddDomainRecord("TXT", $selfdomain, $argv[4]);

	if ($data["httpcode"] != 200) {
		echo "aly dns 域名增加失败-".$data["Code"].":".$data["Message"];
		exit;
	}
	break;
}

echo "域名 API 调用结束\n";

############ Class 定义

class AliDns
{
	private $accessKeyId  = null;
	private $accessSecrec = null;
	private $DomainName   = null;

	public function __construct($accessKeyId, $accessSecrec, $domain)
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

		//https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains
		//常见根域名
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

	public function DescribeDomainRecords()
	{
		$requestParams = array(
			"Action" => "DescribeDomainRecords"
		);
		$val           = $this->send($requestParams);

		return $this->out($val);
	}

	public function UpdateDomainRecord($id, $type, $rr, $value)
	{
		$requestParams = array(
			"Action" => "UpdateDomainRecord",
			"RecordId" => $id,
			"RR" => $rr,
			"Type" => $type,
			"Value" => $value,
		);
		$val           = $this->send($requestParams);
		return $this->out($val);
	}

	public function DeleteDomainRecord($id)
	{
		$requestParams = array(
			"Action" => "DeleteDomainRecord",
			"RecordId" => $id,
		);
		$val           = $this->send($requestParams);
		return $this->out($val);
	}

	public function AddDomainRecord($type, $rr, $value)
	{

		$requestParams = array(
			"Action" => "AddDomainRecord",
			"RR" => $rr,
			"Type" => $type,
			"Value" => $value,
		);
		$val           = $this->send($requestParams);
		return $this->out($val);
	}

	private function send($requestParams)
	{
		$publicParams = array(
			"DomainName" => $this->DomainName,
			"Format" => "JSON",
			"Version" => "2015-01-09",
			"AccessKeyId" => $this->accessKeyId,
			"Timestamp" => date("Y-m-d\TH:i:s\Z"),
			"SignatureMethod" => "HMAC-SHA1",
			"SignatureVersion" => "1.0",
			"SignatureNonce" => substr(md5(rand(1, 99999999)), rand(1, 9), 14),
		);

		$params              = array_merge($publicParams, $requestParams);
		$params['Signature'] = $this->sign($params, $this->accessSecrec);
		$uri                 = http_build_query($params);
		$url                 = 'http://alidns.aliyuncs.com/?'.$uri;
		return $this->curl($url);
	}

	private function sign($params, $accessSecrec, $method = "GET")
	{
		ksort($params);
		$stringToSign = strtoupper($method).'&'.$this->percentEncode('/').'&';

		$tmp = "";
		foreach ($params as $key => $val) {
			$tmp .= '&'.$this->percentEncode($key).'='.$this->percentEncode($val);
		}
		$tmp          = trim($tmp, '&');
		$stringToSign = $stringToSign.$this->percentEncode($tmp);

		$key  = $accessSecrec.'&';
		$hmac = hash_hmac("sha1", $stringToSign, $key, true);

		return base64_encode($hmac);
	}

	private function percentEncode($value = null)
	{
		$en = urlencode($value);
		$en = str_replace("+", "%20", $en);
		$en = str_replace("*", "%2A", $en);
		$en = str_replace("%7E", "~", $en);
		return $en;
	}

	private function curl($url)
	{
		$ch     = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_HEADER, 1);
		//curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		$result = curl_exec($ch);
		$info   = curl_getinfo($ch);

		curl_close($ch);
		return array($info["http_code"], $result);
	}

	private function out($arr)
	{
		$t             = json_decode($arr[1], true);
		$t["httpcode"] = $arr[0];
		return $t;
	}
}
