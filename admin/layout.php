<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trang quản trị - JobFinder</title>

  <!-- Vendor CSS Files -->
  <link href="/JobFind/public/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/JobFind/public/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="/JobFind/public/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="/JobFind/public/assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="/JobFind/public/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="/JobFind/public/assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="/JobFind/public/assets/css/style.css" rel="stylesheet">
</head>

<body>

  <?php include dirname(__DIR__) . '/app/views/admin/includes/header.php'; ?>
  <?php include dirname(__DIR__) . '/app/views/admin/includes/sidebar.php'; ?>

  <main id="main" class="main">
    <?php echo  $content; ?>
  </main>

  <?php include dirname(__DIR__) . '/app/views/admin/includes/footer.php'; ?>

  <!-- Vendor JS Files -->
  <script src="/JobFind/public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/JobFind/public/assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="/JobFind/public/assets/vendor/quill/quill.min.js"></script>
  <script src="/JobFind/public/assets/vendor/tinymce/tinymce.min.js"></script>

  <!-- Template Main JS File -->
  <script src="/JobFind/public/assets/js/main.js"></script>

</body>
</html>
