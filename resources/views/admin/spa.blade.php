<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin-SAB</title>
  <link rel="icon" href="/static/img/admin-favicon.ico" sizes="any" />
  <link rel="icon" type="image/svg+xml" href="/static/img/admin-favicon.svg" />
  @vite(['console/main.js'])
</head>
<body>
  <div id="console"></div>
</body>
</html>
