@echo off
title Auto Push to GitHub and Render
color 0A
echo ========================================================
echo    Pushing latest changes to GitHub & Render...
echo ========================================================
echo.

git add .
git commit -m "Auto update from local: %date% %time%"
git push origin main

echo.
echo ========================================================
echo    SUCCESS! Changes pushed to GitHub!
echo    Render will now automatically deploy live.
echo ========================================================
echo.
timeout /t 5
