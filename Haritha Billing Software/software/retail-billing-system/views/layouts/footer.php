</main>
</div><!-- /main-wrapper -->

<!-- Mobile overlay -->
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99"
    onclick="closeSidebar()"></div>

<!-- Global Modal -->
<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal-box" id="globalModal">
    <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">Modal</h3>
        <button class="modal-close" id="modalClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer" id="modalFooter"></div>
</div>

<!-- Loading -->
<div class="loading-overlay" id="loadingOverlay">
    <div style="text-align:center">
        <div class="spinner" style="margin:0 auto 10px"></div>
        <span style="font-size:0.82rem;color:#64748b">Processing...</span>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<!-- JsBarcode CDN - for barcode label generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<!-- App JS -->
<script src="<?php echo BASE_URL; ?>/public/assets/js/api.js"></script>
<script src="<?php echo BASE_URL; ?>/public/assets/js/dashboard.js"></script>
<script src="<?php echo BASE_URL; ?>/public/assets/js/barcode.js"></script>
<script src="<?php echo BASE_URL; ?>/public/assets/js/product.js"></script>
<script src="<?php echo BASE_URL; ?>/public/assets/js/billing.js"></script>

<script>
    window.APP = {
        baseUrl: '<?php echo BASE_URL; ?>',
        apiUrl: '<?php echo BASE_URL; ?>/api/api.php',
        companyName: '<?php echo addslashes(COMPANY_NAME); ?>',
        companyAddress: '<?php echo addslashes(COMPANY_ADDRESS); ?>',
        companyPhone: '<?php echo addslashes(COMPANY_PHONE); ?>',
        companyEmail: '<?php echo addslashes(COMPANY_EMAIL); ?>',
        companyGSTIN: '<?php echo addslashes(COMPANY_GSTIN); ?>',
        currency: '<?php echo CURRENCY_SYMBOL; ?>',
        page: '<?php echo trim(isset($_GET['url']) ? trim($_GET['url'], '/') : 'dashboard'); ?>'
    };

    // Live clock
    function updateClock() {
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        var ss = String(now.getSeconds()).padStart(2, '0');
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var dateStr = days[now.getDay()] + ', ' + String(now.getDate()).padStart(2, '0') + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        var el = document.getElementById('topClock');
        var el2 = document.getElementById('topDate');
        if (el) el.textContent = hh + ':' + mm + ':' + ss;
        if (el2) el2.textContent = dateStr;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Mobile sidebar
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').style.display = 'none';
    }
    var mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
            var sb = document.getElementById('sidebar');
            var ov = document.getElementById('sidebarOverlay');
            var open = sb.classList.toggle('open');
            ov.style.display = open ? 'block' : 'none';
        });
    }
</script>
</body>

</html>