<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM workspaces WHERE id = ?");
$stmt->execute([$id]);
$space = $stmt->fetch();

if (!$space) {
    header("Location: admin-dashboard.php");
    exit();
}

// استخراج البيانات الحالية للأسعار وساعات العمل والمسؤول من الوصف
$open_t = ""; $close_t = ""; $student_p = ""; $freelancer_p = ""; $m_name = ""; $m_phone = "";
if(preg_match('/ساعات العمل:\s*من\s*([^\s]+)\s*إلى\s*([^|]+)/u', $space['description'], $m)) { $open_t = trim($m[1]); $close_t = trim($m[2]); }
if(preg_match('/سعر الطالب:\s*₪([0-9.]+)/u', $space['description'], $m)) { $student_p = $m[1]; }
if(preg_match('/سعر المستقل:\s*₪([0-9.]+)/u', $space['description'], $m)) { $freelancer_p = $m[1]; }
if(preg_match('/المسؤول:\s*([^|-]+)/u', $space['description'], $m)) { $m_name = trim($m[1]); }
if(preg_match('/هاتف:\s*([0-9]+)/u', $space['description'], $m)) { $m_phone = trim($m[1]); }

// استخراج المزايا الحالية المخزنة لتحديد المربعات المختارة مسبقاً
$current_features = [];
if (preg_match('/المزايا:\s*(.+)$/u', $space['description'], $m)) {
    $current_features = array_map('trim', preg_split('/[،,]/u', $m[1]));
}

