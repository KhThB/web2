function myFunction() {
    var input, filter, table, tr, td, i, txtValue
    input = document.getElementById('txtFind')
    filter = input.value.toUpperCase()
    table = document.querySelector('table')
    tr = table.getElementsByTagName('tr')
    console.log(tr)
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName('td')[1]
        if (td) {
            txtValue = td.textContent || td.innerText
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = ''
            } else {
                tr[i].style.display = 'none'
            }
        }
    }
}
function searchAll() {
    var inputName = document.getElementById('txtFindName').value.trim().toUpperCase();
    var inputPhone = document.getElementById('txtFindPhone').value.trim().toUpperCase();
    var inputAddress = document.getElementById('txtFindAddress').value.trim().toUpperCase();
    var table = document.querySelector('table');
    var tr = table.getElementsByTagName('tr');
    var noResultsDiv = document.getElementById('noResults');
    var found = false;

    // Bỏ qua hàng tiêu đề (thead)
    for (var i = 1; i < tr.length; i++) {
        var tdName = tr[i].getElementsByTagName('td')[1]; // Cột "Khách Hàng"
        var tdPhone = tr[i].getElementsByTagName('td')[8]; // Cột "Số Điện Thoại" (ẩn)
        var tdAddress = tr[i].getElementsByTagName('td')[9]; // Cột "Địa Chỉ Đơn Hàng" (ẩn)

        var matchName = true, matchPhone = true, matchAddress = true;

        // Kiểm tra tên khách hàng
        if (inputName && tdName) {
            var txtValueName = tdName.textContent || tdName.innerText;
            matchName = txtValueName.toUpperCase().indexOf(inputName) > -1;
        }

        // Kiểm tra số điện thoại
        if (inputPhone && tdPhone) {
            var txtValuePhone = tdPhone.textContent || tdPhone.innerText;
            matchPhone = txtValuePhone.toUpperCase().indexOf(inputPhone) > -1;
        }

        // Kiểm tra địa chỉ
        if (inputAddress && tdAddress) {
            var txtValueAddress = tdAddress.textContent || tdAddress.innerText;
            matchAddress = txtValueAddress.toUpperCase().indexOf(inputAddress) > -1;
        }

        // Chỉ hiển thị hàng nếu tất cả điều kiện đều khớp
        if (matchName && matchPhone && matchAddress) {
            tr[i].style.display = '';
            found = true;
        } else {
            tr[i].style.display = 'none';
        }
    }

    // Hiển thị thông báo nếu không tìm thấy kết quả
    if (!found) {
        noResultsDiv.style.display = 'block';
    } else {
        noResultsDiv.style.display = 'none';
    }
}

function resetSearch() {
    // Xóa các ô nhập
    document.getElementById('txtFindName').value = '';
    document.getElementById('txtFindPhone').value = '';
    document.getElementById('txtFindAddress').value = '';

    // Hiển thị lại tất cả hàng
    var table = document.querySelector('table');
    var tr = table.getElementsByTagName('tr');
    var noResultsDiv = document.getElementById('noResults');

    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = '';
    }

    // Ẩn thông báo không tìm thấy
    noResultsDiv.style.display = 'none';
}