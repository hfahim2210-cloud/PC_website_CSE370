<?php
session_start();
// Database Connection
require 'DBconnect.php';

// 1. Check Login
if (!isset($_SESSION['users_id'])) { // Changed to 'users_id' to match your Cart code
    header("Location: signin.php"); 
    exit();
}
$user_id = $_SESSION['users_id'];

// 2. Fetch User Details
$user_sql = "SELECT name, email, address FROM Users WHERE users_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Fetch Cart Items
$cart_id = 0;
$cart_items = [];
$subtotal = 0;

// Get active cart
$c_sql = "SELECT cart_id FROM Cart WHERE users_id = ? ORDER BY cart_id DESC LIMIT 1";
$c_stmt = $conn->prepare($c_sql);
$c_stmt->bind_param("i", $user_id);
$c_stmt->execute();
$c_res = $c_stmt->get_result();

// 3. Fetch Cart Items
$cart_id = 0;
$cart_items = [];
$subtotal = 0;

// --- FIXED QUERY ---
// We join Cart with Cart_Item to ensure we only pick a cart that actually contains products.
$c_sql = "SELECT c.cart_id 
          FROM Cart c
          INNER JOIN Cart_Item ci ON c.cart_id = ci.cart_id
          WHERE c.users_id = ? 
          ORDER BY c.cart_id DESC 
          LIMIT 1";

$c_stmt = $conn->prepare($c_sql);
$c_stmt->bind_param("i", $user_id);
$c_stmt->execute();
$c_res = $c_stmt->get_result();

