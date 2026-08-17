<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 text-slate-900">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Absence & Leave Management System - Form SGIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <?php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $cssFile = isset($manifest['resources/js/app.jsx']['css'][0]) ? '/build/' . $manifest['resources/js/app.jsx']['css'][0] : (isset($manifest['resources/css/app.css']['file']) ? '/build/' . $manifest['resources/css/app.css']['file'] : '');
        $jsFile = isset($manifest['resources/js/app.jsx']['file']) ? '/build/' . $manifest['resources/js/app.jsx']['file'] : '';
    ?>

    <?php if($cssFile): ?>
        <link rel="stylesheet" href="<?php echo e(asset($cssFile)); ?>">
    <?php endif; ?>

    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>
<body class="h-full bg-[#F5FAF7] font-sans antialiased text-slate-900 selection:bg-emerald-600 selection:text-white">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>

    <?php if($jsFile): ?>
        <script type="module" src="<?php echo e(asset($jsFile)); ?>"></script>
    <?php endif; ?>
</body>
</html>
<?php /**PATH D:\FormSGIN\resources\views/app.blade.php ENDPATH**/ ?>