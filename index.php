<?php
include 'db.php';

$stmt = $pdo->query("SELECT * FROM workspaces WHERE status = 'approved' ORDER BY id DESC");
$approved_spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getFeatureIcon($feature) {
    if (strpos($feature, 'فايبر') !== false || strpos($feature, 'إنترنت') !== false || strpos($feature, 'نت') !== false) return 'fa-wifi';
    if (strpos($feature, 'طاقة') !== false || strpos($feature, 'كهرباء') !== false || strpos($feature, 'شمسية') !== false) return 'fa-solar-panel';
    if (strpos($feature, 'اجتماعات') !== false || strpos($feature, 'قاعات') !== false) return 'fa-users';
    if (strpos($feature, 'هادئة') !== false || strpos($feature, 'هدوء') !== false) return 'fa-volume-xmark';
    if (strpos($feature, 'مشروبات') !== false || strpos($feature, 'بوفيه') !== false || strpos($feature, 'قهوة') !== false) return 'fa-mug-saucer';
    if (strpos($feature, 'تكييف') !== false) return 'fa-snowflake';
    if (strpos($feature, 'دورات مياه') !== false) return 'fa-restroom';
    if (strpos($feature, 'سيدات') !== false) return 'fa-person-dress';
    if (strpos($feature, 'مكاتب فردية') !== false || strpos($feature, 'مكاتب') !== false) return 'fa-chair';
    if (strpos($feature, 'دفع') !== false) return 'fa-credit-card';
    if (strpos($feature, 'دورات تدريبية') !== false) return 'fa-graduation-cap';
    if (strpos($feature, 'طباعة') !== false) return 'fa-print';
    return 'fa-check-circle';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>دليل مساحات العمل | مساحات</title>
  
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
    .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

    nav { position: fixed; top: 0; left: 0; width: 100%; background: #ffffff; border-bottom: 1px solid #e2e8f0; z-index: 1000; }
    .nav-container { display: flex; justify-content: space-between; align-items: center; height: 70px; }
    
    .logo { display: flex; align-items: center; gap: 10px; }
    .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #b45309, #78350f); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 4px 10px rgba(180, 83, 9, 0.2); }
    .logo-text h2 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; }

    .nav-links { display: flex; gap: 25px; background: #f1f5f9; padding: 8px 20px; border-radius: 30px; border: 1px solid #e2e8f0; }
    .nav-links a { font-size: 14px; font-weight: 600; color: #475569; transition: 0.3s; }
    .nav-links a:hover { color: #b45309; }

    .nav-actions { display: flex; gap: 12px; align-items: center; }
    .btn-outline { padding: 8px 16px; background: #ffffff; border: 1px solid #cbd5e1; color: #b45309; border-radius: 8px; font-size: 13px; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-outline:hover { border-color: #b45309; background: #fffdfb; }
    
    .btn-primary { padding: 8px 18px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border-radius: 8px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 12px rgba(180, 83, 9, 0.2); transition: 0.3s; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary:hover { background: linear-gradient(135deg, #d97706, #b45309); }

    .hero { padding: 140px 0 80px 0; }
    .hero-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 50px; align-items: center; }
    .hero-content h1 { font-size: 42px; font-weight: 900; line-height: 1.4; margin-bottom: 20px; color: #0f172a; }
    .hero-content h1 span { color: #b45309; }
    .hero-content p { font-size: 14px; color: #475569; margin-bottom: 30px; line-height: 1.8; }

    .search-box { background: #ffffff; border: 1px solid #e2e8f0; padding: 15px; border-radius: 16px; display: flex; gap: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); align-items: stretch; }
    .search-group { flex: 1; position: relative; }

    .location-dropdown-wrapper { position: relative; width: 100%; height: 46px; }
    .location-select-trigger { width: 100%; height: 100%; padding: 0 35px 0 35px; background: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; border-radius: 10px; font-size: 13px; font-weight: 400; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: 0.2s; }
    .location-select-trigger:hover, .location-select-trigger.active { border-color: #b45309; background: #ffffff; }
    .location-select-trigger i.fa-location-dot { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #b45309; font-size: 13px; }
    .location-select-trigger i.fa-chevron-down { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #64748b; transition: transform 0.2s; }
    .location-select-trigger.active i.fa-chevron-down { transform: translateY(-50%) rotate(180deg); }

    .location-menu { display: none; position: absolute; top: calc(100% + 6px); right: 0; width: 100%; max-height: 340px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); z-index: 9999; padding: 8px; }
    .location-menu.open { display: block; }
    
    .gov-group { margin-bottom: 6px; }
    .gov-header { width: 100%; padding: 9px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 800; color: #0f172a; cursor: pointer; transition: 0.2s; }
    .gov-header.expanded { background: #b45309; color: #ffffff; border-color: #b45309; }
    .gov-header i { transition: transform 0.25s ease; font-size: 10px; }
    .gov-header.expanded i { transform: rotate(180deg); }
    
    .sub-list { display: none; padding: 6px 10px 4px 4px; border-right: 2px solid #fed7aa; margin-right: 10px; margin-top: 5px; }
    .sub-list.show { display: block; }
    .city-header { padding: 8px 10px; background: #fff5eb; color: #92400e; border-radius: 6px; font-size: 12px; font-weight: 800; display: flex; justify-content: space-between; align-items: center; cursor: pointer; margin-bottom: 4px; transition: 0.2s; }
    .city-header.expanded { background: #92400e; color: #ffffff; }
    .city-header i { transition: transform 0.25s ease; font-size: 10px; }
    .city-header.expanded i { transform: rotate(180deg); }
    .area-item { padding: 7px 10px; font-size: 12px; font-weight: 600; color: #334155; border-radius: 6px; cursor: pointer; transition: 0.15s; }
    .area-item:hover { background: #fff5eb; color: #b45309; }

    .custom-multiselect { position: relative; width: 100%; height: 46px; }
    .multiselect-btn { width: 100%; height: 100%; padding: 12px 35px 12px 35px; background: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; border-radius: 10px; font-size: 13px; text-align: right; cursor: pointer; display: flex; align-items: center; justify-content: space-between; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: 0.2s; }
    .multiselect-btn:hover, .multiselect-btn.active { border-color: #b45309; background: #ffffff; }
    .multiselect-btn .wifi-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #b45309; }
    .multiselect-btn .arrow-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #64748b; pointer-events: none; }
    
    .multiselect-dropdown { position: absolute; top: calc(100% + 6px); right: 0; left: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); z-index: 999; padding: 8px; display: none; flex-direction: column; gap: 4px; max-height: 280px; overflow-y: auto; }
    .multiselect-dropdown.show { display: flex; }
    .multiselect-option { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; font-size: 12px; color: #334155; cursor: pointer; transition: 0.2s; user-select: none; }
    .multiselect-option:hover { background: #fff5eb; color: #b45309; }
    .multiselect-option input[type="checkbox"] { accent-color: #b45309; width: 14px; height: 14px; cursor: pointer; }

    .search-btn { padding: 0 24px; height: 46px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 13px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .search-btn:hover { background: linear-gradient(135deg, #d97706, #b45309); }

    .hero-img-box { position: relative; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px rgba(0,0,0,0.1); }
    .hero-img-box img { width: 100%; height: 380px; object-fit: cover; display: block; transition: transform 0.5s ease; }
    .hero-img-box:hover img { transform: scale(1.05); }

    .hero-badge { position: absolute; bottom: 20px; right: 20px; left: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 12px 18px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .badge-info h4 { font-size: 13px; font-weight: 700; color: #0f172a; }
    .badge-info span { font-size: 11px; color: #64748b; }
    .status-tag { background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0; }

    .about-section { padding: 80px 0; background: #ffffff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
    .about-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 50px; align-items: center; }
    .about-img { border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; }
    .about-img img { width: 100%; height: 320px; object-fit: cover; display: block; transition: transform 0.5s ease; }
    .about-img:hover img { transform: scale(1.05); }
    
    .section-plain-text { font-size: 14px; font-weight: 700; color: #b45309; margin-bottom: 12px; display: block; }
    .about-text h2 { font-size: 28px; font-weight: 800; margin-bottom: 15px; color: #0f172a; }
    .about-text p { font-size: 14px; color: #475569; margin-bottom: 25px; line-height: 1.8; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .stat-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 14px; text-align: center; }
    .stat-card h3 { font-size: 22px; font-weight: 900; color: #b45309; }
    .stat-card span { font-size: 11px; color: #64748b; }

    .spaces-section { padding: 80px 0; }
    .spaces-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
    .spaces-header h2 { font-size: 28px; font-weight: 800; color: #0f172a; }
    .reset-btn { background: none; border: none; color: #b45309; font-size: 13px; font-weight: 700; cursor: pointer; }
    
    .spaces-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .space-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .space-card:hover { border-color: #b45309; transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
    
    .card-img { position: relative; height: 160px; overflow: hidden; }
    .card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .space-card:hover .card-img img { transform: scale(1.08); }

    .card-rating { position: absolute; top: 10px; right: 10px; background: #B45309; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; z-index: 2; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
    
    .card-body { padding: 15px; }
    .card-body h3 { font-size: 15px; font-weight: 700; margin-bottom: 6px; color: #0f172a; }
    .card-body p { font-size: 12px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
    .card-tags { display: flex; gap: 5px; flex-wrap: wrap; }
    .card-tags span { background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; padding: 2px 7px; border-radius: 4px; font-size: 10px; display: inline-flex; align-items: center; gap: 4px; }
    .card-tags span.more-tag { background: #fff5eb; color: #b45309; border-color: #fed7aa; font-weight: 700; }
    
    .card-footer { padding: 15px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .price-box span { font-size: 10px; color: #64748b; display: block; }
    .price-box strong { font-size: 13px; color: #b45309; }
    .details-btn { padding: 6px 14px; background: #fff5eb; border: 1px solid #fed7aa; color: #b45309; border-radius: 8px; font-size: 12px; font-weight: 700; transition: 0.3s; }
    .details-btn:hover { background: #b45309; color: white; }

    .features-section { padding: 80px 0; background: #ffffff; border-top: 1px solid #e2e8f0; text-align: center; }
    .features-section h2 { font-size: 28px; font-weight: 800; margin-bottom: 40px; color: #0f172a; }
    .features-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; text-align: right; }
    
    .feature-card { background: #fef08a9f; border: 1px solid #EFECE6; padding: 22px 18px; border-radius: 16px; transition: 0.3s; }
    .feature-card:hover { border-color: #b45309; transform: translateY(-4px); box-shadow: 0 10px 20px rgba(180, 83, 9, 0.05); }
    .feature-icon { width: 45px; height: 45px; background: #ffffff; border: 1px solid #EFECE6; color: #b45309; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 15px; }
    .feature-card h3 { font-size: 15px; font-weight: 700; margin-bottom: 8px; color: #0f172a; }
    .feature-card p { font-size: 12px; color: #645d53; line-height: 1.6; }

    .no-results { grid-column: 1 / -1; text-align: center; padding: 40px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; }
    .hidden { display: none !important; }

    footer { background: #9A6B43; border-top: 1px solid #B07D53; padding: 60px 0 30px 0; color: #fdfbf7; }
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
    
    .footer-bottom { border-top: 1px solid rgba(255,255,255,0.25); padding-top: 25px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #f5f0eb; }
    .footer-bottom-links { display: flex; gap: 15px; }
    .footer-bottom-links a { color: #f5f0eb; }
    .footer-bottom-links a:hover { color: #ffffff; }

    @media (max-width: 992px) {
      .hero-grid, .about-grid, .footer-grid, .spaces-grid, .features-grid { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
      .nav-links { display: none; }
      .search-box { flex-direction: column; }
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
        <li><a href="#hero">الرئيسية</a></li>
        <li><a href="#about">عن المنصة</a></li>
        <li><a href="#spaces">المساحات</a></li>
        <li><a href="#features">لماذا نعتمدنا</a></li>
        <li><a href="#footer">تواصل معنا</a></li>
      </ul>
      
      <div class="nav-actions">
        <a href="add-workspace.php" class="btn-outline"><i class="fa-solid fa-plus"></i> إضافة مساحة</a>
        <a href="login.php" class="btn-primary"><i class="fa-solid fa-right-to-bracket"></i> تسجيل دخول</a>
      </div>
    </div>
  </nav>

  <section id="hero" class="hero">
    <div class="container">
      <div class="hero-grid">
        <div class="hero-content">
          <h1>
            اعثر على بيئة عملك المناسبة <br>
            <span>بإنترنت سريع وطاقة مستمرة</span>
          </h1>
          <p>منصة متخصصة تفاعلية تتيح للطلاب والمستقلين استكشاف مساحات العمل المتاحة <br>ومتابعة جودة الإنترنت والكهرباء والأسعار بسهولة.</p>

          <div class="search-box">
            <div class="search-group">
              <div class="location-dropdown-wrapper" id="locationDropdown">
                <input type="hidden" id="selectedRegionInput" value="all">
                <div class="location-select-trigger" id="locationTrigger">
                  <i class="fa-solid fa-location-dot"></i>
                  <span id="locationDisplayText">اختر المنطقة...</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="location-menu" id="locationMenu">
                  
                  <div class="gov-group">
                    <div class="gov-header" onclick="toggleAccordion(event, this)">
                      <span>شمال غزة</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>جباليا</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('شمال غزة - جباليا - جباليا البلد', 'شمال غزة - جباليا - جباليا البلد')">جباليا البلد</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - جباليا - جباليا الشرقية', 'شمال غزة - جباليا - جباليا الشرقية')">جباليا الشرقية</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - جباليا - جباليا الغربية', 'شمال غزة - جباليا - جباليا الغربية')">جباليا الغربية</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - جباليا - أم النصر', 'شمال غزة - جباليا - أم النصر')">أم النصر</div>
                      </div>

                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>بيت حانون</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('شمال غزة - بيت حانون - بيت حانون البلد', 'شمال غزة - بيت حانون - بيت حانون البلد')">بيت حانون البلد</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - بيت حانون - المنطقة الشرقية', 'شمال غزة - بيت حانون - المنطقة الشرقية')">المنطقة الشرقية</div>
                      </div>

                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>بيت لاهيا</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('شمال غزة - بيت لاهيا - بيت لاهيا البلد', 'شمال غزة - بيت لاهيا - بيت لاهيا البلد')">بيت لاهيا البلد</div>
                      </div>

                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>غزة</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الزهراء', 'شمال غزة - غزة - الزهراء')">الزهراء</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - المغراقة', 'شمال غزة - غزة - المغراقة')">المغراقة</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - مخيم الشاطئ', 'شمال غزة - غزة - مخيم الشاطئ')">مخيم الشاطئ</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الرمال الشمالي', 'شمال غزة - غزة - الرمال الشمالي')">الرمال الشمالي</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الرمال الجنوبي', 'شمال غزة - غزة - الرمال الجنوبي')">الرمال الجنوبي</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - تل الهوا', 'شمال غزة - غزة - تل الهوا')">تل الهوا</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - النصر', 'شمال غزة - غزة - النصر')">النصر</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الشيخ رضوان', 'شمال غزة - غزة - الشيخ رضوان')">الشيخ رضوان</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الشجاعية', 'شمال غزة - غزة - الشجاعية')">الشجاعية</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - التفاح', 'شمال غزة - غزة - التفاح')">التفاح</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الدرج', 'شمال غزة - غزة - الدرج')">الدرج</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - البلدة القديمة', 'شمال غزة - غزة - البلدة القديمة')">البلدة القديمة</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الزيتون', 'شمال غزة - غزة - الزيتون')">الزيتون</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الصبرة', 'شمال غزة - غزة - الصبرة')">الصبرة</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - النفق', 'شمال غزة - غزة - النفق')">النفق</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الصحابة', 'شمال غزة - غزة - الصحابة')">الصحابة</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - الصناعة', 'شمال غزة - غزة - الصناعة')">الصناعة</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - اليرموك', 'شمال غزة - غزة - اليرموك')">اليرموك</div>
                        <div class="area-item" onclick="selectRegion('شمال غزة - غزة - السرايا', 'شمال غزة - غزة - السرايا')">السرايا</div>
                      </div>
                    </div>
                  </div>

                  <div class="gov-group">
                    <div class="gov-header" onclick="toggleAccordion(event, this)">
                      <span>الوسطى</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>دير البلح</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - البلد', 'الوسطى - دير البلح - البلد')">البلد</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - البركة', 'الوسطى - دير البلح - البركة')">البركة</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - السلام', 'الوسطى - دير البلح - السلام')">السلام</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - المدفع', 'الوسطى - دير البلح - المدفع')">المدفع</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - رمزون الدير', 'الوسطى - دير البلح - رمزون الدير')">رمزون الدير</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - النخيل', 'الوسطى - دير البلح - النخيل')">النخيل</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - التحلية', 'الوسطى - دير البلح - التحلية')">التحلية</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - دوار ابو سمرة', 'الوسطى - دير البلح - دوار ابو سمرة')">دوار ابو سمرة</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - شهداء الاقصى', 'الوسطى - دير البلح - شهداء الاقصى')">شهداء الاقصى</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - المشاعلة', 'الوسطى - دير البلح - المشاعلة')">المشاعلة</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - الحكر', 'الوسطى - دير البلح - الحكر')">الحكر</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - دير البلح - ابو عريف', 'الوسطى - دير البلح - ابو عريف')">ابو عريف</div>
                      </div>

                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>النصيرات</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - المخيم القديم', 'الوسطى - النصيرات - المخيم القديم')">المخيم القديم</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - المخيم الجديد', 'الوسطى - النصيرات - المخيم الجديد')">المخيم الجديد</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - الزهور', 'الوسطى - النصيرات - الزهور')">الزهور</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - الدعوة', 'الوسطى - النصيرات - الدعوة')">الدعوة</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - الحساينة', 'الوسطى - النصيرات - الحساينة')">الحساينة</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - دوار الشهداء', 'الوسطى - النصيرات - دوار الشهداء')">دوار الشهداء</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - النصيرات - السوق', 'الوسطى - النصيرات - السوق')">السوق</div>
                      </div>

                      <div class="city-header" onclick="toggleAccordion(event, this)">
                        <span>المغازي</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </div>
                      <div class="sub-list">
                        <div class="area-item" onclick="selectRegion('الوسطى - المغازي - بلوك A', 'الوسطى - المغازي - بلوك A')">بلوك A</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - المغازي - بلوك B', 'الوسطى - المغازي - بلوك B')">بلوك B</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - المغازي - بلوك C', 'الوسطى - المغازي - بلوك C')">بلوك C</div>
                        <div class="area-item" onclick="selectRegion('الوسطى - المغازي - بلوك D', 'الوسطى - المغازي - بلوك D')">بلوك D</div>
                      </div>
                    </div>
                  </div>

                  <div class="gov-group">
                    <div class="gov-header" onclick="toggleAccordion(event, this)">
                      <span>خانيونس</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectRegion('خانيونس - البلد', 'خانيونس - البلد')">البلد</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - المواصي', 'خانيونس - المواصي')">المواصي</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - القرارة', 'خانيونس - القرارة')">القرارة</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - العطار', 'خانيونس - العطار')">العطار</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - الياباني', 'خانيونس - الياباني')">الياباني</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - بطن السمين', 'خانيونس - بطن السمين')">بطن السمين</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - حي الأمل', 'خانيونس - حي الأمل')">حي الأمل</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - مدينة حمد', 'خانيونس - مدينة حمد')">مدينة حمد</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - الهلال', 'خانيونس - الهلال')">الهلال</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - المعسكر الغربي', 'خانيونس - المعسكر الغربي')">المعسكر الغربي</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - فش فرش', 'خانيونس - فش فرش')">فش فرش</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - شارع 5', 'خانيونس - شارع 5')">شارع 5</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - شارع النص', 'خانيونس - شارع النص')">شارع النص</div>
                      <div class="area-item" onclick="selectRegion('خانيونس - طبريا', 'خانيونس - طبريا')">طبريا</div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <div class="search-group">
              <div class="custom-multiselect" id="serviceMultiselect">
                <button type="button" class="multiselect-btn" id="multiselectBtn">
                  <i class="fa-solid fa-wifi wifi-icon"></i>
                  <span id="multiselectText">جميع الخدمات</span>
                  <i class="fa-solid fa-chevron-down arrow-icon"></i>
                </button>
                <div class="multiselect-dropdown" id="multiselectDropdown">
                  <label class="multiselect-option"><input type="checkbox" value="فايبر" class="service-checkbox"> إنترنت فايبر</label>
                  <label class="multiselect-option"><input type="checkbox" value="طاقة شمسية" class="service-checkbox"> طاقة شمسية</label>
                  <label class="multiselect-option"><input type="checkbox" value="بيئة هادئة" class="service-checkbox"> بيئة هادئة</label>
                  <label class="multiselect-option"><input type="checkbox" value="مشروبات" class="service-checkbox"> مشروبات وضيافة</label>
                  <label class="multiselect-option"><input type="checkbox" value="غرف اجتماعات" class="service-checkbox"> غرف اجتماعات</label>
                  <label class="multiselect-option"><input type="checkbox" value="تكييف" class="service-checkbox"> تكييف ومراوح</label>
                  <label class="multiselect-option"><input type="checkbox" value="دورات مياه" class="service-checkbox"> دورات مياه</label>
                  <label class="multiselect-option"><input type="checkbox" value="قاعات منفصلة للسيدات" class="service-checkbox"> قاعات منفصلة للسيدات</label>
                  <label class="multiselect-option"><input type="checkbox" value="كاتب فردية" class="service-checkbox"> مكاتب فردية</label>
                  <label class="multiselect-option"><input type="checkbox" value="خيارات دفع مرنة" class="service-checkbox"> خيارات دفع مرنة</label>
                  <label class="multiselect-option"><input type="checkbox" value="دورات تدريبية" class="service-checkbox"> دورات تدريبية</label>
                  <label class="multiselect-option"><input type="checkbox" value="طباعة" class="service-checkbox"> طباعة وتصوير</label>
                </div>
              </div>
            </div>

            <button onclick="filterWorkspaces()" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i> بحث</button>
          </div>
        </div>

        <div class="hero-img-box">
          <img src="https://images.unsplash.com/photo-1527192491265-7e15c55b1ed2?auto=format&fit=crop&w=800&q=80" alt="Workspace">
          <div class="hero-badge">
            <div class="badge-info">
              <h4>حالة الطاقة الحالية</h4>
              <span>طاقة شمسية ومولدات طوارئ</span>
            </div>
            <span class="status-tag">متوفر</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="about" class="about-section">
    <div class="container">
      <div class="about-grid">
        <div class="about-img"><img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80" alt="About"></div>
        <div class="about-text">
          <span class="section-plain-text">عن المنصة</span>
          <h2>سهولة الوصول لمساحة العمل المناسبة</h2>
          <p>تم تطوير هذا الدليل لخدمة الطلاب والعمال عن بُعد، وتسهيل متابعة وتحديد أفضل مساحات العمل بحسب موقعها الجغرافي والخدمات اللوجستية المتوفرة فيها.</p>
          <div class="stats-grid">
            <div class="stat-card"><h3>15+</h3><span>مساحة عمل</span></div>
            <div class="stat-card"><h3>100%</h3><span>دقة بيانات</span></div>
            <div class="stat-card"><h3>24/7</h3><span>تتبع خدمات</span></div>
            <div class="stat-card"><h3>98%</h3><span>رضا المستخدمين</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="spaces" class="spaces-section">
    <div class="container">
      <div class="spaces-header">
        <div>
          <h2>المساحات المتاحة حالياً</h2>
        </div>
        <button onclick="resetFilter()" id="resetBtn" class="reset-btn hidden">إعادة إظهار الكل <i class="fa-solid fa-rotate-right"></i></button>
      </div>

      <div class="spaces-grid" id="spacesGrid">
        <?php if (!empty($approved_spaces)): ?>
         <?php foreach ($approved_spaces as $space): 
          $features_list = [];
          
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
          
          $features_string = implode(',', $features_list);
          
          $total_features = count($features_list);
          $displayed_features = array_slice($features_list, 0, 3);
          $extra_count = $total_features - 3;
      ?>
            <div class="space-card" data-location="<?php echo htmlspecialchars($space['location']); ?>" data-services="<?php echo htmlspecialchars($features_string); ?>">
              <div>
                <div class="card-img">
                  <img src="<?php echo htmlspecialchars($space['image']); ?>" alt="Space">
                  <span class="card-rating"><i class="fa-solid fa-star"></i> 5.0</span>
                </div>
                <div class="card-body">
                  <h3><?php echo htmlspecialchars($space['name']); ?></h3>
                  <p><i class="fa-solid fa-location-dot" style="color: #b45309;"></i> <?php echo htmlspecialchars($space['location']); ?></p>
                  
                  <div class="card-tags">
                    <?php foreach ($displayed_features as $feat): ?>
                      <span><i class="fa-solid <?php echo getFeatureIcon($feat); ?>"></i> <?php echo htmlspecialchars($feat); ?></span>
                    <?php endforeach; ?>
                    <?php if ($extra_count > 0): ?>
                      <span class="more-tag">+<?php echo $extra_count; ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <div class="price-box"><span>السعر بالساعة</span><strong>₪<?php echo htmlspecialchars($space['price_per_hour']); ?></strong></div>
                <a href="workspace-details.php?id=<?php echo $space['id']; ?>" class="details-btn">التفاصيل</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-results">
            <i class="fa-solid fa-folder-open" style="font-size: 36px; color: #b45309; margin-bottom: 12px; display: block;"></i>
            <p style="color: #64748b; font-size: 14px;">لا توجد مساحات مقبولة معروضة حالياً. بانتظار قبول طلبات المساحات المضافة حديثاً من لوحة التحكم.</p>
          </div>
        <?php endif; ?>
        
        <div id="noResults" class="no-results hidden">
          <i class="fa-solid fa-magnifying-glass" style="font-size: 32px; color: #b45309; margin-bottom: 10px;"></i>
          <p style="color: #475569; font-size: 14px; margin-bottom: 15px;">لم نجد مساحات تطابق خيارات البحث الحالية.</p>
          <button onclick="resetFilter()" class="btn-primary"><i class="fa-solid fa-rotate"></i> عرض كافة المساحات</button>
        </div>
      </div>
    </div>
  </section>

  <section id="features" class="features-section">
    <div class="container">
      <h2>لماذا نعتمد هذا الدليل؟</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-magnifying-glass-location"></i></div>
          <h3>بحث سريع</h3>
          <p>تصفح وفلترة المساحات بحسب المنطقة والخدمات بدقة عالية وسرعة فائقة.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>بيانات موثوقة</h3>
          <p>تأكيد مباشر لمعلومات المساحات والخدمات اللوجستية بدون أي أخطاء.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
          <h3>متابعة الطاقة</h3>
          <p>الاستعلام المباشر عن توفر شبكة الفايبر ومولدات الطاقة الشمسية والطوارئ.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-tags"></i></div>
          <h3>أسعار ملائمة</h3>
          <p>توضيح كامل لأسعار الطلاب المخفضة وأسعار المستقلين بكل شفافية.</p>
        </div>
      </div>
    </div>
  </section>

  <footer id="footer">
    <div class="container">
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
            <li><a href="#hero">الرئيسية</a></li>
            <li><a href="#about">عن المنصة</a></li>
            <li><a href="#spaces">دليل المساحات</a></li>
            <li><a href="#features">لماذا نعتمدنا</a></li>
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
      <div class="footer-bottom">
        <div>جميع الحقوق محفوظة © <span id="year"></span> دليل مساحات 🇵🇸</div>
        <div class="footer-bottom-links"><a href="#">الشروط والأحكام</a><span>•</span><a href="#">سياسة الخصوصية</a></div>
      </div>
    </div>
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    const locationTrigger = document.getElementById('locationTrigger');
    const locationMenu = document.getElementById('locationMenu');

    locationTrigger.addEventListener('click', (e) => {
      e.stopPropagation();
      locationMenu.classList.toggle('open');
      locationTrigger.classList.toggle('active');
    });

    function toggleAccordion(e, headerElement) {
      e.stopPropagation();
      headerElement.classList.toggle('expanded');
      const nextList = headerElement.nextElementSibling;
      if (nextList) {
        nextList.classList.toggle('show');
      }
    }

    function selectRegion(value, label) {
      document.getElementById('selectedRegionInput').value = value;
      document.getElementById('locationDisplayText').textContent = label;
      locationMenu.classList.remove('open');
      locationTrigger.classList.remove('active');
    }

    const multiselectBtn = document.getElementById('multiselectBtn');
    const multiselectDropdown = document.getElementById('multiselectDropdown');
    const multiselectText = document.getElementById('multiselectText');
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');

    multiselectBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      multiselectDropdown.classList.toggle('show');
      multiselectBtn.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
      if (!document.getElementById('locationDropdown').contains(e.target)) {
        locationMenu.classList.remove('open');
        locationTrigger.classList.remove('active');
      }
      if (!document.getElementById('serviceMultiselect').contains(e.target)) {
        multiselectDropdown.classList.remove('show');
        multiselectBtn.classList.remove('active');
      }
    });

    serviceCheckboxes.forEach(cb => {
      cb.addEventListener('change', () => {
        const checked = Array.from(serviceCheckboxes)
          .filter(item => item.checked)
          .map(item => item.value);
        
        if (checked.length === 0) {
          multiselectText.textContent = 'جميع الخدمات';
        } else if (checked.length <= 2) {
          multiselectText.textContent = checked.join('، ');
        } else {
          multiselectText.textContent = checked.slice(0, 2).join('، ') + ' (+' + (checked.length - 2) + ')';
        }
      });
    });

    function filterWorkspaces() {
      const selectedRegion = document.getElementById('selectedRegionInput').value;
      const selectedServices = Array.from(serviceCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
      
      const cards = document.querySelectorAll('.space-card');
      const noResults = document.getElementById('noResults');
      const resetBtn = document.getElementById('resetBtn');
      let visibleCount = 0;

      cards.forEach(card => {
        const cardLocation = card.getAttribute('data-location');
        const cardServices = card.getAttribute('data-services') || '';
        
        const matchRegion = (selectedRegion === 'all' || cardLocation.includes(selectedRegion));
        
        let matchService = true;
        if (selectedServices.length > 0) {
          matchService = selectedServices.every(service => cardServices.includes(service));
        }

        if (matchRegion && matchService) {
          card.classList.remove('hidden');
          visibleCount++;
        } else {
          card.classList.add('hidden');
        }
      });

      if (visibleCount === 0) {
        noResults.classList.remove('hidden');
      } else {
        noResults.classList.add('hidden');
      }

      if (selectedRegion !== 'all' || selectedServices.length > 0) {
        resetBtn.classList.remove('hidden');
      } else {
        resetBtn.classList.add('hidden');
      }
      
      document.getElementById('spaces').scrollIntoView({ behavior: 'smooth' });
    }

    function resetFilter() {
      document.getElementById('selectedRegionInput').value = 'all';
      document.getElementById('locationDisplayText').textContent = 'اختر المنطقة...';
      serviceCheckboxes.forEach(cb => cb.checked = false);
      multiselectText.textContent = 'جميع الخدمات';
      filterWorkspaces();
    }
  </script>
</body>
</html>