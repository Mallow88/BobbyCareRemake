<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gmapprover') {
    header("Location: ../index.php");
    exit();
}
$picture_url = $_SESSION['picture_url'] ?? null;

// ดึงคำขอที่ผ่าน assignor แล้ว
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        aa.budget_approved,
        requester.name AS requester_name, 
        requester.lastname AS requester_lastname,
        requester.employee_id,
        requester.position,
        requester.department,
        requester.phone,
        requester.email,
        dn.document_number,
        
        -- Division Manager Info
        dma.status as div_mgr_status,
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info  
        aa.status as assignor_status,
        aa.reason as assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
        -- Service Info
        s.name as service_name,
        s.category as service_category
        
    FROM service_requests sr
    JOIN users requester ON sr.user_id = requester.id
    JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id
    JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
    WHERE dma.status = 'approved' 
    AND aa.status = 'approved'
    AND (gma.id IS NULL OR gma.status = 'pending')
    ORDER BY aa.reviewed_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>BobbyCareDev-Report</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="/BobbyCareRemake/img/logo/bobby-icon.png" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["../assets/css/fonts.min.css"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <style>
        :root {

            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            box-shadow: var(--card-shadow);
        }

        .header-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .page-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .request-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #9f7aea;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .request-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .info-icon.employee {
            background: #667eea;
        }

        .info-icon.user {
            background: #10b981;
        }

        .info-icon.position {
            background: #f59e0b;
        }

        .info-icon.department {
            background: #8b5cf6;
        }

        .info-icon.phone {
            background: #ef4444;
        }

        .info-icon.email {
            background: #06b6d4;
        }

        .service-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .service-development {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .service-service {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: #1e40af;
        }

        .category-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-rdc {
            background: #dbeafe;
            color: #1e40af;
        }

        .category-cdc {
            background: #d1fae5;
            color: #065f46;
        }

        .category-bdc {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low {
            background: #c6f6d5;
            color: #2f855a;
        }

        .priority-medium {
            background: #fef5e7;
            color: #d69e2e;
        }

        .priority-high {
            background: #fed7d7;
            color: #c53030;
        }

        .priority-urgent {
            background: #e53e3e;
            color: white;
        }

        .approval-timeline {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }

        .timeline-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            background: white;
            border-left: 4px solid #48bb78;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            background: #48bb78;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .step-details {
            font-size: 0.9rem;
            color: #718096;
            line-height: 1.4;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn-approve {
            background: linear-gradient(135deg, #9f7aea, #805ad5);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(159, 122, 234, 0.3);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(159, 122, 234, 0.4);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: #d1d5db;
            opacity: 0.7;
        }

        .request-meta {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .description-box {
            background: #f7fafc;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }

        .benefits-box {
            background: linear-gradient(135deg, #f0fff4, #e6fffa);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }

        .estimate-info {
            background: #fef5e7;
            border-radius: 8px;
            padding: 10px 15px;
            margin: 10px 0;
            border-left: 3px solid #f59e0b;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .user-info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>

</head>

<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <!-- Logo Header -->
                <div class="logo-header" data-background-color="dark">
                    <a href="developer_dashboard2.php" class="logo">
                        <img src="../img/logo/bobby-full.png" alt="navbar brand" class="navbar-brand" height="30" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>
                </div>
                <!-- End Logo Header -->
            </div>
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
                        <li class="nav-item active">
                            <a href="gmindex2.php">
                                <i class="fas fa-home"></i>
                                <p>หน้าหลัก</p>
                            </a>
                        </li>

                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Components</h4>
                        </li>

                        <li class="nav-item ">
                            <a href="approved_list2.php">
                                <i class="fas fa-check-circle"></i> <!-- รายการที่อนุมัติ -->
                                <p>รายการที่อนุมัติเเล้ว</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="view_completed_tasks2.php">
                                <i class="fas fa-comments"></i> <!-- รีวิวจากผู้ใช้ -->
                                <p>User Reviews</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="developer_dashboard2.php">
                                <i class="fas fa-tachometer-alt"></i> <!-- Dashboard -->
                                <p>Dashboard_DEV</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="report2.php">
                                <i class="fas fa-file-alt"></i> <!-- Report -->
                                <p>Report</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> <!-- Logout -->
                                <p>Logout</p>
                                <span class="badge badge-success"></span>
                            </a>
                        </li>


                    </ul>
                </div>
            </div>
        </div>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="dark">
                        <a href="../index.html" class="logo">
                            <img src="../img/logo/bobby-full.png" alt="navbar brand" class="navbar-brand" height="20" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                    <!-- End Logo Header -->
                </div>

                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">

                            <!-- โปรไฟล์ -->
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">

                                    <div class="avatar-sm">
                                        <img src="<?= htmlspecialchars($picture_url) ?>" alt="..." class="avatar-img rounded-circle" />
                                    </div>

                                    <span class="profile-username">
                                        <span class="op-7">ผู้จัดการทั่วไป:</span>
                                        <span class="fw-bold"><?= htmlspecialchars($_SESSION['name']) ?></span>
                                    </span>
                                </a>

                            </li>


                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>




            <div class="container">





                <!-- Content -->
                <div class="glass-card p-4">

                    <!-- Header -->
                    <div class="d-flex align-items-center mb-4">
                        <i class="fas fa-clipboard-check text-primary me-3 fs-3"></i>
                        <h2 class="mb-0 fw-bold">รายการคำขอที่รอการพิจารณา</h2>
                    </div>

                    <?php if (empty($requests)): ?>

                        <!-- Empty State -->
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3 class="fw-bold mb-3">ไม่มีคำขอที่รอการพิจารณา</h3>
                            <p class="fs-5">ขณะนี้ไม่มีคำขอที่ต้องการการอนุมัติจากคุณ</p>
                        </div>

                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>

                            <div class="request-card">

                                <!-- Header & Meta -->
                                <div class="d-flex justify-content-between align-items-start mb-3">

                                    <div class="flex-grow-1">

                                        <!-- Document Number -->
                                        <?php if (!empty($req['document_number'])): ?>
                                            <div class="text-muted mb-2">
                                                <i class="fas fa-file-alt me-1"></i>
                                                เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
                                            </div>
                                            <input type="hidden" name="document_number"
                                                value="<?= htmlspecialchars($req['document_number']) ?>">
                                        <?php endif; ?>

                                        <!-- Title -->
                                        <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>

                                        <!-- Service Badge -->
                                        <div class="d-flex gap-2 mb-2">
                                            <?php if ($req['service_name']): ?>
                                                <span class="service-badge service-<?= $req['service_category'] ?>">
                                                    <?php if ($req['service_category'] === 'development'): ?>
                                                        <i class="fas fa-code me-1"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-tools me-1"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($req['service_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Request Date -->
                                        <div class="request-meta">
                                            <i class="fas fa-calendar me-1"></i>
                                            วันที่ขอดำเนินเรื่อง: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                        </div>
                                    </div>

                                    <!-- Priority & Estimate -->
                                    <div class="text-end">
                                        <span class="priority-badge priority-<?= $req['priority_level'] ?>">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            <?php
                                            $priorities = [
                                                'low' => 'ต่ำ',
                                                'medium' => 'ปานกลาง',
                                                'high' => 'สูง',
                                                'urgent' => 'เร่งด่วน'
                                            ];
                                            echo $priorities[$req['priority_level']] ?? 'ปานกลาง';
                                            ?>
                                        </span>
                                        <?php if ($req['estimated_days']): ?>
                                            <div class="estimate-info mt-2">
                                                <i class="fas fa-clock me-1"></i>
                                                ประมาณ <?= $req['estimated_days'] ?> วัน
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div class="user-info-grid">
                                    <?php
                                    $userFields = [
                                        ['employee', 'รหัสพนักงาน', 'employee_id', 'fa-id-card'],
                                        ['user', 'ชื่อ-นามสกุล', ['requester_name', 'requester_lastname'], 'fa-user'],
                                        ['position', 'ตำแหน่ง', 'position', 'fa-briefcase'],
                                        ['department', 'หน่วยงาน', 'department', 'fa-building'],
                                        ['phone', 'เบอร์โทร', 'phone', 'fa-phone'],
                                        ['email', 'อีเมล', 'email', 'fa-envelope']
                                    ];

                                    foreach ($userFields as [$class, $label, $field, $icon]) {
                                        $value = is_array($field)
                                            ? htmlspecialchars($req[$field[0]] . ' ' . $req[$field[1]])
                                            : htmlspecialchars($req[$field] ?? 'ไม่ระบุ');
                                    ?>
                                        <div class="info-item">
                                            <div class="info-icon <?= $class ?>">
                                                <i class="fas <?= $icon ?>"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted"><?= $label ?></small>
                                                <div class="fw-bold"><?= $value ?></div>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <!-- Development Details -->
                                <?php if ($req['service_category'] === 'development'): ?>
                                    <div>
                                        <h6 class="fw-bold text-info mb-3">
                                            <i class="fas fa-code me-2"></i>ข้อมูล Development
                                        </h6>
                                        <div class="row">
                                            <?php
                                            $fields = [
                                                'program_purpose' => 'วัตถุประสงค์',
                                                'target_users' => 'กลุ่มผู้ใช้งาน',
                                                'main_functions' => 'ฟังก์ชันหลัก',
                                                'data_requirements' => 'ข้อมูลที่ต้องใช้',
                                                'current_program_name' => 'โปรแกรมที่มีปัญหา',
                                                'problem_description' => 'รายละเอียดปัญหา',
                                                'error_frequency' => 'ความถี่ของปัญหา',
                                                'steps_to_reproduce' => 'ขั้นตอนการทำให้เกิดปัญหา',
                                                'program_name_change' => 'โปรแกรมที่ต้องการเปลี่ยนข้อมูล',
                                                'data_to_change' => 'ข้อมูลที่ต้องการเปลี่ยน',
                                                'new_data_value' => 'ข้อมูลใหม่ที่ต้องการ',
                                                'change_reason' => 'เหตุผลในการเปลี่ยนแปลง',
                                                'program_name_function' => 'โปรแกรมที่ต้องการเพิ่มฟังก์ชั่น',
                                                'new_functions' => 'ฟังก์ชั่นใหม่ที่ต้องการ',
                                                'function_benefits' => 'ประโยชน์ของฟังก์ชั่นใหม่',
                                                'integration_requirements' => 'ความต้องการเชื่อมต่อ',
                                                'program_name_decorate' => 'โปรแกรมที่ต้องการตกแต่ง',
                                                'decoration_type' => 'ประเภทการตกแต่ง',
                                                'reference_examples' => 'ตัวอย่างอ้างอิง',
                                                'current_workflow' => 'ขั้นตอนการทำงานเดิม',
                                                'approach_ideas' => 'แนวทาง/ไอเดีย',
                                                'related_programs' => 'โปรแกรมที่คาดว่าจะเกี่ยวข้อง',
                                                'current_tools' => 'ปกติใช้โปรแกรมอะไรทำงานอยู่',
                                                'system_impact' => 'ผลกระทบต่อระบบ',
                                                'related_documents' => 'เอกสารการทำงานที่เกี่ยวข้อง',
                                            ];

                                            foreach ($fields as $key => $label):
                                                if (!empty($req[$key])):
                                            ?>
                                                    <div class="col-md-6 mb-3">
                                                        <strong><?= $label ?>:</strong><br>
                                                        <?= nl2br(htmlspecialchars($req[$key])) ?>
                                                    </div>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Expected Benefits -->
                                <?php if ($req['expected_benefits']): ?>
                                    <div>
                                        <h6 class="fw-bold text-success mb-2">
                                            <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                                        </h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($req['expected_benefits'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Attachments -->
                                <?php
                                require_once __DIR__ . '/../includes/attachment_display.php';
                                displayAttachments($req['id']);
                                ?>

                                <!-- Approval Timeline -->
                                <div class="approval-timeline">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-route me-2"></i>ขั้นตอนการอนุมัติที่ผ่านมา
                                    </h5>

                                    <!-- Manager Approval -->
                                    <div class="timeline-step">
                                        <div class="step-icon">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">1. ผู้จัดการฝ่าย - อนุมัติแล้ว</div>
                                            <div class="step-details">
                                                <strong>โดย:</strong> <?= htmlspecialchars($req['div_mgr_name']) ?>
                                                <?php if ($req['div_mgr_reason']): ?>
                                                    <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['div_mgr_reason']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Department Approval -->
                                    <div class="timeline-step">
                                        <div class="step-icon">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">2. ผู้จัดการแผนก - อนุมัติแล้ว</div>
                                            <div class="step-details">
                                                <strong>โดย:</strong> <?= htmlspecialchars($req['assignor_name']) ?>
                                                <br><strong>มอบหมายให้ผู้พัฒนา:</strong> <?= htmlspecialchars($req['dev_name'] . ' ' . $req['dev_lastname']) ?>
                                                <?php if (!empty($req['budget_approved'])): ?>
                                                    <br><strong>งบประมาณที่ขอ:</strong> <?= htmlspecialchars($req['budget_approved']) ?>
                                                <?php endif; ?>
                                                <?php if ($req['assignor_reason']): ?>
                                                    <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($req['assignor_reason']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <div class="action-buttons">
                                    <button class="btn btn-success btn-sm"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#gmApprovalSection<?= $req['id'] ?>"
                                        aria-expanded="false"
                                        aria-controls="gmApprovalSection<?= $req['id'] ?>">
                                        <i class="fas fa-clipboard-check"></i> พิจารณาคำขอ
                                    </button>
                                </div>

                                <div class="collapse mt-3" id="gmApprovalSection<?= $req['id'] ?>">
                                    <div class="card card-body">
                                        <div class="gm-approval-content" data-request-id="<?= $req['id'] ?>">
                                            <div class="text-center text-muted py-3">
                                                <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>



            </div>
        </div>
    </div>

    <!-- <footer class="footer">
        <div class="container-fluid d-flex justify-content-between">
            <nav class="pull-left">

            </nav>
            <div class="copyright">
                © 2025, made with by เเผนกพัฒนาระบบงาน for BobbyCareRemake.
                <i class="fa fa-heart heart text-danger"></i>

            </div>
            <div>

            </div>
        </div>
    </footer> -->
    </div>
    </div>




    </div>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <!-- Chart JS -->
    <script src="../assets/js/plugin/chart.js/chart.min.js"></script>
    <!-- jQuery Scrollbar -->
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <!-- Kaiadmin JS -->
    <script src="../assets/js/kaiadmin.min.js"></script>
    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="../assets/js/setting-demo2.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('[data-bs-target^="#gmApprovalSection"]').forEach(button => {
                button.addEventListener("click", function() {
                    const targetId = this.getAttribute("data-bs-target");
                    const container = document.querySelector(targetId + " .gm-approval-content");
                    const requestId = container.getAttribute("data-request-id");

                    if (!container.dataset.loaded) {
                        fetch("gm_approve.php?id=" + requestId)
                            .then(response => response.text())
                            .then(html => {
                                container.innerHTML = html;
                                container.dataset.loaded = "true";
                            })
                            .catch(err => {
                                container.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
                            });
                    }
                });
            });
        });
    </script>


</body>

</html>