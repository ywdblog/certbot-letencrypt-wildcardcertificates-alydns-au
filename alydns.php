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

php alydns.php add "newyingyong.cn" "test" "test2" 
php alydns.php del "newyingyong.cn" "test"  
*/

//add or del
$type = $argv[1];
//manager domain 
$obj = new AliDns(accessKeyId, accessSecrec, $argv[2]);
$data = $obj->DescribeDomainRecords();
$data = $data["DomainRecords"]["Record"];
if (is_array($data)) {
      foreach ($data as $v) {
           if ($v["RR"] == $argv[2]) {
               $obj->DeleteDomainRecord($v["RecordId"]);
           }
      }
} 

print_r($obj->AddDomainRecord("TXT", $argv[3],$argv[4]));

class AliDns {
    private $accessKeyId = null;
    private $accessSecrec = null;
    private $DomainName = null;


    public function __construct($accessKeyId, $accessSecrec, $domain) {
        $this->accessKeyId = $accessKeyId;
        $this->accessSecrec = $accessSecrec;
        $this->DomainName = $domain;
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

