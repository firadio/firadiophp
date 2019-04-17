firadiophp - 飞儿云PHP框架
==================================

# 说明

更新的 firadiophp 已支持访问阿里云的OTS服务。

# 准备好必要的依赖

1：需要PHP 5.6 及以上版本，包括7.0、7.1、7.2、7.3。推荐使用PHP7，以得到最好的性能。

2：下载并安装https://getcomposer.org/download/

# 使用步骤

1. 下载 firadiophp 并解压到本地。

2. 通过下面方式安装依赖。
    根目录中运行：composer_install.bat

## 可在【阿里云】的【函数计算】中使用

1. 完成上述步骤后将本文件夹打包上传到【函数计算】

2. 在【概览】中的【函数属性】中点击【修改】按钮

3. 设置【函数入口】为【aliyun-fc.handler】

4. 打开【是否配置函数初始化入口】功能

5. 【函数初始化入口】的内容填写【aliyun-fc.initializer】

6. 在【环境变量】中，添加键【PHP_INI_SCAN_DIR】，值【/code/extension】

7. 确定修改后即可开始测试了

## 贡献代码
 - 我们非常欢迎大家为 firadiophp 贡献代码

# 帮助和支持 FAQ

 - 作者阿盛 QQ: 309385018
 

