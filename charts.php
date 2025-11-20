<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if the user is an admin, if not, redirect to the dashboard
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== 'Admin'){
    header("location: index.php");
    exit;
}

// Include the central configuration file.
include("conf.php");

// Fetch inventory and sales report data
$report_data = [];
if (isset($conn)) {
    $sql = "SELECT s.id, s.sold_date, f.food_name, i.item_name, s.quantity, s.total_price 
            FROM sales AS s
            LEFT JOIN food f ON s.food_id = f.food_id 
            LEFT JOIN items i ON s.item_id = i.item_id 
            ORDER BY s.sold_date DESC, s.id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    }
}

// For Sales Report, we can use the same detailed data.
$sales_report_data = $report_data;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Reports - SB Admin</title>
    <link
      href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css"
      rel="stylesheet"
    />
    <link href="css/styles.css" rel="stylesheet" />
    <script
      src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"
      crossorigin="anonymous"
    ></script>
  </head>
  <body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <!-- Navbar Brand-->
      <a class="navbar-brand ps-3" href="index.php">Inventory System</a>
      <!-- Sidebar Toggle-->
      <button
        class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0"
        id="sidebarToggle"
        href="#!"
      >
        <i class="fas fa-bars"></i>
      </button>
      <!-- Navbar Search-->
      <form
        class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"
      >
        <div class="input-group navbar-search">
          <input
            class="form-control"
            type="text"
            placeholder="Search for..."
            aria-label="Search for..."
          />
        </div>
      </form>
      <!-- Navbar-->
      <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle"
            id="navbarDropdown"
            href="#"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            ><i class="fas fa-user fa-fw"></i
          ></a>
          <ul
            class="dropdown-menu dropdown-menu-end"
            aria-labelledby="navbarDropdown"
          >
            <li><a class="dropdown-item" href="logout.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
    <div id="layoutSidenav">
      <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
          <div class="sb-sidenav-menu">
            <div class="nav">
              <a class="nav-link" href="index.php">
                <div class="sb-nav-link-icon">
                  <i class="fas fa-tachometer-alt"></i>
                </div>
                Dashboard
              </a>
              <a class="nav-link" href="table.php">
                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                Profile
              </a>
              <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'Admin'): ?>
              <a class="nav-link active" href="charts.php">
                <div class="sb-nav-link-icon">
                  <i class="fas fa-chart-area"></i>
                </div>
                Reports
              </a>
              <?php endif; ?>
              <a class="nav-link" href="activitylog.php">
                <div class="sb-nav-link-icon">
                  <i class="fas fa-history"></i>
                </div>
                Activity Log
              </a>
            </div>
          </div>
        </nav>
      </div>
      <div id="layoutSidenav_content">
        <main>
          <div class="container-fluid px-4">
            <h1 class="mt-4">Reports</h1> 
            <ol class="breadcrumb mb-4">
              <li class="breadcrumb-item">
                <a href="index.php">Dashboard</a>
              </li>
              <li class="breadcrumb-item active">Reports</li>
            </ol>
            <div class="card mb-4">
              <div
                class="card-header d-flex justify-content-between align-items-center"
              >
                <div>
                  <i class="fas fa-table me-1"></i>
                  Inventory Report
                </div>
                <div>
                  <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="fas fa-file-alt"></i> Generate Report</button>
                </div>
              </div>
              <div class="card-body">
                <table id="datatablesSimple">
                  <thead>
                    <tr>
                      <th>Sold Date</th>
                      <th>Food Name</th>
                      <th>Item Name</th>
                      <th>Quantity Sold</th>
                      <th>Total Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Inventory data will be populated here -->
                    <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['sold_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['food_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['item_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($row['total_price'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div
                class="card-footer small text-muted"
              >Updated <?php echo date('F d, Y h:i A'); ?></div>
            </div>
          </div>
        </main>
        <footer class="py-4 bg-light mt-auto">
          <!-- Report Modal -->
          <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="reportModalLabel">Sales Report</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Sold Date</th>
                        <th>Food Name</th>
                        <th>Item Name</th>
                        <th>Quantity Sold</th>
                        <th>Total Price</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($report_data as $row): ?>
                      <tr>
                          <td><?php echo htmlspecialchars($row['sold_date']); ?></td>
                          <td><?php echo htmlspecialchars($row['food_name'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($row['item_name'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                          <td><?php echo htmlspecialchars(number_format($row['total_price'], 2)); ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
          <div class="container-fluid px-4">
            <div
              class="d-flex align-items-center justify-content-between small"
            ></div>
          </div>
        </footer>
      </div>
    </div>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
      crossorigin="anonymous"
    ></script>
    <script src="js/scripts.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
      crossorigin="anonymous"
    ></script>
    <script>
      window.addEventListener("DOMContentLoaded", (event) => {
        // Simple-DataTables
        // https://github.com/fiduswriter/Simple-DataTables/wiki

        const datatablesToInitialize = document.querySelectorAll("#datatablesSimple");
        datatablesToInitialize.forEach((datatable) => {
            new simpleDatatables.DataTable(datatable);
        });
      });
    </script>
     <?php if(isset($conn)) { $conn->close(); } ?>
  </body>

</html>