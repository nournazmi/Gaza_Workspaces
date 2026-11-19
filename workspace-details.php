<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM workspaces WHERE id = ? AND status = 'approved'");
$stmt->execute([$id]);
$space = $stmt->fetch();

if (!$space) {
    header("Location: index.php");
    exit();
}

// دالة تحويل الوقت من نظام 24 ساعة إلى نظام 12 ساعة
function formatTime12($time_str) {
    $time_str = trim($time_str);
    $timestamp = strtotime($time_str);
    if ($timestamp === false) return $time_str;
    
    $hour = date('g', $timestamp);
    $minute = date('i', $timestamp);
    $ampm = (date('A', $timestamp) == 'AM') ? 'صباحاً' : 'مساءً';
    
    return ($minute == '00') ? "$hour $ampm" : "$hour:$minute $ampm";
}

// دالة تحديد الأيقونة والتفاصيل المناسبة لكل ميزة
function getAmenityDetails($feature) {
    $feature = trim($feature);
    if (mb_strpos($feature, 'فايبر') !== false || mb_strpos($feature, 'إنترنت') !== false || mb_strpos($feature, 'نت') !== false) {
        return ['icon' => 'fa-wifi', 'title' => $feature, 'desc' => 'عالي السرعة ومستقر'];
    }
    if (mb_strpos($feature, 'طاقة') !== false || mb_strpos($feature, 'كهرباء') !== false || mb_strpos($feature, 'شمسية') !== false) {
        return ['icon' => 'fa-solar-panel', 'title' => $feature, 'desc' => 'توفير كهرباء مستمر 24/7'];
    }
    if (mb_strpos($feature, 'هادئة') !== false || mb_strpos($feature, 'هدوء') !== false) {
        return ['icon' => 'fa-volume-xmark', 'title' => $feature, 'desc' => 'مثالية للتركيز والدراسة'];
    }
    if (mb_strpos($feature, 'مشروبات') !== false || mb_strpos($feature, 'بوفيه') !== false || mb_strpos($feature, 'قهوة') !== false) {
        return ['icon' => 'fa-mug-saucer', 'title' => $feature, 'desc' => 'متوفرة لخدمة الضيوف'];
    }
    if (mb_strpos($feature, 'اجتماعات') !== false || mb_strpos($feature, 'قاعات') !== false) {
        return ['icon' => 'fa-users', 'title' => $feature, 'desc' => 'مجهزة للفرق واللقاءات'];
    }
    if (mb_strpos($feature, 'تكييف') !== false) {
        return ['icon' => 'fa-snowflake', 'title' => $feature, 'desc' => 'أجواء مريحة طوال اليوم'];
    }
    if (mb_strpos($feature, 'دورات مياه') !== false) {
        return ['icon' => 'fa-restroom', 'title' => $feature, 'desc' => 'نظيفة ومجهزة بالكامل'];
    }
    if (mb_strpos($feature, 'سيدات') !== false) {
        return ['icon' => 'fa-person-dress', 'title' => $feature, 'desc' => 'مختصة وآمنة للنساء'];
    }
    if (mb_strpos($feature, 'مكاتب فردية') !== false || mb_strpos($feature, 'مكاتب') !== false) {
        return ['icon' => 'fa-chair', 'title' => $feature, 'desc' => 'مساحة عمل خاصة ومستقلة'];
    }
    if (mb_strpos($feature, 'دفع') !== false) {
        return ['icon' => 'fa-credit-card', 'title' => $feature, 'desc' => 'تسهيلات وطرق دفع متعددة'];
    }
    if (mb_strpos($feature, 'دورات تدريبية') !== false) {
        return ['icon' => 'fa-graduation-cap', 'title' => $feature, 'desc' => 'تطوير مهارات وورش عمل'];
    }
    if (mb_strpos($feature, 'طباعة') !== false) {
        return ['icon' => 'fa-print', 'title' => $feature, 'desc' => 'خدمات طباعة وتصوير مستندات'];
    }
    return ['icon' => 'fa-circle-check', 'title' => $feature, 'desc' => 'متوفر في المساحة'];
}

// استخراج البيانات بدقة من حقل الوصف المخزن
$working_hours = "غير محدد";
$student_price = $space['price_per_hour'];
$freelancer_price = "غير محدد";
$manager_name = "غير محدد";
$manager_phone = "غير محدد";
$features_list = [];

