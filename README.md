firadiophp - 飞儿云PHP框架
==================================

# 说明

更新的 firadiophp 已支持访问阿里云的OTS服务。

适用于PHP 5.6 及以上版本，包括7.0、7.1、7.2、7.3。只支持64位的PHP系统。推荐使用PHP7，以得到最好的性能。

# 使用步骤

1. 请确认你的PHP版本为 5.6 或更高。你可以通过运行 php --version 获知你当前使用的PHP版本。

2. 设置PHP的时区，在 php.ini（要知道你正在使用的php.ini文件的位置，请执行命令 php --ini）中添加一行：

   date.timezone = Asia/Shanghai  （请根据你当地的时区进行设置）

3. 设置PHP的内存使用限制为128M或者更高。同样是在 php.ini 中修改：

   memory_limit = 128M

4. 下载 firadiophp 并解压到本地。

5. 安装依赖。在解压后的目录中运行： 

   composer_install.bat

## 可在【阿里云】的【函数计算】中使用

1. 完成上述步骤后将本文件夹打包上传到【函数计算】

2. 在【概览】中的【函数属性】中点击【修改】按钮

3. 打开【是否配置函数初始化入口】功能

4. 【函数初始化入口】的内容填写【aliyun-fc.initializer】

5. 在【环境变量】中，添加键【PHP_INI_SCAN_DIR】，值【/code/extension】

6. 确定修改后即可开始测试了

## 贡献代码
 - 我们非常欢迎大家为 firadiophp 贡献代码

# 帮助和支持 FAQ

 - 作者阿盛 QQ: 309385018
 

