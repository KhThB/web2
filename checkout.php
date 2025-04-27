<?php
session_start();
include_once './connect.php';

// Đảm bảo session được khởi tạo
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Checkout: Session không hoạt động, khởi tạo lại");
    session_start();
}

// Kiểm tra order_id từ cart.php
if (!isset($_SESSION['order_id']) || empty($_SESSION['order_id'])) {
    error_log("Checkout: Thiếu order_id trong SESSION, chuyển hướng về cart.php");
    header("Location: cart.php?error=" . urlencode("Không tìm thấy đơn hàng. Vui lòng thử lại từ giỏ hàng."));
    exit();
}

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    error_log("Checkout: Thiếu giỏ hàng, chuyển hướng về cart.php");
    header("Location: cart.php?error=" . urlencode("Giỏ hàng của bạn trống!"));
    exit();
}

// Kiểm tra dữ liệu giỏ hàng
foreach ($_SESSION['cart'] as $item) {
    if (!isset($item['masp'], $item['tensp'], $item['soluong'], $item['gia'])) {
        error_log("Checkout: Dữ liệu giỏ hàng không hợp lệ: " . json_encode($item, JSON_UNESCAPED_UNICODE));
        header("Location: cart.php?error=" . urlencode("Dữ liệu giỏ hàng không hợp lệ!"));
        exit();
    }
}

// Kiểm tra cookie user và lấy thông tin tài khoản
$id_taikhoan = null;
$hoten_macdinh = '';
$sdt_macdinh = '';
$diachi_macdinh = '';

if (isset($_COOKIE['user']) && !empty($_COOKIE['user'])) {
    $email = $_COOKIE['user'];
    $taikhoan_rows = selectAll("SELECT id, hoten, sdt, diachi, status, phanquyen FROM taikhoan WHERE taikhoan = ? AND status = 0", [$email]);
    if (!empty($taikhoan_rows)) {
        $id_taikhoan = $taikhoan_rows[0]['id'];
        $hoten_macdinh = $taikhoan_rows[0]['hoten'] ?? '';
        $sdt_macdinh = $taikhoan_rows[0]['sdt'] ?? '';
        $diachi_macdinh = $taikhoan_rows[0]['diachi'] ?? '';
    } else {
        $error = "Tài khoản không tồn tại hoặc không hợp lệ.";
        error_log("Checkout: Lỗi - Tài khoản không hợp lệ: email = $email");
    }
}

// Kiểm tra nếu không có id_taikhoan hợp lệ
if ($id_taikhoan === null) {
    error_log("Checkout: Không có id_taikhoan hợp lệ, chuyển hướng về login.php");
    header("Location: login.php?error=" . urlencode("Vui lòng đăng nhập để đặt hàng"));
    exit();
}

// Kiểm tra đơn hàng có tồn tại
$donhang_id = $_SESSION['order_id'];
$donhang_rows = selectAll("SELECT * FROM donhang WHERE id = ? AND id_taikhoan = ? AND status = 1", [$donhang_id, $id_taikhoan]);
if (empty($donhang_rows)) {
    error_log("Checkout: Đơn hàng không tồn tại hoặc không hợp lệ: order_id = $donhang_id, id_taikhoan = $id_taikhoan");
    header("Location: cart.php?error=" . urlencode("Đơn hàng không tồn tại. Vui lòng thử lại."));
    exit();
}

// Khởi tạo mảng order_info_list nếu chưa tồn tại
if (!isset($_SESSION['order_info_list'])) {
    $_SESSION['order_info_list'] = [];
}

