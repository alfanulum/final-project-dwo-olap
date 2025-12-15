<?php include 'layouts/head.php'; ?>

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <div class="row mb-4">
        <div class="col-12">
          <div class="card bg-primary">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0 text-white">Active Filter: <span id="filter-status" class="fw-bold">All Years</span></h6>
                <small class="text-light">Klik pada grafik batang "Total Penjualan" untuk melihat detail bulanan.</small>
              </div>
              <button class="btn btn-light btn-sm txt-primary" onclick="resetDashboard()">Reset Filter</button>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xl-8 col-lg-12">
          <div class="card">
            <div class="card-header">
              <h5>1. Tren Penjualan (Drill-down Available)</h5>
            </div>
            <div class="card-body">
              <div id="chart-bq1"></div>
            </div>
          </div>
        </div>

        <div class="col-xl-4 col-lg-12">
          <div class="card">
            <div class="card-header">
              <h5>4. Online vs Offline Channel</h5>
            </div>
            <div class="card-body">
              <div id="chart-bq4"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xl-6">
          <div class="card">
            <div class="card-header">
              <h5>3. Perbandingan Urban vs Rural (Monthly)</h5>
            </div>
            <div class="card-body">
              <div id="chart-bq3"></div>
            </div>
          </div>
        </div>

        <div class="col-xl-6">
          <div class="card">
            <div class="card-header">
              <h5>2. Distribusi Frekuensi Pembelian</h5>
            </div>
            <div class="card-body">
              <div id="chart-bq2"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>5. Top 5 Salesperson Performance</h5>
            </div>
            <div class="card-body">
              <div id="chart-bq5"></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="assets/js/dashboard-apex.js"></script>
  <?php include 'layouts/footer.php'; ?>
</div>
</div>

<?php include 'layouts/tail.php'; ?>