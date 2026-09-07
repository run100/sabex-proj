<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Admin-SAB</title>
  <link rel="icon" href="/static/img/admin-favicon.ico" sizes="any" />
  <link rel="icon" type="image/svg+xml" href="/static/img/admin-favicon.svg" />
  <style>
    :root { color-scheme: dark; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0b1220; color: #e2e8f0; font-family: var(--font-ui, "Avenir Next", "Segoe UI", sans-serif); }
    form { width: min(22rem, 92vw); padding: 1.5rem; border: 1px solid rgba(148,163,184,.2); background: #111827; }
    label { display: block; margin: 0 0 .35rem; font-size: .8rem; color: #94a3b8; }
    input { width: 100%; box-sizing: border-box; margin-bottom: 1rem; padding: .65rem .75rem; border: 1px solid rgba(148,163,184,.28); background: #0b1220; color: #fff; }
    button { width: 100%; padding: .7rem; border: 0; background: #0e7490; color: #ecfeff; font-weight: 700; cursor: pointer; }
    button:hover { background: #155e75; }
  </style>
</head>
<body>
  <form method="post" action="/login">
    @csrf
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="username" required>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit">Sign in</button>
  </form>
</body>
</html>
