<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Ecs\Ecs;
use AlibabaCloud\Vpc\Vpc;

/**
 * https://github.com/rjyxz/aliyun-php-sdk-dm
 */
class AlibabaCloud {

    private $aConfig = array();

    public function __construct($conf = array()) {
        if (isset($conf['config'])) {
            $config = $conf['config'];
            $this->aConfig = $config;
        }
    }

    public function setDefaultClient($accessKeyId, $accessSecret, $regionId) {
        \AlibabaCloud\Client\AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)->regionId($regionId)->asDefaultClient();
    }

    public function EcsDescribeInstances($regionId, $PageNumber = 1) {
        // 调用DescribeInstances查询一台或多台ECS实例的详细信息。
        $request = Ecs::v20140526()->DescribeInstances();
        //$request->withRegionId($regionId);
        $request->withPageNumber($PageNumber);
        $ret = $request->request();
        return $ret;
    }

    public function EcsConvertNatPublicIpToEip($InstanceId) {
        // 调用ConvertNatPublicIpToEip将一台专有网络VPC类型ECS实例的公网IP地址（NatPublicIp）转化为弹性公网IP（EIP）。
        $request = Ecs::v20140526()->ConvertNatPublicIpToEip();
        $request->withInstanceId($InstanceId);
        $ret = $request->request();
        return $ret;
    }

    public function getEipInstanceId($InstanceId) {
        // 获取当前EcsInstanceId的EipInstanceId
        $request = Ecs::v20140526()->DescribeInstances();
        $request->withInstanceIds(json_encode([$InstanceId]));
        $ret = $request->request();
        if (empty($ret['Instances']['Instance'])) return;
        return $ret['Instances']['Instance'][0]['EipAddress']['AllocationId'];
    }

    public function EcsDeleteInstance($InstanceId) {
        // 删除实例
        $request = Ecs::v20140526()->DeleteInstance();
        $request->withInstanceId($InstanceId);
        $request->withForce(TRUE);
        return $request->request();
    }

    public function VpcAddCommonBandwidthPackageIp($IpInstanceId, $BandwidthPackageId) {
        // 调用AddCommonBandwidthPackageIp接口添加EIP到共享带宽中。
        $request = Vpc::v20160428()->AddCommonBandwidthPackageIp();
        $request->withBandwidthPackageId($BandwidthPackageId);
        $request->withIpInstanceId($IpInstanceId);
        $ret = $request->request();
    }

    public function VpcModifyCommonBandwidthPackageIpBandwidth($EipId, $Bandwidth, $BandwidthPackageId) {
        // 为已经加入到共享带宽的EIP设置最大可用带宽值。
        $request = Vpc::v20160428()->ModifyCommonBandwidthPackageIpBandwidth();
        $request->withBandwidthPackageId($BandwidthPackageId);
        $request->withEipId($EipId);
        $request->withBandwidth($Bandwidth);
        $ret = $request->request();
        //print_r($ret->toArray());
    }

    public function VpcEipMonitorData($AllocationId) {
        $request = Vpc::v20160428()->DescribeEipMonitorData();
        $request->withAllocationId($AllocationId);
        $arr = array(60, 300, 900, 3600);
        $period = $arr[0];
        $request->withPeriod($period);
        date_default_timezone_set('UTC');
        $request->withStartTime(date('Y-m-d\TH:i:00\Z', time() - 60 * (15)));
        $request->withEndTime(date('Y-m-d\TH:i:00\Z', time() + 60));
        $ret = $request->request();
        $d1 = $ret['EipMonitorDatas']['EipMonitorData'];
        $d2 = array();
        foreach ($d1 as $k => $v) {
            $d2[$v['TimeStamp']] = $k;
        }
        krsort($d2);
        foreach ($d2 as $k) {
            $row = $d1[$k];
            $row['EipTXPS'] = $row['EipTX'] / $period;
            return($row);
        }
        return 0;
    }

    public function VpcDescribeCommonBandwidthPackages() {
        $request = Vpc::v20160428()->DescribeCommonBandwidthPackages();
        $ret = $request->request();
        return $ret['CommonBandwidthPackages']['CommonBandwidthPackage'];
    }



}
