<#
.SYNOPSIS
  Appelle l'API REST WordPress du site AARISE avec un mot de passe d'application.

  Par défaut sur le staging (pendant la refonte) ; -Site live pour la production.

.EXAMPLE
  ./tools/wp.ps1 GET 'pages?per_page=50&_fields=id,slug,status,title'
  ./tools/wp.ps1 POST pages/12 -Body @{ title = 'Home' }
  ./tools/wp.ps1 POST pages/12 -ContentFile content/home.html
  ./tools/wp.ps1 GET 'pages?_fields=id,slug' -Site live

.NOTES
  Identifiants lus dans .env à la racine du dépôt (jamais commité) :
    WP_URL=https://www.aarise.games
    WP_USER=identifiant WordPress
    WP_APP_PASSWORD=mot de passe d'application
    WP_STAGING_URL=adresse du staging Cloudways
  WP_STAGING_USER et WP_STAGING_APP_PASSWORD sont facultatifs : le staging étant une copie
  de la production, les mêmes identifiants y fonctionnent.
#>
param(
	[Parameter(Mandatory)] [ValidateSet('GET', 'POST', 'DELETE')] [string] $Method,
	[Parameter(Mandatory)] [string] $Path,
	[hashtable] $Body = @{},
	# Fichier de contenu (balisage de blocs Gutenberg) envoyé comme champ "content".
	[string] $ContentFile,
	[ValidateSet('staging', 'live')] [string] $Site = 'staging'
)

$ErrorActionPreference = 'Stop'

$envFile = Join-Path $PSScriptRoot '..\.env'
if (-not (Test-Path $envFile)) { throw "Fichier .env introuvable (voir .env.example)." }
$config = @{}
Get-Content $envFile | Where-Object { $_ -match '^\s*([A-Z_]+)\s*=\s*(.*)\s*$' } | ForEach-Object {
	$config[$Matches[1]] = $Matches[2].Trim('"', "'")
}
if ($Site -eq 'staging') {
	if (-not $config.WP_STAGING_URL) { throw "WP_STAGING_URL manquant dans .env (ou -Site live pour la production)" }
	$config.WP_URL = $config.WP_STAGING_URL
	if ($config.WP_STAGING_USER) { $config.WP_USER = $config.WP_STAGING_USER }
	if ($config.WP_STAGING_APP_PASSWORD) { $config.WP_APP_PASSWORD = $config.WP_STAGING_APP_PASSWORD }
}
foreach ($key in 'WP_URL', 'WP_USER', 'WP_APP_PASSWORD') {
	if (-not $config[$key]) { throw "$key manquant dans .env" }
}

$pair = '{0}:{1}' -f $config.WP_USER, ($config.WP_APP_PASSWORD -replace '\s', '')
$headers = @{ Authorization = 'Basic ' + [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($pair)) }
$uri = '{0}/wp-json/wp/v2/{1}' -f $config.WP_URL.TrimEnd('/'), $Path.TrimStart('/')

if ($ContentFile) {
	# ReadAllText et non Get-Content : sous PowerShell 5, la chaîne de Get-Content porte des
	# propriétés PSPath/PSProvider que ConvertTo-Json tente de sérialiser (blocage).
	$Body['content'] = [IO.File]::ReadAllText((Resolve-Path $ContentFile), [Text.Encoding]::UTF8)
}

# PowerShell 5 envoie « Expect: 100-continue » sur les POST, ce qui bloque derrière Nginx/Varnish.
[Net.ServicePointManager]::Expect100Continue = $false
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$params = @{ Uri = $uri; Method = $Method; Headers = $headers; TimeoutSec = 60 }
if ($Method -eq 'POST') {
	$params.ContentType = 'application/json; charset=utf-8'
	$params.Body = [Text.Encoding]::UTF8.GetBytes(($Body | ConvertTo-Json -Depth 10))
}

# PowerShell 5 enveloppe sinon les tableaux dans {"value":[...],"Count":n}.
Remove-TypeData System.Array -ErrorAction SilentlyContinue
$response = Invoke-RestMethod @params
ConvertTo-Json -InputObject $response -Depth 10
