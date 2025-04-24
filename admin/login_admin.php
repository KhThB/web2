<?php 
    include_once '../connect.php';

    // Xử lý gửi biểu mẫu đăng nhập
    if (isset($_POST["dangnhap"])) {
        $email = $_POST["email"];
        $matkhau = $_POST["matkhau"];
        
        // Làm sạch dữ liệu đầu vào
        $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE taikhoan = ? AND matkhau = ? AND status = 0 AND phanquyen = 1");
        $stmt->execute([$email, $matkhau]);
        if ($stmt->rowCount() == 1) {
            setcookie('user', $email, time() + (86400 * 30), "/");
            header('location: index.php'); // Chuyển hướng đến /admin/index.php
            exit();
        } else {
            $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE taikhoan = ? AND status = 1");
            $stmt->execute([$email]);
            if ($stmt->rowCount() == 1) {
                $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
            } else {
                $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE taikhoan = ? AND phanquyen != 1");
                $stmt->execute([$email]);
                if ($stmt->rowCount() == 1) {
                    $error = 'Tài khoản này không có quyền quản trị.';
                } else {
                    $error = 'Tài khoản hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại.';
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đăng Nhập Quản Trị</title>
    <link rel="icon" href="img/logos.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- animate CSS -->
    <link rel="stylesheet" href="../css/animate.css">
    <!-- owl carousel CSS -->
    <link rel="stylesheet" href="../css/owl.carousel.min.css">
    <!-- font awesome CSS -->
    <link rel="stylesheet" href="../css/all.css">
    <!-- flaticon CSS -->
    <link rel="stylesheet" href="../css/flaticon.css">
    <link rel="stylesheet" href="../css/themify-icons.css">
    <!-- magnific popup CSS -->
    <link rel="stylesheet" href="../css/magnific-popup.css">
    <!-- slick CSS -->
    <link rel="stylesheet" href="../css/slick.css">
    <!-- style CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
       
        .login_part {
            padding: 50px 0;
        }
        .login_part_form {
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 0 auto;
        }
        .login_part_form_iner h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        .text-danger {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <section class="login_part">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="login_part_form">
                        <div class="login_part_form_iner">
                            <h3>Đăng Nhập</h3>
                            <?php if (isset($error)) { ?>
                                <p class="text-danger"><?= htmlspecialchars($error) ?></p>
                            <?php } ?>
                            <form class="row contact_form" action="" method="post" novalidate="novalidate">
                                <div class="col-md-12 form-group">
                                    <input type="text" class="form-control" name="email" value="" required placeholder="Tài khoản (Email)">
                                </div>
                                <div class="col-md-12 form-group">
                                    <input type="password" class="form-control" name="matkhau" value="" required placeholder="Mật khẩu">
                                </div>
                                <div class="col-md-12 form-group">
                                    <button type="submit" name="dangnhap" class="btn_3">Đăng Nhập</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- jquery plugins here-->
    <script src="../js/jquery-1.12.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/jquery.magnific-popup.js"></script>
    <script src="../js/swiper.min.js"></script>
    <script src="../js/masonry.pkgd.js"></script>
    <script src="../js/owl.carousel.min.js"></script>
    <script src="../js/jquery.nice-select.min.js"></script>
    <script src="../js/slick.min.js"></script>
    <script src="../js/jquery.counterup.min.js"></script>
    <script src="../js/waypoints.min.js"></script>
    <script src="../js/contact.js"></script>
    <script src="../js/jquery.ajaxchimp.min.js"></script>
    <script src="../js/jquery.form.js"></script>
    <script src="../js/jquery.validate.min.js"></script>
    <script src="../js/mail-script.js"></script>
    <script src="../js/stellar.js"></script>
    <script src="../js/price_rangs.js"></script>
    <script src="../js/custom.js"></script>
</body>
</html>