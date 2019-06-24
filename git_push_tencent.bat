@ECHO OFF
ECHO =========git push tencent master============
git remote add tencent https://git.code.tencent.com/firadio/firadiophp
git pull tencent master
git push tencent master
PAUSE
