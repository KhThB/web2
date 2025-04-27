<?php
ob_start();
session_start();
include './connect.php';

// Đảm bảo session được khởi tạo
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Cart: Session không hoạt động, khởi tạo lại");
    session_start();
}

?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Giỏ Hàng | Smobile</title>
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
        .padding_top1 {
            padding-top: 20px;
        }
        .a1 {
            padding-top: 130px;
        }
        .a2 {
            height: 230px;
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
                        <h2>Giỏ Hàng</h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================Cart Area =================-->
    <section class="cart_area padding_top1">
        <div class="container">
            <?php
            if (!isset($_COOKIE["user"])) {
                echo "<h2>Vui lòng đăng nhập để xem giỏ hàng.</h2>";
                echo '<a href="login.php" class="btn_1" style="float:right; margin:0px 20px 20px 0px;">Đăng Nhập</a>';
                include 'footer.php';
                exit();
            }

            $taikhoan = $_COOKIE["user"];
            $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$taikhoan]);
            if (empty($taikhoan_rows)) {
                echo "<h2>Không tìm thấy tài khoản. Vui lòng đăng nhập lại.</h2>";
                echo '<a href="login.php" class="btn_1" style="float:right; margin:0px 20px 20px 0px;">Đăng Nhập</a>';
                include 'footer.php';
                exit();
            }

            $idtaikhoan = $taikhoan_rows[0]['id'];
            $diachitaikhoan = $taikhoan_rows[0]['diachi'];

            // Kiểm tra đơn hàng hiện có với status=0
            $donhang_rows = selectAll("SELECT * FROM donhang WHERE status=0 AND id_taikhoan=?", [$idtaikhoan]);
            if (!empty($donhang_rows)) {
                $idDh = $donhang_rows[0]['id'];
                // Đồng bộ SESSION cart với ctdonhang
                $_SESSION['cart'] = [];
                foreach (selectAll("SELECT c.*, s.ten AS tensp FROM ctdonhang c JOIN sanpham s ON c.id_sanpham=s.id WHERE c.id_donhang=?", [$idDh]) as $item) {
                    $_SESSION['cart'][] = [
                        'masp' => $item['id_sanpham'],
                        'tensp' => $item['tensp'],
                        'gia' => $item['gia'],
                        'soluong' => $item['soluong']
                    ];
                }
            } else {
                $idDh = null;
                $_SESSION['cart'] = $_SESSION['cart'] ?? [];
            }

            // Xử lý xóa sản phẩm
            if (isset($_GET['removeproduct']) && $idDh) {
                $remove_product_id = $_GET['removeproduct'];
                exSQL("DELETE FROM ctdonhang WHERE id_donhang=? AND id_sanpham=?", [$idDh, $remove_product_id]);
                // Cập nhật SESSION cart
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($remove_product_id) {
                    return $item['masp'] != $remove_product_id;
                });
                if (empty($_SESSION['cart'])) {
                    exSQL("UPDATE donhang SET status=4 WHERE id=? AND id_taikhoan=?", [$idDh, $idtaikhoan]);
                    unset($_SESSION['order_id']);
                }
                header('Location: cart.php');
                exit();
            }

            // Xử lý đặt hàng
            if (isset($_POST["dathang"])) {
                try {
                    $diachi = $_POST["diachi"] ?? $diachitaikhoan;
                    $today = date('Y-m-d H:i:s');

                    // Tính tổng tiền
                    $tongcong = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        $tong = $item['gia'] * $item['soluong'];
                        $tongcong += $tong;
                    }
                    $tongcong += 50000; // Phí ship

                    // Bắt đầu giao dịch
                    $conn->beginTransaction();

                    if (!$idDh) {
                        // Tạo đơn hàng mới
                        $stmt = $conn->prepare("INSERT INTO donhang (id_taikhoan, diachi, hoten, sdt, tongtien, status, thoigian) VALUES (?, ?, ?, ?, ?, 0, ?)");
                        if (!$stmt->execute([$idtaikhoan, $diachi, 'Chưa xác định', 'Chưa xác định', $tongcong, $today])) {
                            throw new Exception("Lỗi tạo đơn hàng mới: " . implode(", ", $stmt->errorInfo()));
                        }
                        $idDh = $conn->lastInsertId();
                        error_log("Cart: Tạo mới donhang ID $idDh với status=0");
                    } else {
                        // Cập nhật đơn hàng hiện có
                        $stmt = $conn->prepare("UPDATE donhang SET diachi=?, tongtien=?, thoigian=? WHERE id=? AND id_taikhoan=? AND status=0");
                        if (!$stmt->execute([$diachi, $tongcong, $today, $idDh, $idtaikhoan])) {
                            throw new Exception("Lỗi cập nhật đơn hàng ID $idDh: " . implode(", ", $stmt->errorInfo()));
                        }
                        error_log("Cart: Cập nhật donhang ID $idDh với status=0");
                    }

                    // Xóa chi tiết đơn hàng cũ
                    exSQL("DELETE FROM ctdonhang WHERE id_donhang=?", [$idDh]);

                    // Thêm chi tiết đơn hàng mới
                    $stmt_ct = $conn->prepare("INSERT INTO ctdonhang (id_donhang, id_sanpham, soluong, gia) VALUES (?, ?, ?, ?)");
                    foreach ($_SESSION['cart'] as $item) {
                        if (!$stmt_ct->execute([$idDh, $item['masp'], $item['soluong'], $item['gia']])) {
                            throw new Exception("Lỗi thêm chi tiết đơn hàng: " . implode(", ", $stmt_ct->errorInfo()));
                        }
                    }

                    // Cập nhật trạng thái đơn hàng
                    $stmt = $conn->prepare("UPDATE donhang SET status=1 WHERE id=? AND id_taikhoan=?");
                    if (!$stmt->execute([$idDh, $idtaikhoan])) {
                        throw new Exception("Lỗi cập nhật trạng thái đơn hàng ID $idDh: " . implode(", ", $stmt->errorInfo()));
                    }

                    // Lưu order_id vào SESSION
                    $_SESSION['order_id'] = $idDh;
                    error_log("Cart: Đã lưu SESSION order_id: $idDh");

                    // Commit giao dịch
                    $conn->commit();

                    header("Location: checkout.php");
                    exit();
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo "<div class='alert alert-danger'>Lỗi xử lý đơn hàng: " . htmlspecialchars($e->getMessage()) . "</div>";
                    error_log("Cart: Lỗi xử lý đơn hàng ID $idDh: " . $e->getMessage());
                }
            }
            ?>

            <form class="cart_inner" method="post" action="">
                <div class="table-responsive">
                    <a href="history.php" class="btn_1" style="float:right; margin-bottom:20px;">Lịch sử đặt hàng</a>

                    <?php if ($idDh && !empty($_SESSION['cart'])): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col">Giá</th>
                                    <th scope="col">Số lượng</th>
                                    <th scope="col">Tổng</th>
                                    <th scope="col">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tongcong = 0;
                                foreach ($_SESSION['cart'] as $item):
                                    $tong = $item['gia'] * $item['soluong'];
                                    $tongcong += $tong;
                                    $sanpham = selectAll("SELECT * FROM sanpham WHERE id=?", [$item['masp']])[0];
                                ?>
                                <tr>
                                    <td>
                                        <div class="media">
                                            <div class="d-flex">
                                                <img src="img/product/<?= htmlspecialchars($sanpham['anh1']) ?>" alt="" style="width:50px; height:50px;"/>
                                            </div>
                                            <div class="media-body">
                                                <p><?= htmlspecialchars($item['tensp']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <h5><?= number_format($item['gia']) ?>đ</h5>
                                    </td>
                                    <td>
                                        <div class="product_count">
                                            <input class="input-number" type="number" name="soluong[<?= $item['masp'] ?>]" value="<?= $item['soluong'] ?>" min="1" max="100"/>
                                        </div>
                                    </td>
                                    <td>
                                        <h5><?= number_format($tong) ?>đ</h5>
                                    </td>
                                    <td>
                                        <a class="genric-btn primary circle" href="?removeproduct=<?= $item['masp'] ?>">Xóa</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="bottom_button">
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <h5>Tổng cộng:</h5>
                                    </td>
                                    <td>
                                        <h5><?= number_format($tongcong + 50000) ?>đ</h5>
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="checkout_btn_inner float-right">
                            <a class="btn_1" href="product.php">Tiếp Tục Mua Sắm</a>
                            <input class="btn_1" type="submit" name="dathang" value="Đặt Hàng" style="border: none"/>
                        </div>
                    <?php else: ?>
                        <a href="product.php" class="btn_1" style="float:right; margin:0px 20px 20px 0px;">Mua Ngay</a>
                        <h2>Giỏ hàng của bạn đang trống</h2>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>
    <!--================End Cart Area =================-->

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
</body>
</html>
<?php ob_end_flush(); ?>