<?php
include 'db.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

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

// تحديد التبويب الحالي (طلبات معلقة أو مساحات مقبولة)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// معالجة تغيير حالة المساحة (قبول)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE workspaces SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin-dashboard.php?tab=pending&msg=approved");
        exit();
    }
}

// معالجة حذف المساحة
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt_del = $pdo->prepare("DELETE FROM workspaces WHERE id = ?");
    $stmt_del->execute([$delete_id]);
    header("Location: admin-dashboard.php?tab=$tab&msg=deleted");
    exit();
}

// جلب طلبات المساحات المعلقة
$stmt_pending = $pdo->query("SELECT * FROM workspaces WHERE status = 'pending' ORDER BY id DESC");
$pending_spaces = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

// جلب المساحات المقبولة
$stmt_approved = $pdo->query("SELECT * FROM workspaces WHERE status = 'approved' ORDER BY id DESC");
$approved_spaces = $stmt_approved->fetchAll(PDO::FETCH_ASSOC);

// قائمة المساحات المعروضة حسب التبويب النشط
$current_spaces = ($tab == 'approved') ? $approved_spaces : $pending_spaces;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة التحكم | مساحات غزة</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
    body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; }
    a { text-decoration: none; color: inherit; }

    /* السايد بار البني */
    .sidebar { width: 270px; background: #78350f; color: #ffffff; min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; position: fixed; right: 0; top: 0; bottom: 0; z-index: 1000; padding: 25px 18px; }
    .sidebar-header { padding-bottom: 22px; margin-bottom: 25px; }
    .logo-box { display: flex; align-items: center; gap: 14px; }
    .logo-icon { width: 44px; height: 44px; background: #92400e; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
    .logo-text h2 { font-size: 20px; font-weight: 800; color: #ffffff; }
    .logo-text span { font-size: 11px; color: #fde68a; opacity: 0.8; }

    .sidebar-menu { display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
    .menu-label { font-size: 11px; color: #fde68a; font-weight: 700; text-transform: uppercase; padding: 0 8px 6px 0; opacity: 0.7; }
    .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; color: #f5f0eb; background: transparent; transition: 0.2s; border: none; }
    .menu-item i { font-size: 15px; width: 20px; text-align: center; color: #fde68a; }
    .menu-item:hover { background: rgba(255, 255, 255, 0.08); color: #ffffff; }
    .menu-item.active { background: #92400e; color: #ffffff; }
    .menu-item.active i { color: #ffffff; }

    .badge-count { background: #fde68a; color: #78350f; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 800; margin-right: auto; }

    .sidebar-footer { padding-top: 15px; }
    .btn-logout-side { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 16px; background: rgba(0, 0, 0, 0.15); color: #fca5a5; border-radius: 10px; font-size: 13px; font-weight: 700; transition: 0.2s; width: 100%; }
    .btn-logout-side:hover { background: #991b1b; color: #ffffff; }

    /* المحتوى الرئيسي */
    .main-content { margin-right: 270px; flex-grow: 1; padding: 30px 40px; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #ffffff; padding: 18px 25px; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .page-title h1 { font-size: 22px; font-weight: 800; color: #0f172a; }
    .page-title p { font-size: 12px; color: #64748b; }

    .user-profile { display: flex; align-items: center; gap: 12px; }
    .avatar { width: 38px; height: 38px; background: #fff5eb; border: 1px solid #fed7aa; color: #b45309; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }

    /* الإحصائيات الثلاثة مرتبة بجانب بعضها */
    .stats-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 35px; }
    .stat-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px; display: flex; align-items: center; gap: 18px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .stat-icon-wrapper { width: 54px; height: 54px; background: #fffdfb; border: 1px solid #fed7aa; color: #b45309; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
    .stat-info h3 { font-size: 24px; font-weight: 900; color: #0f172a; }
    .stat-info p { font-size: 12px; color: #64748b; font-weight: 600; }

    .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .table-caption { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; background: #fffdfb; }
    .table-caption h3 { font-size: 16px; font-weight: 800; color: #78350f; }

    table { width: 100%; border-collapse: collapse; text-align: right; }
    th { background: #f8fafc; padding: 15px 25px; font-size: 12px; font-weight: 800; color: #475569; border-bottom: 1px solid #e2e8f0; }
    td { padding: 16px 25px; font-size: 13px; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    tr:hover td { background: #fffdfb; }

    .space-img-preview { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid #cbd5e1; }
    .price-text { color: #b45309; font-weight: 800; font-size: 13px; }

    /* الأزرار والإجراءات */
    .action-group { display: flex; gap: 8px; align-items: center; }
    .btn-approve-plus { width: 34px; height: 34px; background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 900; transition: 0.2s; }
    .btn-approve-plus:hover { background: #15803d; color: white; }

    .btn-icon { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; transition: 0.3s; }
    .btn-view { background: #fff5eb; border: 1px solid #fed7aa; color: #b45309; }
    .btn-view:hover { background: #b45309; color: white; }
    .btn-delete { background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626; }
    .btn-delete:hover { background: #dc2626; color: white; }

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
            <span>لوحة التحكم</span>
          </div>
        </div>
      </div>

      <div class="sidebar-menu">
        <span class="menu-label">القائمة الرئيسية</span>
        
        <a href="admin-dashboard.php?tab=pending" class="menu-item <?php echo $tab == 'pending' ? 'active' : ''; ?>">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>طلبات المساحات</span>
          <?php if(count($pending_spaces) > 0): ?>
            <span class="badge-count"><?php echo count($pending_spaces); ?></span>
          <?php endif; ?>
        </a>
        
        <a href="admin-dashboard.php?tab=approved" class="menu-item <?php echo $tab == 'approved' ? 'active' : ''; ?>">
          <i class="fa-solid fa-building-circle-check"></i>
          <span>المساحات المقبولة</span>
        </a>
        
        <a href="index.php" class="menu-item" target="_blank">
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
        <h1><?php echo $tab == 'approved' ? 'المساحات المقبولة في الموقع' : 'طلبات مساحات العمل الجديدة'; ?></h1>
        <p><?php echo $tab == 'approved' ? 'قائمة المساحات المعروضة حالياً للزوار' : 'مراجعة وتأكيد قبول المساحات التي قامت بالتسجيل حديثاً'; ?></p>
      </div>
      <div class="user-profile">
        <div class="avatar">آدمن</div>
      </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert-banner">
          <i class="fa-solid fa-circle-check"></i> 
          <?php 
            if($_GET['msg'] == 'approved') echo 'تم قبول مساحة العمل ونقلها بنجاح إلى المساحات المقبولة.';
            elseif($_GET['msg'] == 'deleted') echo 'تم حذف مساحة العمل بنجاح.';
          ?>
        </div>
    <?php endif; ?>

    <!-- الصناديق الثلاثة للإحصائيات في الأعلى -->
    <div class="stats-container">
      <div class="stat-box">
        <div class="stat-icon-wrapper"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
          <h3><?php echo count($pending_spaces); ?></h3>
          <p>طلبات بانتظار القبول</p>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon-wrapper"><i class="fa-solid fa-building-circle-check"></i></div>
        <div class="stat-info">
          <h3><?php echo count($approved_spaces); ?></h3>
          <p>إجمالي المساحات المقبولة</p>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon-wrapper"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="stat-info">
          <h3>نشط</h3>
          <p>حالة النظام المباشر</p>
        </div>
      </div>
    </div>

    <div class="table-container">
      <div class="table-caption">
        <h3>
          <i class="fa-solid <?php echo $tab == 'approved' ? 'fa-building-circle-check' : 'fa-list-ul'; ?>"></i> 
          <?php echo $tab == 'approved' ? 'قائمة المساحات المقبولة' : 'طلبات مساحات العمل التي لم تقبل بعد'; ?>
        </h3>
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
          <?php if (!empty($current_spaces)): ?>
              <?php foreach ($current_spaces as $space): ?>
                  <tr>
                    <td><img src="<?php echo htmlspecialchars($space['image']); ?>" class="space-img-preview" alt="Space"></td>
                    <td><strong><?php echo htmlspecialchars($space['name']); ?></strong></td>
                    <td><i class="fa-solid fa-location-dot" style="color: #b45309;"></i> <?php echo htmlspecialchars($space['location']); ?></td>
                    <td class="price-text"><?php echo htmlspecialchars(formatDescriptionTime($space['description'])); ?></td>
                    <td>
                      <div class="action-group">
                        <?php if($space['status'] == 'pending'): ?>
                          <a href="admin-dashboard.php?action=approve&id=<?php echo $space['id']; ?>" class="btn-approve-plus" title="تأكيد القبول (+)">
                            <i class="fa-solid fa-plus"></i>
                          </a>
                        <?php else: ?>
                          <a href="workspace-details.php?id=<?php echo $space['id']; ?>" class="btn-icon btn-view" title="معاينة التفاصيل">
                            <i class="fa-solid fa-eye"></i>
                          </a>
                        <?php endif; ?>
                        
                        <a href="admin-dashboard.php?delete=<?php echo $space['id']; ?>&tab=<?php echo $tab; ?>" class="btn-icon btn-delete" onclick="return confirm('هل أنتِ متأكدة من حذف هذه المساحة؟');" title="حذف">
                          <i class="fa-solid fa-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
              <?php endforeach; ?>
          <?php else: ?>
              <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                  <i class="fa-solid fa-folder-open" style="font-size: 30px; color: #b45309; margin-bottom: 10px; display: block;"></i>
                  <?php echo $tab == 'approved' ? 'لا توجد مساحات مقبولة حالياً.' : 'لا توجد طلبات مساحات جديدة بانتظار القبول.'; ?>
                </td>
              </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>