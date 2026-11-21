# Lektori Libra – one-page sajt (SR + EN)

Statičan sajt (jedan `index.html`, CSS unutra, bez JavaScript-a) za uslugu lekture i korekture.

- `index.html` – stranica
- `img/` – logo (SVG) i optimizovane fotografije (WebP)
- `en/` – engleska verzija (`en/`, `en/terms/`, `en/privacy-policy/`), povezana hreflang oznakama
- `llms.txt`, `llms-full.txt` – sažetak sajta za AI modele
- `uslovi-koriscenja/`, `politika-privatnosti/` – pravne stranice (podaci preduzetnika: PIB 109642260, MB 64324861)
- `page2/`, `page3/` – alternativne dizajn verzije (pink/teget i ljubičasto/lila)

Lighthouse (mobilni): Performance 100 · Accessibility 100 · Best Practices 100 · SEO 100*

> \* Dok je ovo probna verzija, sve stranice imaju `<meta name="robots" content="noindex, nofollow">`
> (zato Lighthouse SEO prikazuje 63). Pre objave na pravom domenu vratiti na `index, follow, max-image-preview:large`
> i dodati `<link rel="canonical">`.
>
> Kontakt forma šalje upit i do 3 priloga (ukupno 10 MB) preko `posalji.php` na prevodiocibg@gmail.com.

## Objavljivanje na lektorilibra.rs

Svaka izmena na grani `main` automatski se postavlja na hosting (`.github/workflows/deploy.yml`).
Na hosting idu glavna verzija, engleska verzija (`en/`), pravne stranice, `llms.txt`, `posalji.php`, `.htaccess`, `.user.ini`, `robots.txt`, `sitemap.xml` i `404.html`;
`page2/`, `page3/` i README ostaju samo na GitHub-u. Prilikom postavljanja noindex se automatski menja u `index, follow`.

Potrebno u GitHub → Settings → Secrets and variables → Actions:
- **Secrets:** `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
- **Variables (opciono):** `FTP_PROTOCOL` (`ftps` podrazumevano, ili `ftp`), `FTP_PORT` (21), `FTP_DIR` (`./` podrazumevano)
