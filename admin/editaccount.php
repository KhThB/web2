<?php 
include 'header.php';

if (isset($_COOKIE["user"])) {
    $user = $_COOKIE["user"];
    foreach (selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$user]) as $row) {
        $permission = $row['phanquyen'];
    }
    if ($permission == 1) {
        if (isset($_GET["id"])) {
            foreach (selectAll("SELECT * FROM taikhoan WHERE id=?", [$_GET['id']]) as $item) {
                $taikhoan = $item['taikhoan'];
                $matkhau = $item['matkhau'];
                $hoten = $item['hoten'];
                $phanquyen = $item['phanquyen'];
            }
        }
        if (isset($_POST['sua'])) {
            $ten = $_POST["ten"];
            $phanquyen = $_POST["phanquyen"];
            $email = $_POST["email"];
            if ($email == $taikhoan) {
                exSQL("UPDATE taikhoan SET taikhoan=?, hoten=?, phanquyen=? WHERE id=?", 
                    [$email, $ten, $phanquyen, $_GET["id"]]);
                setcookie("user", null, -1, '/');
                header('location:../login.php');
            } else {
                if (rowCount("SELECT * FROM taikhoan WHERE taikhoan=?", [$email]) > 0) {
                    echo "<script>alert('Lỗi: Tài khoản(Email) đã tồn tại!')</script>";
                } else {
                    exSQL("UPDATE taikhoan SET taikhoan=?, hoten=?, phanquyen=? WHERE id=?", 
                        [$email, $ten, $phanquyen, $_GET["id"]]);
                    setcookie("user", null, -1, '/');
                    header('location:../login.php');
                }
            }
        }
?>
<!-- partial -->
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body addfont">
                        <h4 class="card-title">Sửa Tài Khoản</h4>
                        <form class="forms-sample" action="" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="exampleInputName1">Họ Tên</label>
                                <input type="text" name="ten" value="<?= htmlspecialchars($hoten) ?>" required class="form-control text-light" placeholder="Nhập họ và tên">
                            </div>

                            <div class="form-group">
                                <label for="exampleInputName1">Tài Khoản(Email)</label>
                                <input type="text" name="email" value="<?= htmlspecialchars($taikhoan) ?>" required class="form-control text-light" placeholder="Nhập email">
                            </div>
                            
                            <div class="form-group">
                                <label for="exampleInputEmail3">Loại Tài Khoản</label>
                                <select required name="phanquyen" id="input" class="form-control text-light">
                                    <option value="" disabled>Chọn loại tài khoản</option>
                                    <option value="1" <?= $phanquyen == 1 ? 'selected' : '' ?>>Admin</option>
                                    <option value="0" <?= $phanquyen == 0 ? 'selected' : '' ?>>Khách hàng</option>
                                </select>
                            </div>
                            <button type="submit" name="sua" class="btn btn-primary mr-2" onclick="return confirm('Sau khi sửa thành công vui lòng đăng nhập lại tài khoản?')">Sửa Tài Khoản</button>
                            <a class="btn btn-dark" href="account.php">Hủy</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
    } else {
        header('location:../login.php');
    }
} else {
    header('location:../login.php');
}
include 'footer.php';
?>