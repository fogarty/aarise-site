<#
.SYNOPSIS
  Installe le contenu initial du site à partir de content/ : pages, projets, pied de page, menus.

.DESCRIPTION
  Lit content/pages.json. Chaque page ou projet est créé s'il n'existe pas (repéré par son slug),
  sinon son contenu est remplacé. À n'utiliser que pour une installation ou une remise à zéro :
  ensuite, le contenu se modifie dans l'éditeur WordPress, et ce script écraserait ces changements.

.EXAMPLE
  ./tools/push-content.ps1                      # staging
  ./tools/push-content.ps1 -Only about,contact  # seulement ces slugs
#>
param(
	[ValidateSet('staging', 'live')] [string] $Site = 'staging',
	[string[]] $Only
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$contentDir = Join-Path $root 'content'
$config = [IO.File]::ReadAllText((Join-Path $contentDir 'pages.json'), [Text.Encoding]::UTF8) | ConvertFrom-Json

function Wp([string] $Method, [string] $Path, [hashtable] $Body = @{}, [string] $File, [string] $Upload) {
	$params = @{ Method = $Method; Path = $Path; Body = $Body; Site = $Site }
	if ($File) { $params.ContentFile = Join-Path $contentDir $File }
	if ($Upload) { $params.UploadFile = $Upload }
	& (Join-Path $PSScriptRoot 'wp.ps1') @params | ConvertFrom-Json
}

function Wanted([string] $Slug) { -not $Only -or $Only -contains $Slug }

function Find([string] $Type, [string] $Slug) {
	$found = @(Wp GET "$Type`?slug=$Slug&status=publish,draft,private,pending&_fields=id")
	if ($found.Count) { $found[0].id } else { $null }
}

foreach ($page in $config.pages) {
	if (-not (Wanted $page.slug)) { continue }
	$body = @{ title = $page.title; slug = $page.slug; status = 'publish'; template = $page.template; excerpt = $page.excerpt }
	$id = Find 'pages' $page.slug
	$result = if ($id) { Wp POST "pages/$id" $body $page.file } else { Wp POST 'pages' $body $page.file }
	'page     {0,-16} #{1}  {2}' -f $page.slug, $result.id, $result.link
}

foreach ($project in $config.projects) {
	if (-not (Wanted $project.slug)) { continue }
	$body = @{ title = $project.title; slug = $project.slug; status = 'publish'; excerpt = $project.excerpt }
	$id = Find 'project' $project.slug
	if (-not $id -and $project.image) {
		$media = Wp POST 'media' -Upload (Join-Path $contentDir $project.image)
		Wp POST "media/$($media.id)" @{ alt_text = $project.image_alt } | Out-Null
		$body.featured_media = $media.id
	}
	$result = if ($id) { Wp POST "project/$id" $body $project.file } else { Wp POST 'project' $body $project.file }
	'project  {0,-16} #{1}  {2}' -f $project.slug, $result.id, $result.link
}

foreach ($pattern in $config.synced_patterns) {
	if (-not (Wanted $pattern.slug)) { continue }
	$body = @{ title = $pattern.title; slug = $pattern.slug; status = 'publish' }
	$id = Find 'blocks' $pattern.slug
	$result = if ($id) { Wp POST "blocks/$id" $body $pattern.file } else { Wp POST 'blocks' $body $pattern.file }
	'pattern  {0,-16} #{1}' -f $pattern.slug, $result.id
}

if (-not $Only) {
	foreach ($menu in $config.menus) {
		$existing = @(Wp GET "menus?_fields=id,name" | Where-Object { $_.name -eq $menu.name })
		if ($existing.Count) {
			$menuId = $existing[0].id
			foreach ($item in @(Wp GET "menu-items?menus=$menuId&per_page=100&_fields=id")) {
				Wp DELETE "menu-items/$($item.id)?force=true" | Out-Null
			}
			Wp POST "menus/$menuId" @{ locations = @($menu.location) } | Out-Null
		} else {
			$menuId = (Wp POST 'menus' @{ name = $menu.name; locations = @($menu.location) }).id
		}
		$order = 1
		foreach ($slug in $menu.pages) {
			$pageId = Find 'pages' $slug
			if (-not $pageId) { continue }
			Wp POST 'menu-items' @{ menus = $menuId; type = 'post_type'; object = 'page'; object_id = $pageId; status = 'publish'; menu_order = $order } | Out-Null
			$order++
		}
		'menu     {0,-16} #{1}  ({2} liens)' -f $menu.location, $menuId, ($order - 1)
	}
}
