require_once __DIR__ . '/../../../config.php';
require_once MODELS_PATH . 'db.php';

$pdo = connectDB();

// 1. બ્રાઉઝરમાં કયું ડોમેન ખુલ્યું છે તે પકડો
$current_domain = $_SERVER['HTTP_HOST']; // દા.ત. 'www.abc-retailer.com'
$current_domain = str_replace('www.', '', $current_domain);

// 2. ચેક કરો કે આ ડોમેન આપણા સોફ્ટવેરમાં રજીસ્ટર છે કે નહિ
$stmt = $pdo->prepare("SELECT * FROM portals WHERE domain_name = ? AND status = 'active' LIMIT 1");
$stmt->execute([$current_domain]);
$portal_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($portal_data) {
    // જો પોર્ટલ એક્ટિવ હોય, તો તેના સેટિંગ્સ સેશન (Session) માં સેવ કરો
    $_SESSION['current_portal_id'] = $portal_data['id'];
    $_SESSION['portal_folder'] = $portal_data['folder_path'];
    $_SESSION['portal_logo'] = $portal_data['logo_url'];
    $_SESSION['portal_theme'] = $portal_data['theme_color'];
    
    // સબસ્ક્રિપ્શન ચેક કરો
    if (strtotime($portal_data['expiry_date']) < time()) {
        die("<h1>તમારું સબસ્ક્રિપ્શન પૂરું થઈ ગયું છે. કૃપા કરીને રિન્યૂ કરો.</h1>");
    }
} else {
    // જો કોઈ જ ડોમેન ના મળે, તો Master Admin નું મુખ્ય પોર્ટલ ખોલો
    if($current_domain !== 'master-project-management.com') { // તમારું અસલ ડોમેન
        die("<h1>404 - Portal Not Found or Suspended</h1>");
    }
}
?>