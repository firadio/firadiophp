<?php

namespace FiradioPHP\Api;

use FiradioPHP\F;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Ecs\Ecs;
use AlibabaCloud\Vpc\Vpc;
use AlibabaCloud\Cms\Cms;

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

    /*
     * 常用功能
    */

    public function setDefaultClient($accessKeyId, $accessSecret, $regionId) {
        \AlibabaCloud\Client\AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)->regionId($regionId)->asDefaultClient();
    }


    /*
     * 开始ECS相关功能
    */

    public function EcsCreateInstance($rowNthostOperate) {
        print_r($rowNthostOperate);
        $request = Ecs::v20140526()->CreateInstance();
        $request->withImageId($rowNthostOperate['vps_imageid']);
        $request->withSecurityEnhancementStrategy('Deactive'); // 是否免费使用云安全中心服务。
        $request->withInstanceType($rowNthostOperate['ecs_instancetype']); // 2H4G
        $request->withVSwitchId($rowNthostOperate['VSwitchId']);
        if (!empty($rowNthostOperate['SecurityGroupId'])) {
            $request->withSecurityGroupId($rowNthostOperate['SecurityGroupId']);
        }
        //$request->withSecurityGroupId(getSecurityGroupId());
        $InstanceName = 'hvu-' . $rowNthostOperate['hvu_id'] . '-' . $rowNthostOperate['username'];
        $request->withInstanceName($InstanceName);
        $request->withInternetChargeType('PayByTraffic'); // PayByTraffic || PayByBandwidth
        //$request->withAutoRenew('true');
        //$request->withAutoRenewPeriod('1');
        $request->withInternetMaxBandwidthOut($rowNthostOperate['bandwidth_upload_mbps']); // 上传速度
        //$request->withInternetMaxBandwidthIn(100); // 下载速度
        //$request->withHostName('');
        if (empty($rowNthostOperate['password'])) {
            // 是否使用镜像预设的密码。使用该参数时，Password参数必须为空，同时您需要确保使用的镜像已经设置了密码。
            $request->withPasswordInherit('true');
        } else {
             // 长度为8至30个字符，必须同时包含大小写英文字母、数字和特殊符号中的三类字符。特殊符号可以是：()`~!@#$%^&*-_+=|{}[]:;'<>,.?/
            $request->withPassword($rowNthostOperate['password']);
        }
        $request->withZoneId($rowNthostOperate['zoneId']);
        $request->withSystemDiskSize($rowNthostOperate['limit_storagegb']); // 系统盘大小，单位为GiB。取值范围：20~500
        $request->withSystemDiskCategory('cloud_efficiency'); // cloud_efficiency(高效云盘)
        $request->withInstanceChargeType('PostPaid'); // PrePaid(包年包月) || PostPaid(按量付费)
        $request->withSpotStrategy('SpotAsPriceGo'); // NoSpot(正常按量付费) | SpotWithPriceLimit(设置上限价格) | SpotAsPriceGo(系统自动出价)
        //$request->withSpotPriceLimit(1);
        return $request->request();
    }


    public function EcsReplaceSystemDisk($rowNthostOperate) {
        // 更换系统盘
        print_r($rowNthostOperate);
        $request = Ecs::v20140526()->ReplaceSystemDisk();
        $request->withInstanceId($rowNthostOperate['vps_instanceid']);
        $request->withImageId($rowNthostOperate['vps_imageid']);
        $request->withSystemDiskSize($rowNthostOperate['limit_storagegb']); // 系统盘大小，单位为GiB。取值范围：20~500
        $request->withPassword($rowNthostOperate['password']);
        $request->withSecurityEnhancementStrategy('Deactive'); // 更换系统盘后，是否免费使用云安全中心服务。
        return $request->request();
    }

    public function EcsAllocatePublicIpAddress($InstanceId) {
        $request = Ecs::v20140526()->AllocatePublicIpAddress();
        $request->withInstanceId($InstanceId);
        //$request->withIpAddress('1.1.1.1');
        //$request->withVlanId('100');
        return $request->request();
    }

    public function EcsStartInstance($InstanceId) {
        // 启动实例
        $request = Ecs::v20140526()->StartInstance();
        $request->withInstanceId($InstanceId);
        //$request->withInitLocalDisk('false'); // 适用于实例规格族d1、i1或者i2等包含本地盘的实例。
        return $request->request();
    }

    public function EcsDeleteInstance($InstanceId) {
        // 删除实例
        $request = Ecs::v20140526()->DeleteInstance();
        $request->withInstanceId($InstanceId);
        $request->withForce(TRUE);
        return $request->request();
    }

    public function EcsRebootInstance($InstanceId, $ForceStop = TRUE) {
        // 重置实例密码
        $request = Ecs::v20140526()->RebootInstance();
        $request->withInstanceId($InstanceId);
        $request->withForceStop($ForceStop);
        return $request->request();
    }

    public function EcsStopInstance($InstanceId, $ForceStop = FALSE) {
        // 关机
        $request = Ecs::v20140526()->StopInstance();
        $request->withInstanceId($InstanceId);
        // StopCharging：停止计费 | KeepCharging：继续计费。
        $request->withStoppedMode('StopCharging');
        $request->withForceStop($ForceStop);
        return $request->request();
    }

    public function EcsCreateImageByInstanceId($InstanceId, $ImageName) {
        // 创建镜像
        $request = Ecs::v20140526()->CreateImage();
        $request->withInstanceId($InstanceId);
        $request->withImageName($ImageName);
        return $request->request();
    }

    public function EcsDeleteImage($ImageId, $Force = TRUE) {
        // 删除镜像
        $request = Ecs::v20140526()->DeleteImage();
        $request->withImageId($ImageId);
        $request->withForce($Force);
        return $request->request();
    }

    public function EcsDescribeImagesByImageId($ImageId) {
        // 查询镜像
        $request = Ecs::v20140526()->DescribeImages();
        $request->withImageId($ImageId);
        $ret = $request->request();
        $rows = $ret['Images']['Image'];
        return isset($rows[0]) ? $rows[0] : array();
    }

    public function EcsModifyInstancePassword($InstanceId, $Password) {
        // 重置实例密码
        $request = Ecs::v20140526()->ModifyInstanceAttribute();
        $request->withInstanceId($InstanceId);
        $request->withPassword($Password);
        $ret = $request->request();
        $this->EcsRebootInstance($InstanceId, FALSE);
        return $ret;
    }

    public function EcsConvertNatPublicIpToEip($InstanceId) {
        // 调用ConvertNatPublicIpToEip将一台专有网络VPC类型ECS实例的公网IP地址（NatPublicIp）转化为弹性公网IP（EIP）。
        $request = Ecs::v20140526()->ConvertNatPublicIpToEip();
        $request->withInstanceId($InstanceId);
        $ret = $request->request();
        return $ret;
    }

    public function EcsDeleteSnapshot($SnapshotId) {
        // 删除快照
        $request = Ecs::v20140526()->DeleteSnapshot();
        $request->withSnapshotId($SnapshotId);
        $ret = $request->request();
        return $ret;
    }

    public function getSecurityGroupId() {
        $request = Ecs::v20140526()->DescribeSecurityGroups();
        $ret = $request->request();
        $sgs = $ret['SecurityGroups']['SecurityGroup'];
        return $sgs[0]['SecurityGroupId'];
    }

    public function getEipInstanceId($InstanceId) {
        // 获取当前EcsInstanceId的EipInstanceId
        $request = Ecs::v20140526()->DescribeInstances();
        $request->withInstanceIds(json_encode([$InstanceId]));
        $ret = $request->request();
        if (empty($ret['Instances']['Instance'])) return;
        return $ret['Instances']['Instance'][0]['EipAddress']['AllocationId'];
    }

    public function EcsDescribeInstances($regionId, $PageNumber = 1) {
        // 调用DescribeInstances查询一台或多台ECS实例的详细信息。
        $request = Ecs::v20140526()->DescribeInstances();
        //$request->withRegionId($regionId);
        $request->withPageNumber($PageNumber);
        $ret = $request->request();
        return $ret;
    }

    public function EcsModifyInstanceVncPasswd($InstanceId, $VncPassword) {
        $request = Ecs::v20140526()->ModifyInstanceVncPasswd();
        $request->withInstanceId($InstanceId);
        $request->withVncPassword($VncPassword);
        return $request->request();
    }

    public function EcsDescribeInstanceVncUrl($InstanceId) {
        $request = Ecs::v20140526()->DescribeInstanceVncUrl();
        $request->withInstanceId($InstanceId);
        return $request->request();
    }

    public function EcsDescribeDisks($PageNumber = 1) {
        // 查询一块或多块您已经创建的块存储（包括云盘以及本地盘）。
        $request = Ecs::v20140526()->DescribeDisks();
        $request->withPageNumber($PageNumber);
        $ret = $request->request();
        return $ret;
    }

    public function getDiskByEcsInstanceId($InstanceId, $DiskType = 'system') {
        // 查询一块或多块您已经创建的块存储（包括云盘以及本地盘）。
        $request = Ecs::v20140526()->DescribeDisks();
        $request->withInstanceId($InstanceId);
        $request->withDiskType($DiskType);
        $ret = $request->request();
        if (empty($ret['Disks'])) return FALSE;
        if (empty($ret['Disks']['Disk'])) return FALSE;
        if (empty($ret['Disks']['Disk'][0])) return FALSE;
        return $ret['Disks']['Disk'][0];
    }

    public function EcsModifyDiskAttribute($DiskId, $DeleteWithInstance = FALSE, $DeleteAutoSnapshot = FALSE, $DiskName = NULL) {
        // 修改一个块存储的名称、描述、是否随实例释放等属性。
        $request = Ecs::v20140526()->ModifyDiskAttribute();
        $request->withDiskId($DiskId);
        $request->withDeleteWithInstance($DeleteWithInstance);
        $request->withDeleteAutoSnapshot($DeleteAutoSnapshot);
        if ($DiskName !== NULL) $request->withDiskName($DiskName);
        $ret = $request->request();
        return $ret;
    }




    /*
     * 开始VPC相关功能
    */

    public function VpcAddCommonBandwidthPackageIp($IpInstanceId, $BandwidthPackageId) {
        // 调用AddCommonBandwidthPackageIp接口添加EIP到共享带宽中。
        $request = Vpc::v20160428()->AddCommonBandwidthPackageIp();
        $request->withBandwidthPackageId($BandwidthPackageId);
        $request->withIpInstanceId($IpInstanceId);
        $ret = $request->request();
        return $ret;
    }

    public function VpcReleaseEipAddress($IpInstanceId) {
        // 调用ReleaseEipAddress接口释放指定的弹性公网IP（EIP）。
        $request = Vpc::v20160428()->ReleaseEipAddress();
        $request->withAllocationId($IpInstanceId);
        $ret = $request->request();
        return $ret;
    }

    public function VpcRemoveCommonBandwidthPackageIp($IpInstanceId, $BandwidthPackageId) {
        // 调用RemoveCommonBandwidthPackageIp接口移除共享带宽实例中的EIP。
        $request = Vpc::v20160428()->RemoveCommonBandwidthPackageIp();
        $request->withBandwidthPackageId($BandwidthPackageId);
        $request->withIpInstanceId($IpInstanceId);
        $ret = $request->request();
        return $ret;
    }

    public function VpcUnassociateEipAddress($IpInstanceId) {
        $request = Vpc::v20160428()->UnassociateEipAddress();
        $request->withAllocationId($IpInstanceId);
        $ret = $request->request();
    }

    public function VpcAllocateEipAddress() {
        $request = Vpc::v20160428()->AllocateEipAddress();
        $request->withBandwidth(1);
        $request->withInstanceChargeType('PostPaid'); // PostPaid（默认值）：按量计费。
        $request->withInternetChargeType('PayByTraffic'); // PayByTraffic：按流量计费。
        $ret = $request->request();
        return $ret;
    }

    public function VpcAssociateEipAddress($AllocationId, $InstanceId) {
        $request = Vpc::v20160428()->AssociateEipAddress();
        $request->withAllocationId($AllocationId);
        $request->withInstanceId($InstanceId);
        $ret = $request->request();
        return $ret;
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

    private function array_orderby() {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
                }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    public function VpcEipMonitorData($AllocationId, $countLimit = 1, $countKey = 'EipTX') {
        $request = Vpc::v20160428()->DescribeEipMonitorData();
        $request->withAllocationId($AllocationId);
        $arr = array(60, 300, 900, 3600);
        $period = $arr[0];
        $request->withPeriod($period);
        date_default_timezone_set('UTC');
        $request->withStartTime(date('Y-m-d\TH:i:00\Z', time() - 60 * ($countLimit + 3)));
        $request->withEndTime(date('Y-m-d\TH:i:00\Z', time() + 60 * (10)));
        $ret = $request->request();
        $d1 = $ret['EipMonitorDatas']['EipMonitorData'];
        $sorted = $this->array_orderby($d1, 'TimeStamp', SORT_DESC);
        $count_i = 0;
        $count_val = 0;
        foreach ($sorted as $k => $row) {
            $val = floatval($row[$countKey]);
            if ($k == 0 && $val == 0) continue;
            $count_i++;
            $count_val += $val;
            if ($count_i >= $countLimit) {
                break;
            }
        }
        if ($count_i == 0) {
            return 0;
        }
        return $count_val / $count_i / $period;
    }

    public function VpcDescribeCommonBandwidthPackage() {
        $request = Vpc::v20160428()->DescribeCommonBandwidthPackages();
        $ret = $request->request();
        return $ret['CommonBandwidthPackages']['CommonBandwidthPackage'];
    }

    public function VpcDescribeCommonBandwidthPackages($PageNumber = 1) {
        $request = Vpc::v20160428()->DescribeCommonBandwidthPackages();
        $request->withPageNumber($PageNumber);
        $ret = $request->request();
        return $ret;
    }

    public function VpcDescribeEipAddresses($PageNumber = 1, $PageSize = NULL) {
        // 参考 https://help.aliyun.com/document_detail/36018.html
        $request = Vpc::v20160428()->DescribeEipAddresses();
        $request->withPageNumber($PageNumber);
        if ($PageSize !== NULL) {
            $request->withPageSize($PageSize);
        }
        $ret = $request->request();
        return $ret;
    }



    /*
     * 开始Cms相关功能
    */

    public function CmsDescribeMetricList($Namespace, $MetricName, $countLimit = 1, $groupby = NULL) {
        // 参考 https://help.aliyun.com/document_detail/51936.html
        $request = Cms::v20190101()->DescribeMetricList();
        $request->withNamespace($Namespace);
        $request->withMetricName($MetricName);
        $arr = array(60, 300, 900, 3600);
        $period = $arr[0];
        $request->withPeriod($period);
        date_default_timezone_set('UTC');
        $request->withStartTime(date('Y-m-d\TH:i:00\Z', time() - 60 * ($countLimit + 3)));
        $request->withEndTime(date('Y-m-d\TH:i:00\Z', time() + 60 * (10)));
        if ($groupby !== NULL) {
            $Express = json_encode(array('groupby' => explode(',', $groupby)));
            $request->withExpress($Express);
        }
        $ret = $request->request();
        $Datapoints = json_decode($ret->Datapoints, TRUE);
        return $Datapoints;
    }

    public function CmsCbwpTxRatesMap($countLimit = 1) {
        // 参考 https://help.aliyun.com/document_detail/165008.html
        $Namespace = 'acs_bandwidth_package';
        $MetricName = 'net_tx.rate'; // 流出带宽
        $Datapoints = $this->CmsDescribeMetricList($Namespace, $MetricName);
        $sorted = $this->array_orderby($Datapoints, 'timestamp', SORT_DESC);
        $mRet = array();
        foreach ($sorted as $mRow) {
            if (!isset($mRet[$mRow['instanceId']])) {
                $mRet[$mRow['instanceId']] = array();
            }
            if (count($mRet[$mRow['instanceId']]) >= $countLimit) {
                continue;
            }
            $mRet[$mRow['instanceId']][] = floatval($mRow['Value']);
        }
        return $mRet;
    }

    public function CmsEipTxRatesMap($countLimit = 1) {
        // 参考 https://help.aliyun.com/document_detail/162874.html
        $Namespace = 'acs_vpc_eip';
        $MetricName = 'net_tx.rate'; // 流出带宽
        $Datapoints = $this->CmsDescribeMetricList($Namespace, $MetricName, $countLimit);
        $sorted = $this->array_orderby($Datapoints, 'timestamp', SORT_DESC);
        $mRet = array();
        foreach ($sorted as $mRow) {
            if (!isset($mRet[$mRow['instanceId']])) {
                $mRet[$mRow['instanceId']] = array();
            }
            if (count($mRet[$mRow['instanceId']]) >= $countLimit) {
                continue;
            }
            $mRet[$mRow['instanceId']][] = floatval($mRow['Value']);
        }
        return $mRet;
    }



}
