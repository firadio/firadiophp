@ECHO OFF
mkdir "%~dp0\github.com"
mkdir "%~dp0\github.com\aliyun"
git clone https://github.com/aliyun/aliyun-openapi-php-sdk.git %~dp0\github.com\aliyun\aliyun-openapi-php-sdk
@ECHO OFF
SET Path=%Path%;D:\Apps\Php\PHP7.0
php aliyun-openapi-php-sdk.php
PAUSE
