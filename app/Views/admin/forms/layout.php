<?php
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'Form Admin - KosOnline'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f1f5f9;
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-container {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        h2 {
            text-align: center;
            color: #1e3a8a;
            margin-bottom: 25px;
            font-size: 1.5rem;
        }
        label {
            font-weight: 600;
            font-size: 14px;
            color: #334155;
            display: block;
            margin-bottom: 8px;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background: #f8fafc;
            transition: 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        button[type="submit"] {
            background: #1e3a8a;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            font-size: 15px;
            transition: 0.3s;
        }
        button:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }
        .btn-batal {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-batal:hover {
            color: #1e3a8a;
        }
        .tutorial {
            background: #eff6ff;
            padding: 15px;
            border-radius: 8px;
            font-size: 13px;
            color: #1e3a8a;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
            line-height: 1.6;
        }
        @media (max-width: 480px) {
            body { padding: 15px; }
            .form-container { padding: 20px; }
            h2 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <?php if ($successMessage !== null): ?>
        <script>alert(<?php echo json_encode($successMessage); ?>);</script>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <script>alert(<?php echo json_encode($errorMessage); ?>);</script>
    <?php endif; ?>

    <div class="form-container">
        <?php echo $content; ?>
    </div>
</body>
</html>
