@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
SET path=%path%;C:\data\program\PortableGit\bin\
git config --global user.email "xiangxisheng@gmail.com"
git config --global user.name "Xiang/Xisheng"
git config --global core.safecrlf warn
git config --global core.autocrlf true
git remote rm origin
git remote add origin https://gitee.com/firadio/firadiophp.git
ECHO =========git diff============
git diff
ECHO =========git pull ^&^& git commit============
PAUSE
git pull origin master
git commit
ECHO =========git add *============
PAUSE
git add --all
ECHO =========git commit============
PAUSE
git commit
ECHO =========git push origin master============
PAUSE
git push origin master
ECHO =========EXIT============
PAUSE
EXIT
