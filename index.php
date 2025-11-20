<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include the database configuration file
include("conf.php");

$message = '';

// Handle form submission for selling an item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sellItem']) && isset($conn)) {
    $itemId = $_POST['sellItemId'];
    $quantity = $_POST['sellQuantity'];
    $soldDate = date("Y-m-d");

    // Get price and current stock from items table
    $stmt = $conn->prepare("SELECT price, stocks FROM items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($item = $result->fetch_assoc()) {
        if ($quantity <= $item['stocks']) {
            $totalPrice = $item['price'] * $quantity;
            
            // Use a transaction to ensure data consistency
            $conn->begin_transaction();
            
            // Insert into sales
            $insertStmt = $conn->prepare("INSERT INTO sales (sold_date, item_id, quantity, total_price) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("siid", $soldDate, $itemId, $quantity, $totalPrice);
            
            // Update stocks
            $newStock = $item['stocks'] - $quantity;
            $updateStmt = $conn->prepare("UPDATE items SET stocks = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $newStock, $itemId);

            if ($insertStmt->execute() && $updateStmt->execute()) {
                $conn->commit();
                $message = '<div class="alert alert-success">Sale recorded and stock updated successfully!</div>';
            } else {
                $conn->rollback();
                $message = '<div class="alert alert-danger">Error recording sale.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Not enough stock. Available: ' . $item['stocks'] . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Item not found.</div>';
    }
}


// Handle form submission for adding a new food item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addFood']) && isset($conn)) {
    $foodName = $_POST['foodName'];
    $price = $_POST['price'];
    $category = $_POST['category'];

    if (!empty($foodName) && !empty($price) && !empty($category)) {
        $stmt = $conn->prepare("INSERT INTO food (food_name, price, category) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $foodName, $price, $category);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">New food item added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">All fields are required for food items.</div>';
    }
}

// Handle form submission for editing a food item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editFood']) && isset($conn)) {
    $id = $_POST['editFoodId'];
    $foodName = $_POST['editFoodName'];
    $price = $_POST['editPrice'];
    $category = $_POST['editCategory'];

    if (!empty($id) && !empty($foodName) && !empty($price)) {
        $stmt = $conn->prepare("UPDATE food SET food_name = ?, price = ?, category = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $foodName, $price, $category, $id);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Food item updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">All fields are required for updating food items.</div>';
    }
}

// Handle form submission for deleting a food item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteFood']) && isset($conn)) {
    $id = $_POST['deleteFoodId'];

    if (!empty($id)) {
        $stmt = $conn->prepare("DELETE FROM food WHERE food_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Food item deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Invalid food item ID.</div>';
    }
}

// Handle form submission for selling a food item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sellFood']) && isset($conn)) {
    $foodId = $_POST['sellFoodId'];
    $quantity = $_POST['sellQuantity'];
    $soldDate = date("Y-m-d"); // Use current date for sold_date

    // Get price from food table
    $stmt = $conn->prepare("SELECT price FROM food WHERE id = ?");
    $stmt->bind_param("i", $foodId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($food = $result->fetch_assoc()) {
        $totalPrice = $food['price'] * $quantity;
        $insertStmt = $conn->prepare("INSERT INTO sales (sold_date, food_id, quantity, total_price) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("siid", $soldDate, $foodId, $quantity, $totalPrice);
        if ($insertStmt->execute()) {
            $message = '<div class="alert alert-success">Sale recorded successfully!</div>';
        }
    }
}

// Handle form submission for adding a new inventory item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addItem']) && isset($conn)) {
    $itemName = $_POST['itemName'];
    $location = $_POST['location'];
    $itemPrice = $_POST['itemPrice'];
    $stocks = $_POST['stocks'];
    $issuedDate = $_POST['issuedDate'];

    if (!empty($itemName) && !empty($itemPrice) && !empty($stocks)) {
        $stmt = $conn->prepare("INSERT INTO items (item_name, location, price, stocks, issued_date) VALUES (?, ?, ?, ?, ?)");
        // Set issuedDate to null if it's empty
        $issuedDate = !empty($issuedDate) ? $issuedDate : null;
        $stmt->bind_param("ssdis", $itemName, $location, $itemPrice, $stocks, $issuedDate);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">New inventory item added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Item Name, Price, and Stocks are required for inventory items.</div>';
    }
}

// Handle form submission for editing an inventory item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editItem']) && isset($conn)) {
    $itemId = $_POST['editItemId'];
    $itemName = $_POST['editItemName'];
    $location = $_POST['editLocation'];
    $itemPrice = $_POST['editItemPrice'];
    $stocks = $_POST['editStocks'];
    $issuedDate = !empty($_POST['editIssuedDate']) ? $_POST['editIssuedDate'] : null;

    if (!empty($itemId) && !empty($itemName) && !empty($itemPrice) && !empty($stocks)) {
        $stmt = $conn->prepare("UPDATE items SET item_name = ?, location = ?, price = ?, stocks = ?, issued_date = ? WHERE id = ?");
        $stmt->bind_param("ssdisi", $itemName, $location, $itemPrice, $stocks, $issuedDate, $itemId);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Inventory item updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Item Name, Price, and Stocks are required for updating inventory items.</div>';
    }
}

// Handle form submission for deleting an inventory item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteItem']) && isset($conn)) {
    $itemId = $_POST['deleteItemId'];

    if (!empty($itemId)) {
        $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Inventory item deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Invalid item ID.</div>';
    }
}

// Fetch food inventory data
$food_inventory = [];
if (isset($conn)) {
    $sql = "SELECT food_id, food_name, price, category FROM food ORDER BY food_name ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $food_inventory[] = $row;
        }
    }
}

