<?php
date_default_timezone_set("GMT");

############ 请在腾讯云申请“API密钥”，替换下面两个常量
// 去 https://console.cloud.tencent.com/cam/capi 页面申请 
define("txyaccessKeyId", "AKIDwlPr7DUpLgpZBb4tlT0MWUHtIVXOJwxm");
define("txyaccessSecrec", "mMkxzoTxOirrfJlFYfbS7g7792jEi5GG");

######### 类测试

/*
$obj = new TxyDns(txyaccessKeyId, txyaccessSecrec, "yudadan.com");
//显示所有域名
//$obj->DomainList();
//添加域名 TXT 记录
//$obj->RecordCreate("www3","TXT","s"); 
//显示某个域名所有的 TXT 记录
//$obj->RecordList("www3","TXT");
*/

###### 代码运行
// php txydns.php  "simplehttps.com" "txtname" "txtvalue"  
$argv[1] = "yudadan.com";
$argv[2] = "www3";
$argv[3] = "ss";

$obj = new TxyDns(txyaccessKeyId, txyaccessSecrec, $argv[1]);
$data = $obj->RecordList($argv[2],"TXT");
if ($data["code"]!="0") {
    $obj->error($data["code"], $data["message"]);
}
 
$records = $data["data"]["records"];
foreach ($records as $k=>$v) {
    if ($v["name"] == $argv[2]) {
        echo "sss";
        
        
        $obj->RecordDelete($v["recordId"]);
        exit ;
    }
}
 
class TxyDns {

    private $accessKeyId = null;
    private $accessSecrec = null;
    private $DomainName = null;
    private $Host = "cns.api.qcloud.com";
    private $Path = "/v2/index.php";

    public function __construct($accessKeyId, $accessSecrec, $domain="") {
        $this->accessKeyId = $accessKeyId;
        $this->accessSecrec = $accessSecrec;
        $this->DomainName = $domain;
    }
    
    public function error($code,$str) {
        echo "操作错误:" . $code .":" . $str ;
        exit ;
    }
 
    public function RecordDelete($recordId) {
        $param["domain"] = $this->DomainName ;
        $param["recordId"] = $recordId ;
        
        $data = $this->send("RecordDelete", "GET", $param);
        return ($this->out($data));
    }
    
    public function RecordList($subDomain,$recordType="") {
        
        if ($recordType!="")
        $param["recordType"] = $recordType ;
        $param["subDomain"] = $subDomain ;
        $param["domain"] = $this->DomainName ;
        
        $data = $this->send("RecordList", "GET", $param);
        return ($this->out($data));
        
    }
    
    public function RecordCreate($subDomain,$recordType = "TXT",$value) {
        $param["recordType"] = $recordType ;
        $param["subDomain"] = $subDomain ;
        $param["domain"] = $this->DomainName ;
        $param["recordLine"]="默认";
        $param["value"] = $value;
        //print_r($param);
       
        $data = $this->send("RecordCreate", "GET", $param);
        return ($this->out($data));
        
    }
    public function DomainList() {
 
        $data = $this->send("DomainList", "GET", array());
        return ($this->out($data));
    }

    public function send($action, $reqMethod, $requestParams) {

        $params = $this->formatRequestData($action, $requestParams, $reqMethod);

        $uri = http_build_query($params);
        $url = "https://" . $this->Host . "" . $this->Path . "?" . $uri;
        return $this->curl($url);
    }

    public function formatRequestData($action, $request, $reqMethod) {
        $param = $request;
        $param["Action"] = ucfirst($action);
//$param["RequestClient"] = $this->sdkVersion;
        $param["Nonce"] = rand();
        $param["Timestamp"] = time();
//$param["Version"] = $this->apiVersion;

        $param["SecretId"] = $this->accessKeyId;

        $signStr = $this->formatSignString($this->Host, $this->Path, $param, $reqMethod);
        $param["Signature"] = $this->sign($signStr);
        return $param;
    }

//签名
    public function formatSignString($host, $path, $param, $requestMethod) {
        $tmpParam = [];
        ksort($param);
        foreach ($param as $key => $value) {
            array_push($tmpParam, str_replace("_", ".", $key) . "=" . $value);
        }
        $strParam = join("&", $tmpParam);
        $signStr = strtoupper($requestMethod) . $host . $path . "?" . $strParam;
        return $signStr;
    }

    public function sign($signStr) {

        $signature = base64_encode(hash_hmac("sha1", $signStr, $this->accessSecrec, true));
        return $signature;
    }

    private function curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function out($msg) {
        return json_decode($msg, true);
    }

}





 
 
 
