<?php
session_start();

// Load persistent language system & DB config
require_once 'includes/lang.php';
require_once 'includes/config.php';

// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Profile Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    // Fetch current profile pic to keep it if no new upload
    $stmt_curr = mysqli_prepare($conn, "SELECT profile_pic, role FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_curr, "i", $user_id);
    mysqli_stmt_execute($stmt_curr);
    $res_curr = mysqli_stmt_get_result($stmt_curr);
    $curr_data = mysqli_fetch_assoc($res_curr);
    $profile_pic = $curr_data['profile_pic'] ?? '';
    mysqli_stmt_close($stmt_curr);

    // Handle Profile Picture Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['profile_pic']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['profile_pic']['name']);
        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $profile_pic = $file_name;
        }
    }

    // Secure Update
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, profile_pic = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($update_stmt, "sssssi", $full_name, $email, $phone, $address, $profile_pic, $user_id);

    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['full_name'] = $full_name;
        $success = __('profile_updated_success');
    } else {
        $error = "Error updating profile: " . mysqli_error($conn);
    }
    mysqli_stmt_close($update_stmt);
}

// Fetch Latest User Information
$stmt = mysqli_prepare($conn, "SELECT user_id, full_name, email, phone, role, security_question, address, profile_pic FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Determine Dashboard Link based on Role
$role_lower = strtolower($user['role'] ?? 'renter');
$dashboard_link = ($role_lower === 'lender') ? 'lender_dashboard.php' : 'renter_dashboard.php';
$user_name = $user['full_name'] ?? 'User';
$profile_img = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default_avatar.png';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('my_profile'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .profile-container { max-width: 850px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .top-banner { background: #0f4c5c; color: white; padding: 15px 25px; font-weight: bold; font-size: 18px; display: flex; justify-content: space-between; align-items: center; }
        .profile-header { background: #f8fafc; padding: 30px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 25px; }
        .avatar-box { width: 110px; height: 110px; border-radius: 50%; overflow: hidden; border: 3px solid #0f4c5c; background: #e2e8f0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .profile-body { padding: 30px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 8px; }
        .info-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 5px; }
        .info-value { font-size: 15px; font-weight: 600; color: #1e293b; }
        .badge-role { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .lang-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: #fff; cursor: pointer; }
    </style>
</head>
<body>

    <div class="profile-container">
        <!-- Top Banner with Language Selector matching dashboard -->
        <div class="top-banner">
            <span><i class="fa-regular fa-user me-2"></i> <?php echo __('my_profile'); ?></span>
            
            <div class="d-flex align-items-center gap-3">
                <select class="lang-select text-dark" onchange="location = this.value;">
                    <option value="profile.php?lang=en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>🌐 English</option>
                    <option value="profile.php?lang=hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>🌐 हिन्दी (Hindi)</option>
                    <option value="profile.php?lang=kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
                </select>
                <a href="<?php echo $dashboard_link; ?>" class="btn btn-sm btn-light fw-bold text-dark"><i class="fa-solid fa-arrow-left me-1"></i> <?php echo __('back_to_dashboard'); ?></a>
            </div>
        </div>

        <!-- Profile Header Section -->
        <div class="profile-header">
            <div class="avatar-box">
                <img src="uploads/<?php echo htmlspecialchars($profile_img); ?>" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=0f4c5c&color=fff';" 
                     alt="Profile Picture">
            </div>
            <div>
                <h2 class="mb-1 text-dark fw-bold"><?php echo htmlspecialchars($user_name); ?></h2>
                <p class="text-muted mb-2"><i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge-role"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>
        </div>

        <div class="profile-body">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- View Mode -->
            <div id="viewMode">
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label"><?php echo __('full_name'); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><?php echo __('email_address'); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><?php echo __('phone_number'); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not specified'); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><?php echo __('address'); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($user['address'] ?? 'Not specified'); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><?php echo __('security_question'); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($user['security_question'] ?? 'Not specified'); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><?php echo __('account_user_id'); ?></div>
                        <div class="info-value">#<?php echo intval($user['user_id']); ?></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <a href="forgot_password.php" class="text-danger text-decoration-none fw-bold"><i class="fa-solid fa-key me-1"></i> <?php echo __('change_password'); ?></a>
                    <button type="button" class="btn text-white px-4 fw-bold" style="background-color: #0f4c5c;" onclick="toggleEditMode(true)"><i class="fa-solid fa-pen-to-square me-1"></i> <?php echo __('edit_profile'); ?></button>
                </div>
            </div>

            <!-- Edit Form Mode -->
            <div id="editMode" style="display: none;">
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <h4 class="mb-3 text-dark fw-bold" style="font-size: 18px;"><i class="fa-solid fa-pen-to-square me-2 text-info"></i> <?php echo __('update_profile_info'); ?></h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php echo __('full_name'); ?></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php echo __('email_address'); ?></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php echo __('phone_number'); ?></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?php echo __('update_profile_pic'); ?></label>
                            <input type="file" name="profile_pic" class="form-control">
                            <div class="form-text"><?php echo __('leave_blank_pic'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('address'); ?></label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" onclick="toggleEditMode(false)"><?php echo __('cancel'); ?></button>
                        <button type="submit" class="btn text-white px-4 fw-bold" style="background-color: #0f4c5c;"><i class="fa-solid fa-floppy-disk me-1"></i> <?php echo __('save_changes'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleEditMode(enable) {
            if (enable) {
                document.getElementById('viewMode').style.display = 'none';
                document.getElementById('editMode').style.display = 'block';
            } else {
                document.getElementById('editMode').style.display = 'none';
                document.getElementById('viewMode').style.display = 'block';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>