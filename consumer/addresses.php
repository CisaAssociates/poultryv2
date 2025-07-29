<?php
require_once '../config.php';

// Check if user is logged in and is a consumer
if (!is_logged_in() || $user['role_id'] != 4) {
    redirect('auth.error.403');
}

$mysqli = db_connect();

// Function to refresh addresses list (to avoid code duplication)
function refreshAddresses($mysqli, $user_id) {
    $stmt = $mysqli->prepare("SELECT * FROM consumer_addresses WHERE user_id = ? ORDER BY is_default DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    return $addresses;
}

// Get user's saved addresses
$addresses = refreshAddresses($mysqli, $_SESSION['id']);

// Process form submissions
$success_message = '';
$error_message = '';

// Add new address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['label']) && !isset($_POST['update_address'])) {
    if (!verify_token($_POST['token'])) {
        $error_message = 'Invalid token. Please try again.';
    } else {
        $label = trim($_POST['label'] ?? '');
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $street_address = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $contact_number = trim($_POST['phone'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        // If this is set as default OR it's the first address, make it default
        if ($is_default || count($addresses) === 0) {
            $stmt = $mysqli->prepare("UPDATE consumer_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['id']);
            $stmt->execute();
            $is_default = 1;
        }

        // Validate inputs
        if (empty($label) || empty($recipient_name) || empty($street_address) || empty($barangay) || empty($city) || empty($province) || empty($zip_code) || empty($contact_number)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            // Insert new address
            $stmt = $mysqli->prepare("INSERT INTO consumer_addresses 
                         (user_id, label, recipient_name, street_address, barangay, city, province, zip_code, contact_number, is_default) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssi", $_SESSION['id'], $label, $recipient_name, $street_address, $barangay, $city, $province, $zip_code, $contact_number, $is_default);

            if ($stmt->execute()) {
                $success_message = 'New address added successfully.';
                // Refresh addresses list
                $addresses = refreshAddresses($mysqli, $_SESSION['id']);
            } else {
                $error_message = 'Failed to add address. Please try again.';
            }
        }
    }
}

// Update existing address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    if (!verify_token($_POST['token'])) {
        $error_message = 'Invalid token. Please try again.';
    } else {
        $address_id = (int)$_POST['address_id'];
        $label = trim($_POST['label'] ?? '');
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $street_address = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $zip_code = trim($_POST['zip_code'] ?? '');
        $contact_number = trim($_POST['phone'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        // Validate inputs first
        if (empty($label) || empty($recipient_name) || empty($street_address) || empty($barangay) || empty($city) || empty($province) || empty($zip_code) || empty($contact_number)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            // Verify that the address belongs to the current user
            $stmt = $mysqli->prepare("SELECT address_id FROM consumer_addresses WHERE address_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $_SESSION['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error_message = 'Address not found or access denied.';
            } else {
                // If setting as default, clear other defaults first
                if ($is_default) {
                    $stmt = $mysqli->prepare("UPDATE consumer_addresses SET is_default = 0 WHERE user_id = ? AND address_id != ?");
                    $stmt->bind_param("ii", $_SESSION['id'], $address_id);
                    $stmt->execute();
                }

                // Update the address
                $stmt = $mysqli->prepare("UPDATE consumer_addresses 
                             SET label = ?, recipient_name = ?, street_address = ?, barangay = ?, city = ?, province = ?, 
                                 zip_code = ?, contact_number = ?, is_default = ? 
                             WHERE address_id = ? AND user_id = ?");
                $stmt->bind_param("ssssssssiii", $label, $recipient_name, $street_address, $barangay, $city, $province, $zip_code, $contact_number, $is_default, $address_id, $_SESSION['id']);

                if ($stmt->execute()) {
                    $success_message = 'Address updated successfully.';
                    // Refresh addresses list
                    $addresses = refreshAddresses($mysqli, $_SESSION['id']);
                } else {
                    $error_message = 'Failed to update address. Please try again.';
                }
            }
        }
    }
}

// Set address as default (from dropdown)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    if (!verify_token($_POST['token'])) {
        $error_message = 'Invalid token. Please try again.';
    } else {
        $address_id = (int)$_POST['address_id'];
        
        // Verify that the address belongs to the current user
        $stmt = $mysqli->prepare("SELECT address_id FROM consumer_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = 'Address not found or access denied.';
        } else {
            // Clear all defaults first
            $stmt = $mysqli->prepare("UPDATE consumer_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['id']);
            $stmt->execute();
            
            // Set this address as default
            $stmt = $mysqli->prepare("UPDATE consumer_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $_SESSION['id']);
            
            if ($stmt->execute()) {
                $success_message = 'Default address updated successfully.';
                // Refresh addresses list
                $addresses = refreshAddresses($mysqli, $_SESSION['id']);
            } else {
                $error_message = 'Failed to update default address. Please try again.';
            }
        }
    }
}

// Delete address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    if (!verify_token($_POST['token'])) {
        $error_message = 'Invalid token. Please try again.';
    } else {
        $address_id = (int)$_POST['address_id'];

        // Check if this address exists and belongs to user
        $stmt = $mysqli->prepare("SELECT is_default FROM consumer_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = 'Address not found or access denied.';
        } else {
            $address_data = $result->fetch_assoc();
            $is_default = $address_data['is_default'];
            
            // Don't allow deletion of default address if there are other addresses
            if ($is_default && count($addresses) > 1) {
                $error_message = 'Cannot delete default address. Please set another address as default first.';
            } else {
                // Delete address
                $stmt = $mysqli->prepare("DELETE FROM consumer_addresses WHERE address_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $address_id, $_SESSION['id']);

                if ($stmt->execute()) {
                    $success_message = 'Address deleted successfully.';
                    // Refresh addresses list
                    $addresses = refreshAddresses($mysqli, $_SESSION['id']);
                } else {
                    $error_message = 'Failed to delete address. Please try again.';
                }
            }
        }
    }
}

$title = "Manage Addresses";
$sub_title = "My Account";

ob_start();
?>

<!-- Page Heading -->
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-address-modal">
        <i class="fas fa-plus-circle mr-1"></i> Add New Address
    </button>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <i class="fas fa-check-circle mr-1"></i> <?= $success_message ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <i class="fas fa-exclamation-circle mr-1"></i> <?= $error_message ?>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($addresses)): ?>
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-body text-center py-5">
                    <i class="fas fa-map-marker-alt fa-4x text-gray-300 mb-3"></i>
                    <h4 class="text-gray-800 mb-3">No Addresses Found</h4>
                    <p class="text-gray-600 mb-4">You haven't added any delivery addresses yet. Add your first address to enable delivery.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-address-modal">
                        <i class="fas fa-plus-circle mr-1"></i> Add New Address
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($addresses as $address): ?>
            <div class="col-lg-6">
                <div class="card shadow mb-4 <?= $address['is_default'] ? 'border-left-primary' : '' ?>">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= $address['is_default'] ? '<i class="fas fa-star text-warning mr-1"></i> Default Address' : 'Delivery Address' ?>
                        </h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editAddressModal<?= $address['address_id'] ?>">
                                    <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i> Edit
                                </a>
                                <?php if (!$address['is_default'] || count($addresses) == 1): ?>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deleteAddressModal<?= $address['address_id'] ?>">
                                        <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i> Delete
                                    </a>
                                <?php endif; ?>
                                <?php if (!$address['is_default']): ?>
                                    <div class="dropdown-divider"></div>
                                    <form method="post" action="">
                                        <?= csrf_token() ?>
                                        <input type="hidden" name="set_default" value="1">
                                        <input type="hidden" name="address_id" value="<?= $address['address_id'] ?>">
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-star fa-sm fa-fw mr-2 text-warning"></i> Set as Default
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <address>
                            <strong><?= special_chars($address['label']) ?>: <?= special_chars($address['recipient_name']) ?></strong><br>
                            <?= special_chars($address['street_address']) ?><br>
                            <?= special_chars($address['barangay']) ?><br>
                            <?= special_chars($address['city']) ?>, <?= special_chars($address['province']) ?> <?= special_chars($address['zip_code']) ?><br>
                            <abbr title="Phone">P:</abbr> <?= special_chars($address['contact_number']) ?>
                        </address>
                    </div>
                </div>
            </div>

            <!-- Edit Address Modal -->
            <div class="modal fade" id="editAddressModal<?= $address['address_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editAddressModalLabel<?= $address['address_id'] ?>" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editAddressModalLabel<?= $address['address_id'] ?>">Edit Address</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="">
                            <div class="modal-body">
                                <?= csrf_token() ?>
                                <input type="hidden" name="update_address" value="1">
                                <input type="hidden" name="address_id" value="<?= $address['address_id'] ?>">

                                <div class="form-group mb-3">
                                    <label for="label_<?= $address['address_id'] ?>">Label *</label>
                                    <input type="text" class="form-control" id="label_<?= $address['address_id'] ?>" name="label" value="<?= special_chars($address['label']) ?>" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="recipient_name_<?= $address['address_id'] ?>">Recipient Name *</label>
                                    <input type="text" class="form-control" id="recipient_name_<?= $address['address_id'] ?>" name="recipient_name" value="<?= special_chars($address['recipient_name']) ?>" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="street_address_<?= $address['address_id'] ?>">Street Address *</label>
                                    <input type="text" class="form-control" id="street_address_<?= $address['address_id'] ?>" name="street_address" value="<?= special_chars($address['street_address']) ?>" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="barangay_<?= $address['address_id'] ?>">Barangay *</label>
                                    <input type="text" class="form-control" id="barangay_<?= $address['address_id'] ?>" name="barangay" value="<?= special_chars($address['barangay']) ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="city_<?= $address['address_id'] ?>">City *</label>
                                        <input type="text" class="form-control" id="city_<?= $address['address_id'] ?>" name="city" value="<?= special_chars($address['city']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="province_<?= $address['address_id'] ?>">Province *</label>
                                        <input type="text" class="form-control" id="province_<?= $address['address_id'] ?>" name="province" value="<?= special_chars($address['province']) ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="zip_code_<?= $address['address_id'] ?>">Postal Code *</label>
                                        <input type="text" class="form-control" id="zip_code_<?= $address['address_id'] ?>" name="zip_code" value="<?= special_chars($address['zip_code']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone_<?= $address['address_id'] ?>">Phone Number *</label>
                                        <input type="text" class="form-control" id="phone_<?= $address['address_id'] ?>" name="phone" value="<?= special_chars($address['contact_number']) ?>" required>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_default_<?= $address['address_id'] ?>" name="is_default" <?= $address['is_default'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_default_<?= $address['address_id'] ?>">Set as default address</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Address Modal -->
            <div class="modal fade" id="deleteAddressModal<?= $address['address_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="deleteAddressModalLabel<?= $address['address_id'] ?>" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteAddressModalLabel<?= $address['address_id'] ?>">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this address?</p>
                            <address>
                                <strong><?= special_chars($address['label']) ?>: <?= special_chars($address['recipient_name']) ?></strong><br>
                                <?= special_chars($address['street_address']) ?><br>
                                <?= special_chars($address['barangay']) ?><br>
                                <?= special_chars($address['city']) ?>, <?= special_chars($address['province']) ?> <?= special_chars($address['zip_code']) ?>
                            </address>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post" action="" style="display: inline;">
                                <?= csrf_token() ?>
                                <input type="hidden" name="delete_address" value="1">
                                <input type="hidden" name="address_id" value="<?= $address['address_id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include layouts('consumer.main');
?>

<!-- Add Address Modal -->
<div id="add-address-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="addAddressModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="addAddressModalLabel">Add New Address</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <?= csrf_token() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="label" class="form-label">Address Label *</label>
                            <input type="text" class="form-control" id="label" name="label" placeholder="e.g., Home, Work" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="recipient_name" class="form-label">Recipient Name *</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" placeholder="e.g., Juan Dela Cruz" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="street_address" class="form-label">Street Address *</label>
                            <input type="text" class="form-control" id="street_address" name="street_address" placeholder="e.g., Rizal Street" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="barangay" class="form-label">Barangay *</label>
                            <input type="text" class="form-control" id="barangay" name="barangay" placeholder="e.g., Barangay San Roque" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="e.g., Maasin City" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="province" class="form-label">Province *</label>
                            <input type="text" class="form-control" id="province" name="province" placeholder="e.g., Southern Leyte" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="zip_code" class="form-label">Postal Code *</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" placeholder="e.g., 6600" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g., 09XXXXXXXXX" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_default" name="is_default" <?= empty($addresses) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_default">Set as default address</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Address</button>
                </div>
            </form>
        </div>
    </div>
</div>