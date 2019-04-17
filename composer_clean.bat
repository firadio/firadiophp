@ECHO OFF
CHCP 65001
TITLE 清理PHP依赖的Composer类库
CLS
ECHO 开始清理PHP依赖的Composer类库
RD /q /s vendor
DEL composer.lock
CLS
ECHO Composer类库清理完成
PAUSE
