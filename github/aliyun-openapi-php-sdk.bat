@ECHO OFF
mkdir "%~dp0\github.com"
mkdir "%~dp0\github.com\aliyun"
git clone https://github.com/aliyun/aliyun-openapi-php-sdk.git %~dp0\github.com\aliyun\aliyun-openapi-php-sdk
PAUSE
