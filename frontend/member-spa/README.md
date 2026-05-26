# Balancirk Member SPA (Angular)

Mobile-first one-page ledenmodule voor Balancirk, gebouwd op de bestaande Joomla API backend.

## Doel

- eenvoudig voor niet-technische gebruikers
- helder op smartphone schermen
- bruikbaar als PWA (installable op GSM)
- integreerbaar in Joomla via `layout=spa`

## Local development

```bash
cd frontend/member-spa
npm install
npm start
```

Standaard API-base: `/api/index.php/v1/balancirk` (zelfde domain, Joomla sessie/cookies).

## Productie build + Joomla deploy

```bash
cd frontend/member-spa
npm run build:deploy
```

Dit kopieert de build naar:

`components/com_balancirk/media/member-spa/browser`

De Joomla layout `member&layout=spa` laadt daarna automatisch deze bestanden.

## Auth in de app

- Primair: Joomla sessiecookie (`withCredentials`)
- Optioneel: Bearer token via `localStorage.setItem('balancirk_api_token', '...')`
