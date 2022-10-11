@ECHO OFF
CD /d %~dp0
git config pull.rebase true
git config user.email "www@firadio.com"
git config user.name "firadio"
git config core.safecrlf false
git config core.autocrlf input
git remote rm origin
git remote add origin git@github.com:firadio/firadiophp.git
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
