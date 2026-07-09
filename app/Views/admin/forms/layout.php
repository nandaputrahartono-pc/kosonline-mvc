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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            max-width: <?php echo e($formMaxWidth ?? '500px'); ?>;
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
        .form-control,
        .form-select {
            min-height: 48px;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group .form-control {
            width: 1%;
            margin-bottom: 0;
        }
        .input-group .input-group-text {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            border-radius: 8px 0 0 8px;
            font-weight: 700;
        }
        .input-group > .form-control {
            border-radius: 0 8px 8px 0;
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
        button[type="submit"]:hover {
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
        .section-divider {
            margin: 28px 0;
            border: 0;
            border-top: 1px dashed #cbd5e1;
        }
        .section-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }
        .section-heading h3 {
            color: #1e3a8a;
            margin: 0 0 5px;
            font-size: 18px;
        }
        .section-heading p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }
        .btn-add-gallery,
        .btn-remove-gallery {
            border: 0;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-add-gallery {
            flex-shrink: 0;
            border-radius: 8px;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 10px 14px;
        }
        .btn-remove-gallery {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
        }
        .existing-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .gallery-editor-card,
        .new-gallery-row,
        .empty-gallery-note {
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #f8fafc;
            padding: 14px;
        }
        .gallery-editor-card img {
            display: block;
            width: 100%;
            height: 150px;
            object-fit: cover;
            margin-bottom: 14px;
        }
        .gallery-editor-card input,
        .new-gallery-row input {
            margin-bottom: 12px;
        }
        .delete-photo-option {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #b91c1c;
            margin: 3px 0 0;
        }
        .delete-photo-option input {
            width: auto;
            margin: 0;
        }
        .empty-gallery-note {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 18px;
        }
        .new-gallery-row {
            margin-bottom: 16px;
        }
        .new-gallery-row-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #1e3a8a;
            margin-bottom: 12px;
        }
        .field-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 130px;
            gap: 14px;
        }
        .admin-form-flash-stack {
            position: fixed;
            top: 22px;
            right: 22px;
            z-index: 3000;
            display: grid;
            gap: 12px;
            width: min(420px, calc(100vw - 32px));
        }
        .admin-form-flash {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
            color: #0f172a;
            font-weight: 800;
        }
        .admin-form-flash span { flex: 1; }
        .admin-form-flash.success { color: #16a34a; }
        .admin-form-flash.danger { color: #ef4444; }
        @media (max-width: 480px) {
            body { padding: 15px; }
            .form-container { padding: 20px; }
            h2 { font-size: 1.3rem; }
            .section-heading { display: block; }
            .btn-add-gallery { margin-top: 12px; width: 100%; }
            .field-grid { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>
    <?php if ($successMessage !== null || $errorMessage !== null): ?>
        <div class="admin-form-flash-stack" aria-live="polite">
            <?php if ($successMessage !== null): ?>
                <div class="admin-form-flash success" data-notification>
                    <span><?php echo e($successMessage); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage !== null): ?>
                <div class="admin-form-flash danger" data-notification>
                    <span><?php echo e($errorMessage); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <?php echo $content; ?>
    </div>
    <script src="<?php echo e(asset('js/notifications.js')); ?>"></script>
</body>
</html>
