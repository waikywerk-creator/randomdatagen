param(
    [ValidateRange(1, 65535)]
    [int] $Port = 8000,

    [switch] $NoBrowser
)

$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

function Stop-WithMessage {
    param([string] $Message)

    Write-Host ''
    Write-Host $Message -ForegroundColor Red
    Write-Host 'Druk op Enter om dit venster te sluiten.' -ForegroundColor DarkGray
    [void] (Read-Host)
    exit 1
}

Write-Host ''
Write-Host '  Nederlandse nepdata-generator' -ForegroundColor Cyan
Write-Host '  De applicatie wordt voorbereid...' -ForegroundColor DarkGray
Write-Host ''

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Stop-WithMessage 'PHP is niet gevonden. Installeer PHP 8.3 of hoger en voeg php toe aan PATH.'
}

& php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    Stop-WithMessage 'Deze applicatie vereist PHP 8.3 of hoger.'
}

if (-not (Test-Path -LiteralPath 'artisan')) {
    Stop-WithMessage 'artisan is niet gevonden. Start dit bestand vanuit de projectmap.'
}

if (-not (Test-Path -LiteralPath '.env')) {
    if (-not (Test-Path -LiteralPath '.env.example')) {
        Stop-WithMessage '.env en .env.example zijn niet gevonden.'
    }

    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    Write-Host '[gereed] .env aangemaakt' -ForegroundColor Green
}

if (-not (Test-Path -LiteralPath 'vendor\autoload.php')) {
    Write-Host '[eenmalig] PHP-pakketten installeren...' -ForegroundColor Yellow

    if (Get-Command composer -ErrorAction SilentlyContinue) {
        & composer install --no-interaction --prefer-dist
    }
    elseif (Test-Path -LiteralPath 'composer.phar') {
        & php composer.phar install --no-interaction --prefer-dist
    }
    else {
        Stop-WithMessage 'Composer is niet gevonden. Installeer Composer 2 en voeg composer toe aan PATH.'
    }

    if ($LASTEXITCODE -ne 0) {
        Stop-WithMessage 'Het installeren van de PHP-pakketten is mislukt.'
    }

    Write-Host '[gereed] PHP-pakketten geïnstalleerd' -ForegroundColor Green
}

$appKeyLine = Get-Content -LiteralPath '.env' | Where-Object { $_ -match '^APP_KEY=' } | Select-Object -First 1
if (-not $appKeyLine -or $appKeyLine -eq 'APP_KEY=') {
    & php artisan key:generate --no-interaction
    if ($LASTEXITCODE -ne 0) {
        Stop-WithMessage 'De applicatiesleutel kon niet worden aangemaakt.'
    }
}

$url = "http://127.0.0.1:$Port"
Write-Host ''
Write-Host "De generator draait op $url" -ForegroundColor Green
Write-Host 'Laat dit venster open. Druk op Ctrl+C om de app te stoppen.' -ForegroundColor DarkGray
Write-Host ''

if (-not $NoBrowser) {
    $openCommand = "Start-Sleep -Seconds 2; Start-Process '$url'"
    Start-Process powershell -WindowStyle Hidden -ArgumentList '-NoProfile', '-Command', $openCommand | Out-Null
}

& php artisan serve --host=127.0.0.1 --port=$Port

if ($LASTEXITCODE -ne 0) {
    Stop-WithMessage "De webserver kon niet op poort $Port worden gestart. Mogelijk is die poort al in gebruik."
}
