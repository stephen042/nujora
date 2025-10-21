<?php
http_response_code(404);
// your existing 404 markup follows
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Not Found - 404</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fb;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            text-align: center;
        }

        .container {
            max-width: 600px;
            padding: 20px;
        }

        h1 {
            font-size: 72px;
            margin: 0;
            color: #1a56db;
        }

        p {
            font-size: 18px;
            margin: 10px 0 20px;
        }

        .btn-home {
            display: inline-block;
            padding: 12px 24px;
            background: #1a56db;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-home:hover {
            background: #0f3bbf;
        }

        img {
            max-width: 90%;
            height: auto;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://cdn-icons-png.flaticon.com/512/564/564619.png" alt="404 Error">
        <h1>404</h1>
        <p>Oops! The page you are looking for does not exist.</p>
        <a href="/" class="btn-home">Return Home</a>
    </div>
</body>
</html>
