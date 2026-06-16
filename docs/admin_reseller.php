<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$resellers_db = 'resellers.json';
$resellers = [];
$form_error = '';

if (file_exists($resellers_db)) {
    $loaded = json_decode(file_get_contents($resellers_db), true);
    if (is_array($loaded)) {
        $resellers = $loaded;
    }
}

function clean_input($value) {
    return trim((string)($value ?? ''));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reseller_slug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'reseller';
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name_value = clean_input($_POST['name'] ?? '');
    $logo_value = '';

    if ($name_value === '') {
        $form_error = 'Name is required.';
    }

    if (isset($_FILES['logo_file']) && is_array($_FILES['logo_file']) && $_FILES['logo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($form_error !== '') {
            $form_error = 'Enter reseller name before uploading logo.';
        } elseif ($_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $original_name = $_FILES['logo_file']['name'] ?? '';
            $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if ($extension === 'png') {
                $slug = reseller_slug($name_value);
                $final_name = $slug . '.png';
                $target_path = __DIR__ . DIRECTORY_SEPARATOR . $final_name;

                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_path)) {
                    $logo_value = 'https://keypanel.tech/samurai/' . $final_name;
                } else {
                    $form_error = 'Logo upload failed. Please try again.';
                }
            } else {
                $form_error = 'Invalid logo type. Please upload a PNG file.';
            }
        } else {
            $form_error = 'Logo upload error. Please try again.';
        }
    }

    $payload = [
        'name' => $name_value,
        'description' => clean_input($_POST['description'] ?? ''),
        'country' => clean_input($_POST['country'] ?? ''),
        'payments' => clean_input($_POST['payments'] ?? ''),
        'telegram' => clean_input($_POST['telegram'] ?? ''),
        'whatsapp' => clean_input($_POST['whatsapp'] ?? ''),
        'discord' => clean_input($_POST['discord'] ?? ''),
        'website' => clean_input($_POST['website'] ?? ''),
        'logo' => $logo_value
    ];

    if ($action === 'update') {
        $edit_index = isset($_POST['edit_index']) ? (int)$_POST['edit_index'] : -1;
        if ($edit_index >= 0 && $edit_index < count($resellers) && $payload['logo'] === '') {
            $payload['logo'] = isset($resellers[$edit_index]['logo']) ? (string)$resellers[$edit_index]['logo'] : '';
        }
    }

    if ($payload['name'] !== '' && $form_error === '') {
        if ($action === 'update') {
            $edit_index = isset($_POST['edit_index']) ? (int)$_POST['edit_index'] : -1;
            if ($edit_index >= 0 && $edit_index < count($resellers)) {
                $resellers[$edit_index] = $payload;
                file_put_contents($resellers_db, json_encode(array_values($resellers), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                header('Location: admin_reseller.php?success=updated');
                exit;
            }
        }

        if ($action === 'add') {
            $resellers[] = $payload;
            file_put_contents($resellers_db, json_encode(array_values($resellers), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            header('Location: admin_reseller.php?success=added');
            exit;
        }
    }

}

if (isset($_GET['delete'])) {
    $delete_index = (int)$_GET['delete'];
    if ($delete_index >= 0 && $delete_index < count($resellers)) {
        unset($resellers[$delete_index]);
        $resellers = array_values($resellers);
        file_put_contents($resellers_db, json_encode($resellers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    header('Location: admin_reseller.php?success=deleted');
    exit;
}

$edit_index = isset($_GET['edit']) ? (int)$_GET['edit'] : -1;
$is_edit = $edit_index >= 0 && $edit_index < count($resellers);
$form_data = [
    'name' => '',
    'description' => '',
    'country' => '',
    'payments' => '',
    'telegram' => '',
    'whatsapp' => '',
    'discord' => '',
    'website' => ''
];

if ($is_edit) {
    foreach ($form_data as $key => $value) {
        $form_data[$key] = isset($resellers[$edit_index][$key]) ? (string)$resellers[$edit_index][$key] : '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form_error !== '') {
    foreach ($form_data as $key => $value) {
        $form_data[$key] = clean_input($_POST[$key] ?? '');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samurai Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: '#ef4444', dark: '#050b14' },
                    fontFamily: { sans: ['Inter', 'sans-serif'], orbitron: ['Orbitron', 'sans-serif'] }
                }
            }
        };
    </script>
    <style>
        body {
            background-color: #050b14;
            background-image: radial-gradient(circle at 50% 0%, rgba(239, 68, 68, 0.1) 0%, transparent 50%);
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        .form-input {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <nav class="glass-panel fixed top-0 w-full z-50 h-16">
        <div class="max-w-7xl mx-auto px-4 h-full flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full border-2 border-red-500 shadow-[0_0_15px_rgba(239,68,68,0.5)] bg-gray-800 flex items-center justify-center">
                    <i class="fa fa-users text-red-500"></i>
                </div>
                <span class="font-orbitron text-white text-xl tracking-wider">Samurai <span class="text-red-500">Engine</span></span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-gray-300 text-sm">
                    <i class="fa fa-user mr-1"></i>
                    <?php echo e($_SESSION['admin_username'] ?? 'Admin'); ?>
                </span>
                <a href="?logout=true" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-white text-sm font-bold transition">
                    <i class="fa fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="pt-24 pb-12 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="glass-panel p-4 rounded-xl">
                    <div class="text-gray-400 text-sm">Total Resellers</div>
                    <div class="text-white text-2xl font-bold"><?php echo count($resellers); ?></div>
                </div>
                <div class="glass-panel p-4 rounded-xl">
                    <div class="text-gray-400 text-sm">With Telegram</div>
                    <div class="text-white text-2xl font-bold"><?php echo count(array_filter($resellers, function ($item) { return !empty($item['telegram']); })); ?></div>
                </div>
                <div class="glass-panel p-4 rounded-xl">
                    <div class="text-gray-400 text-sm">With Website</div>
                    <div class="text-white text-2xl font-bold"><?php echo count(array_filter($resellers, function ($item) { return !empty($item['website']); })); ?></div>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 rounded-xl border border-green-500/30 bg-green-500/10 p-3 text-sm text-green-300">
                    <?php
                        if ($_GET['success'] === 'added') echo 'Reseller added successfully.';
                        elseif ($_GET['success'] === 'updated') echo 'Reseller updated successfully.';
                        elseif ($_GET['success'] === 'deleted') echo 'Reseller deleted successfully.';
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($form_error !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-300">
                    <?php echo e($form_error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <section class="lg:col-span-1">
                    <div class="glass-panel p-6 rounded-2xl sticky top-24">
                        <h2 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-3">
                            <?php echo $is_edit ? 'Modify Reseller' : 'Add Reseller'; ?>
                        </h2>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'add'; ?>">
                            <input type="hidden" name="edit_index" value="<?php echo $is_edit ? $edit_index : -1; ?>">

                            <label class="block text-gray-300 text-sm mb-2">Name *</label>
                            <input type="text" name="name" class="form-input mb-4" value="<?php echo e($form_data['name']); ?>" placeholder="Hint: reseller display name (e.g. HTJ STORE)" required>

                            <label class="block text-gray-300 text-sm mb-2">Description</label>
                            <input type="text" name="description" class="form-input mb-4" value="<?php echo e($form_data['description']); ?>" placeholder="Hint: short trust/seller note">

                            <label class="block text-gray-300 text-sm mb-2">Country</label>
                            <input type="text" name="country" class="form-input mb-4" value="<?php echo e($form_data['country']); ?>" placeholder="Hint: country or region (e.g. Global)">

                            <label class="block text-gray-300 text-sm mb-2">Payments (USDT - Bitcoin - Cards - Paypal)</label>
                            <input type="text" name="payments" class="form-input mb-4" value="<?php echo e($form_data['payments']); ?>" placeholder="Hint: Cards, PayPal, USDT">

                            <label class="block text-gray-300 text-sm mb-2">Telegram</label>
                            <input type="text" name="telegram" class="form-input mb-4" value="<?php echo e($form_data['telegram']); ?>" placeholder="Hint: @username">

                            <label class="block text-gray-300 text-sm mb-2">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-input mb-4" value="<?php echo e($form_data['whatsapp']); ?>" placeholder="Hint: phone with country code">

                            <label class="block text-gray-300 text-sm mb-2">Discord</label>
                            <input type="text" name="discord" class="form-input mb-4" value="<?php echo e($form_data['discord']); ?>" placeholder="Hint: discord username">

                            <label class="block text-gray-300 text-sm mb-2">Website</label>
                            <input type="text" name="website" class="form-input mb-4" value="<?php echo e($form_data['website']); ?>" placeholder="Hint: full URL (https://...)">

                            <label class="block text-gray-300 text-sm mb-2">Upload Logo (optional)</label>
                            <input type="file" name="logo_file" class="form-input" accept=".png,image/png">
                            <p class="text-xs text-gray-400 mt-1 mb-6">Hint: saved as reseller name and stored as `https://keypanel.tech/samurai/reseller-name.png`.</p>

                            <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold transition">
                                <?php echo $is_edit ? 'Save Changes' : 'Add Reseller'; ?>
                            </button>

                            <?php if ($is_edit): ?>
                                <a href="admin_reseller.php" class="mt-3 w-full inline-block text-center py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold transition">
                                    Cancel Edit
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </section>

                <section class="lg:col-span-2">
                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-bold text-white">Resellers List</h2>
                            <span class="text-gray-500 text-sm"><?php echo count($resellers); ?> total</span>
                        </div>

                        <?php if (empty($resellers)): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fa fa-users text-4xl mb-4"></i>
                                <p>No reseller entries found.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($resellers as $index => $reseller): ?>
                                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                            <div class="flex-1">
                                                <h3 class="text-white font-semibold text-base mb-2"><?php echo e($reseller['name'] ?? 'Unknown'); ?></h3>
                                                <div class="text-sm text-gray-300 space-y-1">
                                                    <p><span class="text-gray-500">Description:</span> <?php echo e($reseller['description'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">Country:</span> <?php echo e($reseller['country'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">Payments:</span> <?php echo e($reseller['payments'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">Telegram:</span> <?php echo e($reseller['telegram'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">WhatsApp:</span> <?php echo e($reseller['whatsapp'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">Discord:</span> <?php echo e($reseller['discord'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">Website:</span> <?php echo e($reseller['website'] ?? ''); ?></p>
                                                    <p><span class="text-gray-500">Logo:</span> <?php echo e($reseller['logo'] ?? ''); ?></p>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <a href="admin_reseller.php?edit=<?php echo $index; ?>" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition">
                                                    <i class="fa fa-pen mr-1"></i> Modify
                                                </a>
                                                <a href="admin_reseller.php?delete=<?php echo $index; ?>" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold transition" onclick="return confirm('Delete this reseller?');">
                                                    <i class="fa fa-trash mr-1"></i> Remove
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>