if (preg_match('/ساعات العمل:\s*من\s*([^\s]+)\s*إلى\s*([^|]+)/u', $space['description'], $matches)) {
    $open_12 = formatTime12($matches[1]);
    $close_12 = formatTime12($matches[2]);
    $working_hours = "من " . $open_12 . " إلى " . $close_12;
} elseif (preg_match('/ساعات العمل:\s*([^|]+)/u', $space['description'], $matches)) {
    $working_hours = trim($matches[1]);
}

if (preg_match('/سعر الطالب:\s*₪([0-9.]+)/u', $space['description'], $matches)) {
    $student_price = trim($matches[1]);
}
if (preg_match('/سعر المستقل:\s*₪([0-9.]+)/u', $space['description'], $matches)) {
    $freelancer_price = trim($matches[1]);
}
if (preg_match('/المسؤول:\s*([^|-]+)/u', $space['description'], $matches)) {
    $manager_name = trim($matches[1]);
}
if (preg_match('/هاتف:\s*([0-9]+)/u', $space['description'], $matches)) {
    $manager_phone = trim($matches[1]);
}

// استخراج كافة المزايا المضافة بدقة باستخدام نفس الطريقة الذكية المعتمدة بالرئيسية
$all_possible_features = [
    'فايبر', 'طاقة شمسية', 'بيئة هادئة', 'مشروبات', 
    'غرف اجتماعات', 'تكييف', 'دورات مياه', 'قاعات منفصلة للسيدات', 
    'كاتب فردية', 'خيارات دفع مرنة', 'دورات تدريبية', 'طباعة'
];

foreach ($all_possible_features as $feat) {
    if (mb_strpos($space['description'], $feat) !== false) {
        if (!in_array($feat, $features_list)) {
            $features_list[] = $feat;
        }
    }
}

