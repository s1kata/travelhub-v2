param([string]$Root)
$rx = '(?:bg|text|from|to|via|border|ring|hover:bg|hover:text|focus:ring|focus:border)-(?:sky|indigo|orange)-\d+(?:/\d+)?'
$found = @{}
Get-ChildItem (Join-Path $Root 'frontend'), (Join-Path $Root 'backend\components') -Recurse -Include *.php -File | ForEach-Object {
    [regex]::Matches([IO.File]::ReadAllText($_.FullName), $rx) | ForEach-Object {
        $found[$_.Value] = [int]$found[$_.Value] + 1
    }
}
$found.GetEnumerator() | Sort-Object Value -Descending | ForEach-Object { "{0} {1}" -f $_.Key, $_.Value }
