<?php 
    include './connect.php';
    if (isset($_GET["id"])) {
        foreach (selectAll("SELECT * FROM danhmuc WHERE id_dm=" . (int)$_GET['id']) as $item) {
           $tendanhmuc = $item['danhmuc'];
           $iddanhmuc = $item['id_dm'];
        }
    } else {
        header("Location: product.php");
        exit;
    }
?>
<!doctype html>
<html lang="zxx">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Smobile</title>
    <link rel="icon" href="img/logos.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- animate CSS -->
    <link rel="stylesheet" href="css/animate.css">
    <!-- owl carousel CSS -->
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <!-- nice select CSS -->
    <link rel="stylesheet" href="css/nice-select.css">
    <!-- font awesome CSS -->
    <link rel="stylesheet" href="css/all.css">
    <!-- flaticon CSS -->
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <!-- font awesome CSS -->
    <link rel="stylesheet" href="css/magnific-popup.css">
    <!-- swiper CSS -->
    <link rel="stylesheet" href="css/slick.css">
    <link rel="stylesheet" href="css/price_rangs.css">
    <!-- style CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
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
.filter_section {
    margin-bottom: 20px;
}
.filter_section label {
    display: block;
    margin: 5px 0;
}
.filter_section input[type="checkbox"] {
    margin-right: 10px;
}
.widgets_inner select {
    width: 100%;
    padding: 8px;
    margin-top: 10px;
}
.filter_dropdown .dropdown-menu {
    padding: 15px;
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
}
.filter_dropdown .dropdown-item {
    padding: 5px 0;
}
.filter_dropdown h4 {
    font-size: 16px;
    margin-bottom: 10px;
}
.filter_dropdown .btn-filter {
    margin-top: 10px;
    width: 100%;
}
.filter_dropdown .search-input {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}
</style>
<body>
    <?php include 'header.php'; ?>
    <!--================Home Banner Area =================-->
    <section class="breadcrumb header_bg">
        <div class="container">
            <div class="row justify-content-center a2">
                <div class="col-lg-8 a2">
                    <div class="a1">
                        <h2><?= htmlspecialchars($tendanhmuc) ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================Category Product Area =================-->
    <section class="cat_product_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="left_sidebar_area">
                        <aside class="left_widgets p_filter_widgets">
                            <div class="l_w_title">
                                <h3>Danh Mục Sản Phẩm</h3>
                            </div>
                            <div class="widgets_inner">
                                <form id="category_form">
                                    <select name="category" onchange="redirectCategory(this.value)">
                                        <option value="all">Tất cả</option>
                                        <?php 
                                            foreach (selectAll("SELECT * FROM danhmuc") as $item) {
                                                $selected = ($iddanhmuc == $item['id_dm']) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $item['id_dm'] ?>" <?= $selected ?>><?= htmlspecialchars($item['danhmuc']) ?></option>
                                                <?php
                                            }
                                        ?>
                                    </select>
                                </form>
                            </div>
                            <!-- Sắp xếp theo giá -->
                            <div class="l_w_title">
                                <h3>Xếp Theo Giá</h3>
                            </div>
                            <div class="widgets_inner">
                                <form action="category.php" method="GET" id="sort_form">
                                    <select name="sort_price" onchange="this.form.submit()">
                                        <option value="">Mặc định</option>
                                        <option value="asc" <?= isset($_GET['sort_price']) && $_GET['sort_price'] == 'asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                                        <option value="desc" <?= isset($_GET['sort_price']) && $_GET['sort_price'] == 'desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                                    </select>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($iddanhmuc) ?>">
                                    <?php if (isset($_GET['search'])) { ?>
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
                                    <?php } ?>
                                    <?php if (isset($_GET['brands'])) { 
                                        foreach ($_GET['brands'] as $brand) { ?>
                                            <input type="hidden" name="brands[]" value="<?= htmlspecialchars($brand) ?>">
                                        <?php } 
                                    } ?>
                                    <?php if (isset($_GET['price_ranges'])) { 
                                        foreach ($_GET['price_ranges'] as $range) { ?>
                                            <input type="hidden" name="price_ranges[]" value="<?= htmlspecialchars($range) ?>">
                                        <?php } 
                                    } ?>
                                </form>
                            </div>
                            <!-- Bộ lọc hãng và giá -->
                            <div class="l_w_title">
                                <h3>Bộ Lọc</h3>
                            </div>
                            <div class="widgets_inner filter_section">
                                <form action="category.php" method="GET" id="filter_form">
                                    <div class="filter_dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Chọn bộ lọc
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="filterDropdown">
                                            <h4>Tìm Kiếm Sản Phẩm</h4>
                                            <div class="dropdown-item">
                                                <input type="text" name="search" class="search-input" placeholder="Nhập tên sản phẩm..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                            </div>
                                            <h4>Lọc Theo Hãng</h4>
                                            <?php
                                                $brands = ['iPhone', 'Samsung', 'Xiaomi', 'Oppo', 'Vivo'];
                                                foreach ($brands as $brand) {
                                                    $checked = (isset($_GET['brands']) && in_array($brand, $_GET['brands'])) ? 'checked' : '';
                                                    echo "<div class='dropdown-item'><label><input type='checkbox' name='brands[]' value='$brand' $checked> $brand</label></div>";
                                                }
                                            ?>
                                            <h4>Lọc Theo Phân Khúc Giá</h4>
                                            <?php
                                                $price_ranges = [
                                                    'under_5m' => 'Dưới 5 triệu',
                                                    '5m_10m' => '5 - 10 triệu',
                                                    '10m_20m' => '10 - 20 triệu',
                                                    'over_20m' => 'Trên 20 triệu'
                                                ];
                                                foreach ($price_ranges as $key => $label) {
                                                    $checked = (isset($_GET['price_ranges']) && in_array($key, $_GET['price_ranges'])) ? 'checked' : '';
                                                    echo "<div class='dropdown-item'><label><input type='checkbox' name='price_ranges[]' value='$key' $checked> $label</label></div>";
                                                }
                                            ?>
                                            <button type="submit" class="btn btn-primary btn-filter">Lọc</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($iddanhmuc) ?>">
                                    <?php if (isset($_GET['sort_price'])) { ?>
                                        <input type="hidden" name="sort_price" value="<?= htmlspecialchars($_GET['sort_price']) ?>">
                                    <?php } ?>
                                </form>
                            </div>
                        </aside>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="product_top_bar d-flex justify-content-between align-items-center">
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-center latest_product_inner">
                        <?php 
                            $item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 6;
                            $current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
                            $offset = ($current_page - 1) * $item_per_page;

                            // Xử lý điều kiện lọc
                            $whereClause = "id_danhmuc = " . (int)$iddanhmuc . " AND status = 0";

                            // Lọc theo từ khóa tìm kiếm
                            if (isset($_GET["search"]) && !empty(trim($_GET["search"]))) {
                                $keyword = $conn->quote("%" . $_GET["search"] . "%");
                                $whereClause .= " AND ten LIKE $keyword";
                            }

                            // Lọc theo hãng (dựa trên tên sản phẩm)
                            if (isset($_GET['brands']) && !empty($_GET['brands'])) {
                                $brandConditions = [];
                                foreach ($_GET['brands'] as $brand) {
                                    $brand = $conn->quote("%" . $brand . "%");
                                    $brandConditions[] = "ten LIKE $brand";
                                }
                                $whereClause .= " AND (" . implode(" OR ", $brandConditions) . ")";
                            }

                            // Lọc theo phân khúc giá
                            if (isset($_GET['price_ranges']) && !empty($_GET['price_ranges'])) {
                                $priceConditions = [];
                                foreach ($_GET['price_ranges'] as $range) {
                                    switch ($range) {
                                        case 'under_5m':
                                            $priceConditions[] = "gia < 5000000";
                                            break;
                                        case '5m_10m':
                                            $priceConditions[] = "gia BETWEEN 5000000 AND 10000000";
                                            break;
                                        case '10m_20m':
                                            $priceConditions[] = "gia BETWEEN 10000001 AND 20000000";
                                            break;
                                        case 'over_20m':
                                            $priceConditions[] = "gia > 20000000";
                                            break;
                                    }
                                }
                                $whereClause .= " AND (" . implode(" OR ", $priceConditions) . ")";
                            }

                            // Sắp xếp theo giá
                            $orderClause = "";
                            if (isset($_GET['sort_price']) && in_array($_GET['sort_price'], ['asc', 'desc'])) {
                                $orderClause = " ORDER BY gia " . ($_GET['sort_price'] == 'asc' ? 'ASC' : 'DESC');
                            }

                            // Tính tổng số sản phẩm
                            $numrow = rowCount("SELECT * FROM sanpham WHERE $whereClause");
                            $totalpage = ceil($numrow / $item_per_page);

                            // Lấy danh sách sản phẩm
                            $query = "SELECT * FROM sanpham WHERE $whereClause $orderClause LIMIT $item_per_page OFFSET $offset";
                            $products = selectAll($query);

                            if ($numrow > 0) {
                                foreach ($products as $row) {
                        ?>
                            <div class="col-lg-4 col-sm-6" style="height: 500px;">
                                <div class="single_product_item" <?= $row['id'] ?>>
                                    <a href="detail.php?id=<?= $row['id'] ?>">
                                        <img src="img/product/<?= $row['anh1'] ?>" style="width: 230px;height: 230px;" alt="">
                                    </a>
                                    <div class="single_product_text">
                                        <h4 style="font-size: 16px"><?= $row['ten'] ?></h4>
                                        <h3><?= number_format($row['gia']) . 'đ' ?></h3>
                                        <p><a href="detail.php?id=<?= $row['id'] ?>" style="font-size: 14px">Xem chi tiết</a></p>
                                        <a href="detail.php?id=<?= $row['id'] ?>">+ Thêm vào giỏ</a>
                                    </div>
                                </div>
                            </div>
                        <?php
                                }
                            } else {
                        ?>
                            <p>Không tìm thấy sản phẩm</p>
                        <?php
                            }
                        ?>
                        <!-- Phân trang -->
                        <div class="col-lg-12">
                            <div class="pageination">
                                <nav aria-label="Page navigation example">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($current_page > 1) {
                                            $prev_page = $current_page - 1;
                                            $url = "?id=$iddanhmuc&per_page=$item_per_page&page=$prev_page";
                                            if (isset($_GET['search'])) $url .= "&search=" . urlencode($_GET['search']);
                                            if (isset($_GET['sort_price'])) $url .= "&sort_price=" . urlencode($_GET['sort_price']);
                                            if (isset($_GET['brands'])) foreach ($_GET['brands'] as $brand) $url .= "&brands[]=" . urlencode($brand);
                                            if (isset($_GET['price_ranges'])) foreach ($_GET['price_ranges'] as $range) $url .= "&price_ranges[]=" . urlencode($range);
                                        ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= $url ?>" aria-label="Previous">
                                                    <i class="ti-angle-double-left"></i>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <?php for ($num = 1; $num <= $totalpage; $num++) {
                                            $url = "?id=$iddanhmuc&per_page=$item_per_page&page=$num";
                                            if (isset($_GET['search'])) $url .= "&search=" . urlencode($_GET['search']);
                                            if (isset($_GET['sort_price'])) $url .= "&sort_price=" . urlencode($_GET['sort_price']);
                                            if (isset($_GET['brands'])) foreach ($_GET['brands'] as $brand) $url .= "&brands[]=" . urlencode($brand);
                                            if (isset($_GET['price_ranges'])) foreach ($_GET['price_ranges'] as $range) $url .= "&price_ranges[]=" . urlencode($range);
                                            if ($num != $current_page) {
                                                if ($num > $current_page - 3 && $num < $current_page + 3) {
                                        ?>
                                                    <li class="page-item"><a class="page-link" href="<?= $url ?>"><?= $num ?></a></li>
                                                <?php }
                                            } else { ?>
                                                <strong class="page-item"><a class="page-link"><?= $num ?></a></strong>
                                            <?php }
                                        } ?>
                                        <?php if ($current_page < $totalpage - 1) {
                                            $next_page = $current_page + 1;
                                            $url = "?id=$iddanhmuc&per_page=$item_per_page&page=$next_page";
                                            if (isset($_GET['search'])) $url .= "&search=" . urlencode($_GET['search']);
                                            if (isset($_GET['sort_price'])) $url .= "&sort_price=" . urlencode($_GET['sort_price']);
                                            if (isset($_GET['brands'])) foreach ($_GET['brands'] as $brand) $url .= "&brands[]=" . urlencode($brand);
                                            if (isset($_GET['price_ranges'])) foreach ($_GET['price_ranges'] as $range) $url .= "&price_ranges[]=" . urlencode($range);
                                        ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= $url ?>" aria-label="Next">
                                                    <i class="ti-angle-double-right"></i>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================End Category Product Area =================-->
    <!-- product_list part start-->
    <section class="product_list best_seller">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="section_tittle text-center">
                        <h3>Sản Phẩm HOT</h3>
                    </div>
                </div>
            </div>
            <div class="row align-items-center justify-content-between">
                <div class="col-lg-12">
                    <div class="best_product_slider owl-carousel">
                        <?php 
                            foreach (selectAll("SELECT * FROM sanpham ORDER BY luotxem DESC LIMIT 5") as $item) {
                        ?>
                            <div class="single_product_item">
                                <a href="detail.php?id=<?= $item['id'] ?>">
                                    <img src="img/product/<?= $item['anh1'] ?>" alt="">
                                </a>
                                <div class="single_product_text">
                                    <a href="detail.php?id=<?= $item['id'] ?>">
                                        <h4><?= $item['ten'] ?></h4>
                                        <h3><?= number_format($item['gia']) . 'đ' ?></h3>
                                    </a>
                                </div>
                            </div>
                        <?php
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- product_list part end-->
    <?php include 'footer.php'; ?>
    <!-- jquery plugins here-->
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
        $(document).ready(function() {
            $('select[name="category"]').niceSelect();
            // Đảm bảo dropdown không tự đóng khi click vào checkbox, nút Lọc, hoặc thanh tìm kiếm
            $('.filter_dropdown .dropdown-menu').on('click', function(e) {
                e.stopPropagation();
            });
        });

        function redirectCategory(value) {
            let url = '';
            if (value === 'all') {
                url = 'product.php';
            } else {
                url = 'category.php?id=' + value;
            }
            // Giữ các tham số bộ lọc hiện tại
            const params = new URLSearchParams(window.location.search);
            params.delete('id'); // Xóa id để tránh xung đột
            if (params.toString()) {
                url += (url.includes('?') ? '&' : '?') + params.toString();
            }
            window.location.href = url;
        }
    </script>
</body>
</html>