// Xử lý thanh toán
$order_success = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hoten = trim($_POST['hoten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $use_new_info = isset($_POST['use_new_info']) && $_POST['use_new_info'] === '1';
    $payment_method = $_POST['payment_method'] ?? '';

    // Ghi log dữ liệu POST
    error_log("Checkout: Raw POST data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
    error_log("Checkout: use_new_info = " . ($use_new_info ? 'true' : 'false'));

    // Nếu không chọn thông tin mới, sử dụng thông tin mặc định
    if (!$use_new_info) {
        $hoten = $hoten_macdinh ?: 'Khách hàng không xác định';
        $sdt = $sdt_macdinh ?: 'Không có';
        $diachi = $diachi_macdinh ?: 'Không có địa chỉ';
        error_log("Checkout: Sử dụng thông tin mặc định - hoten: '$hoten', sdt: '$sdt', diachi: '$diachi'");
    } else {
        // Kiểm tra thông tin mới
        if (empty($hoten) || empty($sdt) || empty($diachi)) {
            $error = "Vui lòng điền đầy đủ thông tin nhận hàng mới.";
            error_log("Checkout: Lỗi - Thiếu thông tin nhận hàng mới");
        } elseif (strlen($hoten) > 100 || strlen($sdt) > 15 || strlen($diachi) > 255) {
            $error = "Thông tin nhận hàng vượt quá độ dài cho phép.";
            error_log("Checkout: Lỗi - Dữ liệu quá dài: hoten='$hoten', sdt='$sdt', diachi='$diachi'");
        }
    }

    // Kiểm tra dữ liệu đầu vào
    if (empty($payment_method)) {
        $error = "Vui lòng chọn phương thức thanh toán.";
        error_log("Checkout: Lỗi - Thiếu phương thức thanh toán");
    } elseif (!empty($sdt) && !preg_match('/^[0-9]{10,11}$/', $sdt)) {
        $error = "Số điện thoại không hợp lệ.";
        error_log("Checkout: Lỗi - Số điện thoại không hợp lệ: '$sdt'");
    } else {
        // Tính tổng tiền
        $tongtien = 0;
        foreach ($_SESSION['cart'] as $item) {
            $tongtien += $item['gia'] * $item['soluong'];
        }
        $phiship = 50000;
        $tongcong = $tongtien + $phiship;

        try {
            // Bắt đầu giao dịch
            $conn->beginTransaction();

            // Cập nhật thông tin đơn hàng
            $stmt = $conn->prepare("UPDATE donhang SET hoten = ?, sdt = ?, diachi = ?, tongtien = ?, thoigian = NOW() WHERE id = ? AND id_taikhoan = ? AND status = 1");
            if (!$stmt->execute([$hoten, $sdt, $diachi, $tongcong, $donhang_id, $id_taikhoan])) {
                throw new Exception("Lỗi cập nhật đơn hàng ID $donhang_id: " . implode(", ", $stmt->errorInfo()));
            }
            error_log("Checkout: Cập nhật đơn hàng ID $donhang_id thành công - hoten: '$hoten', sdt: '$sdt', diachi: '$diachi'");

            // Lưu thông tin vào SESSION
            $_SESSION['order_info_list'][$donhang_id] = [
                'hoten' => $hoten,
                'sdt' => $sdt,
                'diachi' => $diachi
            ];
            $_SESSION['latest_order_id'] = $donhang_id;
            error_log("Checkout: Đã lưu SESSION order_info_list[$donhang_id]: " . json_encode($_SESSION['order_info_list'][$donhang_id], JSON_UNESCAPED_UNICODE));
            error_log("Checkout: Đã lưu SESSION latest_order_id: $donhang_id");

            // Lưu thông tin vào COOKIE
            $cookie_data = json_encode($_SESSION['order_info_list'], JSON_UNESCAPED_UNICODE);
            if (!headers_sent()) {
                if (!setcookie('order_info_list', $cookie_data, time() + (7 * 24 * 3600), "/", "", false, true)) {
                    error_log("Checkout: Lỗi khi lưu cookie order_info_list");
                } else {
                    error_log("Checkout: Đã lưu COOKIE order_info_list: " . $cookie_data);
                }
            } else {
                error_log("Checkout: Lỗi - Headers đã được gửi, không thể thiết lập COOKIE");
            }

            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            unset($_SESSION['order_id']); // Xóa order_id để tránh trùng lặp

            // Commit giao dịch
            $conn->commit();
            $order_success = true;
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Lỗi xử lý đơn hàng: " . $e->getMessage();
            error_log("Checkout: Lỗi xử lý đơn hàng: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đặt Hàng | Smobile</title>
    <link rel="icon" href="img/logos.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/slick.css">
    <link rel="stylesheet" href="css/price_rangs.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .header_bg {
            background-color: #ecfdff;
            height: 230px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .padding_top {
            padding-top: 20px;
        }
        .a1 {
            padding-top: 130px;
        }
        .a2 {
            height: 230px;
        }
        .checkout-container {
            max-width: 1200px;
            margin: 50px auto;
        }
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .form-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .payment-methods label {
            margin-right: 20px;
        }
        .error-message {
            color: red;
            font-size: 0.9em;
        }
        .default-info {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!--================Home Banner Area =================-->
    <section class="breadcrumb header_bg">
        <div class="container">
            <div class="row justify-content-center a2">
                <div class="col-lg-8 a2">
                    <div class="a1">
                        <h2>Đặt Hàng</h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================Checkout Area =================-->
    <section class="checkout_area padding_top">
        <div class="container checkout-container">
            <?php if ($order_success): ?>
                <div class="text-center">
                    <h2>Cảm ơn bạn đã đặt hàng!</h2>
                    <p>Đơn hàng của bạn đã được ghi nhận. Chúng tôi sẽ xử lý sớm nhất có thể.</p>
                    <p>Mã đơn hàng của bạn là: <strong>#<?= htmlspecialchars($donhang_id) ?></strong>. Vui lòng ghi lại để tra cứu.</p>
                    <a href="index.php" class="btn btn-primary">Quay về trang chủ</a>
                </div>
            <?php else: ?>
                <h2 class="mb-4 text-center">Thanh Toán Đơn Hàng</h2>
                <div class="row">
                    <!-- Tóm tắt đơn hàng -->
                    <div class="col-md-5 order-summary mb-4">
                        <h4>Tóm Tắt Đơn Hàng</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tongtien = 0;
                                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                    foreach ($_SESSION['cart'] as $item):
                                        $thanhtien = $item['gia'] * $item['soluong'];
                                        $tongtien += $thanhtien;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['tensp']) ?></td>
                                    <td><?= $item['soluong'] ?></td>
                                    <td><?= number_format($thanhtien) ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th colspan="2">Tổng</th>
                                    <th><?= number_format($tongtien) ?>đ</th>
                                </tr>
                                <tr>
                                    <th colspan="2">Phí ship</th>
                                    <th><?= number_format($phiship = 50000) ?>đ</th>
                                </tr>
                                <tr>
                                    <th colspan="2">Tổng cộng</th>
                                    <th><?= number_format($tongtien + $phiship) ?>đ</th>
                                </tr>
                                <?php } else { ?>
                                <tr>
                                    <td colspan="3" class="text-center">Giỏ hàng của bạn đang trống.</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Biểu mẫu thanh toán -->
                    <div class="col-md-7 form-section">
                        <h4>Thông Tin Thanh Toán</h4>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Thông Tin Nhận Hàng <span class="text-danger">*</span></label>
                                <div class="default-info">
                                    <strong>Họ và Tên:</strong> <?= $hoten_macdinh ? htmlspecialchars($hoten_macdinh) : 'Chưa có thông tin' ?><br>
                                    <strong>Số Điện Thoại:</strong> <?= $sdt_macdinh ? htmlspecialchars($sdt_macdinh) : 'Chưa có thông tin' ?><br>
                                    <strong>Địa Chỉ:</strong> <?= $diachi_macdinh ? htmlspecialchars($diachi_macdinh) : 'Chưa có thông tin' ?>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="use_new_info" name="use_new_info" value="1">
                                    <label class="form-check-label" for="use_new_info">Nhập thông tin nhận hàng mới</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="hoten" class="form-label">Họ và Tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hoten" name="hoten" value="<?= isset($hoten) && isset($use_new_info) ? htmlspecialchars($hoten) : ($hoten_macdinh ?: '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="sdt" class="form-label">Số Điện Thoại <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sdt" name="sdt" value="<?= isset($sdt) && isset($use_new_info) ? htmlspecialchars($sdt) : ($sdt_macdinh ?: '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="diachi" class="form-label">Địa Chỉ Nhận Hàng <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="diachi" name="diachi" rows="4" required><?= isset($diachi) && isset($use_new_info) ? htmlspecialchars($diachi) : ($diachi_macdinh ?: '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phương Thức Thanh Toán <span class="text-danger">*</span></label>
                                <div class="payment-methods">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                        <label class="form-check-label" for="cod">Thanh toán khi nhận hàng (COD)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="bank" value="bank">
                                        <label class="form-check-label" for="bank">Chuyển khoản ngân hàng</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Xác Nhận Đặt Hàng</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!--================End Checkout Area =================-->

    <?php include 'footer.php'; ?>
    <script src="js/jquery-1.12.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.magnific-popup.js"></script>
    <script src="js/swiper.min.js"></script>
    <script src="js/masonry.pkgd.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/slick.min.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/waypoints.min.js"></script>
    <script src="js/contact.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/mail-script.js"></script>
    <script src="js/stellar.js"></script>
    <script src="js/price_rangs.js"></script>
    <script src="js/custom.js"></script>
    <script>
        // Bật/tắt các ô nhập thông tin mới dựa trên checkbox
        document.getElementById('use_new_info').addEventListener('change', function() {
            const fields = [
                document.getElementById('hoten'),
                document.getElementById('sdt'),
                document.getElementById('diachi')
            ];
            if (this.checked) {
                fields.forEach(field => {
                    field.value = ''; // Xóa giá trị mặc định khi chọn nhập mới
                });
            } else {
                fields[0].value = '<?= addslashes($hoten_macdinh ?: '') ?>';
                fields[1].value = '<?= addslashes($sdt_macdinh ?: '') ?>';
                fields[2].value = '<?= addslashes($diachi_macdinh ?: '') ?>';
            }
        });
    </script>
</body>
</html>