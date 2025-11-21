<?php
// 404.php
http_response_code(404); // Set HTTP status to 404 Not Found
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>404 — Page Not Found</title>
  <style>
    :root {
      --bg: #ffffff;
      --accent: #ff7b00;
      --text: #1a1a1a;
      --muted: #777;
      --shadow: rgba(0, 0, 0, 0.08);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      padding: 32px 20px;
    }

    img {
      width: 520px;
      max-width: 80%;
      height: auto;
      margin-bottom: 28px;
      border-radius: 12px;
      box-shadow: 0 8px 30px var(--shadow);
    }

    h1 {
      font-size: 28px;
      margin: 0 0 8px 0;
      font-weight: 700;
      color: var(--accent);
    }

    p {
      font-size: 15px;
      color: var(--muted);
      margin-bottom: 28px;
      max-width: 420px;
      line-height: 1.5;
    }

    .btn {
      display: inline-block;
      background: var(--accent);
      color: #fff;
      padding: 12px 22px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      box-shadow: 0 6px 16px rgba(255, 123, 0, 0.3);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(255, 123, 0, 0.35);
    }

    .btn:active {
      transform: translateY(1px);
    }

    .hint {
      font-size: 13px;
      color: var(--muted);
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <main role="main" aria-labelledby="notfound-title">
    <img src="https://nujora.ng/images/404.jpg"
      alt="Lost in the ocean illustration">

    <h1 id="notfound-title">404 — Page Not Found</h1>
    <p>Oops! The page you’re looking for doesn’t exist or may have been moved. Let’s get you back on track.</p>

    <a href="https://nujora.ng/" class="btn">← Go Back Home</a>
    <div class="hint">If there’s no previous page, you’ll be redirected to the homepage.</div>
  </main>
</body>
</html>
