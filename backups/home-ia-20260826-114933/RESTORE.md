# Откат главной / навигации (2026-08-26)

Скопировать обратно:

```
backups/home-ia-20260826-114933/frontend/index.php
  → frontend/index.php

backups/home-ia-20260826-114933/backend/components/header.php
  → backend/components/header.php

backups/home-ia-20260826-114933/backend/components/footer.php
  → backend/components/footer.php

backups/home-ia-20260826-114933/frontend/css/pages/home.css
  → frontend/css/pages/home.css

backups/home-ia-20260826-114933/frontend/js/th-app-promo.js
  → frontend/js/th-app-promo.js
```

PowerShell из корня репо:

```powershell
$b = "backups\home-ia-20260826-114933"
Copy-Item "$b\frontend\index.php" "frontend\index.php" -Force
Copy-Item "$b\backend\components\header.php" "backend\components\header.php" -Force
Copy-Item "$b\backend\components\footer.php" "backend\components\footer.php" -Force
Copy-Item "$b\frontend\css\pages\home.css" "frontend\css\pages\home.css" -Force
Copy-Item "$b\frontend\js\th-app-promo.js" "frontend\js\th-app-promo.js" -Force
```
