<?php 
    include 'header.php';

    if (isset($_COOKIE["user"])) {
        $user = $_COOKIE["user"];
        foreach (selectAll("SELECT * FROM taikhoan WHERE taikhoan='$user'") as $row) {
            $permission = $row['phanquyen'];
        }
        if ($permission==1) {
            if (isset($_GET["id"])) {
                foreach (selectAll("SELECT * FROM sanpham WHERE id={$_GET['id']}") as $item) {
                    $ten = $item['ten'];
                    $id_danhmuc = $item['id_danhmuc'];
                    $manhinh = $item['manhinh'];
                    $hedieuhanh = $item['hedieuhanh'];
                    $cpu = $item['cpu'];
                    $camera = $item['camera'];
                    $pin = $item['pin'];
                    $ram = $item['ram'];
                    $bonho = $item['bonho'];
                    $gia = $item['gia'];
                    $chitiet = $item['chitiet'];
                    $mota = $item['mota'];
                }
            }
            if (isset($_POST['sua'])) {
                $ten = $_POST["ten"];
                $id_danhmuc = $_POST["danhmuc"];
                $manhinh = $_POST["manhinh"];
                $hedieuhanh = $_POST["hedieuhanh"];
                $cpu = $_POST["cpu"];
                $camera = $_POST["camera"];
                $pin = $_POST["pin"];
                $ram = $_POST["ram"];
                $bonho = $_POST["bonho"];
                $gia = $_POST["gia"];
                $anh1 = $_FILES['anh1']['name'];
                $tmp1 = $_FILES['anh1']['tmp_name'];
                $type1 = $_FILES['anh1']['type'];
                $anh2 = $_FILES['anh2']['name'];
                $tmp2 = $_FILES['anh2']['tmp_name'];
                $type2 = $_FILES['anh2']['type'];
                $anh3 = $_FILES['anh3']['name'];
                $tmp3 = $_FILES['anh3']['tmp_name'];
                $type3 = $_FILES['anh3']['type'];
                $chitiet = $_POST["chitiet"];
                $mota = $_POST["mota"];
                $dir = '../img/product/';
                move_uploaded_file($tmp1, $dir . $anh1);
                move_uploaded_file($tmp2, $dir . $anh2);
                move_uploaded_file($tmp3, $dir . $anh3);
                if (empty($_FILES['anh1']['name'] || $_FILES['anh2']['name'] || $_FILES['anh3']['name'])) {
                    selectAll("UPDATE sanpham SET ten='$ten',id_danhmuc='$id_danhmuc',manhinh='$manhinh',hedieuhanh='$hedieuhanh',cpu='$cpu',camera='$camera',pin='$pin',ram='$ram',bonho='$bonho',gia='$gia',chitiet='$chitiet',mota='$mota' WHERE id={$_GET['id']}");
                    header('location:product.php');
                }else{
                    selectAll("UPDATE sanpham SET ten='$ten',id_danhmuc='$id_danhmuc',manhinh='$manhinh',hedieuhanh='$hedieuhanh',cpu='$cpu',camera='$camera',pin='$pin',ram='$ram',bonho='$bonho',gia='$gia',anh1='$anh1',anh2='$anh2',anh3='$anh3',chitiet='$chitiet',mota='$mota' WHERE id={$_GET['id']}");
                    header('location:product.php');
                }
            }
        ?>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row ">
                        <div class="col-12 grid-margin">
                        <div class="card">
                  <div class="card-body addfont">
                    <h4 class="card-title addfont">Sửa Sản Phẩm</h4>
                    <form class="forms-sample" action="" method="post" enctype="multipart/form-data">

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Tên Sản Phẩm</label>
                        <input type="text" value="<?= $ten ?>" name = "ten" required class="form-control text-light" placeholder="Nhập tên sản phẩm">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Giá</label>
                        <input type="number" value="<?= $gia ?>" name ="gia" required class="form-control text-light" placeholder="Nhập giá bán">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputEmail3">Danh mục</label>
                        <select required name="danhmuc" id="input" class="form-control text-light">
                        <?php
                        foreach (selectAll("SELECT * FROM danhmuc ") as $item) {
                        ?>
                            <option value="<?= $item['id_dm'] ?>"><?= $item['danhmuc'] ?></option>
                        <?php
                        }
                        ?>
                        </select>
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Kích Thước Màn Hình</label>
                        <input type="text" value="<?= $manhinh ?>" name="manhinh" required class="form-control text-light" placeholder="Nhập kích thước màn hình">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Hệ Điều Hành</label>
                        <input type="text" value="<?= $hedieuhanh ?>" name ="hedieuhanh" required class="form-control text-light" placeholder="Nhập hệ điều hành">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">CPU</label>
                        <input type="text" value="<?= $cpu ?>" name ="cpu" required class="form-control text-light" placeholder="Nhập tên chipset">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Camera</label>
                        <input type="text" value="<?= $camera ?>" name ="camera" required class="form-control text-light" placeholder="Nhập thông số camera">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Pin</label>
                        <input type="number" value="<?= $pin ?>" name ="pin" required class="form-control text-light" placeholder="Nhập dung lượng pin">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">RAM</label>
                        <input type="number" value="<?= $ram ?>" name ="ram" required class="form-control text-light" placeholder="Nhập dung lượng RAM">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleInputName1">Bộ Nhớ Trong</label>
                        <input type="text" value="<?= $bonho ?>" name ="bonho" required class="form-control text-light" placeholder="Nhập dung lượng bộ nhớ trong">
                      </div>

                      

                      <div class="form-group addfont">
                        <label>Ảnh Sản Phẩm</label>
                        <input type="file" name="anh1" class="form-control">
                        <input type="file" name="anh2" class="form-control">
                        <input type="file" name="anh3" class="form-control">
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleTextarea1">Mô Tả Ngắn</label>
                        <textarea type="text" name ="mota" required class="form-control text-light" rows="3" style="line-height: 2" placeholder="Nhập mô tả ngắn gọn"><?= $mota ?></textarea>
                      </div>

                      <div class="form-group addfont">
                        <label for="exampleTextarea1">Chi Tiết</label>
                        <textarea type="text" name ="chitiet" required class="form-control text-light" style="line-height: 2" rows="6" placeholder="Nhập chi tiết"><?= $chitiet ?></textarea>
                      </div>
                      
                      <button type="submit" name="sua" class="btn btn-primary mr-2">Sửa sản phẩm</button>
                      <a class="btn btn-dark" href="product.php" >Hủy</a>
                    </form>
                  </div>
                </div>
                        </div>
                    </div>
                </div>
            <?php
        }
    }
    include 'footer.php';
?>