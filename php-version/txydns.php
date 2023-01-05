<?php
date_default_timezone_set("GMT");

$dir = dirname(dirname(__FILE__));
#根域名列表文件，如果自己的根域名不存在该文件中，可自行添加
$domainfile = $dir.DIRECTORY_SEPARATOR."domain.ini";

/*
  $obj = new TxyDns(accessKeyId, accessSecret, domain);
  //显示所有域名
  $data = $obj->DomainList();
  if (isset($data["Response"]["Error"])) {
      echo $data["Response"]["Error"]["Message"] . "\n";
  }
  print_r($data);
  //可以增加同名的二条
  $data = $obj->RecordCreate("www3","TXT",rand(10,1000));
  $data = $obj->RecordCreate("www3","TXT",rand(10,1000));
  $data = $obj->RecordCreate("www3.www3","TXT",rand(10,1000));

  if (isset($data["Response"]["Error"])) {
      echo $data["Response"]["Error"]["Message"] . "\n";
  }

  //查看一个主机的所有txt 记录
  $data = $obj->RecordList("www3.www3","TXT");

  $data = $obj->RecordList("www3","TXT");
  $records = $data["Response"]["RecordList"];
  foreach ($records as $k=>$v) {
      //根据ID修改记录
      $data = $obj->RecordModify("www3", "TXT", rand(1000,2000), $v["RecordId"]);
      //根据ID删除记录
      $obj->RecordDelete($v["RecordId"]);
  }
*/

###### 代码运行
//php txydns.php add "www.yudadan.com" "k1" "v1"  AKIDwlPr7DUpLgpZBb4tlT0MWUHtIVXOJwxm mMkxzoTxOirrfJlFYfbS7g7792jEi5GG
# 第一个参数是 action，代表 (add/clean)
# 第二个参数是域名
# 第三个参数是主机名（第三个参数+第二个参数组合起来就是要添加的 TXT 记录）
# 第四个参数是 TXT 记录值
# 第五个参数是 APPKEY
# 第六个参数是 APPTOKEN

echo "域名 API 调用开始\n";


if (count($argv) < 7) {
    echo "参数有误\n";
    exit;
}

echo $argv[1]."-".$argv[2]."-".$argv[3]."-".$argv[4]."-".$argv[5]."-".$argv[6]."\n";

$domainarray = TxyDns::getDomain($argv[2]);
$selfdomain = ($domainarray[0] == "") ? $argv[3] : $argv[3].".".$domainarray[0];
$obj = new TxyDns($argv[5], $argv[6], $domainarray[1]);

switch ($argv[1]) {
    case "clean":
        $data = $obj->RecordList($selfdomain, "TXT");
        if (isset($data["Response"]["Error"])) {
            echo "txy dns 记录获取失败-".$data["Response"]["Error"]["Message"]."\n";
            exit;
        }
        $records = $data["Response"]["RecordList"];
        foreach ($records as $k => $v) {
            $data = $obj->RecordDelete($v["RecordId"]);

            if (isset($data["Response"]["Error"])) {
                echo "txy dns 记录删除失败-".$data["Response"]["Error"]["Message"]."\n";
                exit;
            }
        }

        break;

    case "add":
        $data = $obj->RecordCreate($selfdomain, "TXT", $argv[4]);
        if (isset($data["Response"]["Error"])) {
            echo "txy dns 记录添加失败-".$data["Response"]["Error"]["Message"]."\n";
            exit;
        }
        break;
}

echo "域名 API 调用成功结束\n";

####### 基于腾讯云 DNS API 实现的 PHP 类，参考 https://cloud.tencent.com/document/product/1427/56193

class TxyDns
{

    private $accessKeyId = null;
    private $accessSecret = null;
    private $DomainName = null;
    private $Host = "dnspod.tencentcloudapi.com";
    private $Path = "/";
    private $apiVersion = "2021-03-23";

