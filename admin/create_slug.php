<?php
require '../app/config.php'; // Contains DB + slug functions
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content py-4">

        <div class="container mt-5">

            <form method="POST">
                <button type="submit" name="regenerate_slugs" class="btn btn-danger btn-lg w-100 py-3 fw-bold">
                    Regenerate Missing Slugs for All Products
                </button>
            </form>

            <?php
            if (isset($_POST['regenerate_slugs'])) {

                // Fetch only products with empty slugs
                $stmt = $pdo->query("SELECT id, name FROM products WHERE slug IS NULL OR slug = ''");
                $products = $stmt->fetchAll();

                $updated = 0;

                foreach ($products as $product) {

                    // Use your existing slug generator
                    $newSlug = generateSlug($product['name']);

                    $update = $pdo->prepare("UPDATE products SET slug = ? WHERE id = ?");
                    $update->execute([$newSlug, $product['id']]);

                    $updated++;
                }

                echo "
                <div class='alert alert-success mt-4'>
                    Successfully generated <strong>$updated</strong> new slugs.
                </div>";
            }
            ?>
        </div>

    </div>

    <?php include 'includes/script.php'; ?>
</body>

</html>
