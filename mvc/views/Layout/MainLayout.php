<!-- filepath: c:\xampp\htdocs\VNPay\mvc\views\Layout\MainLayout.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Shop</title>
</head>
<body>
    <!-- Nhúng Header -->
    <?php require_once "./mvc/views/Block/Header.php"; ?>

    <!-- Bố cục chính -->
    <div style="display: flex; min-height: 100vh;">
        <!-- Nội dung chính -->
        <!-- filepath: c:\xampp\htdocs\VNPay\mvc\views\Layout\MainLayout.php -->
        <div class="content" style="flex: 1; padding: 20px;">
            <?php
            // Nội dung của từng trang sẽ được render tại đây
            if (isset($data["Page"])) {
                require_once "./mvc/views/" . $data["Page"] . ".php";
            }
            ?>
        </div>
    </div>

    <!-- Nhúng Footer -->
    <?php require_once "./mvc/views/Block/Footer.php"; ?>
</body>
</html>