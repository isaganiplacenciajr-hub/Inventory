<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'header.php';

// default to today so report shows sales for the current day on open
$defaultStart = date('Y-m-d');
$defaultEnd = date('Y-m-d');
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Sales Report</h1>
          <small>Use the date range to generate the report</small>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <form id="reportForm" class="form-inline justify-content-center" data-default-start="<?php echo htmlspecialchars($defaultStart); ?>" data-default-end="<?php echo htmlspecialchars($defaultEnd); ?>">
            <label class="mr-2">Start</label>
            <input type="date" id="startDate" class="form-control mr-2" value="<?php echo htmlspecialchars($defaultStart); ?>">
            <label class="mr-2">End</label>
            <input type="date" id="endDate" class="form-control mr-2" value="<?php echo htmlspecialchars($defaultEnd); ?>">
            <button id="applyBtn" type="button" class="btn btn-primary">Apply</button>
            <span id="reportLoading" class="spinner-border spinner-border-sm ml-2" role="status" style="display:none;" aria-hidden="true"></span>
            <a id="printBtn" class="btn btn-secondary ml-2" href="#" target="_blank">Print</a>
            <button id="viewTransactionsBtn" type="button" class="btn btn-info ml-2" style="display:none;"><i class="fas fa-list"></i> View Transactions</button>
          </form>
        </div>

        <div class="card-body">
          <div id="reportMessage" class="alert alert-info" style="display:none;"></div>

          <!-- Transaction list is shown in the modal only -->

          <div id="reportSummary" class="mb-4" style="display:none;">
            <div class="row">
              <div class="col-md-6">
                <div class="info-box bg-info">
                  <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Total Sales</span>
                    <span class="info-box-number" style="font-size:1.8rem;">₱<span id="grandTotal">0.00</span></span>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-box bg-success">
                  <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Total Orders</span>
                    <span class="info-box-number" style="font-size:1.8rem;"><span id="totalOrders">0</span></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table id="reportTable" class="table table-hover" style="display:none;">
              <thead class="table-primary">
                <tr>
                  <th style="font-weight:700;font-size:1rem;">Period</th>
                  <th class="text-center" style="font-weight:700;font-size:1rem;">Orders</th>
                  <th class="text-right" style="font-weight:700;font-size:1rem;">Total Sales</th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot style="font-weight:700;background-color:#f0f0f0;font-size:1.05rem;">
                <tr>
                  <td class="text-right">Total</td>
                  <td class="text-center" id="tfootOrders">0</td>
                  <td class="text-right" id="tfootSales">₱0.00</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function formatCurrency(n) {
    return parseFloat(n).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
  }


  function fetchReport() {
    $('#reportMessage').hide();
    $('#reportTable').hide();
    $('#reportSummary').hide();

    var start = $('#startDate').val();
    var end = $('#endDate').val();
      // fallback to server-provided defaults if inputs are empty
      var $form = $('#reportForm');
      if (!start) start = $form.attr('data-default-start');
      if (!end) end = $form.attr('data-default-end');
      // populate inputs so users see the values
      $('#startDate').val(start);
      $('#endDate').val(end);
      if (!start || !end) {
        $('#reportMessage').text('Please select both start and end dates').show();
        return;
      }
      console.debug('fetchReport start=', start, 'end=', end);

    $('#applyBtn').prop('disabled', true).text('Loading...');
    $('#reportLoading').show();

    $.getJSON('api/sales_report.php', { start: start, end: end, view: 'period' })
      .done(function(res) {
        if (!res.success) {
          $('#reportMessage').text(res.message || 'Failed to load report').show();
          return;
        }
        const rows = res.rows || [];
        const $tbody = $('#reportTable tbody').empty();

        if (rows.length === 0) {
          $('#reportMessage').text('No sales data for the selected date range').show();
          $('#reportTable').hide();
          $('#reportSummary').hide();
          $('#printBtn').attr('href', '#');
        } else {
          // period view: rows contain period_label, total_sales, orders_count
          // when grouping by hour (single-day view), hide rows with zero orders AND zero sales
          var filteredRows = rows;
          try {
            if ((res.grouping || '') === 'hour') {
              filteredRows = rows.filter(function(rr){
                var sales = parseFloat(rr.total_sales) || 0;
                var orders = parseInt(rr.orders_count) || 0;
                return (sales !== 0) || (orders !== 0);
              });
            }
          } catch (e) { filteredRows = rows; }

          filteredRows.forEach(function(r) {
            // format period_label to MM/DD/YYYY (or variants)
            var lbl = r.period_label;
            var grp = res.grouping || '';
            if (grp === 'day') {
              // YYYY-MM-DD -> MM/DD/YYYY
              var p = lbl.split('-');
              if (p.length === 3) lbl = p[1] + '/' + p[2] + '/' + p[0];
            } else if (grp === 'month') {
              // YYYY-MM -> MM/YYYY
              var p = lbl.split('-');
              if (p.length === 2) lbl = p[1] + '/' + p[0];
            } else if (grp === 'hour') {
              // YYYY-MM-DD HH:00 -> MM/DD/YYYY  (remove hour portion)
              var parts = lbl.split(' ');
              var d = parts[0] ? parts[0].split('-') : [];
              if (d.length === 3) lbl = d[1] + '/' + d[2] + '/' + d[0];
            } else {
              // year
              // leave as-is
            }

            const tr = `<tr><td>${lbl}</td><td class="text-center">${r.orders_count}</td><td class="text-right">₱${formatCurrency(r.total_sales)}</td></tr>`;
            $tbody.append(tr);
          });

          $('#reportTable').show();
          $('#reportSummary').show();

          // populate sample orders if provided
          if (res.sample_orders && res.sample_orders.length) {
              // Populate modal table with all sample orders
              var $modalBody = $('#transactionTableBody').empty();
              res.sample_orders.forEach(function(o) {
                  var displayDate = (o.order_date || '').split(' ')[0]; // Show only the date portion
                  // modal row only
                  var mrow = '<tr>' +
                             '<td>#' + o.invoice_id + '</td>' +
                             '<td>' + (o.customer_name || '') + '</td>' +
                             '<td class="text-right">₱' + formatCurrency(o.total) + '</td>' +
                             '<td><small>' + displayDate + '</small></td>' +
                             '</tr>';
                  $modalBody.append(mrow);
                });
                $('#viewTransactionsBtn').show();
              } else {
                // no sample orders; still show button to fetch full transactions
                $('#viewTransactionsBtn').show();
              }

          // compute totals for displayed (filtered) period rows
          var clientSum = filteredRows.reduce(function(s,r){ return s + (parseFloat(r.total_sales)||0); }, 0);
          var totalOrdersSum = filteredRows.reduce(function(s,r){ return s + (parseInt(r.orders_count)||0); }, 0);
          $('#grandTotal').text(formatCurrency(clientSum));
          // prefer server-supplied total_orders but fall back to summed value
          $('#totalOrders').text((typeof res.total_orders !== 'undefined') ? res.total_orders : totalOrdersSum);
          // update tfoot totals
          $('#tfootOrders').text(totalOrdersSum);
          $('#tfootSales').text('₱' + formatCurrency(clientSum));

          // update print link to open server-side print (reuse current page with query)
          $('#printBtn').attr('href', 'sales_report_print.php?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end));
        }
      })
      .fail(function() {
        $('#reportMessage').text('An error occurred while fetching the report').show();
      })
      .always(function() {
        $('#applyBtn').prop('disabled', false).text('Apply');
        $('#reportLoading').hide();
      });
  }

  (function waitForjQuery(){
    if (typeof window.jQuery === 'undefined') {
      setTimeout(waitForjQuery, 50);
      return;
    }
    $(function(){
      $('#applyBtn').on('click', fetchReport);
      $('#viewTransactionsBtn').on('click', function(){
        // load full transactions from API then show modal
        var start = $('#startDate').val() || $('#reportForm').attr('data-default-start');
        var end = $('#endDate').val() || $('#reportForm').attr('data-default-end');
        $('#viewTransactionsBtn').prop('disabled', true).text('Loading...');
        $.getJSON('api/sales_report.php', { start: start, end: end, view: 'transactions' })
          .done(function(r) {
            if (!r.success) {
              alert(r.message || 'Failed to load transactions');
              return;
            }
            var $mbody = $('#transactionTableBody').empty();
            (r.transactions || []).forEach(function(t) {
              var dateOnly = (t.order_date || '').split(' ')[0];
              var row = '<tr>' +
                        '<td>#' + t.invoice_id + '</td>' +
                        '<td>' + (t.customer_name || '') + '</td>' +
                        '<td class="text-right">₱' + formatCurrency(t.total) + '</td>' +
                        '<td><small>' + dateOnly + '</small></td>' +
                        '</tr>';
              $mbody.append(row);
            });
            $('#transactionModal').modal('show');
          })
          .fail(function(){ alert('Failed to fetch transactions'); })
          .always(function(){ $('#viewTransactionsBtn').prop('disabled', false).text('View Transactions'); });
      });
      // automatically load today's report when page opens
      fetchReport();
    });
  })();
</script>

<!-- Transaction List Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" role="dialog" aria-labelledby="transactionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="transactionModalLabel">Transaction List</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>Invoice ID</th>
                <th>Customer</th>
                <th class="text-right">Total</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody">
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include_once 'footer.php'; ?>
