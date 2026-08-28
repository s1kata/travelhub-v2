# Rollback Tourvisor-like search

```powershell
$b = "backups\search-tv-20260826-115617"
Copy-Item "$b\frontend\index.php" "frontend\index.php" -Force
# Or without full restore — open /?search=legacy
```

Quick switch: `/?search=legacy` (old wizard) · `/?search=tv` (Tourvisor-like)
