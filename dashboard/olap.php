<?php include 'layouts/head.php'; ?>

<div class="page-body-wrapper">
  <?php include 'layouts/sidebar.php'; ?>

  <div class="page-body">
    <div class="container-fluid">

      <!-- Modern Hero Section -->
      <div class="page-hero">
        <h3>OLAP Analysis</h3>
        <p>Interactive multidimensional data analysis and reporting</p>
      </div>

      <!-- OLAP Card -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>OLAP WH ADVENTURE WORKS</h4>
              <p class="text-muted mb-0">Explore data across multiple dimensions</p>
            </div>
            <div class="card-body" style="padding: 0; height: 70vh;">
              <iframe
                src="http://localhost:8080/mondrian/testpage.jsp?query=wh_adventureworks"
                style="width: 100%; height: 100%; border: none;"
                title="OLAP Analysis">
              </iframe>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include 'layouts/footer.php'; ?>
</div>

<?php include 'layouts/tail.php'; ?>