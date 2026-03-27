<?php
$files = [
    'views/poster_studio.php',
    'views/resume_builder.php',
    'views/smart_card.php',
    'views/passport_photo.php',
    'views/photo_studio.php',
    'views/document_converter.php',
    'views/size_converter.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // 1. Fix the $user_role variable
    $content = str_replace('const userRole = "<?= $user_role ?>";', 'const userRole = "<?= $_SESSION[\'user_role\'] ?? \'guest\' ?>";', $content);
    
    // Some files might have different spacing
    $content = preg_replace('/const\s+userRole\s*=\s*"\s*<\?=\s*\$user_role\s*\?>\s*";/', 'const userRole = "<?= $_SESSION[\'user_role\'] ?? \'guest\' ?>";', $content);

    // Double check if there are other variables like $user_permissions
    // Let's also enforce $user_role at the very top of those files
    $topPhp = "<?php\n\$user_role = \$_SESSION['user_role'] ?? 'guest';\nif(!isset(\$currency)) \$currency = '₹';\n";
    
    // Instead of replacing const, let's just make sure $user_role is defined at the top of the file in the existing PHP block!
    $content = str_replace("try {\n    \$stmt", "\$user_role = \$_SESSION['user_role'] ?? 'guest';\n    try {\n    \$stmt", $content);
    
    file_put_contents($file, $content);
    echo "Fixed JS variables in $file\n";
}
