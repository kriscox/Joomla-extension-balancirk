# Balancirk SPA (Angular)

Mobile-first Progressive Web App for Balancirk, hosted by Joomla via `view=spa`.

## Goals

- Simple for non-technical users
- Clear on smartphone screens
- Installable as a PWA
- Role-aware: members, teachers, accounting, admin (later)

## Local development

```bash
cd frontend/spa
npm install
npm start
```

Default API base: `/api/index.php/v1` (same domain, Joomla session/cookies).

## Production build + Joomla deploy

```bash
cd frontend/spa
npm run build:deploy
```

Or from the repository root:

```bash
make spa-deploy
```

This copies the build to:

`components/com_balancirk/media/spa/browser`

Create a Joomla menu item:

`index.php?option=com_balancirk&view=spa`

Optional: in Balancirk component options, set **Newsletter archive menu item** to your AcyMailing archive item.

Legacy menu items `view=member&layout=spa` still redirect to `view=spa`.

## Auth in the app

- Primary: Joomla session cookie (`withCredentials`)
- Optional: Bearer token via `localStorage.setItem('balancirk_api_token', '...')`
- Guest users see an in-app login screen that forwards to Joomla login
- Logout / password reset use Joomla user URLs injected by the host view
