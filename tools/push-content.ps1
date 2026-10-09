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

# Identifiants des formulaires SureForms, par slug : {{FORM:slug}} dans un contenu devient l'ID.
$formIds = @{}

function Wp([string] $Method, [string] $Path, [hashtable] $Body = @{}, [string] $File, [string] $Upload, [hashtable] $Replace = @{}) {
	$params = @{ Method = $Method; Path = $Path; Body = $Body; Site = $Site }
	if ($File) {
		$text = [IO.File]::ReadAllText((Join-Path $contentDir $File), [Text.Encoding]::UTF8)
		foreach ($key in $formIds.Keys) { $text = $text.Replace("{{FORM:$key}}", [string] $formIds[$key]) }
		foreach ($key in $Replace.Keys) { $text = $text.Replace($key, [string] $Replace[$key]) }
		$Body['content'] = $text
	}
	if ($Upload) { $params.UploadFile = $Upload }
	& (Join-Path $PSScriptRoot 'wp.ps1') @params | ConvertFrom-Json
}

function Wanted([string] $Slug) { -not $Only -or $Only -contains $Slug }

function Find([string] $Type, [string] $Slug) {
	$found = @(Wp GET "$Type`?slug=$Slug&status=publish,future,draft,private,pending&_fields=id")
	if ($found.Count) { $found[0].id } else { $null }
}

# Style des formulaires SureForms : couleurs du site (le thème complète dans site.css).
$formStyling = @{
	primary_color = '#cdab5b'; text_color = '#ece8df'; text_color_on_primary = '#0c0c0e'
	field_spacing = 'medium'; submit_button_alignment = 'left'; bg_type = 'color'; bg_color = 'transparent'
}

foreach ($form in $config.forms) {
	$id = Find 'sureforms_form' $form.slug
	if (-not $id) {
		$id = (Wp POST 'sureforms_form' @{ title = $form.title; slug = $form.slug; status = 'publish' }).id
	}
	$formIds[$form.slug] = $id
	if (-not (Wanted $form.slug)) { continue }
	$meta = @{
		_srfm_submit_button_text = $form.submit
		_srfm_forms_styling      = $formStyling
		# Copie cachée à l'adresse d'administration (Réglages > Général) : l'envoi passe par le compte
		# Gmail de cette adresse, et Gmail ne montre pas en boîte de réception les messages qu'on
		# s'envoie via un groupe (contact@, press@, jobs@) ; une copie directe, si.
		_srfm_email_notification = @(@{
			id = 1; status = $true; is_raw_format = $false; name = 'Admin Notification Email'
			email_to = $form.email_to; email_reply_to = '{form:srfm-email}'; from_name = '{site_title}'
			from_email = 'contact@aarise.games'; email_cc = ''; email_bcc = '{admin_email}'
			subject = $(if ($form.subject) { $form.subject } else { 'New {form_title} - {site_title}' }); email_body = '{all_data}'
		})
		_srfm_form_confirmation  = @(@{
			id = 1; confirmation_type = 'same page'; page_url = ''; custom_url = ''
			message = "<h3>Thank you</h3><p>$($form.confirmation)</p>"; submission_action = 'hide form'
			enable_query_params = $false; query_params = @()
		})
	}
	Wp POST "sureforms_form/$id" @{ title = $form.title; status = 'publish'; meta = $meta } $form.file -Replace @{ '{{FORM_ID}}' = $id } | Out-Null
	'form     {0,-16} #{1}  -> {2}' -f $form.slug, $id, $form.email_to
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
	# Teaser dont le vrai projet est publié : il a été retiré par le site, on ne le recrée pas.
	if ($project.reveals) {
		$targetId = Find 'project' $project.reveals
		if ($targetId -and (Wp GET "project/$targetId`?context=edit&_fields=status").status -eq 'publish') {
			'teaser   {0,-16} retiré ({1} est publié)' -f $project.slug, $project.reveals
			continue
		}
	}
	# Statut : publié par défaut ; « future » + date_gmt (UTC) pour un projet planifié (annonce).
	$status = if ($project.status) { $project.status } else { 'publish' }
	$body = @{ title = $project.title; slug = $project.slug; status = $status; excerpt = $project.excerpt }
	if ($project.date_gmt) { $body.date_gmt = $project.date_gmt }
	$id = Find 'project' $project.slug
	if (-not $id -and $project.image) {
		$media = Wp POST 'media' -Upload (Join-Path $contentDir $project.image)
		Wp POST "media/$($media.id)" @{ alt_text = $project.image_alt } | Out-Null
		$body.featured_media = $media.id
	}
	$result = if ($id) { Wp POST "project/$id" $body $project.file } else { Wp POST 'project' $body $project.file }
	'project  {0,-16} #{1}  {2}  [{3}]' -f $project.slug, $result.id, $result.link, $result.status
}

# Teasers : « reveals » = slug du vrai projet ; le teaser disparaît quand celui-ci est publié.
foreach ($project in $config.projects) {
	if (-not $project.reveals -or -not (Wanted $project.slug)) { continue }
	$teaserId = Find 'project' $project.slug
	$targetId = Find 'project' $project.reveals
	if ($teaserId -and $targetId) {
		Wp POST "project/$teaserId" @{ meta = @{ aarise_reveals = [int] $targetId } } | Out-Null
		'teaser   {0,-16} -> {1} (#{2})' -f $project.slug, $project.reveals, $targetId
	}
}

foreach ($job in $config.jobs) {
	if (-not (Wanted $job.slug)) { continue }
	$body = @{ title = $job.title; slug = $job.slug; status = 'publish'; excerpt = $job.excerpt }
	$id = Find 'job' $job.slug
	$result = if ($id) { Wp POST "job/$id" $body $job.file } else { Wp POST 'job' $body $job.file }
	'job      {0,-16} #{1}  {2}' -f $job.slug, $result.id, $result.link
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
