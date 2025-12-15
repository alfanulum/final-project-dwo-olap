  <?php include 'layouts/head.php'; ?>

  <div class="page-body-wrapper">
    <?php include 'layouts/sidebar.php'; ?>

    <div class="page-body">
      <div class="container-fluid">
        <div class="page-title">
          <div class="row">
            <div class="col-6">
              <h4>OLAP</h4>
            </div>
            <div class="col-6">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index-2.html">
                    <svg class="stroke-icon">
                      <use href="https://admin.pixelstrap.net/riho/assets/svg/icon-sprite.svg#stroke-home"></use>
                    </svg></a></li>
                <li class="breadcrumb-item">Dashboard</li>
                <li class="breadcrumb-item active">OLAP</li>
              </ol>
            </div>
          </div>
        </div>
      </div>
      <!-- Container-fluid starts-->
      <div class="container-fluid">
        <div class="card">
          <div class="card-header sales-chart card-no-border">
            <h4>OLAP WH ADVENTURE WORKS</h4>
          </div>
          <div class="card-body" style="height: 70vh;">
            <iframe src="http://localhost:8080/mondrian/testpage.jsp?query=wh_adventureworks" style="width: 100%; height: 100%" frameborder="0"></iframe>
          </div>
        </div>
      </div>
      <!-- Container-fluid Ends-->
    </div>

    <?php include 'layouts/footer.php'; ?>
  </div>
  </div>

  <?php include 'layouts/tail.php'; ?>