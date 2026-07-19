param([string]$Root)
# Travel Hub v2 recolor: indigo/orange/sky -> navy/teal/coral (CSS + PHP inline styles).

$map = @(
    @('rgba(79, 70, 229',  'rgba(26, 26, 64'),
    @('rgba(79,70,229',    'rgba(26,26,64'),
    @('rgba(99, 102, 241', 'rgba(93, 169, 164'),
    @('rgba(99,102,241',   'rgba(93,169,164'),
    @('rgba(255, 107, 0',  'rgba(255, 107, 107'),
    @('rgba(255,107,0',    'rgba(255,107,107'),
    @('rgba(229, 95, 0',   'rgba(246, 82, 82'),
    @('rgba(229,95,0',     'rgba(246,82,82'),
    @('rgba(59, 163, 255', 'rgba(93, 169, 164'),
    @('rgba(59,163,255',   'rgba(93,169,164'),
    @('rgba(123, 196, 255','rgba(140, 199, 195'),
    @('rgba(123,196,255',  'rgba(140,199,195'),
    @('#4f46e5', '#1A1A40'), @('#4F46E5', '#1A1A40'),
    @('#4338ca', '#10102E'), @('#4338CA', '#10102E'),
    @('#6366f1', '#5DA9A4'), @('#6366F1', '#5DA9A4'),
    @('#818cf8', '#7FC1BC'), @('#818CF8', '#7FC1BC'),
    @('#a5b4fc', '#A8D5D1'), @('#A5B4FC', '#A8D5D1'),
    @('#eef2ff', '#EEF7F6'), @('#EEF2FF', '#EEF7F6'),
    @('#e0e7ff', '#DCEEEC'), @('#E0E7FF', '#DCEEEC'),
    @('#c7d2fe', '#C2E2DF'), @('#C7D2FE', '#C2E2DF'),
    @('#FF6B00', '#FF6B6B'), @('#ff6b00', '#FF6B6B'),
    @('#E55F00', '#F65252'), @('#e55f00', '#F65252'),
    @('#ff8b3d', '#FF8A80'), @('#FF8B3D', '#FF8A80'),
    @('#fff7f0', '#FFF5F5'), @('#fff7ed', '#FFF5F5'), @('#FFF7ED', '#FFF5F5'),
    @('#3ba3ff', '#5DA9A4'), @('#3BA3FF', '#5DA9A4'),
    @('#7bc4ff', '#8CC7C3'), @('#7BC4FF', '#8CC7C3')
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
