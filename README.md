# nanoCDS

## Small Content Delivery System v2.0

Lightweight PHP tool for local hosting of libraries and assets. No database, no
Composer, no build step, no framework — drop the folder on a PHP 8 host and it
works.

> [Test live on my website](https://dev.fhnb.ru/assets/)

![Preview image](/preview.jpg)

[![fhnb16](https://img.shields.io/badge/Made_by_fhnb16-2020—Now-5B3A32.svg?style=plastic&labelColor=1a2026)](https://fhnb.ru/)

---

## Contents

- [What it does](#what-it-does)
- [Requirements](#requirements)
- [Installation](#installation)
- [Repository layout](#repository-layout)
- [URLs](#urls)
- [JSON API](#json-api)
- [Version resolution](#version-resolution)
- [Configuration](#configuration)
- [Project structure](#project-structure)
- [Security model](#security-model)
- [Upgrading from 1.x](#upgrading-from-1x)
- [Testing](#testing)
- [Roadmap](#roadmap)

---

## What it does

nanoCDS turns a folder of libraries into a browsable, linkable asset host.
Point it at a directory that contains `bootstrap/5.3.3/…`, `jquery/3.7.1/…` and
so on, and it gives you:

- a browsable web UI for the whole tree;
- stable URLs you can paste into `<link>` and `<script>` tags on any site;
- a "give me the newest version of X" endpoint that resolves versions
  semantically;
- a JSON API so other projects can resolve asset URLs without scraping HTML;
- a per-file preview page with ready-to-copy URLs and HTML tags.

Everything is rendered server side. The only JavaScript nanoCDS ships is a
~15-line clipboard handler for the Copy buttons; the UI works without it.

---

## Requirements

- **PHP 8.1 or newer** (developed and tested against PHP 8.4).
  `str_contains`, `match`, enums-free but typed code, `never` return types.
- Apache with `mod_rewrite`, or any server you can point at
  `NanoCDS/index.php` as a front controller.
- No PHP extensions beyond the defaults. `mime_content_type()` (fileinfo) is
  used as a fallback and degrades gracefully if missing.
- **No shell access required.** Unlike 1.x, nanoCDS never calls `du`, `find` or
  `exec()`, so it runs on Windows and on hosts where `exec` is disabled.

---

## Installation

1. Copy the contents of `src/` into the folder that should serve your assets,
   for example `public_html/assets/`:

   ```
   assets/
     .htaccess
     NanoCDS/
   ```

2. Put your library folders next to `NanoCDS/`:

   ```
   assets/
     .htaccess
     NanoCDS/
     bootstrap/
     jquery/
   ```

3. Open `https://example.com/assets/`.

That is the whole installation. The public URL prefix (`/assets/`) is detected
automatically, so you can also install into `/cdn/`, `/static/` or the document
root without editing anything.

If your host has `AllowOverride None`, add the equivalent of the `.htaccess`
rewrite to the vhost:

```apache
RewriteEngine On
RewriteRule ^/assets/(.*)$ /assets/NanoCDS/index.php [L,QSA]
```

### nginx

```nginx
location /assets/ {
    try_files $uri @nanocds;
}
location @nanocds {
    fastcgi_pass  unix:/run/php/php8.4-fpm.sock;
    include       fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/assets/NanoCDS/index.php;
    fastcgi_param SCRIPT_NAME     /assets/NanoCDS/index.php;
}
```

`SCRIPT_NAME` matters: nanoCDS derives the public prefix from it. If you cannot
set it, pin the prefix explicitly with `base_path` in `config.php`.

---

## Repository layout

nanoCDS expects, but does not require, the conventional CDN shape:

```
assets/
  bootstrap/
    5.3.3/
      bootstrap.min.css
      bootstrap.css
    5.10.0/
      bootstrap.min.css
  jquery/
    3.7.1/
      jquery.min.js
```

- The **first** path segment is the asset name (`bootstrap`).
- Any segment that looks like a version (`5.3.3`, `v2`, `1.0.0-beta`) is treated
  as the version. The deepest one wins, so `bootstrap/5.3.3/js/` still resolves
  to `5.3.3`.
- A folder named **`__hidden`** is invisible everywhere — listings, search,
  API and direct URLs.
- Files and folders whose name starts with a dot (`.git`, `.env`, `.htaccess`)
  are invisible and unservable.
- The `NanoCDS` folder itself is invisible.

---

## URLs

Every page is reachable in two equivalent forms. The classic query-string form
is what nanoCDS 1.x produced and **is still fully supported** — existing
integrations do not need to change.

| Page | Classic | Pretty |
|---|---|---|
| Index | `/assets/` | `/assets/` |
| About | `/assets/?page=about` | `/assets/about` |
| Tools | `/assets/?page=tools` | `/assets/tools` |
| Folder | `/assets/?page=dir&name=bootstrap/5.3.3` | `/assets/dir/bootstrap/5.3.3` |
| Raw file | `/assets/?page=view&dir=bootstrap/5.3.3&name=bootstrap.min.css` | `/assets/view/bootstrap/5.3.3/f/bootstrap.min.css` |
| Preview | `/assets/?page=preview&dir=…&name=…` | `/assets/preview/bootstrap/5.3.3/f/bootstrap.min.css` |
| Search | `/assets/?page=search&query=jquery` | `/assets/search/jquery` |
| Latest | `/assets/?page=latest&asset=jquery&type=js&size=1&auto=1` | `/assets/latest/jquery/t/js/s/1/a/1` |
| JSON API | `/assets/?page=api&q=bootstrap` | `/assets/api/bootstrap` |

A query string always wins: if the request has one, the pretty path is ignored.

### `view` — the raw file endpoint

This is the URL other projects consume. It sends the file with an explicit
`Content-Type`, `Content-Length`, `X-Content-Type-Options: nosniff`,
`Cache-Control: public, max-age=86400` and permissive CORS headers.

```html
<link rel="stylesheet" href="https://cdn.example.com/assets/view/bootstrap/5.3.3/f/bootstrap.min.css">
```

### `search` — find files by name

`query` accepts glob wildcards: `*`, `?`, `[...]`, `{a,b}`.

- `b*r?p` → bootstrap
- `gr[ae]y` → gray / grey

Path separators are stripped from the pattern; it only ever matches file names.

### `latest` — resolve the newest version

| Parameter | Values | Meaning |
|---|---|---|
| `asset` | name or glob | Library to look for |
| `type` | `any` `css` `js` `json` `xml` `svg` `png` `gif` `jpg` `ttf` `zip` `rar` `7z` `exe` `txt` `html` | Restrict by extension |
| `size` | `0` any, `1` minified only, `2` full (non-minified) only | |
| `auto` | `0` list matches, `1` redirect to the newest file, `-1` show the permanent URL | |

`auto=1` is the interesting one: the URL is stable, and the redirect target
moves as you add new versions.

```html
<script src="https://cdn.example.com/assets/latest/jquery/t/js/s/1/a/1"></script>
```

---

## JSON API

```
GET /assets/?page=api&q=bootstrap
GET /assets/api/bootstrap
```

| Parameter | Default | Meaning |
|---|---|---|
| `q` | *(required)* | Asset name or glob |
| `type` | `any` | Same values as `latest` |
| `size` | `0` | `0` any, `1` minified only, `2` full only |
| `latest` | `0` | `1` = only the newest version of each asset |
| `flat` | `0` | `1` = a flat `files` array instead of the asset/version tree |

Responses are `application/json`, cached for 5 minutes, and CORS-open so they
can be fetched from any origin. All URLs in the response are absolute.

```json
{
  "nanocds": "2.0",
  "query": "bootstrap",
  "count": 3,
  "truncated": false,
  "assets": [
    {
      "name": "bootstrap",
      "latest": "5.10.0",
      "versions": [
        {
          "version": "5.10.0",
          "files": [
            {
              "name": "bootstrap.min.css",
              "dir": "bootstrap/5.10.0",
              "rel": "bootstrap/5.10.0/bootstrap.min.css",
              "version": "5.10.0",
              "size": 195678,
              "modified": "2026-08-17T06:39:30+00:00",
              "url": "https://cdn.example.com/assets/?page=view&dir=bootstrap/5.10.0&name=bootstrap.min.css",
              "pretty": "https://cdn.example.com/assets/view/bootstrap/5.10.0/f/bootstrap.min.css"
            }
          ]
        }
      ]
    }
  ]
}
```

Errors return the same envelope with an `error` field and an HTTP status:

```json
{ "nanocds": "2.0", "error": "Parameter `q` is required", "usage": "…" }
```

Example — resolve the newest minified build from another project:

```js
const r = await fetch('https://cdn.example.com/assets/api/bootstrap?type=css&size=1&latest=1&flat=1');
const { files } = await r.json();
document.head.insertAdjacentHTML('beforeend', `<link rel="stylesheet" href="${files[0].pretty}">`);
```

---

## Version resolution

Versions are compared with PHP's `version_compare()`, not alphabetically.

| | 1.x | 2.0 |
|---|---|---|
| `1.9` vs `1.10` | `1.9` wins (wrong) | `1.10` wins |
| `5.9.9` vs `5.10.0` | `5.9.9` wins (wrong) | `5.10.0` wins |
| `v2` vs `v10` | `v2` wins (wrong) | `v10` wins |
| Pre-release `1.0.0-beta` vs `1.0.0` | undefined | `1.0.0` wins |

Files with no version in their path sort last. Ties are broken by natural order
of the full path, so results are stable between requests.

> **This changes which file `?page=latest&auto=1` returns** for repositories
> that have a two-digit minor or patch version. That is the point of the fix,
> but it is a behaviour change worth knowing about before you deploy.

---

## Configuration

Everything tunable lives in `NanoCDS/config.php`, which returns a plain array.

| Key | Default | Purpose |
|---|---|---|
| `version` | `'2.0'` | Reported on About and in the API |
| `base_path` | `null` | Public prefix. `null` = autodetect from `SCRIPT_NAME` |
| `canonical_host` | `null` | Host used for absolute URLs. Set it to be immune to `Host` header spoofing |
| `hidden` | `['__hidden', 'NanoCDS']` | Never listed, searched or served |
| `blocked_ext` | php, phtml, phar, ini, env, sh, … | Never sent to a client |
| `listing_hidden_ext` | php, phtml, htm, html | Hidden from listings and search |
| `max_results` | `2000` | Hard cap on search / API result sets |
| `dir_size` | `true` | Show total size of sub-folders in listings. Set to `false` on very large repositories — computing it walks each sub-tree |
| `preview_bytes` | `262144` | How much of a text file the preview renders |
| `preview_text_ext` | css, js, json, xml, … | Rendered as text |
| `preview_image_ext` | png, jpg, svg, … | Rendered as an image |
| `mime` | map | Explicit `Content-Type` per extension |
| `asset_max_age` | `86400` | `Cache-Control: max-age` for assets |

The legacy `$rootDir` global from 1.x is still honoured if you set it.

---

## Project structure

```
src/
  .htaccess              Apache front-controller rewrite, MIME types, caching
  NanoCDS/
    index.php            Front controller: bootstrap + dispatch. No HTML.
    config.php           All configuration, returns an array
    router.php           URL -> page + $_GET, for both URL styles
    url_parser.php       Compatibility wrapper around router.php (1.x API)
    header.php           Document head and page opening
    footer.php           Page closing, timing, clipboard handler
    lib/
      path.php           Path resolution, containment checks, URL building
      fs.php             Listings, sizes, counts, glob search, byte formatting
      version.php        Semantic version detection, sorting, grouping
      http.php           Headers, CORS, asset delivery, JSON, redirects
      view.php           Escaping and the small rendering helpers
      legacy.php         1.x function names kept as thin wrappers
    pages/
      main.php           Project index
      about.php          About
      dir.php            Directory listing
      view.php           Raw file delivery
      preview.php        Preview + copyable links
      search.php         Search by file name
      latest.php         Newest-version resolution
      tools.php          Statistics and search forms
      api.php            JSON API
      support.php        Redirect
```

**Rules the code follows**

- `index.php` contains no HTML and no page logic; it only wires things up.
- Pages hold markup, `lib/` holds logic. No page touches the filesystem
  directly — it goes through `lib/fs.php` and `lib/path.php`.
- Every module starts with `defined('NANO_BOOT') or exit;`, so no file can be
  requested on its own.
- Every file declares `strict_types=1`.
- Every value printed into a page goes through `e()`.
- There is no `goto` anywhere. 1.x dispatched with `goto` labels and jumped
  into `if` blocks; 2.0 uses a whitelist of page names mapped to files.

---

## Security model

The rule is simple: **nothing outside the repository root is ever readable, and
nothing inside `NanoCDS/`, `__hidden/` or any dotfile is readable at all.**

Enforcement lives in one function, `nano_resolve()` in `lib/path.php`:

1. reject null bytes;
2. normalise `\` to `/` and split into segments;
3. reject any segment starting with `.` (covers `.`, `..`, `.git`, `.env`,
   `.htaccess`) and any segment in the `hidden` list;
4. `realpath()` the result, which also resolves symlinks;
5. require the resolved path to be a strict prefix match of the repository root;
6. re-run the segment checks on the resolved path, in case a symlink landed on
   hidden content.

Additional measures:

- **No shell.** `exec()`, `du` and `find` are gone; sizes and counts come from
  `RecursiveIteratorIterator`. There is no command injection surface left.
- **Output escaping.** All page output goes through
  `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`, including file
  names, which are attacker-controllable if someone can write to the
  repository.
- **Header injection.** CR, LF and NUL are stripped from every value that
  reaches `header()`, including `Content-Disposition` and `Location`.
- **Page dispatch.** The `page` parameter is matched against a whitelist before
  it is turned into a file name, so it cannot be used for local file inclusion.
- **Host header.** Absolute URLs use `SERVER_NAME` with a character whitelist,
  or `canonical_host` from the config if you set it.
- **JSON.** Encoded with `JSON_HEX_TAG|HEX_AMP|HEX_APOS|HEX_QUOT` and served
  with `nosniff`, so a response can never be reinterpreted as markup.
- **SVG.** Served with `Content-Security-Policy: default-src 'none'; sandbox`,
  which stops a hostile SVG from running scripts if opened directly. Embedding
  via `<img>`, `<use>` or CSS is unaffected.
- **CORS.** Wildcard CORS is sent **only** on the raw asset endpoint and the
  JSON API — exactly the responses that need to be readable cross-origin. The
  HTML UI no longer advertises it. This keeps libraries usable from any other
  site, including sites on other servers.
- **Search patterns.** Path separators, `..` and hidden names are stripped from
  glob patterns; a pattern can only ever match a file name.
- **Result caps.** Search and API results are capped at `max_results` and the
  response says so, so a `*` query cannot be used to exhaust memory.

### Verified attack coverage

The following are exercised against a live server on every change and all are
blocked:

| Class | Examples |
|---|---|
| Path traversal | `../`, `%2e%2e`, double-encoded, backslash, absolute paths, `....//`, traversal inside pretty URLs |
| Symlink escape | symlinked directory and symlinked file pointing at `/etc` |
| Hidden content | `__hidden` via `view`, `dir`, `search` and the API |
| Source disclosure | `NanoCDS/*.php`, `lib/*.php`, `pages/*.php`, `.htaccess`, `.git/config` |
| Null byte | `index.php%00.css` extension bypass |
| Reflected XSS | `query`, `asset`, `dir`, `name`, `q`, attribute breakout, malicious file names in listings |
| Header injection | CRLF in `name`, in `Location`, open redirect via `//evil.com` |
| Host header poisoning | spoofed `Host` reflected into permalinks |
| LFI via router | `?page=../../etc/passwd`, `?page=../lib/path` |
| Direct module access | requesting any module file without the front controller |

---

## Upgrading from 1.x

Drop-in. Copy the new `src/` over the old one; your asset folders are
untouched.

**Nothing to change:** every 1.x URL, both query-string and pretty, resolves to
the same file. The bytes and headers of `?page=view` responses are identical.

**Behaviour that intentionally changed:**

| | 1.x | 2.0 |
|---|---|---|
| `?page=dir`, `?page=tools` on PHP 8 | fatal `TypeError` | work |
| Missing file via `?page=view` | HTTP 200 with an error page | HTTP 404 |
| Blocked extension via `?page=view` | HTTP 200 with a warning page | HTTP 403 |
| `latest` ordering | alphabetical | semantic (`version_compare`) |
| CORS on HTML pages | wildcard | none (assets and API keep it) |
| Pretty links in listings | built in JS, hardcoded to `dev.fhnb.ru` | built in PHP, correct host |
| `.htaccess`, `.git`, dotfiles | readable through `dir=.` | blocked |
| Listing order | one alphabetical pile, descending | folders first (newest version on top), then files in natural order |
| Public prefix | hardcoded `/assets/` | autodetected, configurable |
| Short open tags `<?` | required `short_open_tag=On` | standard `<?php` / `<?=` |

The 404 and 403 status codes are the only change that can be noticed by a
consumer, and only by one that was checking status codes — previously those
requests returned a `200 OK` HTML page where a file was expected.

**Removed nothing.** `header.php`, `footer.php` and `url_parser.php` still
exist, and the 1.x helper functions (`formatBytes`, `glob_tree_search`,
`countFilesAndDirs`, `removeLastOccurrence`, `getDirectoryPath`, `clean_url`,
`getDirContents`) are still defined in `lib/legacy.php`.

---

## Testing

There is no test framework dependency. A live-server check is enough:

```bash
# syntax check every module
cd src/NanoCDS
for f in *.php lib/*.php pages/*.php; do php -l "$f"; done

# run it with every diagnostic turned on
php -d error_reporting=E_ALL -d display_errors=1 -S 127.0.0.1:8080 -t /path/to/docroot
```

Walk the UI with `error_reporting=E_ALL`; a correct build emits **zero**
warnings, notices and deprecations on every page.

When changing anything that touches `view`, compare the response bytes and
headers against the previous version — that endpoint is the contract dependent
projects rely on.

---

## Roadmap

Not implemented yet, in rough priority order:

- `ETag` / `Last-Modified` with `304 Not Modified` handling — the single
  biggest win left for consumers.
- `Range` request support for large files.
- Pre-compressed `.gz` / `.br` delivery based on `Accept-Encoding`.
- SRI (`integrity="sha384-…"`) hashes shown on the preview page.
- Cached directory index so `tools` and `search` do not walk the tree on every
  request.
- PHPStan level 6 in CI.

---

Place folders with content in the same location as the `NanoCDS` folder and the
`.htaccess` file. Name a folder `__hidden` to hide it from nanoCDS.
