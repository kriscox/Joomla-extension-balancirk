# Balancirk Member SPA (Angular)

Mobile-first one-page member area for Balancirk, built on the existing Joomla API backend.

## Goals

- Simple for non-technical users
- Clear on smartphone screens
- Usable as a PWA (installable on mobile)
- Integrable in Joomla via `layout=spa`

## Local development

```bash
cd frontend/member-spa
npm install
npm start
```

Default API base: `/api/index.php/v1/balancirk` (same domain, Joomla session/cookies).

## Production build + Joomla deploy

```bash
cd frontend/member-spa
npm run build:deploy
```

This copies the build to:

`components/com_balancirk/media/member-spa/browser`

The Joomla layout `member&layout=spa` then loads these files automatically.

## Auth in the app

- Primary: Joomla session cookie (`withCredentials`)
- Optional: Bearer token via `localStorage.setItem('balancirk_api_token', '...')`
