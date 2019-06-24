@ECHO OFF
git config --global user.email "xiangxisheng@gmail.com"
git config --global user.name "Xiang/Xisheng"
git config --global core.safecrlf false
git config --global core.autocrlf input
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
