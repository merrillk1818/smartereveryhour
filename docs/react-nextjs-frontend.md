# React + Next.js in this project

> **Note:** this public repo contains application code only — the deploy/infra
> layer (docker-compose, nginx vhost configs, CI) lives in the private
> production repo and isn't included here. The nginx/Docker details below
> describe how it's wired up in production; treat them as the "how this would
> be deployed" story rather than files present in this checkout.

This app was pure Laravel + Blade + a sprinkle of vanilla JS. Two pieces of frontend
were rewritten to demonstrate React and Next.js, chosen deliberately for *where they
actually fit* rather than bolted on everywhere:

1. The AI chat / learn-session page → a **React component**, still served by Laravel.
2. The public landing page (`/`) → a standalone **Next.js app**, run as its own
   container and routed to by nginx.

Why two different approaches instead of one? Because "React" and "Next.js" solve
different problems, and this app has two different problems:

- The chat page is a single, already-authenticated view with non-trivial client
  state (a growing message list, in-flight loading states, a multi-step "save
  topic" form). That's a **UI state management** problem — React's job.
- The landing page is a public, stateless marketing page that benefits from
  being served as fast static HTML with its own routing/build pipeline. That's
  an **application framework** problem — Next.js's job. Next.js can't be *part of*
  a page Laravel renders — it owns the request lifecycle (routing, SSR) the same
  way Laravel does — so it only makes sense as its own service for a boundary
  Laravel doesn't need to touch.

---

## 1. React: the learn/chat page

**Where:** `laravel/resources/js/components/{LearnChat,ChatPanel,SaveTopicPanel}.jsx`,
mounted by `laravel/resources/js/learn-chat.jsx`, into `resources/views/learn/session.blade.php`.

**What it replaced:** ~120 lines of imperative vanilla JS — manual DOM node creation
for every chat bubble, manually toggling `style.display` on the save-topic panel,
manually reading `.value` off five different form fields on every save attempt.

**Why this is the right tool here:**

- The chat transcript, the "sending…" spinner state, and the save-topic form
  (title/description/curriculum/skill-level/suggest/save, each with its own
  loading and result state) are all pieces of **UI state that change together**.
  In the old vanilla JS, every state change was a manual `document.getElementById`
  + direct DOM mutation — easy to get out of sync (e.g. forgetting to reset a
  button's disabled state on one code path). In React, state and the DOM it
  produces are always consistent by construction: you set state, React
  re-renders, done.
- The three concerns (chat transcript, message composer, topic-saving form) are
  now three components (`ChatPanel`, `SaveTopicPanel`, and `LearnChat` composing
  them) instead of one flat script — easier to read, easier to extend.

**How it works:**

1. Blade still renders the page shell and does the one thing Blade is good at —
   pulling `$session` and `$curricula` from the database. Instead of looping
   over messages in Blade, it serializes everything the component needs into a
   single JSON blob on a mount-point `<div>`:
   ```blade
   <div id="learn-chat-root" data-props='@json([...])'></div>
   @vite(['resources/js/learn-chat.jsx'])
   ```
2. `learn-chat.jsx` is a new Vite build entry (added in `vite.config.js`
   alongside the existing `app.js`/`app.css` entries). On page load it reads
   `data-props`, `JSON.parse`s it, and calls React's `createRoot(...).render(...)`
   on that div — this is the whole "hydrate a React island inside a
   server-rendered page" pattern.
3. `LearnChat` owns the actual state (`messages`, `sending`) and the three
   `fetch()` calls to Laravel's existing endpoints (`/learn/{token}/chat`,
   `/suggest-topic`, `/save-topic`) — the API didn't change at all, only what
   calls it. `ChatPanel` and `SaveTopicPanel` are controlled by props/callbacks
   from `LearnChat`, the standard React "lift state up" pattern.
4. `ChatPanel` uses `useEffect` to auto-scroll to the newest message whenever
   `messages` changes — declarative ("run this when `messages` changes") instead
   of the old imperative `messagesEl.scrollTop = messagesEl.scrollHeight` sprinkled
   after every mutation site.

Nothing about Laravel's routes, controllers, or auth changed — React only
replaced what was already client-side rendering.

---

## 2. Next.js: the public landing page

**Where:** new top-level `landing/` directory — a complete, independent Next.js
15 App Router project with its own `package.json`, `Dockerfile`, and `next.config.js`.

**What it replaced:** `laravel/resources/views/welcome.blade.php` is left in place
untouched (so nothing was torn down / the route still exists if ever needed
directly), but public traffic to `/` no longer reaches it — nginx now sends
that one path to the new service instead.

**Why Next.js specifically, and why as a separate service:**

- Next.js's App Router defaults every component to a **Server Component**:
  rendered to HTML on the server (here, at build time, since the page has no
  per-request data) with **zero JavaScript shipped to the browser** for it. Only
  `NavBar.jsx` opts into being a **Client Component** (via the `'use client'`
  directive) because it's the only piece of the page with actual interactivity
  (the mobile hamburger menu's open/closed state). The build output confirms
  this split: the production build is ~103KB JS total for the *whole page*,
  almost entirely React's own runtime — the marketing content itself costs
  nothing client-side.
- This Server vs. Client Component boundary is the core new concept Next.js's
  App Router brings over plain React/Vite (which has no equivalent — everything
  in a Vite SPA bundle ships to the browser). It's a direct, visible example of
  why Next.js is a framework and not just "React with a build tool."
- It genuinely cannot be embedded inside the existing Laravel+Vite build: Next.js
  needs its own dev/production server process to do file-based routing and
  server rendering — the same job `php artisan serve` / Laravel's router already
  does. Two things can't own routing for the same request. So it runs as its
  own container, and nginx decides which requests go to which app.

**How it works, end to end:**

1. `landing/` is a normal Next.js app: `app/layout.jsx` (root HTML shell +
   `<meta>`/manifest), `app/page.jsx` (the hero + features, a Server Component),
   `app/components/NavBar.jsx` (the one Client Component), `app/globals.css`.
2. `next.config.js` sets `output: 'standalone'`, which makes `next build` emit a
   self-contained `.next/standalone/server.js` that bundles only the files
   actually needed at runtime — so the Docker image doesn't need the full
   `node_modules` tree, just that output copied in (see `landing/Dockerfile`'s
   three-stage build: install deps → build → copy only the standalone output
   into a clean `node:20-alpine` runtime stage).
3. `docker-compose.yml` gained a new `landing` service (container name
   `seh-landing`), built from `landing/Dockerfile`, on the `internal` network
   only — it's never exposed directly, only reachable from `seh-nginx`.
4. `nginx/conf.d/smartereveryhour.conf` (and the dev equivalent) gained two new
   `location` blocks placed before the catch-all:
   ```nginx
   location = / {
       proxy_pass http://landing:3000;
       ...
   }
   location /_next/ {
       proxy_pass http://landing:3000;
       ...
   }
   ```
   `location = /` is an *exact* match (highest-priority match type in nginx),
   so it only intercepts the bare root path. `/_next/` catches Next's own
   built JS/CSS asset requests. Every other path — `/login`, `/register`,
   `/dashboard`, `/learn/{token}`, `/manifest.json`, `/sw.js`, the whole API —
   falls through to the existing `location /` block exactly as before, which
   still sends PHP requests to `seh-laravel:9000`. Laravel didn't lose any
   routes; nginx just stopped asking it for one specific path.

**To build/run it:**
```bash
docker compose build landing          # or: docker compose build (builds everything)
docker compose up -d --force-recreate landing nginx
```
(`nginx` needs to recreate/restart too, for the same DNS-caching reason covered
earlier when the PHP containers were patched — it needs to resolve the new
`landing` container's address.)
