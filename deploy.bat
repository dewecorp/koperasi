@echo off
chcp 65001 >nul
title Deploy Koperasi - Commit, Push & Backup

set REPO_DIR=D:\laragon\www\koperasi
set BACKUP_ZIP=%REPO_DIR%\koperasi-backup.zip
set GITHUB_URL=https://github.com/dewecorp/koperasi.git

cd /d "%REPO_DIR%"

echo ========================================
echo  DEPLOY KOPERASI - Commit, Push & Backup
echo ========================================
echo.

echo [1/4] Checking git status...
git status --short

echo.
echo [2/4] Adding all changes...
git add -A

echo.
echo [3/4] Committing changes...
set /p COMMIT_MSG="Enter commit message (default: Update): "
if "%COMMIT_MSG%"=="" set COMMIT_MSG=Update
git commit -m "%COMMIT_MSG%"

echo.
echo [4/4] Pushing to GitHub...
if not exist ".git\config" (
    git remote add origin https://github.com/dewecorp/koperasi.git
)
git branch -M main
git push -u origin main

echo.
echo [5/5] Creating backup zip (overwrite mode)...
if exist "%BACKUP_ZIP%" del "%BACKUP_ZIP%"
powershell -Command "Compress-Archive -Path '%REPO_DIR%\*' -DestinationPath '%BACKUP_ZIP%' -Force -CompressionLevel Optimal"

echo.
echo ========================================
echo  DEPLOY COMPLETE!
echo ========================================
echo Backup saved to: %BACKUP_ZIP%
echo GitHub: https://github.com/dewecorp/koperasi
echo.
pause