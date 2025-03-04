cd C:\YardMaster\ai.redlionsalvage.net
git add .
git commit -m "Auto-sync: $(Get-Date -Format 'yyyy-MM-dd HH:mm')" 2>$null
git pull origin main
git push origin main