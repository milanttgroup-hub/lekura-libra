# Lektura Libra – one-page sajt

Statičan sajt (jedan `index.html`, CSS unutra, bez JavaScript-a) za uslugu lekture i korekture.

- `index.html` – stranica
- `img/` – logo (SVG) i optimizovane fotografije (WebP)

Lighthouse (mobilni): Performance 100 · Accessibility 100 · Best Practices 100 · SEO 100*

> \* Dok je ovo probna verzija, `index.html` ima `<meta name="robots" content="noindex, nofollow">`
> (zato Lighthouse SEO prikazuje 63). Pre objave na pravom domenu vratiti na `index, follow, max-image-preview:large`
> i dodati `<link rel="canonical">`.
>
> Kontakt forma trenutno otvara imejl klijent (`mailto:`); za pravo slanje poruka povezati je sa servisom za forme ili PHP skriptom na hostingu.
