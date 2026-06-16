<?php
session_start();
// Default admin credentials (change these!)
$admin_username = "admin";
$admin_password = "hunter123";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: admin_reseller.php');
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}

// If already logged in, redirect to admin panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_reseller.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samurai Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            background-color: #050b14; 
            background-image: radial-gradient(circle at 50% 0%, rgba(239, 68, 68, 0.1) 0%, transparent 50%);
            color: #e2e8f0; 
            font-family: 'Inter', sans-serif;
        }
        .glass-panel { 
            background: rgba(17, 24, 39, 0.6); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4); 
        }
        .btn-brand { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.3); 
        }
        .login-input {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .login-input:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }
        .login-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            color: #fca5a5;
            font-size: 14px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="glass-panel p-8 rounded-2xl w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full border-2 border-red-500 shadow-[0_0_15px_rgba(239,68,68,0.5)] bg-gray-800 flex items-center justify-center">
                    <i class="fa fa-lock text-red-500 text-xl"></i>
                </div>
                <span class="font-orbitron text-white text-2xl tracking-wider">Samurai <span class="text-red-500">Engine</span></span>
            </div>
            <p class="text-gray-300">Login to manage resellers</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message flex items-center">
                <i class="fa fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" 
                   name="username" 
                   class="login-input" 
                   placeholder="Username" 
                   required>
            
            <input type="password" 
                   name="password" 
                   class="login-input" 
                   placeholder="Password" 
                   required>
            
            <button type="submit" 
                    class="btn-brand w-full py-3 rounded-xl text-white font-bold text-center flex justify-center items-center gap-2 transition hover:scale-[1.02]">
                <i class="fa fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="mt-6 text-center text-gray-400 text-sm">
            <i class="fa fa-shield-alt mr-1"></i>
            Secure Admin Access
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
