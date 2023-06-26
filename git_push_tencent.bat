@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
SET path=%path%;C:\data\program\PortableGit\bin\
ECHO =========git push tencent master============
git remote remove tencent
git remote add tencent git@git.code.tencent.com:firadio/firadiophp.git
git pull tencent master
git push tencent master
PAUSE
