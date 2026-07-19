param([string]$Root)
# Travel Hub v2 recolor pass 2: sky/blue -> teal scale.

$map = @(
    @('#e0f2fe', '#DCEEEC'), @('#E0F2FE', '#DCEEEC'),
    @('#bae6fd', '#C2E2DF'), @('#BAE6FD', '#C2E2DF'),
    @('#7dd3fc', '#9CCFCB'), @('#7DD3FC', '#9CCFCB'),
    @('#38bdf8', '#79BCB7'), @('#38BDF8', '#79BCB7'),
    @('#0ea5e9', '#5DA9A4'), @('#0EA5E9', '#5DA9A4'),
    @('#0284c7', '#457F7B'), @('#0284C7', '#457F7B'),
    @('#0369a1', '#366360'), @('#0369A1', '#366360'),
    @('#93c5fd', '#9CCFCB'), @('#93C5FD', '#9CCFCB'),
    @('#60a5fa', '#79BCB7'), @('#60A5FA', '#79BCB7'),
    @('#3b82f6', '#5DA9A4'), @('#3B82F6', '#5DA9A4'),
    @('#2563eb', '#457F7B'), @('#2563EB', '#457F7B'),
    @('#1d4ed8', '#366360'), @('#1D4ED8', '#366360'),
    @('rgba(14, 165, 233', 'rgba(93, 169, 164'),
    @('rgba(14,165,233',   'rgba(93,169,164'),
    @('rgba(56, 189, 248', 'rgba(121, 188, 183'),
    @('rgba(56,189,248',   'rgba(121,188,183'),
    @('rgba(59, 130, 246', 'rgba(93, 169, 164'),
    @('rgba(59,130,246',   'rgba(93,169,164'),
    @('rgba(37, 99, 235',  'rgba(69, 127, 123'),
    @('rgba(37,99,235',    'rgba(69,127,123')
)

$targets = @(
    (Join-Path $Root 'frontend'),
    (Join-Path $Root 'backend\components')
)

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$changed = 0

foreach ($dir in $targets) {
    Get-ChildItem -Path $dir -Recurse -Include *.css, *.php -File | ForEach-Object {
        $text = [System.IO.File]::ReadAllText($_.FullName)
        $orig = $text
        foreach ($pair in $map) {
            $text = $text.Replace($pair[0], $pair[1])
        }
        if ($text -ne $orig) {
            [System.IO.File]::WriteAllText($_.FullName, $text, $utf8NoBom)
            $script:changed++
            Write-Output ("CHANGED " + $_.FullName.Substring($Root.Length + 1))
        }
    }
}

Write-Output ("TOTAL CHANGED: " + $changed)
