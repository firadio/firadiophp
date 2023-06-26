@ECHO OFF
SET path=%path%;P:\data\code\PortableGit\bin\
SET path=%path%;X:\program\PortableGit\bin\
SET path=%path%;E:\data\program\PortableGit\bin\
SET path=%path%;C:\data\program\PortableGit\bin\
ECHO =========git push coding master============
git remote remove coding
git remote add coding git@e.coding.net:firadio/firadiophp.git
git pull coding master
git push coding master
PAUSE
