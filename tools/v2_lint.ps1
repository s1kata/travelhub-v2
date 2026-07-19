param([string]$Root)
$fail = 0
Get-ChildItem (Join-Path $Root 'frontend'), (Join-Path $Root 'backend') -Recurse -Include *.php -File | ForEach-Object {
    $out = & php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        $script:fail++
        Write-Output ("FAIL " + $_.FullName)
        Write-Output $out
    }
}
Write-Output ("LINT FAILURES: " + $fail)
