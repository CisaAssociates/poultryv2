<?php
require_once '../config.php';

// Check if user is logged in and is a consumer
if (!is_logged_in() || $user['role_id'] != 4) {
    redirect('error.php');
}

$mysqli = db_connect();

// Get user profile data
$stmt = $mysqli->prepare("SELECT u.*, c.phone, c.profile_image 
                         FROM users u 
                         LEFT JOIN consumer_profiles c ON u.id = c.user_id 
                         WHERE u.id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_token($_POST['token'])) {
        $error_message = 'Invalid token. Please try again.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            $error_message = 'Please fill in all required fields.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Check if email is already in use by another user
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $_SESSION['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = 'Email address is already in use by another account.';
            } else {
                // Update user data
                $stmt = $mysqli->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("sssi", $first_name, $last_name, $email, $_SESSION['id']);
                $stmt->execute();
                
                // Check if consumer profile exists
                $stmt = $mysqli->prepare("SELECT user_id FROM consumer_profiles WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update consumer profile
                    $stmt = $mysqli->prepare("UPDATE consumer_profiles SET phone = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $phone, $_SESSION['id']);
                } else {
                    // Insert consumer profile
                    $stmt = $mysqli->prepare("INSERT INTO consumer_profiles (user_id, phone) VALUES (?, ?)");
                    $stmt->bind_param("is", $_SESSION['id'], $phone);
                }
                $stmt->execute();
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['profile_image']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        $file_name = 'user_' . $_SESSION['id'] . '_' . time() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $upload_dir = '../uploads/profile_images/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $upload_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            // Update profile image in database
                            $image_path = 'uploads/profile_images/' . $file_name;
                            $stmt = $mysqli->prepare("UPDATE consumer_profiles SET profile_image = ? WHERE user_id = ?");
                            $stmt->bind_param("si", $image_path, $_SESSION['id']);
                            $stmt->execute();
                        } else {
                            $error_message = 'Failed to upload profile image. Please try again.';
                        }
                    } else {
                        $error_message = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.';
                    }
                }
                
                if (empty($error_message)) {
                    $success_message = 'Profile updated successfully.';
                    
                    // Refresh profile data
                    $stmt = $mysqli->prepare("SELECT u.*, c.phone, c.profile_image 
                                             FROM users u 
                                             LEFT JOIN consumer_profiles c ON u.id = c.user_id 
                                             WHERE u.id = ?");
                    $stmt->bind_param("i", $_SESSION['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $profile = $result->fetch_assoc();
                }
            }
        }
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_token($_POST['token'])) {
        $error_message = 'Invalid token. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields.';
        } else if ($new_password !== $confirm_password) {
            $error_message = 'New password and confirmation do not match.';
        } else if (strlen($new_password) < 8) {
            $error_message = 'New password must be at least 8 characters long.';
        } else {
            // Verify current password
            $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $_SESSION['id']);
                
                if ($stmt->execute()) {
                    $success_message = 'Password changed successfully.';
                } else {
                    $error_message = 'Failed to change password. Please try again.';
                }
            } else {
                $error_message = 'Current password is incorrect.';
            }
        }
    }
}

// Set page title
$page_title = "My Profile";

// Include header
include_once "../layouts/consumer/main.php";
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> <?= $success_message ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-1"></i> <?= $error_message ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($profile['profile_image'])): ?>
                        <img class="img-profile rounded-circle" src="<?= asset($profile['profile_image']) ?>" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                        <img class="img-profile rounded-circle" src="<?= asset('img/undraw_profile.svg') ?>" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <h4 class="font-weight-bold"><?= $profile['first_name'] ?> <?= $profile['last_name'] ?></h4>
                    <p class="text-gray-600 mb-4">Consumer</p>
                    <div class="text-left">
                        <p><i class="fas fa-envelope fa-fw mr-2 text-gray-400"></i> <?= $profile['email'] ?></p>
                        <p><i class="fas fa-phone fa-fw mr-2 text-gray-400"></i> <?= $profile['phone'] ?? 'Not set' ?></p>
                        <p><i class="fas fa-calendar fa-fw mr-2 text-gray-400"></i> Member since <?= !empty($profile['created_at']) ? date('F Y', strtotime($profile['created_at'])) : 'N/A' ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Account Links Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Account Management</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?= view('consumer/addresses.php') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt fa-fw mr-2 text-gray-400"></i> Manage Addresses
                        </a>
                        <a href="<?= view('consumer/delivery-schedule.php') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-truck fa-fw mr-2 text-gray-400"></i> Delivery Preferences
                        </a>
                        <a href="<?= view('consumer/orders.php') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-bag fa-fw mr-2 text-gray-400"></i> Order History
                        </a>
                        <a href="<?= view('consumer/loyalty.php') ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-star fa-fw mr-2 text-gray-400"></i> Loyalty Program
                        </a>
                        <a href="#" data-toggle="modal" data-target="#changePasswordModal" class="list-group-item list-group-item-action">
                            <i class="fas fa-key fa-fw mr-2 text-gray-400"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Edit Profile Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Profile</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <?= csrf_token() ?>
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-group row">
                            <label for="first_name" class="col-sm-3 col-form-label">First Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= $profile['first_name'] ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="last_name" class="col-sm-3 col-form-label">Last Name *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?= $profile['last_name'] ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="email" class="col-sm-3 col-form-label">Email Address *</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control" id="email" name="email" value="<?= $profile['email'] ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="phone" class="col-sm-3 col-form-label">Phone Number *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= $profile['phone'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="profile_image" class="col-sm-3 col-form-label">Profile Image</label>
                            <div class="col-sm-9">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                                    <label class="custom-file-label" for="profile_image">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Upload a profile picture (JPEG, PNG, or GIF, max 2MB)</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Notification Preferences Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Notification Preferences</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <?= csrf_token() ?>
                        <input type="hidden" name="update_notifications" value="1">
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_order_updates" name="email_order_updates" checked>
                                <label class="custom-control-label" for="email_order_updates">Email notifications for order updates</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_promotions" name="email_promotions" checked>
                                <label class="custom-control-label" for="email_promotions">Email notifications for promotions and discounts</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sms_order_updates" name="sms_order_updates" checked>
                                <label class="custom-control-label" for="sms_order_updates">SMS notifications for order updates</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sms_promotions" name="sms_promotions">
                                <label class="custom-control-label" for="sms_promotions">SMS notifications for promotions and discounts</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-bell mr-1"></i> Update Notification Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?= csrf_token() ?>
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small class="form-text text-muted">Password must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update custom file input label with selected filename
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
});
</script>

<?php include_once "../layouts/consumer/footer.php"; ?>