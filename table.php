<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// This file handles database creation, connection, and table creation.
include("conf.php");

$message = '';

// Handle form submission for adding a new user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addUser']) && isset($conn)) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $role = $_POST['role'];

    // Basic validation
    if (!empty($firstName) && !empty($lastName) && !empty($role)) {
        // The form only provides first name, last name, and role.
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $firstName, $lastName, $role);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">New user added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();

    } else {
        $message = '<div class="alert alert-warning">All fields are required.</div>';
    }
}

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteUser']) && isset($conn)) {
    $id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">User deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting user: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Handle user update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editUser']) && isset($conn)) {
    $id = $_POST['edit_id'];
    $firstName = $_POST['editFirstName'];
    $lastName = $_POST['editLastName'];
    $role = $_POST['editRole'];

    if (!empty($id) && !empty($firstName) && !empty($lastName) && !empty($role)) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $firstName, $lastName, $role, $id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">User updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating user: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">All fields are required for an update.</div>';
    }
}

// Fetch all users from the database
$users = [];
$sql = "SELECT id, first_name, last_name, role FROM users WHERE username NOT IN ('admin', 'user') OR username IS NULL";
$result = $conn->query($sql); // This is line 47

if ($result) { // This check prevents errors if the query fails
    if ($result->num_rows > 0) { // This is now line 49
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}
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
    <title>Profile - SB Admin</title>
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
        <div class="input-group">
          <input
            class="form-control"
            type="text"
            placeholder="Search for..."
            aria-label="Search for..."
            aria-describedby="btnNavbarSearch"
          />
          <button class="btn btn-light" id="btnNavbarSearch" type="button">
            <i class="fas fa-search"></i>
          </button>
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
              <a class="nav-link active" href="table.php">
                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                Profile
              </a>
              <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'Admin'): ?>
              <a class="nav-link" href="charts.php">
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
            <h1 class="mt-4">Profile</h1>
            <ol class="breadcrumb mb-4">
              <li class="breadcrumb-item">
                <a href="index.php">Dashboard</a>
              </li>
              <li class="breadcrumb-item active">Profile</li>
            </ol>
            <?php echo $message; ?>
            <div class="card mb-4">
              <div
                class="card-header d-flex justify-content-between align-items-center"
              >
                <div>
                  <i class="fas fa-table me-1"></i>
                  Profile Table
                </div>
                <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">
                  Add New User
                </button>
              </div>
              <div class="card-body">
                <table id="datatablesSimple">
                  <thead>
                    <tr>
                      <th>FirstName</th>
                      <th>LastName</th>
                      <th>Role</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                      <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                      <td><?php echo htmlspecialchars($user['role']); ?></td>
                      <td>
                          <button class="btn btn-warning btn-sm edit-btn" 
                                  data-bs-toggle="modal" 
                                  data-bs-target="#editUserModal"
                                  data-id="<?php echo $user['id']; ?>"
                                  data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                  data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                  data-role="<?php echo htmlspecialchars($user['role']); ?>">
                              <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-danger btn-sm delete-btn" 
                                  data-bs-toggle="modal" 
                                  data-bs-target="#deleteUserModal"
                                  data-id="<?php echo $user['id']; ?>">
                              <i class="fas fa-trash"></i>
                          </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </main>
        <footer class="py-4 bg-light mt-auto">
          <div class="container-fluid px-4">
            <div
              class="d-flex align-items-center justify-content-between small"
            ></div>
          </div>
        </footer>
      </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="table.php">
              <div class="mb-3">
                <label for="firstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="firstName" name="firstName" required>
              </div>
              <div class="mb-3">
                <label for="lastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lastName" name="lastName" required>
              </div>
              <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <input type="text" class="form-control" id="role" name="role" required>
              </div>
              <button type="submit" name="addUser" class="btn btn-primary">Save User</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="table.php">
              <input type="hidden" name="edit_id" id="edit_id">
              <div class="mb-3">
                <label for="editFirstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="editFirstName" name="editFirstName" required>
              </div>
              <div class="mb-3">
                <label for="editLastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="editLastName" name="editLastName" required>
              </div>
              <div class="mb-3">
                <label for="editRole" class="form-label">Role</label>
                <input type="text" class="form-control" id="editRole" name="editRole" required>
              </div>
              <button type="submit" name="editUser" class="btn btn-primary">Save Changes</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this user? This action cannot be undone.
          </div>
          <div class="modal-footer">
            <form method="POST" action="table.php">
              <input type="hidden" name="delete_id" id="delete_id">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="deleteUser" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
            </form>
          </div>
        </div>
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
    <script src="js/datatables-simple-demo.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editUserModal = document.getElementById('editUserModal');
            editUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const firstName = button.getAttribute('data-first-name');
                const lastName = button.getAttribute('data-last-name');
                const role = button.getAttribute('data-role');

                const modalBodyInputId = editUserModal.querySelector('#edit_id');
                const modalBodyInputFirstName = editUserModal.querySelector('#editFirstName');
                const modalBodyInputLastName = editUserModal.querySelector('#editLastName');
                const modalBodyInputRole = editUserModal.querySelector('#editRole');

                modalBodyInputId.value = id;
                modalBodyInputFirstName.value = firstName;
                modalBodyInputLastName.value = lastName;
                modalBodyInputRole.value = role;
            });

            const deleteUserModal = document.getElementById('deleteUserModal');
            deleteUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const modalInputId = deleteUserModal.querySelector('#delete_id');
                modalInputId.value = id;
            });
        });
    </script>
    <?php if(isset($conn)) { $conn->close(); } ?>
  </body>
</html>