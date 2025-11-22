<section class="header">
    <div class="logo">
        <i class="ri-menu-line icon icon-0 menu"></i>
        <h2>Hệ thống điểm danh</h2>
    </div>
    <div class="search--notification--profile">
        <div id="searchInput" class="search">
            <input type="text" id="searchText" placeholder="Tìm kiếm .....">
            <button onclick="searchItems()"><i class="ri-search-2-line"></i></button>
        </div>
        <div class="notification--profile">
            <div class="picon lock">
                @ <?php echo user()->name ?>
            </div>

            <div class="picon profile">
                <img src="resources/images/user.png" alt="">
            </div>
            <a href="logout" class="picon" title="Đăng xuất" style="text-decoration:none; display:flex; align-items:center; gap:6px;">
                <i class="ri-logout-box-r-line"></i> <span>Đăng xuất</span>
            </a>
        </div>
    </div>
</section>
<script>
    function searchItems() {
        var input = document.getElementById('searchText').value.toLowerCase();
        var rows = document.querySelectorAll('table tr');

        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            var found = false;

            cells.forEach(function(cell) {
                if (cell.innerText.toLowerCase().includes(input)) {
                    found = true;
                }
            });

            if (found) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
