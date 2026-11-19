<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$owner_name = $_SESSION['admin_name'] ?? 'مالك المساحة';

// دالة تحويل الوقت من 24 ساعة إلى 12 ساعة داخل نص الوصف
function formatDescriptionTime($desc) {
    return preg_replace_callback('/\b([01]?[0-9]|2[0-3]):([0-5][0-9])\b/', function($matches) {
        $hour = intval($matches[1]);
        $minute = $matches[2];
        $ampm = ($hour >= 12) ? 'مساءً' : 'صباحاً';
        
        $hour12 = $hour % 12;
        if ($hour12 == 0) $hour12 = 12;
        
        return ($minute == '00') ? "$hour12 $ampm" : "$hour12:$minute $ampm";
    }, $desc);
}

// جلب مساحة المالك الخاصة بناءً على مطابقة الاسم في الوصف
$stmt_my = $pdo->prepare("SELECT * FROM workspaces WHERE description LIKE ? ORDER BY id DESC");
$stmt_my->execute(['%' . $owner_name . '%']);
$my_spaces = $stmt_my->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم صاحب المساحة | مساحات</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
    body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; }
    a { text-decoration: none; color: inherit; }

    .sidebar { width: 270px; background: #78350f; color: #ffffff; min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; position: fixed; right: 0; top: 0; bottom: 0; z-index: 1000; padding: 25px 18px; }
    .sidebar-header { padding-bottom: 22px; margin-bottom: 25px; }
    .logo-box { display: flex; align-items: center; gap: 14px; }
    .logo-icon { width: 44px; height: 44px; background: #92400e; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
    .logo-text h2 { font-size: 20px; font-weight: 800; color: #ffffff; }
    .logo-text span { font-size: 11px; color: #fde68a; opacity: 0.8; }

    .sidebar-menu { display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
    .menu-label { font-size: 11px; color: #fde68a; font-weight: 700; text-transform: uppercase; padding: 0 8px 6px 0; opacity: 0.7; }
    .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; color: #f5f0eb; background: #92400e; transition: 0.2s; border: none; }
    .menu-item i { font-size: 15px; width: 20px; text-align: center; color: #fde68a; }
    .menu-item:hover { background: rgba(255, 255, 255, 0.08); color: #ffffff; }

    .sidebar-footer { padding-top: 15px; }
    .btn-logout-side { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 16px; background: rgba(0, 0, 0, 0.15); color: #fca5a5; border-radius: 10px; font-size: 13px; font-weight: 700; transition: 0.2s; width: 100%; }
    .btn-logout-side:hover { background: #991b1b; color: #ffffff; }

    .main-content { margin-right: 270px; flex-grow: 1; padding: 30px 40px; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #ffffff; padding: 18px 25px; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .page-title h1 { font-size: 22px; font-weight: 800; color: #0f172a; }
    .page-title p { font-size: 12px; color: #64748b; }

    .user-profile { display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 700; color: #475569; }
    .avatar { width: 38px; height: 38px; background: #fff5eb; border: 1px solid #fed7aa; color: #b45309; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }

    .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .table-caption { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; background: #fffdfb; }
    .table-caption h3 { font-size: 16px; font-weight: 800; color: #78350f; }

    table { width: 100%; border-collapse: collapse; text-align: right; }
    th { background: #f8fafc; padding: 15px 25px; font-size: 12px; font-weight: 800; color: #475569; border-bottom: 1px solid #e2e8f0; }
    td { padding: 16px 25px; font-size: 13px; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    tr:hover td { background: #fffdfb; }

    .space-img-preview { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid #cbd5e1; }
    
    .action-group { display: flex; gap: 8px; align-items: center; }
    .btn-icon { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; transition: 0.3s; }
    .btn-view { background: #fff5eb; border: 1px solid #fed7aa; color: #b45309; }
    .btn-view:hover { background: #b45309; color: white; }
    .btn-edit { background: #e0f2fe; border: 1px solid #bae6fd; color: #0284c7; }
    .btn-edit:hover { background: #0284c7; color: white; }

    .alert-banner { background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; padding: 14px 20px; border-radius: 12px; font-size: 13px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div>
      <div class="sidebar-header">
        <div class="logo-box">
          <div class="logo-icon"><i class="fa-solid fa-layer-group"></i></div>
          <div class="logo-text">
            <h2>مساحات</h2>
            <span>لوحة المالك</span>
          </div>
        </div>
      </div>

      <div class="sidebar-menu">
        <span class="menu-label">القائمة الرئيسية</span>
        <a href="owner-dashboard.php" class="menu-item">
          <i class="fa-solid fa-store"></i>
          <span>مساحتي الخاصة</span>
        </a>
        <a href="index.php" class="menu-item" style="background: transparent;" target="_blank">
          <i class="fa-solid fa-globe"></i>
          <span>معاينة الموقع</span>
        </a>
      </div>
    </div>

    <div class="sidebar-footer">
      <a href="logout.php" class="btn-logout-side"><i class="fa-solid fa-right-from-bracket"></i><span>تسجيل الخروج</span></a>
    </div>
  </aside>

  <main class="main-content">
    <div class="top-bar">
      <div class="page-title">
        <h1>إدارة مساحة العمل الخاصة بك</h1>
        <p>مرحباً بك، <?php echo htmlspecialchars($owner_name); ?></p>
      </div>
      <div class="user-profile">
        <div class="avatar"><?php echo mb_substr($owner_name, 0, 1, 'UTF-8'); ?></div>
      </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert-banner">
          <i class="fa-solid fa-circle-check"></i> تم تحديث بيانات وأسعار المساحة بنجاح.
        </div>
    <?php endif; ?>

    <div class="table-container">
      <div class="table-caption">
        <h3><i class="fa-solid fa-list-ul"></i> مساحتك المسجلة في المنصة</h3>
      </div>
      <table>
        <thead>
          <tr>
            <th>الصورة</th>
            <th>اسم المساحة</th>
            <th>الموقع</th>
            <th>التفاصيل والسعر</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($my_spaces)): ?>
              <?php foreach ($my_spaces as $space): ?>
                  <tr>
                    <td><img src="<?php echo htmlspecialchars($space['image']); ?>" class="space-img-preview" alt="Space"></td>
                    <td><strong><?php echo htmlspecialchars($space['name']); ?></strong></td>
                    <td><i class="fa-solid fa-location-dot" style="color: #b45309;"></i> <?php echo htmlspecialchars($space['location']); ?></td>
                    <td style="color: #b45309; font-weight: 800;"><?php echo htmlspecialchars(formatDescriptionTime($space['description'])); ?></td>
                    <td>
                      <div class="action-group">
                        <a href="workspace-details.php?id=<?php echo $space['id']; ?>" class="btn-icon btn-view" title="معاينة بالموقع"><i class="fa-solid fa-eye"></i></a>
                        <a href="edit-workspace.php?id=<?php echo $space['id']; ?>" class="btn-icon btn-edit" title="تعديل الأسعار والبيانات"><i class="fa-solid fa-pen-to-square"></i></a>
                      </div>
                    </td>
                  </tr>
              <?php endforeach; ?>
          <?php else: ?>
              <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                  <i class="fa-solid fa-folder-open" style="font-size: 30px; color: #b45309; margin-bottom: 10px; display: block;"></i>
                  لم يتم العثور على مساحة مسجلة باسمك حالياً.
                </td>
              </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>