<?php

date_default_timezone_set("GMT");

//这两个值需要去阿里云申请
define("accessKeyId", "");
define("accessSecrec", "");

/*
//$obj = new AliDns(accessKeyId, accessSecrec, "newyingyong.cn");

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

php alydns.php  "simplehttps.com" "test" "test2" 
*/

########## 配合 cerbot 运行 

echo $argv[1] . "-" . $argv[2] . "-" . $argv[3];

$domainarray = AliDns::getDomain($argv[1]);
$selfdomain = ($domainarray[0]=="")?$argv[2]:$argv[2] . "." . $domainarray[0];

$obj = new AliDns(accessKeyId, accessSecrec, $domainarray[1]);
$data = $obj->DescribeDomainRecords();
$data = $data["DomainRecords"]["Record"];
if (is_array($data)) {
      foreach ($data as $v) {
           if ($v["RR"] == $selfdomain) {
               $res = $obj->DeleteDomainRecord($v["RecordId"]);
           }
      }
} 

$res = $obj->AddDomainRecord("TXT", $selfdomain,$argv[3]);

############ Class 定义

class AliDns {
    private $accessKeyId = null;
    private $accessSecrec = null;
    private $DomainName = null;


    public function __construct($accessKeyId, $accessSecrec, $domain) {
        $this->accessKeyId = $accessKeyId;
        $this->accessSecrec = $accessSecrec;
        $this->DomainName = $domain;
    }
    /*
	根据域名返回主机名和二级域名
    */
    public static function getDomain($domain) {
	
	//https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains	
    	//常见根域名
    	$arr[]=".co.jp";
    	$arr[]=".com.tw";
    	$arr[]=".net";
    	$arr[]=".com";
    	$arr[]=".com.cn";
    	$arr[]=".org";
    	$arr[]=".cn";
    	$arr[]=".gov";
    	$arr[]=".net.cn";
    	$arr[]=".io";
    	$arr[]=".top";
    	$arr[]=".me";
    	$arr[]=".int";
    	$arr[]=".edu";
    	$arr[]=".link";
	$arr[]=".uk";
	$arr[]=".hk";

    	//二级域名
    	$seconddomain ="";
    	//子域名
    	$selfdomain = "";
    	//根域名
    	$rootdomain = "";
    	foreach ($arr as $k=>$v) {
        	$pos = stripos($domain,$v);
        	if ($pos) {
            	$rootdomain = substr($domain,$pos);
            	$s = explode(".",substr($domain,0,$pos));
            	$seconddomain =  $s[count($s)-1] . $rootdomain;
            	for ($i=0;$i<count($s)-1;$i++)
                    	$selfdomain .= $s[$i];
            	break;
        	}	
    	}
    	//echo $seconddomain ;exit;
    	if ($rootdomain=="") {
        	$seconddomain = $domain;
        	$selfdomain = "";
    	}
    	return array($selfdomain,$seconddomain);

    }

    public function DescribeDomainRecords() {
        $requestParams = array(
             "Action" => "DescribeDomainRecords"
        );
        $val = $this->send($requestParams);
        return $this->out($val);
    }


    public function UpdateDomainRecord($id, $type, $rr,$value){
        $requestParams = array(
            "Action" => "UpdateDomainRecord",
            "RecordId" => $id,
            "RR" => $rr,
            "Type" => $type,
            "Value" => $value,
        );
        $val = $this->send($requestParams);
        return $this->out($val);
    }
    public function DeleteDomainRecord($id) {
	$requestParams = array(
            "Action" => "DeleteDomainRecord",
            "RecordId" => $id,
        );
        $val = $this->send($requestParams);
        return $this->out($val);
    }

    public function AddDomainRecord($type, $rr, $value) {

        $requestParams = array(
            "Action" => "AddDomainRecord",
            "RR" => $rr,
            "Type" => $type,
            "Value" => $value,
        );
        $val = $this->send($requestParams);
        return $this->out($val);

    }

    private function send($requestParams) {
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

        $params = array_merge($publicParams, $requestParams);
        $params['Signature'] = $this->sign($params, $this->accessSecrec);
        $uri = http_build_query($params);
        $url = 'http://alidns.aliyuncs.com/?'.$uri;
        return $this->curl($url);
    }



    private function sign($params, $accessSecrec, $method = "GET") {
        ksort($params);
        $stringToSign = strtoupper($method).'&'.$this->percentEncode('/').'&';

        $tmp = "";
        foreach($params as $key => $val){
            $tmp .= '&'.$this->percentEncode($key).'='.$this->percentEncode($val);
        }
        $tmp = trim($tmp, '&');
        $stringToSign = $stringToSign.$this->percentEncode($tmp);

        $key = $accessSecrec.'&';
        $hmac = hash_hmac("sha1", $stringToSign, $key, true);

        return base64_encode($hmac);
    }


    private function percentEncode($value = null){
        $en = urlencode($value);
        $en = str_replace("+", "%20", $en);
        $en = str_replace("*", "%2A", $en);
        $en = str_replace("%7E", "~", $en);
        return $en;
    }

    private function curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        $result = curl_exec ($ch);
        curl_close($ch);
        return $result;
    }

    private function out($msg) {
        return json_decode($msg, true);
    }
}

