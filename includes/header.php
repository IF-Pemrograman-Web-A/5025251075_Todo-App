<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Todo List</title>

    <link rel="stylesheet" href="style.css?v=3">

    <link rel="manifest" href="./manifest.webmanifest">

    <meta name="theme-color" content="#6c63ff">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Todo List">

    <link rel="apples-touch-icon" href="./icons/icon-192.png">
    </head>
<body>
<header>
    <h1>My Todo List</h1>

    <div class="header-actions">
        <button
            type="button"
            id="theme-toggle"
            class="theme-btn"
            aria-pressed="false"
        >
            Toggle Theme
        </button>

        <button
            id="installBtn"
            type="button"
            class="theme-btn"
            style="display: none;"
        >
            Install App
        </button>
    </div>
</header>

<main>
    <div class="container">