@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
SET path=%path%;C:\data\program\PortableGit\bin\
ECHO =========git push gitee master============
git remote remove gitee
git remote add gitee git@gitee.com:firadio/firadiophp.git
git pull gitee master
git push gitee master
PAUSE
