# -*- coding: utf-8 -*-
import sys
import os
import json
from tencentcloud.common import credential
from tencentcloud.common.profile.client_profile import ClientProfile
from tencentcloud.common.profile.http_profile import HttpProfile
from tencentcloud.common.exception.tencent_cloud_sdk_exception import TencentCloudSDKException
from tencentcloud.dnspod.v20210323 import dnspod_client, models


class Client(object):
    @staticmethod
    def getDomain(secret_id, secret_key, domain):
        try:
            cred = credential.Credential(secret_id, secret_key)
            # 实例化一个http选项，可选的，没有特殊需求可以跳过
            httpProfile = HttpProfile()
            httpProfile.endpoint = "dnspod.tencentcloudapi.com"

            # 实例化一个client选项，可选的，没有特殊需求可以跳过
            clientProfile = ClientProfile()
            clientProfile.httpProfile = httpProfile
            # 实例化要请求产品的client对象,clientProfile是可选的
            client = dnspod_client.DnspodClient(cred, "", clientProfile)

            # 实例化一个请求对象,每个接口都会对应一个request对象

            req = models.DescribeRecordListRequest()
            params = {
                'Domain': domain
            }
            req.from_json_string(json.dumps(params))

            # 返回的resp是一个DescribeRegionsResponse的实例，与请求对象对应
            resp = client.DescribeRecordList(req)
            # 输出json格式的字符串回包
            # print(resp.to_json_string())

        except TencentCloudSDKException as err:
            print(err)


class Cns:
    def __init__(self, secret_id, secret_key):
        self.secret_id = secret_id
        self.secret_key = secret_key
        cred = credential.Credential(self.secret_id, self.secret_key)
        # 实例化一个http选项，可选的，没有特殊需求可以跳过
        httpProfile = HttpProfile()
        httpProfile.endpoint = "dnspod.tencentcloudapi.com"

        # 实例化一个client选项，可选的，没有特殊需求可以跳过
        clientProfile = ClientProfile()
        clientProfile.httpProfile = httpProfile
        # 实例化要请求产品的client对象,clientProfile是可选的
        self.client = dnspod_client.DnspodClient(cred, "", clientProfile)

    def list(self, domain, subDomain):
        try:

            # 实例化一个请求对象,每个接口都会对应一个request对象

            req = models.DescribeRecordListRequest()
            params = {
                'Domain': domain,
                'SubDomain' : subDomain,
                'RecordType' : 'TXT'
            }
            req.from_json_string(json.dumps(params))

            # 返回的resp是一个DescribeRegionsResponse的实例，与请求对象对应
            resp = self.client.DescribeRecordList(req)
            # 输出json格式的字符串回包
            # print(resp.to_json_string())
            return resp

        except TencentCloudSDKException as err:
            print(err)

        return

    @staticmethod
    def getDomain(domain):
        domain_parts = domain.split('.')

        if len(domain_parts) > 2:
            dirpath = os.path.dirname(
                os.path.dirname(os.path.realpath(__file__)))
            domainfile = dirpath + "/domain.ini"
            domainarr = []
            with open(domainfile) as f:
                for line in f:
                    val = line.strip()
                    domainarr.append(val)

            rootdomain = '.'.join(
                domain_parts[-(2 if domain_parts[-1] in domainarr else 3):])
            selfdomain = domain.split(rootdomain)[0]
            return (selfdomain[0:len(selfdomain)-1], rootdomain)
        return ("", domain)

    def create(self, domain, name, _type, value):
        try:
            req = models.CreateRecordRequest()
            params = {
                'Action': 'RecordCreate',
                'Domain': domain,
                'SubDomain': name,
                'RecordType': _type,
                'RecordLine': '默认',
                'Value': value
            }
            req.from_json_string(json.dumps(params))

            # 返回的resp是一个CreateRecordResponse的实例，与请求对象对应
            resp = self.client.CreateRecord(req)
            # 输出json格式的字符串回包
            #print(resp.to_json_string())
            return resp

        except TencentCloudSDKException as err:
            print(err)

    def delete(self, domain, _id):
        try:
            # 实例化一个请求对象,每个接口都会对应一个request对象
            req = models.DeleteRecordRequest()
            params = {
                'Domain': domain,
                'RecordId': _id
            }
            req.from_json_string(json.dumps(params))

            # 返回的resp是一个DeleteRecordResponse的实例，与请求对象对应
            resp = self.client.DeleteRecord(req)
            # 输出json格式的字符串回包
            #print(resp.to_json_string())
            return resp

        except TencentCloudSDKException as err:
            print(err)
        return


if __name__ == '__main__':
    # Create your secret_id and secret_key at https://console.cloud.tencent.com/cam/capi

    _, option, domain, name, value, secret_id, secret_key = sys.argv  # pylint: disable=all

    domain = Cns.getDomain(domain)
    if domain[0] == "":
        selfdomain = name
    else:
        selfdomain = name + "." + domain[0]

    cns = Cns(secret_id, secret_key)
    if option == 'add':
        result = (cns.create(domain[1], selfdomain, 'TXT', value))
    elif option == 'clean':
        list = cns.list(domain[1], selfdomain)
        for record in list.RecordList:
            #print (record.Name,record.RecordId)
            result = cns.delete(domain[1], record.RecordId)
            #print (result)