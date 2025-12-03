<?php

function generateSlug($name)
{
    $base = str_slug($name);
    $slug = $base;
    $i = 1;

    while (slugExists($slug)) {
        $slug = $base . '-' . $i;
        $i++;
    }
    
    return $slug;
}

function str_slug($string)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return rtrim($slug, '-');
}

function slugExists($slug)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->rowCount() > 0;
}