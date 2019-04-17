@ECHO OFF
CHCP 65001
TITLE 安装PHP依赖的Composer类库
CLS
ECHO 开始安装PHP依赖的Composer类库
CALL composer install
ECHO Composer类库安装完成
PAUSE
