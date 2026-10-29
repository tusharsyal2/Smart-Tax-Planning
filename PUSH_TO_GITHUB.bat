@echo off
setlocal enabledelayedexpansion
title Smart Tax Planning Assistance - Push to GitHub
color 0B
cls

:: Always run inside the Smart-Tax-Planning project root
cd /d "%~dp0"

set "TARGET_REPO=https://github.com/tusharsyal2/Smart-Tax-Planning.git"

echo ===============================================================================
echo                SMART TAX PLANNING ASSISTANCE - GITHUB UPLOADER
echo ===============================================================================
echo  Project Path: %cd%
echo  GitHub Repo:  %TARGET_REPO%
echo ===============================================================================
echo.

:: 1. Locate Git executable
set "GIT_BIN=git"
where git >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Program Files\Git\cmd\git.exe" (
        set "GIT_BIN=C:\Program Files\Git\cmd\git.exe"
    ) else (
        color 0C
        echo [ERROR] Git is not installed or not found in system PATH!
        echo Please install Git from https://git-scm.com/
        pause
        exit /b 1
    )
)

echo [OK] Using Git: "%GIT_BIN%"
echo.

:: 2. Initialize Git repository if not already initialized
if not exist ".git" (
    echo [STEP 1] Initializing local Git repository...
    "%GIT_BIN%" init
    "%GIT_BIN%" branch -M main
    echo [OK] Repository initialized on branch 'main'.
    echo.
) else (
    echo [STEP 1] Local Git repository already initialized.
    echo.
)

:: 3. Ensure Git Author Identity (user.name & user.email) is configured
set "GIT_USER="
for /f "tokens=*" %%u in ('"%GIT_BIN%" config user.name 2^>nul') do set "GIT_USER=%%u"
if "!GIT_USER!"=="" (
    set "GIT_USER=tusharsyal2"
    "%GIT_BIN%" config user.name "!GIT_USER!"
)

set "GIT_EMAIL="
for /f "tokens=*" %%e in ('"%GIT_BIN%" config user.email 2^>nul') do set "GIT_EMAIL=%%e"
if "!GIT_EMAIL!"=="" (
    set "GIT_EMAIL=tusharsyal2@users.noreply.github.com"
    "%GIT_BIN%" config user.email "!GIT_EMAIL!"
)

echo [OK] Git Identity: !GIT_USER! ^<!GIT_EMAIL!^>
echo.

:: 4. Ensure .gitignore protects .env before staging
if not exist ".gitignore" (
    (
        echo # Environment files
        echo .env
        echo.
        echo # XAMPP / Apache logs
        echo *.log
        echo error_log
        echo.
        echo # OS generated files
        echo .DS_Store
        echo Thumbs.db
        echo.
        echo # IDE
        echo .vscode/
        echo .idea/
    ) > ".gitignore"
    echo [INFO] Created .gitignore to protect .env credentials.
)

:: 5. Automatically link or update GitHub Remote URL (origin)
set "CURRENT_REMOTE="
for /f "tokens=*" %%r in ('"%GIT_BIN%" remote get-url origin 2^>nul') do set "CURRENT_REMOTE=%%r"

if "!CURRENT_REMOTE!"=="" (
    "%GIT_BIN%" remote add origin "%TARGET_REPO%"
    echo [STEP 2] Linked remote 'origin' to: %TARGET_REPO%
) else if /i not "!CURRENT_REMOTE!"=="%TARGET_REPO%" (
    "%GIT_BIN%" remote set-url origin "%TARGET_REPO%"
    echo [STEP 2] Updated remote 'origin' to: %TARGET_REPO%
) else (
    echo [STEP 2] Remote 'origin' already set to: %TARGET_REPO%
)
echo.

:: 6. Stage all project files (respecting .gitignore so .env is excluded)
echo [STEP 3] Staging all Smart Tax Planning project files...
"%GIT_BIN%" add -A

:: Check if there are changes to commit
"%GIT_BIN%" diff --cached --quiet
if %errorlevel% equ 0 (
    echo [INFO] All files are already committed. Nothing new to commit.
) else (
    echo.
    echo Files staged for upload:
    echo -------------------------------------------------------------------------------
    "%GIT_BIN%" status -s
    echo -------------------------------------------------------------------------------
    echo.

    :: Determine default commit message
    "%GIT_BIN%" rev-parse --verify HEAD >nul 2>nul
    if %errorlevel% neq 0 (
        set "DEFAULT_MSG=Initial commit: Smart Tax Planning Assistance platform"
    ) else (
        set "DEFAULT_MSG=Update Smart Tax Planning project (%date%)"
    )

    set "COMMIT_MSG="
    set /p COMMIT_MSG="Enter commit message (press Enter for '!DEFAULT_MSG!'): "
    if "!COMMIT_MSG!"=="" set "COMMIT_MSG=!DEFAULT_MSG!"

    echo.
    echo [STEP 4] Committing files...
    "%GIT_BIN%" commit -m "!COMMIT_MSG!"
)

:: 7. Detect current branch (default to main)
set "CURRENT_BRANCH=main"
for /f "tokens=*" %%b in ('"%GIT_BIN%" branch --show-current 2^>nul') do (
    if not "%%b"=="" set "CURRENT_BRANCH=%%b"
)

:: 8. Push to GitHub
echo.
echo [STEP 5] Pushing project to %TARGET_REPO% (branch: !CURRENT_BRANCH!)...
"%GIT_BIN%" push -u origin !CURRENT_BRANCH!

if %errorlevel% equ 0 (
    color 0A
    echo.
    echo ===============================================================================
    echo  [SUCCESS] All Smart Tax Planning files have been pushed to GitHub!
    echo  Repository: %TARGET_REPO%
    echo ===============================================================================
) else (
    color 0E
    echo.
    echo ===============================================================================
    echo  [NOTICE] Standard push was rejected (the remote GitHub repo may already
    echo           contain a README or License file created on GitHub).
    echo ===============================================================================
    echo.
    echo  How would you like to resolve this?
    echo    [1] Merge remote files with local project and push (Recommended)
    echo    [2] Overwrite GitHub repository with local project files (--force)
    echo    [3] Cancel
    echo.
    set "RESOLVE_CHOICE="
    set /p RESOLVE_CHOICE="Select an option (1/2/3): "

    if "!RESOLVE_CHOICE!"=="1" (
        echo.
        echo Pulling remote files and merging...
        "%GIT_BIN%" pull origin !CURRENT_BRANCH! --allow-unrelated-histories --no-edit
        "%GIT_BIN%" push -u origin !CURRENT_BRANCH!
        if !errorlevel! equ 0 (
            color 0A
            echo.
            echo [SUCCESS] Merged and pushed all files to %TARGET_REPO%!
        )
    ) else if "!RESOLVE_CHOICE!"=="2" (
        echo.
        echo Force-pushing local project to GitHub...
        "%GIT_BIN%" push -u origin !CURRENT_BRANCH! --force
        if !errorlevel! equ 0 (
            color 0A
            echo.
            echo [SUCCESS] Force-pushed all local files to %TARGET_REPO%!
        )
    ) else (
        echo.
        echo Push cancelled.
    )
)

echo.
pause
