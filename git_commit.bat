@ECHO OFF
git config --global user.email "xiangxisheng@gmail.com"
git config --global user.name "Xiang/Xisheng"
git config --global core.safecrlf false
git config --global core.autocrlf input
ECHO =========git diff============
git diff
ECHO =========git pull ^&^& git commit============
PAUSE
git pull
git commit
ECHO =========git add *============
PAUSE
git add --all
ECHO =========git commit============
PAUSE
git commit
ECHO =========git push============
PAUSE
git push
ECHO =========EXIT============
PAUSE
EXIT