if ($c_res->num_rows > 0) {
    $cart_id = $c_res->fetch_assoc()['cart_id'];
    
    // Now fetch the item details for this valid cart
    $i_sql = "SELECT p.name, p.price, ci.quantity, ci.part_id 
              FROM Cart_Item ci 
              JOIN PC_Part p ON ci.part_id = p.part_id 
              WHERE ci.cart_id = ?";
              
    $i_stmt = $conn->prepare($i_sql);
    $i_stmt->bind_param("i", $cart_id);
    $i_stmt->execute();
    $i_res = $i_stmt->get_result();
    
    while ($row = $i_res->fetch_assoc()) {
        $cart_items[] = $row;
        $subtotal += ($row['price'] * $row['quantity']);
    }
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    
    $district = $_POST['district'];
    $city = $_POST['city'];
    $del_method = $_POST['delivery_method']; // 'pickup' or 'courier'
    $pay_method = $_POST['payment_method'];
    $final_address = "";
    
    // 1. Determine Address
    if ($del_method === 'pickup') {
        $final_address = "Store Pickup: Multiplan, Eliphant Road, Dhaka";
    } else {
        $courier_opt = $_POST['courier_address_option']; // 'current' or 'other'
        if ($courier_opt === 'current') {
            $final_address = $user['address'] . ", " . $city . ", " . $district;
        } else {
            $final_address = $_POST['custom_address'] . ", " . $city . ", " . $district;
        }
    }

    // 2. Determine Delivery Charge (Backend Calculation for safety)
    $delivery_charge = (strtolower(trim($district)) === 'dhaka') ? 80 : 150;
    if ($del_method === 'pickup') $delivery_charge = 0;
    
    $total_amount = $subtotal + $delivery_charge;

    // 3. Insert Order
    // Note: Default status is 'Pending' in DB, which suits COD.
    // If you add online payment later, you'd update status here based on $pay_method.
    
    $ins_sql = "INSERT INTO Orders (users_id, cart_id, total_amount, payment_method, delivery_address, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
    
    $ins_stmt = $conn->prepare($ins_sql);
    $ins_stmt->bind_param("iidss", $user_id, $cart_id, $total_amount, $pay_method, $final_address);
    
    if ($ins_stmt->execute()) {
        // 1. Get the ID of the Order we just created
        $new_order_id = $conn->insert_id;

        // 2. SNAPSHOT: Get items from Cart to save their price/details permanently
        $cart_sql = "SELECT ci.part_id, ci.quantity, p.price 
                     FROM Cart_Item ci
                     JOIN PC_Part p ON ci.part_id = p.part_id
                     WHERE ci.cart_id = ?";
                     
        $stmt_items = $conn->prepare($cart_sql);
        $stmt_items->bind_param("i", $cart_id);
        $stmt_items->execute();
        $cart_result = $stmt_items->get_result();

        // 3. COPY items into Order_Items table
        while ($item = $cart_result->fetch_assoc()) {
            $part = $item['part_id'];
            $qty  = $item['quantity'];
            $price = $item['price']; // The price at this exact moment

            $insert_item = "INSERT INTO Order_Items (order_id, part_id, quantity, price_at_purchase) 
                            VALUES ('$new_order_id', '$part', '$qty', '$price')";
            $conn->query($insert_item);
        }

        // 4. EMPTY the Cart (Delete items so cart is empty for next time)
        // We delete from Cart_Item, keeping the Cart ID alive (or you can create a new one as you preferred)
        $conn->query("DELETE FROM Cart_Item WHERE cart_id = '$cart_id'");

        // 5. Success Message
        echo "<script>
            alert('Order has been recorded successfully! ✅');
            window.location.href = 'index.php';
        </script>";
    } else {
        echo "<script>alert('Error placing order.');</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        /* --- STYLES MATCHING YOUR CART PAGE --- */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h2, h3 { color: #333; }
        
        /* Grid Layout for Wireframe */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* Form Elements */
        label { display: block; margin-bottom: 5px; font-weight: 500; }
        input[type="text"], select {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 4px;
            box-sizing: border-box;
        }
        
        .radio-group { margin-bottom: 10px; }
        .radio-group label { display: inline-block; margin-right: 15px; cursor: pointer; font-weight: normal; }
        
        /* Sub-options indentation */
        .sub-options { margin-left: 20px; border-left: 2px solid #28a745; padding-left: 10px; display: none; margin-bottom: 15px; }
        .hidden { display: none; }

        /* Order Summary Accordion */
        .summary-header {
            display: flex; justify-content: space-between; align-items: center;
            background: #eee; padding: 10px; cursor: pointer; border-radius: 4px; font-weight: bold;
        }
        .summary-details { display: none; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9em; color: #555; }
        
        .totals { margin-top: 20px; border-top: 2px solid #ddd; padding-top: 10px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .grand-total { font-size: 1.2em; font-weight: bold; color: #28a745; }

        /* Buttons */
        .btn-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .btn { padding: 12px 25px; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; }
        .btn-back { background: #555; color: white; }
        .btn-place { background: #28a745; color: white; }
        .btn:hover { opacity: 0.9; }

    </style>
</head>
<body>

<div class="container">
    <h2>Checkout</h2>
    <form id="checkoutForm" method="POST" onsubmit="return validateForm()">
        
        <div class="checkout-grid">
            
            <div class="box">
                <h3>1. Account Details</h3>
                <label>Name</label>
                <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" readonly style="background: #f9f9f9;">
                
                <label>Email</label>
                <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: #f9f9f9;">
                
                <label>Saved Address (Database)</label>
                <input type="text" value="<?php echo htmlspecialchars($user['address']); ?>" readonly style="background: #f9f9f9;">
            </div>

            <div class="box">
                <h3>3. Payment Method</h3>
                <div class="radio-group">
                    <label><input type="radio" name="payment_method" value="Debit Card"> Debit Card</label><br>
                    <label><input type="radio" name="payment_method" value="Internet Banking"> Internet Banking</label><br>
                    <label><input type="radio" name="payment_method" value="Mobile Banking"> Mobile Banking</label><br>
                    <label><input type="radio" name="payment_method" value="COD" checked> Cash On Delivery (COD)</label>
                </div>
            </div>

            <div class="box">
                <h3>2. Delivery</h3>
                
                <label>District *</label>
                <input type="text" name="district" id="district" placeholder="e.g., Dhaka" required>
                
                <label>City *</label>
                <input type="text" name="city" id="city" placeholder="e.g., Mirpur" required>

                <hr style="border-color: #eee;">
                <label>Delivery Mode:</label>
                <div class="radio-group">
                    <label><input type="radio" name="delivery_method" value="pickup" onclick="toggleDelivery('pickup')"> Store Pickup</label>
                    <label><input type="radio" name="delivery_method" value="courier" onclick="toggleDelivery('courier')" checked> Courier</label>
                </div>

                <div id="pickup-info" class="hidden" style="color: #28a745; margin-bottom:15px; font-weight:bold;">
                    📍 Multiplan, Eliphant Road, Dhaka
                </div>

                <div id="courier-options" class="sub-options" style="display: block;">
                    <label>Where to deliver?</label><br>
                    <label><input type="radio" name="courier_address_option" value="current" checked onclick="toggleAddressInput(false)"> Current Address (Above)</label><br>
                    <label><input type="radio" name="courier_address_option" value="other" onclick="toggleAddressInput(true)"> Other Address</label>
                    
                    <input type="text" name="custom_address" id="custom_address" placeholder="Enter full address here..." class="hidden" style="margin-top: 10px;">
                </div>
            </div>

            <div class="box">
                <h3>4. Complete Order</h3>
                
                <div class="summary-header" onclick="toggleSummary()">
                    <span>Order Summary</span>
                    <span id="summ-icon">+</span>
                </div>
                
                <div id="summary-content" class="summary-details">
                    <?php foreach($cart_items as $item): ?>
                    <div class="item-row">
                        <span><?php echo $item['name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                        <span>Tk<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>Tk<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Discount</span>
                        <span>Tk0.00</span>
                    </div>
                    <div class="total-row">
                        <span>Delivery Charge</span>
                        <span id="delivery-cost">Tk150.00</span>
                    </div>
                    <hr style="border-color: #ddd; width: 100%;">
                    <div class="total-row grand-total">
                        <span>Total</span>
                        <span id="final-total">Tk<?php echo number_format($subtotal + 150, 2); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-back" onclick="window.location.href='cart.php'">Back</button>
            <button type="submit" name="place_order" class="btn btn-place">Place Order</button>
        </div>

    </form>
</div>

<script>
    // Variables from PHP
    const subtotal = <?php echo $subtotal; ?>;
    
    // Elements
    const pickupInfo = document.getElementById('pickup-info');
    const courierOpts = document.getElementById('courier-options');
    const customAddrInput = document.getElementById('custom_address');
    const districtInput = document.getElementById('district');
    const deliveryCostEl = document.getElementById('delivery-cost');
    const finalTotalEl = document.getElementById('final-total');

    // 1. Toggle Delivery Mode
    function toggleDelivery(mode) {
        if (mode === 'pickup') {
            pickupInfo.style.display = 'block';
            courierOpts.style.display = 'none';
            updateTotals(0); // Free pickup
        } else {
            pickupInfo.style.display = 'none';
            courierOpts.style.display = 'block';
            checkDistrictCost(); // Re-calculate based on district input
        }
    }

    // 2. Toggle Custom Address Input
    function toggleAddressInput(show) {
        if (show) {
            customAddrInput.style.display = 'block';
            customAddrInput.required = true;
        } else {
            customAddrInput.style.display = 'none';
            customAddrInput.required = false;
        }
    }

    // 3. Order Summary Accordion
    function toggleSummary() {
        const content = document.getElementById('summary-content');
        const icon = document.getElementById('summ-icon');
        if (content.style.display === 'block') {
            content.style.display = 'none';
            icon.innerText = '+';
        } else {
            content.style.display = 'block';
            icon.innerText = '-';
        }
    }

    // 4. Dynamic Delivery Charge Logic
    districtInput.addEventListener('keyup', checkDistrictCost);

    function checkDistrictCost() {
        // If pickup is selected, ignore district input for cost
        const isPickup = document.querySelector('input[name="delivery_method"]:checked').value === 'pickup';
        if (isPickup) {
            updateTotals(0);
            return;
        }

        const district = districtInput.value.trim().toLowerCase();
        let cost = 150; // Default
        
        // Check if "dhaka" is typed
        if (district === 'dhaka') {
            cost = 80;
        }
        
        updateTotals(cost);
    }

    function updateTotals(deliveryFee) {
        deliveryCostEl.innerText = 'Tk' + deliveryFee.toFixed(2);
        const total = subtotal + deliveryFee;
        finalTotalEl.innerText = 'Tk' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'); // Format currency
    }

    // 5. Form Validation
    function validateForm() {
        const district = document.getElementById('district').value;
        const city = document.getElementById('city').value;
        
        if (district === "" || city === "") {
            alert("Please fill out all the information (District and City).");
            return false;
        }
        return true; // Submit form
    }
</script>

</body>
</html>