// Fetch item inventory data
$item_inventory = [];
if (isset($conn)) {
    $sql = "SELECT item_id, item_name, location, price, stocks, issued_date FROM items ORDER BY item_name ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $item_inventory[] = $row;
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
    <title>Inventory Dashboard</title>
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
              <a class="nav-link active" href="index.php">
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
            <h1 class="mt-4">Dashboard</h1>
            <ol class="breadcrumb mb-4">
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
            <?php echo $message; ?>
            <div class="card mb-4">
              <div
                class="card-header d-flex justify-content-between align-items-center"
              >
                <div>
                  <i class="fas fa-utensils me-1"></i>
                  Food Inventory
                </div>
                <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#addFoodModal">
                  <i class="fas fa-plus"></i> Add Food
                </button>
              </div>
              <div class="card-body">
                <table id="datatablesSimple">
                  <thead>
                    <tr>
                      <th>Food Name</th>
                      <th>Price</th>
                      <th>Category</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tfoot>
                    <tr>
                      <th>Food Name</th>
                      <th>Price</th>
                      <th>Category</th>
                      <th>Actions</th>
                    </tr>
                  </tfoot>
                  <tbody>
                    <!-- Food inventory data will be populated here -->
                    <?php if (!empty($food_inventory)): ?>
                        <?php foreach ($food_inventory as $food): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($food['food_name']); ?></td>
                            <td><?php echo htmlspecialchars($food['price']); ?></td>
                            <td><?php echo htmlspecialchars($food['category']); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm sell-food-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#sellModal"
                                        data-id="<?php echo $food['food_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($food['food_name']); ?>"
                                        data-type="food">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                                <button class="btn btn-warning btn-sm edit-food-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editFoodModal"
                                        data-id="<?php echo $food['food_id']; ?>"
                                        data-food-name="<?php echo htmlspecialchars($food['food_name']); ?>"
                                        data-price="<?php echo htmlspecialchars($food['price']); ?>"
                                        data-category="<?php echo htmlspecialchars($food['category']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm delete-food-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteFoodModal"
                                        data-id="<?php echo $food['food_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                   </tbody>
                </table>
              </div>
            </div>
            <div class="card mb-4">
              <div
                class="card-header d-flex justify-content-between align-items-center"
              >
                <div>
                  <i class="fas fa-box-open me-1"></i>
                  Item Inventory
                </div>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#addItemModal">
                  <i class="fas fa-plus"></i> Add Item
                </button>
              </div>
              <div class="card-body">
                <table id="datatablesItems">
                  <thead>
                    <tr>
                      <th>Item Name</th>
                      <th>Location</th>
                      <th>Price</th>
                      <th>Stocks</th>
                      <th>Issued Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tfoot>
                    <tr>
                      <th>Item Name</th>
                      <th>Location</th>
                      <th>Price</th>
                      <th>Stocks</th>
                      <th>Issued Date</th>
                      <th>Actions</th>
                    </tr>
                  </tfoot>
                  <tbody>
                    <!-- Item inventory data will be populated here -->
                    <?php if (!empty($item_inventory)): ?>
                        <?php foreach ($item_inventory as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                            <td><?php echo htmlspecialchars($item['price']); ?></td>
                            <td><?php echo htmlspecialchars($item['stocks']); ?></td>
                            <td><?php echo htmlspecialchars($item['issued_date']); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm sell-item-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#sellModal"
                                        data-id="<?php echo $item['item_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                        data-type="item">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                                <button class="btn btn-warning btn-sm edit-item-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editItemModal"
                                        data-id="<?php echo $item['item_id']; ?>"
                                        data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                        data-location="<?php echo htmlspecialchars($item['location']); ?>"
                                        data-price="<?php echo htmlspecialchars($item['price']); ?>"
                                        data-stocks="<?php echo htmlspecialchars($item['stocks']); ?>"
                                        data-issued-date="<?php echo htmlspecialchars($item['issued_date']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm delete-item-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteItemModal"
                                        data-id="<?php echo $item['item_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

    <!-- Add Food Modal -->
    <div class="modal fade" id="addFoodModal" tabindex="-1" aria-labelledby="addFoodModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addFoodModalLabel">Add New Food Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="index.php">
              <div class="mb-3">
                <label for="foodName" class="form-label">Food Name</label>
                <input type="text" class="form-control" id="foodName" name="foodName" required>
              </div>
              <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
              </div>
              <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" id="category" name="category" required>
              </div>
              <button type="submit" name="addFood" class="btn btn-primary">Save Food</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addItemModalLabel">Add New Inventory Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="index.php">
              <div class="mb-3">
                <label for="itemName" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="itemName" name="itemName" required>
              </div>
              <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location">
              </div>
              <div class="mb-3">
                <label for="itemPrice" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" id="itemPrice" name="itemPrice" required>
              </div>
              <div class="mb-3">
                <label for="stocks" class="form-label">Stocks</label>
                <input type="number" class="form-control" id="stocks" name="stocks" required>
              </div>
              <div class="mb-3">
                <label for="issuedDate" class="form-label">Issued Date</label>
                <input type="date" class="form-control" id="issuedDate" name="issuedDate">
              </div>
              <button type="submit" name="addItem" class="btn btn-primary">Save Item</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Sell Modal (for both Food and Items) -->
    <div class="modal fade" id="sellModal" tabindex="-1" aria-labelledby="sellModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="sellModalLabel">Sell Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="index.php" id="sellForm">
              <input type="hidden" id="sellItemId" name="sellItemId">
              <input type="hidden" id="sellFoodId" name="sellFoodId">
              <h5 id="sellItemName"></h5>
              <div class="mb-3">
                <label for="sellQuantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="sellQuantity" name="sellQuantity" required min="1" value="1">
              </div>
              <button type="submit" id="sellSubmitButton" name="sellItem" class="btn btn-primary">Record Sale</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Food Modal -->
    <div class="modal fade" id="editFoodModal" tabindex="-1" aria-labelledby="editFoodModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editFoodModalLabel">Edit Food Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="index.php">
              <input type="hidden" id="editFoodId" name="editFoodId">
              <div class="mb-3">
                <label for="editFoodName" class="form-label">Food Name</label>
                <input type="text" class="form-control" id="editFoodName" name="editFoodName" required>
              </div>
              <div class="mb-3">
                <label for="editPrice" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" id="editPrice" name="editPrice" required>
              </div>
              <div class="mb-3">
                <label for="editCategory" class="form-label">Category</label>
                <input type="text" class="form-control" id="editCategory" name="editCategory" required>
              </div>
              <button type="submit" name="editFood" class="btn btn-primary">Update Food</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Food Modal -->
    <div class="modal fade" id="deleteFoodModal" tabindex="-1" aria-labelledby="deleteFoodModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFoodModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this food item? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="deleteFoodId" id="deleteFoodId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deleteFood" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editItemModalLabel">Edit Inventory Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="index.php">
              <input type="hidden" id="editItemId" name="editItemId">
              <div class="mb-3">
                <label for="editItemName" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="editItemName" name="editItemName" required>
              </div>
              <div class="mb-3">
                <label for="editLocation" class="form-label">Location</label>
                <input type="text" class="form-control" id="editLocation" name="editLocation">
              </div>
              <div class="mb-3">
                <label for="editItemPrice" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" id="editItemPrice" name="editItemPrice" required>
              </div>
              <div class="mb-3">
                <label for="editStocks" class="form-label">Stocks</label>
                <input type="number" class="form-control" id="editStocks" name="editStocks" required>
              </div>
              <div class="mb-3">
                <label for="editIssuedDate" class="form-label">Issued Date</label>
                <input type="date" class="form-control" id="editIssuedDate" name="editIssuedDate">
              </div>
              <button type="submit" name="editItem" class="btn btn-primary">Update Item</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Item Modal -->
    <div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteItemModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this inventory item? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="deleteItemId" id="deleteItemId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deleteItem" class="btn btn-danger">Delete</button>
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
    <script>
      window.addEventListener("DOMContentLoaded", (event) => {
        const datatablesToInitialize = document.querySelectorAll("#datatablesSimple, #datatablesItems");
        datatablesToInitialize.forEach((datatable) => {
          if (datatable) {
            new simpleDatatables.DataTable(datatable);
          }
        });

        // Script to handle populating the universal sell modal
        const sellModal = document.getElementById('sellModal');
        if (sellModal) {
            sellModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const itemId = button.getAttribute('data-id');
                const itemName = button.getAttribute('data-name');
                const itemType = button.getAttribute('data-type');
                
                document.getElementById('sellItemName').textContent = itemName;
                const form = document.getElementById('sellForm');
                const submitButton = document.getElementById('sellSubmitButton');

                document.getElementById('sellFoodId').value = (itemType === 'food') ? itemId : '';
                document.getElementById('sellItemId').value = (itemType === 'item') ? itemId : '';
                submitButton.name = (itemType === 'food') ? 'sellFood' : 'sellItem';
            });
        }

        // Script to handle populating the edit food modal
        const editFoodModal = document.getElementById('editFoodModal');
        if (editFoodModal) {
            editFoodModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('editFoodId').value = button.getAttribute('data-id');
                document.getElementById('editFoodName').value = button.getAttribute('data-food-name');
                document.getElementById('editPrice').value = button.getAttribute('data-price');
                document.getElementById('editCategory').value = button.getAttribute('data-category');
            });
        }

        // Script to handle populating the delete food modal
        const deleteFoodModal = document.getElementById('deleteFoodModal');
        if (deleteFoodModal) {
            deleteFoodModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('deleteFoodId').value = button.getAttribute('data-id');
            });
        }

        // Script to handle populating the item edit modal
        const editItemModal = document.getElementById('editItemModal');
        editItemModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editItemId').value = button.getAttribute('data-id');
            document.getElementById('editItemName').value = button.getAttribute('data-item-name');
            document.getElementById('editLocation').value = button.getAttribute('data-location');
            document.getElementById('editItemPrice').value = button.getAttribute('data-price');
            document.getElementById('editStocks').value = button.getAttribute('data-stocks');
            document.getElementById('editIssuedDate').value = button.getAttribute('data-issued-date');
        });

        // Script to handle populating the item delete modal
        const deleteItemModal = document.getElementById('deleteItemModal');
        deleteItemModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('deleteItemId').value = button.getAttribute('data-id');
        });

      });
    </script>
    <?php if(isset($conn)) { $conn->close(); } ?>
  </body>
</html>
