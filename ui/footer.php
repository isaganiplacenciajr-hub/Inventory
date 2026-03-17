
<?php
// Hide footer elements for users (case-insensitive check)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
$hideFooter = ($role === 'user');
if (!$hideFooter):
?>
  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
    <div class="p-3">
      <h5>Title</h5>
      <p>Sidebar content</p>
    </div>
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
    SPM LPG INVENTORY SYSTEM
    </div>
    <!-- Default to the left -->
    <strong>BY: ISAGANI PLACENCIA JR.</strong> 
  </footer>
<?php endif; ?>
</div> 
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Select2 -->
<script src="../plugins/select2/js/select2.full.min.js"></script>

<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>

<!-- DataTables  & Plugins -->
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../plugins/jszip/jszip.min.js"></script>
<script src="../plugins/pdfmake/pdfmake.min.js"></script>
<script src="../plugins/pdfmake/vfs_fonts.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.colVis.min.js"></script>



<!-- SweetAlert2 -->
<script src="../plugins/sweetalert2/sweetalert2.min.js"></script>

<!-- Chart.js for Sales Graph -->
<script src="../plugins/chart.js/Chart.min.js"></script>

<!-- Sales Graph Script (runs after all dependencies loaded) -->
<script>
  // Only initialize sales graph if on dashboard page
  if (document.getElementById('salesGraphCanvas')) {
    $(document).ready(function() {
      let salesChart = null;

      function formatCurrency(v) {
        return '₱' + parseFloat(v || 0).toFixed(2);
      }

      function formatDateLocal(dt) {
        const year = dt.getFullYear();
        const month = String(dt.getMonth() + 1).padStart(2, '0');
        const day = String(dt.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }

      function setDefaultDates() {
        const now = new Date();
        const dow = now.getDay(); // 0=Sun..6=Sat
        const diff = (dow + 6) % 7; // convert to Monday=0
        const weekStart = new Date(now);
        weekStart.setDate(now.getDate() - diff);

        const dateFrom = formatDateLocal(weekStart);
        const dateTo = formatDateLocal(now);

        if (!$('#startDate').val()) $('#startDate').val(dateFrom);
        if (!$('#endDate').val()) $('#endDate').val(dateTo);
      }

      function fetchSales(startDate, endDate) {
        $('#salesGraphMessage').text('Loading...');
        console.log('[Sales] Fetching:', startDate, 'to', endDate);
        $.ajax({
          url: 'api/get_daily_sales.php',
          type: 'GET',
          data: { start_date: startDate, end_date: endDate },
          dataType: 'json',
          success: function(res) {
            console.log('[Sales] Response:', res);
            if (!res || !res.success) {
              console.error('[Sales] API error:', res?.message);
              $('#salesGraphMessage').text('Failed to load sales data');
              return;
            }

            const totalSales = parseFloat(res.total_sales || 0);
            const totalTx = parseInt(res.total_transactions || 0);
            const totalDep = parseFloat(res.total_deposits || 0);
            const dataArray = res.data || [];
            
            $('#totalSalesAmount').text(formatCurrency(totalSales));
            $('#totalTransactions').text(totalTx);
            $('#totalTankDeposits').text(formatCurrency(totalDep));

            if (!dataArray || dataArray.length === 0) {
              $('#salesGraphMessage').text('No sales data available for selected date range.');
              console.log('[Sales] Empty data, updating chart...');
              if (salesChart) {
                salesChart.data.labels = [];
                salesChart.data.datasets[0].data = [];
                salesChart.update();
              } else {
                initChart([], []);
              }
              return;
            }

            $('#salesGraphMessage').text('');
            const labels = dataArray.map(d => d.label || '');
            const data = dataArray.map(d => parseFloat(d.sales || 0));
            console.log('[Sales] Labels:', labels, 'Data:', data);

            if (!salesChart) {
              initChart(labels, data);
            } else {
              salesChart.data.labels = labels;
              salesChart.data.datasets[0].data = data;
              salesChart.update({ duration: 500 });
            }
          },
          error: function(jqXHR, textStatus, errorThrown) {
            console.error('[Sales] AJAX Error:', textStatus, errorThrown);
            $('#salesGraphMessage').text('Failed to load sales data. Check console.');
          }
        });
      }

      function initChart(labels, data) {
        console.log('[Chart] Initializing with', labels.length, 'data points');
        const $canvas = $('#salesGraphCanvas');
        if (!$canvas.length) {
          console.error('[Chart] Canvas not found!');
          return;
        }
        const ctx = $canvas[0].getContext('2d');
        try {
          salesChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: labels,
              datasets: [{
                label: 'Daily Sales',
                data: data,
                fill: true,
                backgroundColor: 'rgba(54,162,235,0.2)',
                borderColor: 'rgba(54,162,235,1)',
                borderWidth: 2,
                lineTension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(54,162,235,1)',
                borderCapStyle: 'butt'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              animation: { duration: 700, easing: 'easeOutQuart' },
              scales: {
                xAxes: [{
                  display: true,
                  gridLines: { display: false },
                  ticks: { autoSkip: true, maxRotation: 45, fontSize: 10 }
                }],
                yAxes: [{
                  display: true,
                  beginAtZero: true,
                  gridLines: { drawBorder: true },
                  ticks: {
                    beginAtZero: true,
                    callback: function(v) { return formatCurrency(v); }
                  }
                }]
              },
              legend: { display: false },
              tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                  label: function(item) {
                    return 'Sales: ' + formatCurrency(item.yLabel);
                  }
                }
              }
            }
          });
          console.log('[Chart] Created successfully');
        } catch(e) {
          console.error('[Chart] Creation failed:', e);
        }
      }

      // quick filters
      function applyQuick(range) {
        const now = new Date();
        let start, end = new Date();
        if (range === 'today') {
          start = end = new Date();
        } else if (range === 'week') {
          // compute Monday of current week
          start = new Date(now);
          const dow = now.getDay(); // 0=Sun,1=Mon,...
          const diff = (dow + 6) % 7; // convert so Mon=0
          start.setDate(now.getDate() - diff);
          end = new Date();
        } else if (range === 'month') {
          start = new Date(now.getFullYear(), now.getMonth(), 1);
          end = new Date();
        } else if (range === 'year') {
          start = new Date(now.getFullYear(), 0, 1);
          end = new Date();
        }
        const s = formatDateLocal(start);
        const e = formatDateLocal(end);
        console.log('[Filter]', range, ':', s, 'to', e);
        $('#startDate').val(s); $('#endDate').val(e);
        fetchSales(s, e);
      }

      // init
      console.log('[Sales] Init');
      setDefaultDates();
      setActiveFilter('#filterWeek');
      applyQuick('week');

      $('#applyDateRange').on('click', function(){ 
        const s=$('#startDate').val(); 
        const e=$('#endDate').val(); 
        if(s && e) {
          console.log('[Apply] Date range:', s, 'to', e);
          fetchSales(s,e); 
        }
      });
      // Fix filter button logic: only one active, update graph
      function setActiveFilter(btnId) {
        $('#filterToday, #filterWeek, #filterMonth, #filterYear').removeClass('active');
        $(btnId).addClass('active');
      }
      $('#filterToday').on('click', function(){
        applyQuick('today');
        setActiveFilter('#filterToday');
      });
      $('#filterWeek').on('click', function(){
        applyQuick('week');
        setActiveFilter('#filterWeek');
      });
      $('#filterMonth').on('click', function(){
        applyQuick('month');
        setActiveFilter('#filterMonth');
      });
      $('#filterYear').on('click', function(){
        applyQuick('year');
        setActiveFilter('#filterYear');
      });
      // Set default active filter on page load
      setActiveFilter('#filterWeek');

      // make deposit box open modal filtered to current range
      $('#boxDeposits').on('click', function(){
        const s = $('#startDate').val();
        const e = $('#endDate').val();
        $('#depositModal').modal('show');
        if(s && e) {
          $('#depositFilter').val('Custom Date Range').trigger('change');
          $('#depositStart').val(s);
          $('#depositEnd').val(e);
          loadDepositData('Custom Date Range', s, e);
        } else {
          // fallback to Today
          loadDepositData('Today');
        }
      });

      function loadBranchOrders(branchKey) {
        $('#branchOrdersModalLabel').text("Today's Orders - " + branchKey);
        $('#branchOrdersTableBody').html('<tr><td colspan="6" class="text-center">Loading orders...</td></tr>');

        $.ajax({
          url: 'api/get_today_branch_orders.php',
          type: 'GET',
          dataType: 'json',
          data: { branch: branchKey },
          success: function(res) {
            if (!res || !res.success) {
              $('#branchOrdersTableBody').html('<tr><td colspan="6" class="text-center">Failed to load data.</td></tr>');
              return;
            }
            const rows = res.data || [];
            if (!rows.length) {
              $('#branchOrdersTableBody').html('<tr><td colspan="6" class="text-center">No orders today for this branch.</td></tr>');
              return;
            }
            let html = '';
            rows.forEach(function(row) {
              html += '<tr>' +
                      '<td>' + $('<div>').text(row.invoice_id).html() + '</td>' +
                      '<td>' + $('<div>').text(row.product).html() + '</td>' +
                      '<td>' + $('<div>').text(row.qty).html() + '</td>' +
                      '<td>₱' + $('<div>').text(row.total).html() + '</td>' +
                      '<td>' + $('<div>').text(row.order_date).html() + '</td>' +
                      '<td>' + $('<div>').text(row.branch).html() + '</td>' +
                      '</tr>';
            });
            $('#branchOrdersTableBody').html(html);
          },
          error: function() {
            $('#branchOrdersTableBody').html('<tr><td colspan="6" class="text-center">Error loading data.</td></tr>');
          }
        });
      }

      function loadInvoiceDetails(invoiceId) {
        $('#recentTransactionModalLabel').text('Invoice ' + invoiceId + ' Details');
        $('#recentTransactionDetailBody').html('<tr><td colspan="4" class="text-center">Loading details...</td></tr>');

        $.ajax({
          url: 'api/get_invoice_details.php',
          type: 'GET',
          dataType: 'json',
          data: { invoice_id: invoiceId },
          success: function(res) {
            if (!res || !res.success) {
              $('#recentTransactionDetailBody').html('<tr><td colspan="4" class="text-center">Failed to load details.</td></tr>');
              return;
            }
            const rows = res.data || [];
            if (!rows.length) {
              $('#recentTransactionDetailBody').html('<tr><td colspan="4" class="text-center">No details available.</td></tr>');
              return;
            }
            let html = '';
            rows.forEach(function(row) {
              html += '<tr>' +
                      '<td>' + $('<div>').text(row.product).html() + '</td>' +
                      '<td>' + $('<div>').text(row.qty).html() + '</td>' +
                      '<td>₱' + $('<div>').text(row.unit_price).html() + '</td>' +
                      '<td>₱' + $('<div>').text(row.line_total).html() + '</td>' +
                      '</tr>';
            });
            $('#recentTransactionDetailBody').html(html);
          },
          error: function() {
            $('#recentTransactionDetailBody').html('<tr><td colspan="4" class="text-center">Error loading details.</td></tr>');
          }
        });
      }

      $(document).on('click', '.branch-order-card', function() {
        const branchSelected = $(this).data('branch');
        loadBranchOrders(branchSelected);
        $('#branchOrdersModal').modal('show');
      });

      $(document).on('click', '.recent-transaction-row', function() {
        const invoiceId = $(this).data('invoice');
        if (!invoiceId) return;
        loadInvoiceDetails(invoiceId);
        $('#recentTransactionModal').modal('show');
      });

      // auto-refresh on focus
      $(window).on('focus', function(){ fetchSales($('#startDate').val(), $('#endDate').val()); });
    });
  }

  // User dashboard graph support
  if (document.getElementById('userSalesGraph')) {
    $(document).ready(function(){
      try {
        const rawData = window.userSalesGraphData || [];
        const labels = rawData.map(d => d.label || '');
        const values = rawData.map(d => parseFloat(d.sales || 0));
        const ctx = document.getElementById('userSalesGraph').getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Last 7 Days Sales',
              data: values,
              backgroundColor: 'rgba(54, 162, 235, 0.2)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 2,
              pointRadius: 3,
              pointBackgroundColor: 'rgba(54, 162, 235, 1)',
              fill: true,
              lineTension: 0.3
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 650 },
            scales: {
              xAxes: [{
                display: true,
                gridLines: { display: false },
                ticks: { autoSkip: true, maxRotation: 45 }
              }],
              yAxes: [{
                display: true,
                ticks: {
                  beginAtZero: true,
                  callback: function(value) { return '₱' + parseFloat(value).toFixed(2); }
                },
                gridLines: { color: 'rgba(0,0,0,0.06)' }
              }]
            },
            legend: { display: false },
            tooltips: {
              callbacks: {
                label: function(tooltipItem) { return 'Sales: ₱' + parseFloat(tooltipItem.yLabel).toFixed(2); }
              }
            }
          }
        });
      } catch(err) {
        console.error('User sales graph error:', err);
      }
    });
  }
</script>

<script>
  // Logout confirmation
  function logoutConfirm(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Are you sure?',
      text: 'Are you sure you want to log out?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, log out',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'logout.php';
      }
    });
  }
</script>

<?php
// allow pages to inject additional scripts after core libraries
if (!empty($pageScript)) {
  echo $pageScript;
}
?>

</body>
</html>
  