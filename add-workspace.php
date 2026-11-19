<?php
include 'db.php';
session_start();

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $student_price = trim($_POST['student_price']);
    $freelancer_price = trim($_POST['freelancer_price']);
    $manager_name = trim($_POST['manager_name']);
    $phone = trim($_POST['phone']);
    $open_time = trim($_POST['open_time']);
    $close_time = trim($_POST['close_time']);
    
    // التحقق من أن رقم الجوال يتكون من 10 أرقام بالضبط
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "خطأ: يجب أن يتكون رقم الجوال من 10 أرقام فقط.";
    } else {
        // التحقق من عدم وجود مساحة بنفس الاسم مسبقاً لمنع التكرار
        $checkSpace = $pdo->prepare("SELECT id FROM workspaces WHERE name = ?");
        $checkSpace->execute([$name]);
        
        if ($checkSpace->rowCount() > 0) {
            $error = "خطأ: مساحة العمل هذه موجودة مسبقاً في النظام ولا يمكن تكرار إضافتها!";
        } else {
            $owner_email = trim($_POST['owner_email']);
            $raw_password = $_POST['owner_password'];
            
            $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

            // جمع المزايا والخدمات المختارة
            $features = isset($_POST['features']) ? $_POST['features'] : [];
            $features_text = !empty($features) ? implode('، ', $features) : 'خدمات متكاملة';

            $description = "ساعات العمل: من " . $open_time . " إلى " . $close_time . " | سعر الطالب: ₪" . $student_price . " | سعر المستقل: ₪" . $freelancer_price . " - المسؤول: " . $manager_name . " - هاتف: " . $phone . " | المزايا: " . $features_text;
            $status = 'pending';

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = $_FILES['image']['name'];
                $target = "uploads/" . basename($image);
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                move_uploaded_file($_FILES['image']['tmp_name'], $target);
            } else {
                $target = "https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=600&q=80";
            }

            $stmt = $pdo->prepare("INSERT INTO workspaces (name, description, price_per_hour, location, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $student_price, $location, $target, $status]);

            $checkUser = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
            $checkUser->execute([$owner_email]);
            if ($checkUser->rowCount() == 0) {
                $addUser = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                $addUser->execute([$manager_name, $owner_email, $hashed_password]);
            }

            $msg = "تم تقديم الطلب وإنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول بريدك وكلمة المرور من صفحة تسجيل الدخول.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إضافة مساحة عمل جديدة | مساحات</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
    body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
    .main-wrapper { max-width: 650px; width: 100%; }
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 35px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
    .header-box { text-align: center; margin-bottom: 30px; position: relative; }
    .back-top-right { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #b45309; background: #fff5eb; border: 1px solid #fed7aa; padding: 6px 14px; border-radius: 8px; text-decoration: none; transition: 0.3s; margin-bottom: 15px; }
    .back-top-right:hover { background: #b45309; color: white; border-color: #b45309; }
    .top-right-container { text-align: right; width: 100%; }
    .icon-circle { width: 44px; height: 44px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(180, 83, 9, 0.25); }
    .header-box h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
    .header-box p { font-size: 13px; color: #64748b; }
    .section-title { font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
    .section-title i { color: #b45309; font-size: 14px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 18px; }
    .form-group { margin-bottom: 18px; text-align: right; }
    .form-group.no-margin { margin-bottom: 0; }
    label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155; }
    .req { color: #dc2626; margin-right: 2px; }
    input, select, textarea { width: 100%; padding: 11px 14px; background: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; border-radius: 10px; font-size: 13px; outline: none; transition: 0.3s; }
    input:focus, select:focus, textarea:focus { border-color: #b45309; background: #ffffff; box-shadow: 0 0 0 3px rgba(180, 83, 9, 0.08); }
    
    /* أنماط القائمة المتدرجة للمناطق */
    .location-dropdown-wrapper { position: relative; width: 100%; font-family: 'Tajawal', sans-serif; direction: rtl; }
    .location-select-trigger { width: 100%; padding: 11px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; font-size: 13px; font-weight: 400; color: #1e293b; transition: 0.2s; }
    .location-select-trigger:hover, .location-select-trigger.active { border-color: #b45309; background: #ffffff; box-shadow: 0 0 0 3px rgba(180, 83, 9, 0.08); }
    .location-menu { display: none; position: absolute; top: calc(100% + 6px); right: 0; width: 100%; max-height: 320px; overflow-y: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12); z-index: 9999; padding: 10px; }
    .location-menu.open { display: block; }
    .gov-group { margin-bottom: 6px; }
    .gov-header { width: 100%; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 800; color: #0f172a; cursor: pointer; transition: 0.2s; }
    .gov-header.expanded { background: #b45309; color: #ffffff; border-color: #b45309; }
    .gov-header i { transition: transform 0.25s ease; font-size: 11px; }
    .gov-header.expanded i { transform: rotate(180deg); }
    .sub-list { display: none; padding: 6px 10px 4px 4px; border-right: 2px solid #fed7aa; margin-right: 10px; margin-top: 5px; }
    .sub-list.show { display: block; }
    .city-header { padding: 8px 10px; background: #fff5eb; color: #92400e; border-radius: 6px; font-size: 12px; font-weight: 800; display: flex; justify-content: space-between; align-items: center; cursor: pointer; margin-bottom: 4px; transition: 0.2s; }
    .city-header.expanded { background: #92400e; color: #ffffff; }
    .city-header i { transition: transform 0.25s ease; font-size: 10px; }
    .city-header.expanded i { transform: rotate(180deg); }
    .area-item { padding: 7px 12px; font-size: 12px; font-weight: 600; color: #334155; border-radius: 6px; cursor: pointer; transition: 0.15s; display: flex; align-items: center; justify-content: space-between; }
    .area-item:hover { background: #fff5eb; color: #b45309; }

    .checkbox-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 25px; }
    .checkbox-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; transition: 0.3s; }
    .checkbox-item:hover { border-color: #b45309; background: #fffdfb; }
    .checkbox-item input[type="checkbox"] { width: auto; accent-color: #b45309; cursor: pointer; }
    .upload-box { border: 2px dashed #cbd5e1; padding: 25px; text-align: center; border-radius: 12px; background: #f8fafc; cursor: pointer; transition: 0.3s; margin-bottom: 25px; position: relative; }
    .upload-box:hover { border-color: #b45309; background: #fffdfb; }
    .upload-box > i { font-size: 30px; color: #b45309; margin-bottom: 8px; }
    .upload-box p { font-size: 12px; color: #64748b; }
    .upload-box input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
    
    /* تنسيق أنيق وصغير لزر حذف الصورة */
    .remove-image-btn { display: none; align-items: center; justify-content: center; gap: 6px; margin: 12px auto 0; background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; position: relative; z-index: 3; }
    .remove-image-btn i { font-size: 11px; margin: 0; color: inherit; }
    .remove-image-btn:hover { background: #e11d48; color: #ffffff; border-color: #e11d48; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.2); }

    .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 12px rgba(180, 83, 9, 0.2); }
    .btn-submit:hover { background: linear-gradient(135deg, #d97706, #b45309); }
    .alert-success { color: #15803d; background: #dcfce7; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 13px; border: 1px solid #bbf7d0; }
    .alert-error { color: #dc2626; background: #fee2e2; padding: 12px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 13px; border: 1px solid #fecaca; }
    @media (max-width: 600px) { .grid-2, .checkbox-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

  <div class="main-wrapper">
    <div class="form-card">
      <div class="top-right-container">
        <a href="index.php" class="back-top-right"><i class="fa-solid fa-arrow-right"></i> العودة للرئيسية</a>
      </div>

      <div class="header-box">
        <div class="icon-circle"><i class="fa-solid fa-plus"></i></div>
        <h1>إضافة مساحة عمل جديدة</h1>
        <p>قم بتعبئة البيانات لإدراج المساحة الخاصة بك</p>
      </div>

      <?php if($msg): ?><div class="alert-success"><?php echo $msg; ?></div><?php endif; ?>
      <?php if($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

      <form action="" method="POST" enctype="multipart/form-data">
        <div class="section-title"><i class="fa-solid fa-building"></i> البيانات الأساسية</div>
        <div class="grid-2">
          <div class="form-group no-margin">
            <label>اسم مساحة العمل <span class="req">*</span></label>
            <input type="text" name="name" required placeholder="أدخل اسم مساحة العمل">
          </div>
          <div class="form-group no-margin">
            <label>المحافظة / المنطقة التفصيلية <span class="req">*</span></label>
            
            <div class="location-dropdown-wrapper" id="locationDropdown">
              <input type="hidden" name="location" id="selectedLocationInput" value="" required>

              <div class="location-select-trigger" id="locationTrigger">
                <span id="locationDisplayText"><i class="fa-solid fa-location-dot" style="color: #b45309; margin-left: 6px;"></i> اختر المنطقة التفصيلية...</span>
                <i class="fa-solid fa-chevron-down" style="font-size: 11px; color: #64748b;"></i>
              </div>

              <div class="location-menu" id="locationMenu">
                <div class="gov-group">
                  <div class="gov-header" onclick="toggleGovAccordion(event, this)">
                    <span>شمال غزة</span>
                    <i class="fa-solid fa-chevron-down"></i>
                  </div>
                  <div class="sub-list">
                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>جباليا</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('شمال غزة - جباليا - جباليا البلد', 'شمال غزة - جباليا - جباليا البلد')">جباليا البلد</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - جباليا - جباليا الشرقية', 'شمال غزة - جباليا - جباليا الشرقية')">جباليا الشرقية</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - جباليا - جباليا الغربية', 'شمال غزة - جباليا - جباليا الغربية')">جباليا الغربية</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - جباليا - أم النصر', 'شمال غزة - جباليا - أم النصر')">أم النصر</div>
                    </div>

                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>بيت حانون</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('شمال غزة - بيت حانون - بيت حانون البلد', 'شمال غزة - بيت حانون - بيت حانون البلد')">بيت حانون البلد</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - بيت حانون - المنطقة الشرقية', 'شمال غزة - بيت حانون - المنطقة الشرقية')">المنطقة الشرقية</div>
                    </div>

                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>بيت لاهيا</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('شمال غزة - بيت لاهيا - بيت لاهيا البلد', 'شمال غزة - بيت لاهيا - بيت لاهيا البلد')">بيت لاهيا البلد</div>
                    </div>

                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>غزة</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الزهراء', 'شمال غزة - غزة - الزهراء')">الزهراء</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - المغراقة', 'شمال غزة - غزة - المغراقة')">المغراقة</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - مخيم الشاطئ', 'شمال غزة - غزة - مخيم الشاطئ')">مخيم الشاطئ</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الرمال الشمالي', 'شمال غزة - غزة - الرمال الشمالي')">الرمال الشمالي</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الرمال الجنوبي', 'شمال غزة - غزة - الرمال الجنوبي')">الرمال الجنوبي</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - تل الهوا', 'شمال غزة - غزة - تل الهوا')">تل الهوا</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - النصر', 'شمال غزة - غزة - النصر')">النصر</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الشيخ رضوان', 'شمال غزة - غزة - الشيخ رضوان')">الشيخ رضوان</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الشجاعية', 'شمال غزة - غزة - الشجاعية')">الشجاعية</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - التفاح', 'شمال غزة - غزة - التفاح')">التفاح</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الدرج', 'شمال غزة - غزة - الدرج')">الدرج</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - البلدة القديمة', 'شمال غزة - غزة - البلدة القديمة')">البلدة القديمة</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الزيتون', 'شمال غزة - غزة - الزيتون')">الزيتون</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الصبرة', 'شمال غزة - غزة - الصبرة')">الصبرة</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - النفق', 'شمال غزة - غزة - النفق')">النفق</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الصحابة', 'شمال غزة - غزة - الصحابة')">الصحابة</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - الصناعة', 'شمال غزة - غزة - الصناعة')">الصناعة</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - اليرموك', 'شمال غزة - غزة - اليرموك')">اليرموك</div>
                      <div class="area-item" onclick="selectLocation('شمال غزة - غزة - السرايا', 'شمال غزة - غزة - السرايا')">السرايا</div>
                    </div>
                  </div>
                </div>

                <div class="gov-group">
                  <div class="gov-header" onclick="toggleGovAccordion(event, this)">
                    <span>الوسطى</span>
                    <i class="fa-solid fa-chevron-down"></i>
                  </div>
                  <div class="sub-list">
                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>دير البلح</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - البلد', 'الوسطى - دير البلح - البلد')">البلد</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - البركة', 'الوسطى - دير البلح - البركة')">البركة</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - السلام', 'الوسطى - دير البلح - السلام')">السلام</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - المدفع', 'الوسطى - دير البلح - المدفع')">المدفع</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - رمزون الدير', 'الوسطى - دير البلح - رمزون الدير')">رمزون الدير</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - النخيل', 'الوسطى - دير البلح - النخيل')">النخيل</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - التحلية', 'الوسطى - دير البلح - التحلية')">التحلية</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - دوار ابو سمرة', 'الوسطى - دير البلح - دوار ابو سمرة')">دوار ابو سمرة</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - شهداء الاقصى', 'الوسطى - دير البلح - شهداء الاقصى')">شهداء الاقصى</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - المشاعلة', 'الوسطى - دير البلح - المشاعلة')">المشاعلة</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - الحكر', 'الوسطى - دير البلح - الحكر')">الحكر</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - دير البلح - ابو عريف', 'الوسطى - دير البلح - ابو عريف')">ابو عريف</div>
                    </div>

                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>النصيرات</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - المخيم القديم', 'الوسطى - النصيرات - المخيم القديم')">المخيم القديم</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - المخيم الجديد', 'الوسطى - النصيرات - المخيم الجديد')">المخيم الجديد</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - الزهور', 'الوسطى - النصيرات - الزهور')">الزهور</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - الدعوة', 'الوسطى - النصيرات - الدعوة')">الدعوة</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - الحساينة', 'الوسطى - النصيرات - الحساينة')">الحساينة</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - دوار الشهداء', 'الوسطى - النصيرات - دوار الشهداء')">دوار الشهداء</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - النصيرات - السوق', 'الوسطى - النصيرات - السوق')">السوق</div>
                    </div>

                    <div class="city-header" onclick="toggleCityAccordion(event, this)">
                      <span>المغازي</span>
                      <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="sub-list">
                      <div class="area-item" onclick="selectLocation('الوسطى - المغازي - بلوك A', 'الوسطى - المغازي - بلوك A')">بلوك A</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - المغازي - بلوك B', 'الوسطى - المغازي - بلوك B')">بلوك B</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - المغازي - بلوك C', 'الوسطى - المغازي - بلوك C')">بلوك C</div>
                      <div class="area-item" onclick="selectLocation('الوسطى - المغازي - بلوك D', 'الوسطى - المغازي - بلوك D')">بلوك D</div>
                    </div>
                  </div>
                </div>

                <div class="gov-group">
                  <div class="gov-header" onclick="toggleGovAccordion(event, this)">
                    <span>خانيونس</span>
                    <i class="fa-solid fa-chevron-down"></i>
                  </div>
                  <div class="sub-list">
                    <div class="area-item" onclick="selectLocation('خانيونس - البلد', 'خانيونس - البلد')">البلد</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - المواصي', 'خانيونس - المواصي')">المواصي</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - القرارة', 'خانيونس - القرارة')">القرارة</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - العطار', 'خانيونس - العطار')">العطار</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - الياباني', 'خانيونس - الياباني')">الياباني</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - بطن السمين', 'خانيونس - بطن السمين')">بطن السمين</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - حي الأمل', 'خانيونس - حي الأمل')">حي الأمل</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - مدينة حمد', 'خانيونس - مدينة حمد')">مدينة حمد</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - الهلال', 'خانيونس - الهلال')">الهلال</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - المعسكر الغربي', 'خانيونس - المعسكر الغربي')">المعسكر الغربي</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - فش فرش', 'خانيونس - فش فرش')">فش فرش</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - شارع 5', 'خانيونس - شارع 5')">شارع 5</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - شارع النص', 'خانيونس - شارع النص')">شارع النص</div>
                    <div class="area-item" onclick="selectLocation('خانيونس - طبريا', 'خانيونس - طبريا')">طبريا</div>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>وقت الفتح <span class="req">*</span></label>
            <input type="time" name="open_time" required>
          </div>
          <div class="form-group">
            <label>وقت الإغلاق <span class="req">*</span></label>
            <input type="time" name="close_time" required>
          </div>
        </div>

        <div class="section-title"><i class="fa-solid fa-address-book"></i> بيانات الإدارة وحساب الدخول</div>
        <div class="grid-2">
          <div class="form-group">
            <label>اسم مالك / مدير المكان <span class="req">*</span></label>
            <input type="text" name="manager_name" required placeholder="أدخل الاسم الكامل">
          </div>
          <div class="form-group">
            <label>رقم التواصل <span class="req">*</span></label>
            <input type="tel" name="phone" required placeholder="0590000000" pattern="[0-9]{10}" maxlength="10" title="يجب أن يتكون رقم الجوال من 10 أرقام">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>البريد الإلكتروني (للدخول) <span class="req">*</span></label>
            <input type="email" name="owner_email" required placeholder="owner@spaces.ps">
          </div>
          <div class="form-group">
            <label>كلمة المرور للوحة التحكم <span class="req">*</span></label>
            <input type="password" name="owner_password" required placeholder="أدخل كلمة مرور قوية">
          </div>
        </div>

        <div class="section-title"><i class="fa-solid fa-money-bill-wave"></i> تكلفة الساعة (بالشيكل ₪)</div>
        <div class="grid-2">
          <div class="form-group">
            <label>سعر الساعة للطالب <span class="req">*</span></label>
            <input type="number" name="student_price" step="0.5" min="0" required placeholder="0.0">
          </div>
          <div class="form-group">
            <label>سعر الساعة للمفرد/المستقل <span class="req">*</span></label>
            <input type="number" name="freelancer_price" step="0.5" min="0" required placeholder="0.0">
          </div>
        </div>

        <div class="section-title"><i class="fa-solid fa-list-check"></i> الخدمات والتسهيلات</div>
        <div class="checkbox-grid">
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="فايبر" checked> إنترنت فايبر عالي السرعة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="طاقة شمسية" checked> طاقة شمسية / كهرباء دائمة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="بيئة هادئة" checked> بيئة عمل هادئة ومريحة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="مشروبات"> بوفيه ومشروبات ضيافة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="غرف اجتماعات"> غرف اجتماعات خاصة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="تكييف"> نظام تكييف ومراوح</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="دورات مياه"> دورات مياه نظيفة ومجهزة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="قاعات منفصلة للسيدات"> قاعات منفصلة للسيدات</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="كاتب فردية"> مكاتب فردية مستقلة</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="خيارات دفع مرنة (نقدي/بنكي)"> خيارات دفع مرنة (نقدي/بنكي)</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="دورات تدريبيةعدة مجالات"> دورات تدريبية بعدة مجالات</label>
          <label class="checkbox-item"><input type="checkbox" name="features[]" value="طباعة"> خدمات طباعة وتصوير</label>
        </div>

        <div class="section-title"><i class="fa-solid fa-images"></i> صور المساحة</div>
        <div class="upload-box" id="uploadBox">
          <i class="fa-solid fa-cloud-arrow-up" id="upload-icon"></i>
          <p id="upload-text"><strong>اضغط هنا أو اسحب الصور لرفعها</strong></p>
          <input type="file" name="image" id="imageInput" accept="image/*" onchange="handleImageSelection(this)">
          <button type="button" class="remove-image-btn" id="removeImgBtn" onclick="removeSelectedImage(event)"><i class="fa-solid fa-trash-can"></i> حذف الصورة</button>
        </div>

        <button type="submit" class="btn-submit">تأكيد وتسجيل البيانات</button>
      </form>
    </div>
  </div>

  <script>
    const locTrigger = document.getElementById('locationTrigger');
    const locMenu = document.getElementById('locationMenu');

    locTrigger.addEventListener('click', function(e) {
      e.stopPropagation();
      locMenu.classList.toggle('open');
      locTrigger.classList.toggle('active');
    });

    // إغلاق المحافظات الأخرى عند فتح محافظة جديدة
    function toggleGovAccordion(e, headerElement) {
      e.stopPropagation();
      const allGovHeaders = document.querySelectorAll('.gov-header');
      
      allGovHeaders.forEach(header => {
        if (header !== headerElement) {
          header.classList.remove('expanded');
          const sub = header.nextElementSibling;
          if (sub) {
            sub.classList.remove('show');
            // إغلاق أي مدن داخلية في المحافظات المغلقة أيضاً
            sub.querySelectorAll('.city-header').ch?.forEach?.(c => c.classList.remove('expanded'));
            sub.querySelectorAll('.sub-list').forEach(s => s.classList.remove('show'));
          }
        }
      });

      headerElement.classList.toggle('expanded');
      const nextList = headerElement.nextElementSibling;
      if (nextList) {
        nextList.classList.toggle('show');
      }
    }

    // إغلاق المدن الأخرى داخل نفس المحافظة عند فتح مدينة جديدة
    function toggleCityAccordion(e, headerElement) {
      e.stopPropagation();
      const parentSubList = headerElement.closest('.sub-list');
      if (parentSubList) {
        const siblingCityHeaders = parentSubList.querySelectorAll(':scope > .city-header');
        siblingCityHeaders.forEach(city => {
          if (city !== headerElement) {
            city.classList.remove('expanded');
            const sub = city.nextElementSibling;
            if (sub) sub.classList.remove('show');
          }
        });
      }

      headerElement.classList.toggle('expanded');
      const nextList = headerElement.nextElementSibling;
      if (nextList) {
        nextList.classList.toggle('show');
      }
    }

    function selectLocation(value, label) {
      document.getElementById('selectedLocationInput').value = value;
      document.getElementById('locationDisplayText').innerHTML =
        `<i class="fa-solid fa-location-dot" style="color: #b45309; margin-left: 6px;"></i> ${label}`;
      locMenu.classList.remove('open');
      locTrigger.classList.remove('active');
    }

    document.addEventListener('click', function(e) {
      const wrapper = document.getElementById('locationDropdown');
      if (wrapper && !wrapper.contains(e.target)) {
        locMenu.classList.remove('open');
        locTrigger.classList.remove('active');
      }
    });

    // دوال إدارة وتفريغ الصورة المضافة
    function handleImageSelection(input) {
      if (input.files && input.files[0]) {
        document.getElementById('upload-text').innerHTML = '<strong>تم اختيار الملف: ' + input.files[0].name + '</strong>';
        document.getElementById('removeImgBtn').style.display = 'inline-flex';
      }
    }

    function removeSelectedImage(e) {
      e.stopPropagation();
      const imageInput = document.getElementById('imageInput');
      imageInput.value = ''; // تفريغ حقل الملفات
      document.getElementById('upload-text').innerHTML = '<strong>اضغط هنا أو اسحب الصور لرفعها</strong>';
      document.getElementById('removeImgBtn').style.display = 'none';
    }
  </script>

</body>
</html>