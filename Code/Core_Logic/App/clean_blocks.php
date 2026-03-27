<?php
$files = ['views/poster_studio.php', 'views/resume_builder.php', 'views/smart_card.php', 'views/passport_photo.php', 'views/document_converter.php', 'views/photo_studio.php'];
foreach ($files as $f) {
    $path = 'c:/xampp/htdocs/project_management_system/' . $f;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        $pattern = '/\$user_role\s*=\s*\$_SESSION\[\'user_role\'\]\s*\?\?\s*\'guest\';\s*\$allowed_roles\s*=\s*\[.*?\];\s*\$user_permissions\ s*=\s*\$_SESSION\[\'user_permissions\'\]\s*\?\?\s*\[\];\s*if\s*\(!in_array\(\$user_role,\s*\$allowed_roles\).*?echo.*?you Access to this page is not allowed!.*?return;\s*\}/s';
        
        $new_content = preg_replace($pattern, '', $content);
        if ($new_content !== $content) {
            file_put_contents($path, $new_content);
            echo "Cleaned $f\n";
        } else {
            echo "Did not find block in $f\n";
        }
    }
}
?>