    public function __construct($accessKeyId, $accessSecret, $domain = "")
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessSecret = $accessSecret;
        $this->DomainName = $domain;
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
        $arr = [];
        foreach ($tmp as $k => $v) {
            $v = trim($v);
            if ($v != "") {
                $arr[] = ".".$v;
            }
        }

        //二级域名
        $seconddomain = "";
        //子域名
        $selfdomain = "";
        //根域名
        $rootdomain = "";
        foreach ($arr as $k => $v) {
            $pos = stripos($domain, $v);
            if ($pos) {
                $rootdomain = substr($domain, $pos);
                $s = explode(".", substr($domain, 0, $pos));
                $seconddomain = $s[count($s) - 1].$rootdomain;
                for ($i = 0; $i < count($s) - 1; $i++) {
                    $selfdomain .= $s[$i].".";
                }
                $selfdomain = substr($selfdomain, 0, strlen($selfdomain) - 1);
                break;
            }
        }
        //echo $seconddomain ;exit;
        if ($rootdomain == "") {
            $seconddomain = $domain;
            $selfdomain = "";
        }
        return [$selfdomain, $seconddomain];
    }

    public function error($code, $str)
    {
        echo "操作错误:".$code.":".$str;
        exit;
    }

    public function RecordDelete($recordId)
    {
        $param["Domain"] = $this->DomainName;
        $param["RecordId"] = $recordId;

        $data = $this->send("DeleteRecord", "GET", $param);
        return ($this->out($data));
    }

    public function RecordList($subDomain, $recordType)
    {
        $param["RecordType"] = $recordType;
        $param["Subdomain"] = $subDomain;
        $param["Domain"] = $this->DomainName;

        $data = $this->send("DescribeRecordList", "GET", $param);
        return ($this->out($data));
    }

    public function RecordModify($subDomain, $recordType, $value, $recordId)
    {
        $param["RecordType"] = $recordType;
        $param["SubDomain"] = $subDomain;
        $param["RecordId"] = $recordId;
        $param["Domain"] = $this->DomainName;
        $param["RecordLine"] = "默认";
        $param["Value"] = $value;

        $data = $this->send("ModifyRecord", "GET", $param);
        return ($this->out($data));
    }

    public function RecordCreate($subDomain, $recordType, $value)
    {
        $param["RecordType"] = $recordType;
        $param["SubDomain"] = $subDomain;
        $param["Domain"] = $this->DomainName;
        $param["RecordLine"] = "默认";
        $param["Value"] = $value;

        $data = $this->send("CreateRecord", "GET", $param);
        return ($this->out($data));
    }

    public function DomainList()
    {
        $data = $this->send("DescribeDomainList", "GET", []);
        return ($this->out($data));
    }

    private function send($action, $reqMethod, $requestParams)
    {
        $params = $this->formatRequestData($action, $requestParams, $reqMethod);

        $uri = http_build_query($params);
        $url = "https://".$this->Host.$this->Path."?".$uri;
        return $this->curl($url);
    }

    private function formatRequestData($action, $request, $reqMethod)
    {
        $param = $request;
        $param["Action"] = ucfirst($action);
        $param["Nonce"] = rand();
        $param["Timestamp"] = time();
        $param["Version"] = $this->apiVersion;
        $param["SecretId"] = $this->accessKeyId;

        $signStr = $this->formatSignString($this->Host, $this->Path, $param, $reqMethod);
        $param["Signature"] = $this->sign($signStr);
        return $param;
    }

    //签名
    private function formatSignString($host, $path, $param, $requestMethod)
    {
        $tmpParam = [];
        ksort($param);
        foreach ($param as $key => $value) {
            $tmpParam[] = $key."=".$value;
        }
        $strParam = implode("&", $tmpParam);
        $signStr = strtoupper($requestMethod).$host.$path."?".$strParam;
        return $signStr;
    }

    private function sign($signStr)
    {
        $signature = base64_encode(hash_hmac("sha1", $signStr, $this->accessSecret, true));
        return $signature;
    }

    private function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function out($msg)
    {
        return json_decode($msg, true);
    }

}
