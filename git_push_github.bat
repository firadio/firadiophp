@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
ECHO =========git push github master============
git remote add github https://github.com/firadio/firadiophp.git
git pull github master
git push github master
PAUSE
