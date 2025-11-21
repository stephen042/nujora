<?php
// unauthorized.php
http_response_code(401); // Set HTTP status to 401 Unauthorized
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Unauthorized — Access Denied</title>
  <style>
    :root {
      --orange: #ff7a00;
      --dark-orange: #e56a00;
      --text-dark: #222;
      --muted: #666;
      --bg: #fff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: var(--bg);
      color: var(--text-dark);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
      text-align: center;
    }

    .container {
      max-width: 600px;
      width: 100%;
    }

    h1 {
      font-size: 26px;
      margin-bottom: 8px;
      color: var(--dark-orange);
    }

    p {
      color: var(--muted);
      font-size: 16px;
      margin-bottom: 24px;
    }

    .main-img {
      width: 100%;
      max-width: 450px;
      border-radius: 10px;
      margin: 0 auto 30px auto;
      display: block;
      object-fit: cover;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .btn {
      display: inline-block;
      background: var(--orange);
      color: #fff;
      padding: 12px 28px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.1s ease;
    }

    .btn:hover {
      background: var(--dark-orange);
    }

    .btn:active {
      transform: scale(0.97);
    }

    .code {
      margin-top: 15px;
      font-size: 14px;
      color: var(--muted);
    }

    @media (max-width: 480px) {
      h1 { font-size: 22px; }
      p { font-size: 15px; }
      .btn { padding: 10px 24px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <img
      src="images/401.jpg"
      alt="Unauthorized access illustration"
      class="main-img"
      loading="lazy"
    >

    <h1>Unauthorized Access</h1>
    <p>You don’t have permission to view this page.<br>Please go back or contact support if you believe this is an error.</p>

    <button class="btn" onclick="goBack()">← Go Back</button>

    <div class="code">HTTP 401 · Unauthorized</div>
  </div>

  <script>
    function goBack() {
      try {
        if (history.length > 1) {
          history.go(-1);
          setTimeout(function(){
            if (document.visibilityState === 'visible') fallback();
          }, 300);
          return;
        }
      } catch (err) {
        console.warn('history.go failed', err);
      }
      fallback();

      function fallback() {
        if (document.referrer && document.referrer !== location.href) {
          location.href = document.referrer;
        } else {
          location.href = '/';
        }
      }
    }

    // Allow ESC key to trigger back navigation
    document.addEventListener('keydown', function(ev){
      if (ev.key === 'Escape') {
        goBack();
      }
    });
  </script>
</body>
</html>