if (empty($features_list)) {
    $features_list = ['فايبر', 'طاقة شمسية', 'بيئة هادئة'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($space['name']); ?> | تفاصيل المساحة</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
    html { scroll-behavior: smooth; }
    
    body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; line-height: 1.6; }
    a { text-decoration: none; color: inherit; }
    ul { list-style: none; }
    .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }

    nav { position: fixed; top: 0; left: 0; width: 100%; background: #ffffff; border-bottom: 1px solid #e2e8f0; z-index: 1000; }
    .nav-container { display: flex; justify-content: space-between; align-items: center; height: 70px; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    
    .logo { display: flex; align-items: center; gap: 10px; }
    .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #b45309, #78350f); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; box-shadow: 0 4px 10px rgba(180, 83, 9, 0.2); }
    .logo-text h2 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; }

    .nav-links { display: flex; gap: 25px; background: #f1f5f9; padding: 8px 20px; border-radius: 30px; border: 1px solid #e2e8f0; }
    .nav-links a { font-size: 14px; font-weight: 600; color: #475569; transition: 0.3s; }
    .nav-links a:hover { color: #b45309; }

    .nav-actions { display: flex; gap: 12px; align-items: center; }
    .btn-outline { padding: 8px 16px; background: #ffffff; border: 1px solid #cbd5e1; color: #b45309; border-radius: 8px; font-size: 13px; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-outline:hover { border-color: #b45309; background: #fffdfb; }
    .btn-primary { padding: 8px 18px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border-radius: 8px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 12px rgba(180, 83, 9, 0.2); transition: 0.3s; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary:hover { background: linear-gradient(135deg, #d97706, #b45309); }

    .main-content { padding: 110px 0 60px 0; }
    .breadcrumb { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; }
    .breadcrumb-links { display: flex; gap: 10px; align-items: center; }
    .breadcrumb-links a:hover { color: #b45309; }
    .breadcrumb-links i { font-size: 12px; color: #b45309; }

    .details-header { background: #ffffff; border: 1px solid #e2e8f0; padding: 25px 30px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .details-header h1 { font-size: 26px; font-weight: 900; margin-bottom: 8px; color: #0f172a; }
    .details-header p { font-size: 14px; color: #64748b; display: flex; align-items: center; gap: 10px; }
    .details-header p i { font-size: 18px; color: #b45309; }
    .status-badge { background: #dcfce7; color: #15803d; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 8px; }

    .single-img-container { width: 100%; height: 400px; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
    .single-img-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .single-img-container:hover img { transform: scale(1.02); }

    .price-box-inline { display: flex; justify-content: space-between; align-items: center; background: #fff5eb; border: 1px solid #fed7aa; padding: 20px 30px; border-radius: 16px; margin-bottom: 25px; }
    .price-box-inline div span { font-size: 13px; color: #b45309; font-weight: 700; display: block; margin-bottom: 4px; }
    .price-box-inline h2 { color: #b45309; font-size: 26px; font-weight: 900; }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 28px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .info-card h3 { font-size: 18px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; color: #0f172a; border-bottom: 2px solid #fff5eb; padding-bottom: 10px; }
    .info-card h3 i { font-size: 22px; color: #b45309; }
    .info-card p { font-size: 14px; color: #475569; line-height: 1.8; }

    .amenities-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .amenity-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; display: flex; align-items: center; gap: 14px; transition: 0.3s; }
    .amenity-item:hover { border-color: #b45309; transform: translateY(-3px); }
    .amenity-item i { font-size: 22px; color: #b45309; width: 30px; text-align: center; }
    .amenity-item strong { font-size: 13px; display: block; color: #0f172a; }
    .amenity-item span { font-size: 11px; color: #64748b; }

    .contact-list { display: flex; flex-direction: column; gap: 14px; margin-top: 10px; }
    .contact-item { display: flex; align-items: center; gap: 12px; font-size: 14px; color: #334155; }
    .contact-item i { font-size: 18px; color: #b45309; width: 24px; text-align: center; }

    footer { background: #9A6B43; border-top: 1px solid #B07D53; padding: 60px 0 30px 0; color: #fdfbf7; margin-top: 60px; }
    .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .footer-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
    
    .footer-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .footer-logo-text { font-size: 20px; font-weight: 800; color: #ffffff; }

    .footer-col h4 { font-size: 15px; font-weight: 800; color: #ffffff; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.25); padding-bottom: 10px; }
    .footer-col p { font-size: 13px; line-height: 1.8; margin-top: 15px; color: #f5f0eb; }
    .footer-col ul li { margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .footer-col ul li i { font-size: 14px; margin-left: 6px; }
    .footer-col ul li a { font-size: 13px; color: #f5f0eb; transition: 0.3s; }
    .footer-col ul li a:hover { color: #fef08a; }
    
    .social-links { display: flex; gap: 10px; margin-top: 15px; }
    .social-links a { width: 38px; height: 38px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ffffff; transition: 0.3s; }
    .social-links a:hover { background: #ffffff; color: #9A6B43; }
    
    .footer-bottom { border-top: 1px solid rgba(255,255,255,0.25); padding-top: 25px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #f5f0eb; max-width: 1200px; margin: 0 auto; padding-left: 20px; padding-right: 20px; }
    .footer-bottom-links { display: flex; gap: 15px; }
    .footer-bottom-links a { color: #f5f0eb; }
    .footer-bottom-links a:hover { color: #ffffff; }

    @media(max-width: 992px) {
      .details-header, .amenities-grid, .footer-grid { grid-template-columns: 1fr; }
      .nav-links { display: none; }
    }
  </style>
</head>
<body>

  <nav>
    <div class="container nav-container">
      <a href="index.php" class="logo">
        <div class="logo-icon"><i class="fa-solid fa-layer-group"></i></div>
        <div class="logo-text"><h2>مساحات</h2></div>
      </a>
      
      <ul class="nav-links">
        <li><a href="index.php#hero">الرئيسية</a></li>
        <li><a href="index.php#about">عن المنصة</a></li>
        <li><a href="index.php#spaces">المساحات</a></li>
        <li><a href="index.php#features">لماذا نعتمدنا</a></li>
        <li><a href="#footer">تواصل معنا</a></li>
      </ul>
      
      <div class="nav-actions">
        <a href="add-workspace.php" class="btn-outline"><i class="fa-solid fa-plus"></i> إضافة مساحة</a>
        <a href="login.php" class="btn-primary"><i class="fa-solid fa-right-to-bracket"></i> تسجيل دخول</a>
      </div>
    </div>
  </nav>

  <main class="container main-content">
    <div class="breadcrumb">
      <div class="breadcrumb-links">
        <a href="index.php">الرئيسية</a>
        <i class="fa-solid fa-chevron-left"></i>
        <span><?php echo htmlspecialchars($space['name']); ?></span>
      </div>
      <a href="index.php#spaces" style="color: #b45309; font-weight: 700; display: flex; align-items: center; gap: 6px;">
        <i class="fa-solid fa-arrow-right"></i> استعراض كافة المساحات
      </a>
    </div>

    <div class="details-header">
      <div>
        <h1><?php echo htmlspecialchars($space['name']); ?></h1>
        <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($space['location']); ?></p>
      </div>
      <span class="status-badge"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> مفتوح الآن</span>
    </div>

    <div class="single-img-container">
      <img src="<?php echo htmlspecialchars($space['image']); ?>" alt="<?php echo htmlspecialchars($space['name']); ?>">
    </div>

    <div class="price-box-inline">
      <div>
        <span>سعر الساعة للطالب</span>
        <h2>₪<?php echo htmlspecialchars($student_price); ?></h2>
      </div>
      <div style="text-align: left;">
        <span style="color: #64748b;">سعر الساعة للمستقل</span>
        <h2 style="color: #475569; font-size: 22px;">₪<?php echo htmlspecialchars($freelancer_price); ?></h2>
      </div>
    </div>

    <div class="info-card">
      <h3><i class="fa-solid fa-clock"></i> ساعات العمل</h3>
      <p style="font-weight: 700; color: #b45309; font-size: 15px;"><?php echo htmlspecialchars($working_hours); ?></p>
    </div>

    <div class="info-card">
      <h3><i class="fa-solid fa-list-check"></i> الخدمات والتسهيلات المتاحة</h3>
      <div class="amenities-grid">
        <?php foreach ($features_list as $feat): 
          $amenity = getAmenityDetails($feat);
        ?>
          <div class="amenity-item">
            <i class="fa-solid <?php echo $amenity['icon']; ?>"></i>
            <div>
              <strong><?php echo htmlspecialchars($amenity['title']); ?></strong>
              <span><?php echo htmlspecialchars($amenity['desc']); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="info-card">
      <h3><i class="fa-solid fa-address-book"></i> بيانات التواصل والإدارة</h3>
      <div class="contact-list">
        <div class="contact-item">
          <i class="fa-solid fa-user-tie"></i>
          <span><strong>مسؤول / مدير المكان:</strong> <?php echo htmlspecialchars($manager_name); ?></span>
        </div>
        <div class="contact-item">
          <i class="fa-brands fa-whatsapp"></i>
          <span><strong>رقم التواصل:</strong> <?php echo htmlspecialchars($manager_phone); ?></span>
        </div>
      </div>
    </div>
  </main>

  <footer id="footer">
    <div class="footer-container">
      <div class="footer-grid">
        <div class="footer-col">
          <a href="index.php" class="footer-logo">
            <div class="logo-icon"><i class="fa-solid fa-layer-group"></i></div>
            <span class="footer-logo-text">مساحات</span>
          </a>
          <p>دليل لمساحات العمل المشتركة لمتابعة جودة خدمات الإنترنت والتسهيلات اللوجستية للشباب والطلاب.</p>
        </div>
        <div class="footer-col">
          <h4>روابط سريعة</h4>
          <ul>
            <li><a href="index.php#hero">الرئيسية</a></li>
            <li><a href="index.php#about">عن المنصة</a></li>
            <li><a href="index.php#spaces">دليل المساحات</a></li>
            <li><a href="index.php#features">لماذا نعتمدنا</a></li>
            <li><a href="add-workspace.php">إضافة مساحة جديدة</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>مركز التواصل</h4>
          <ul>
            <li><i class="fa-solid fa-location-dot" style="color: #fef08a;"></i> <span>فلسطين</span></li>
            <li><i class="fa-brands fa-whatsapp" style="color: #fef08a;"></i> <span>+970 59 900 1234</span></li>
            <li><i class="fa-solid fa-envelope" style="color: #fef08a;"></i> <span>contact@spaces.ps</span></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>حسابات المنصة</h4>
          <div class="social-links">
            <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#"><i class="fa-brands fa-instagram"></i></a>
            <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
            <a href="#"><i class="fa-brands fa-whatsapp"></i></a>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div>جميع الحقوق محفوظة © <span id="year"></span> دليل مساحات 🇵🇸</div>
      <div class="footer-bottom-links"><a href="#">الشروط والأحكام</a><span>•</span><a href="#">سياسة الخصوصية</a></div>
    </div>
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>