$msg = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $student_price = trim($_POST['student_price']);
    $freelancer_price = trim($_POST['freelancer_price']);
    $manager_name = trim($_POST['manager_name']);
    $phone = trim($_POST['phone']);
    $open_time = trim($_POST['open_time']);
    $close_time = trim($_POST['close_time']);
    $image_path = $space['image'];

    // جمع المزايا والخدمات الجديدة المختارة
    $features = isset($_POST['features']) ? $_POST['features'] : [];
    $features_text = !empty($features) ? implode('، ', $features) : 'خدمات متكاملة';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = $_FILES['image']['name'];
        $target = "uploads/" . basename($image_name);
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }

    $description = "ساعات العمل: من " . $open_time . " إلى " . $close_time . " | سعر الطالب: ₪" . $student_price . " | سعر المستقل: ₪" . $freelancer_price . " - المسؤول: " . $manager_name . " - هاتف: " . $phone . " | المزايا: " . $features_text;

    $update_stmt = $pdo->prepare("UPDATE workspaces SET name = ?, location = ?, price_per_hour = ?, description = ?, image = ? WHERE id = ?");
    if ($update_stmt->execute([$name, $location, $student_price, $description, $image_path, $id])) {
        header("Location: admin-dashboard.php?msg=updated");
        exit();
    } else {
        $error = "حدث خطأ أثناء حفظ التعديلات!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تعديل بيانات المساحة | مساحات</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
    body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
    .main-wrapper { max-width: 650px; width: 100%; }
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; padding: 35px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
    
    .back-top-right { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #b45309; background: #fff5eb; border: 1px solid #fed7aa; padding: 6px 14px; border-radius: 8px; text-decoration: none; transition: 0.3s; margin-bottom: 15px; }
    .back-top-right:hover { background: #b45309; color: white; }
    
    .header-box { text-align: center; margin-bottom: 25px; }
    .icon-circle { width: 44px; height: 44px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px; }
    .header-box h1 { font-size: 20px; font-weight: 800; color: #0f172a; }

    .section-title { font-size: 13px; font-weight: 800; color: #0f172a; margin: 20px 0 12px 0; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; }
    .section-title i { color: #b45309; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .form-group { margin-bottom: 15px; text-align: right; }
    label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #334155; }
    input, select { width: 100%; padding: 11px 14px; background: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; border-radius: 10px; font-size: 13px; outline: none; transition: 0.3s; }
    input:focus { border-color: #b45309; background: #ffffff; }

    .checkbox-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; }
    .checkbox-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; transition: 0.3s; }
    .checkbox-item:hover { border-color: #b45309; background: #fffdfb; }
    .checkbox-item input[type="checkbox"] { width: auto; accent-color: #b45309; cursor: pointer; }

    .curr-img-preview { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; margin-top: 8px; border: 1px solid #cbd5e1; }
    .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #b45309, #78350f); color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 15px; }
    .btn-submit:hover { background: linear-gradient(135deg, #d97706, #b45309); }
  </style>
</head>
<body>

  <div class="main-wrapper">
    <div class="form-card">
      <a href="admin-dashboard.php" class="back-top-right"><i class="fa-solid fa-arrow-right"></i> العودة لوحة التحكم</a>

      <div class="header-box">
        <div class="icon-circle"><i class="fa-solid fa-pen-to-square"></i></div>
        <h1>تعديل بيانات مساحة العمل والأسعار</h1>
      </div>

      <form action="" method="POST" enctype="multipart/form-data">
        <div class="section-title"><i class="fa-solid fa-building"></i> البيانات الأساسية</div>
        <div class="grid-2">
          <div class="form-group">
            <label>اسم المساحة</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($space['name']); ?>" required>
          </div>
          <div class="form-group">
            <label>الموقع / المنطقة</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($space['location']); ?>" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>وقت الفتح</label>
            <input type="time" name="open_time" value="<?php echo htmlspecialchars($open_t); ?>" required>
          </div>
          <div class="form-group">
            <label>وقت الإغلاق</label>
            <input type="time" name="close_time" value="<?php echo htmlspecialchars($close_t); ?>" required>
          </div>
        </div>

        <div class="section-title"><i class="fa-solid fa-money-bill-wave"></i> تعديل الأسعار (كل سعر على حدة)</div>
        <div class="grid-2">
          <div class="form-group">
            <label>سعر الساعة للطالب (₪)</label>
            <input type="number" step="0.5" name="student_price" value="<?php echo htmlspecialchars($student_p); ?>" required>
          </div>
          <div class="form-group">
            <label>سعر الساعة للمستقل (₪)</label>
            <input type="number" step="0.5" name="freelancer_price" value="<?php echo htmlspecialchars($freelancer_p); ?>" required>
          </div>
        </div>

        <div class="section-title"><i class="fa-solid fa-address-book"></i> بيانات المسؤول والتواصل</div>
        <div class="grid-2">
          <div class="form-group">
            <label>اسم المسؤول</label>
            <input type="text" name="manager_name" value="<?php echo htmlspecialchars($m_name); ?>" required>
          </div>
          <div class="form-group">
            <label>رقم الهاتف</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($m_phone); ?>" required>
          </div>
        </div>

        <div class="section-title"><i class="fa-solid fa-list-check"></i> تعديل الخدمات والتسهيلات</div>
        <div class="checkbox-grid">
          <?php 
            $all_services = [
              'فايبر' => 'إنترنت فايبر عالي السرعة',
              'طاقة شمسية' => 'طاقة شمسية / كهرباء دائمة',
              'بيئة هادئة' => 'بيئة عمل هادئة ومريحة',
              'مشروبات' => 'بوفيه ومشروبات ضيافة',
              'غرف اجتماعات' => 'غرف اجتماعات خاصة',
              'تكييف' => 'نظام تكييف ومراوح',
              'دورات مياه' => 'دورات مياه نظيفة ومجهزة',
              'قاعات منفصلة للسيدات' => 'قاعات منفصلة للسيدات',
              'كاتب فردية' => 'مكاتب فردية مستقلة',
              'خيارات دفع مرنة' => 'خيارات دفع مرنة (نقدي/بنكي)',
              'دورات تدريبية' => 'دورات تدريبية بعدة مجالات',
              'طباعة' => 'خدمات طباعة وتصوير'
            ];
            foreach ($all_services as $key => $label):
              $isChecked = in_array($key, $current_features) ? 'checked' : '';
          ?>
            <label class="checkbox-item"><input type="checkbox" name="features[]" value="<?php echo $key; ?>" <?php echo $isChecked; ?>> <?php echo $label; ?></label>
          <?php endforeach; ?>
        </div>

        <div class="section-title"><i class="fa-solid fa-image"></i> صورة المساحة</div>
        <div class="form-group">
          <div><img src="<?php echo htmlspecialchars($space['image']); ?>" class="curr-img-preview" alt="Preview"></div>
          <label style="margin-top: 8px;">تغيير الصورة (اختياري)</label>
          <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> حفظ كافة التعديلات</button>
      </form>
    </div>
  </div>

</body>
</html>