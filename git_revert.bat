@ECHO OFF
ECHO =========git diff============
git diff
CLS
ECHO ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
ECHO ┃                                                                          ┃
ECHO ┃      重要提示：即将执行[git checkout *]还原所有修改，注意请确认！        ┃
ECHO ┃                                                                          ┃
ECHO ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
PAUSE
ECHO ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
ECHO ┃                                                                          ┃
ECHO ┃          重要提示：即将还原所有修改，请再次确认！                        ┃
ECHO ┃                                                                          ┃
ECHO ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
PAUSE
git checkout *
PAUSE
