<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Todo List</title>

    <link rel="stylesheet" href="style.css?v=3">

    <link rel="manifest" href="public/manifest.webmanifest">

    <meta name="theme-color" content="#6c63ff">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Todo List">

    <link rel="apple-touch-icon" href="public/icons/icon-192.png">
    </head>
<body>
<header>
    <h1>My Todo List</h1>

    <button
        type="button"
        id="theme-toggle"
        class="theme-btn"
        aria-pressed="false"
    >
        Toggle Theme
    </button>

    <button id="installBtn" style="display: none;">
    Install App
</button>
</header>

<main>
    <div class="container">