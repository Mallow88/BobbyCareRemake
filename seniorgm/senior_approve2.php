<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seniorgm') {
    header("Location: ../index.php");
    exit();
}
$picture_url = $_SESSION['picture_url'] ?? null;

$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    echo "ไม่พบคำขอที่ระบุ";
    exit();
}


// ฟังก์ชันส่งข้อความเข้า LINE Official Account
function sendLinePushFlexToDev($sr)
{
    $access_token = "hAfRJZ7KyjncT3I2IB6UhHqU/DmP1qPxW2PbeDE7KtUUveyiSKgLvJxrahWyrFUmlrta4MAnw8V3QRr5b7LwoKYh4hv1ATfX8yrJOMFQ+zdQxm3rScAAGNaJTEN1mJxHN93jHbqLoK8dQ080ja5BFAdB04t89/1O/w1cDnyilFU="; // ใส่ Channel access token (long-lived)

    $url = "https://api.line.me/v2/bot/message/push";

    $bubble = [
        "type" => "bubble",
        "size" => "mega",
        "header" => [
            "type" => "box",
            "layout" => "vertical",
            "contents" => [
                [
                    "type" => "text",
                    "text" => "📑 มีงานเข้ามาใหม่แเล้ว!",
                    "weight" => "bold",
                    "size" => "lg",
                    "align" => "center",
                    "color" => "#ffffffff"
                ],
                [
                    "type" => "text",
                    "text" => $sr['document_number'] ?? "-",
                    "size" => "md",
                    "align" => "center",
                    "color" => "#FFFFFF",
                    "margin" => "md"
                ]
            ],
            "backgroundColor" => "#5677fc",
            "paddingAll" => "20px"
        ],
        "body" => [
            "type" => "box",
            "layout" => "vertical",
            "spacing" => "md",
            "contents" => [
                ["type" => "text", "text" => "📌 เรื่อง: {$sr['title']}", "wrap" => true, "weight" => "bold", "size" => "sm", "color" => "#333333"],
                ["type" => "text", "text" => "📝 {$sr['description']}", "wrap" => true, "size" => "sm", "color" => "#666666"],
                ["type" => "text", "text" => "✨ ประโยชน์: {$sr['expected_benefits']}", "wrap" => true, "size" => "sm", "color" => "#32CD32"],
                ["type" => "separator", "margin" => "md"],
                ["type" => "text", "text" => "ผู้ขอบริการ : {$sr['name']} {$sr['lastname']}", "size" => "sm", "color" => "#000000"],
                ["type" => "text", "text" => "🆔 {$sr['employee_id']} | 🏢 {$sr['department']}", "size" => "sm", "color" => "#444444"]
            ]
        ],
        "footer" => [
            "type" => "box",
            "layout" => "vertical",
            "contents" => [
                [
                    "type" => "button",
                    "style" => "primary",
                    "color" => "#d0d9ff",
                    "action" => [
                        "type" => "uri",
                        "label" => "🔎 ดูรายละเอียด",
                        "uri" => "http://yourdomain/index2.php?id={$sr['request_id']}"
                    ]
                ]
            ],
            "backgroundColor" => "#5677fc"
        ]
    ];

    $flexMessage = [
        "type" => "flex",
        "altText" => "📑 มีคำขอเอกสารใหม่",
        "contents" => $bubble
    ];

    $data = [
        "to" => $sr['dev_line_id'], // ใช้ line_id ของ Developer
        "messages" => [$flexMessage]
    ];


    $post = json_encode($data, JSON_UNESCAPED_UNICODE);
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}


// ตรวจสอบว่ามีการพิจารณาแล้ว
$check = $conn->prepare("SELECT * FROM senior_gm_approvals WHERE service_request_id = ?");
$check->execute([$request_id]);
if ($check->rowCount() > 0) {
    echo "คำขอนี้ได้รับการพิจารณาโดย Senior GM แล้ว";
    exit();
}

// ดึงข้อมูลคำขอ
$stmt = $conn->prepare("
    SELECT 
        sr.*,
        requester.name AS requester_name, 
        requester.lastname AS requester_lastname,
        requester.employee_id,
        requester.position,
        requester.department,
        requester.phone,
        requester.email,
          dn.document_number,
        
        -- Division Manager Info
        dma.reason as div_mgr_reason,
        div_mgr.name as div_mgr_name,
        
        -- Assignor Info
         aa.budget_approved AS assignor_budget_approved,  
        aa.reason as assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        assignor.name as assignor_name,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        
        -- GM Info
        
        gma.reason as gm_reason,
         gma.budget_approved AS gm_budget_approved,
        gma.reviewed_at as gm_reviewed_at,
        gm.name as gm_name,
        
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
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    JOIN gm_approvals gma ON sr.id = gma.service_request_id
    JOIN users gm ON gma.gm_user_id = gm.id
    LEFT JOIN services s ON sr.service_id = s.id
    WHERE sr.id = ?
");
$stmt->execute([$request_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "ไม่พบคำขอ";
    exit();
}

// เมื่อ submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $reason = trim($_POST['reason'] ?? '');
    $final_notes = trim($_POST['final_notes'] ?? '');
    $senior_gm_id = $_SESSION['user_id'];

    if ($status === 'rejected' && $reason === '') {
        $error = "กรุณาระบุเหตุผลที่ไม่อนุมัติ";
    } else {
        try {
            $conn->beginTransaction();

            // บันทึกการอนุมัติ Senior GM
            $stmt = $conn->prepare("
                INSERT INTO senior_gm_approvals (
                    service_request_id, senior_gm_user_id, status, reason, final_notes, reviewed_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$request_id, $senior_gm_id, $status, $reason, $final_notes]);

            // อัปเดตสถานะใน service_requests
            if ($status === 'approved') {
                // สร้าง task สำหรับ developer
                $dev_stmt = $conn->prepare("SELECT assigned_developer_id FROM assignor_approvals WHERE service_request_id = ?");
                $dev_stmt->execute([$request_id]);
                $dev_result = $dev_stmt->fetch(PDO::FETCH_ASSOC);
                $dev_id = $dev_result['assigned_developer_id'] ?? null;

                if ($dev_id) {
                    $task_stmt = $conn->prepare("
                        INSERT INTO tasks (service_request_id, developer_user_id, task_status, progress_percentage, created_at) 
                        VALUES (?, ?, 'pending', 0, NOW())
                    ");
                    $task_stmt->execute([$request_id, $dev_id]);

                    // อัปเดต developer_status ใน service_requests
                    $update_dev_status = $conn->prepare("UPDATE service_requests SET developer_status = 'pending' WHERE id = ?");
                    $update_dev_status->execute([$request_id]);
                }

                $new_status = 'approved';
                $current_step = 'senior_gm_approved';
            } else {
                $new_status = 'rejected';
                $current_step = 'senior_gm_rejected';
            }

            $stmt = $conn->prepare("UPDATE service_requests SET status = ?, current_step = ? WHERE id = ?");
            $stmt->execute([$new_status, $current_step, $request_id]);

            // บันทึก log
            $stmt = $conn->prepare("
                INSERT INTO document_status_logs (
                    service_request_id, step_name, status, reviewer_id, reviewer_role, notes
                ) VALUES (?, 'senior_gm_review', ?, ?, 'seniorgm', ?)
            ");
            $stmt->execute([$request_id, $status, $senior_gm_id, $reason . ' ' . $final_notes]);

            $conn->commit();



            // ถ้า Senior GM อนุมัติ → แจ้ง Developer อีกครั้ง
            if ($status === 'approved') {
                $sr_stmt = $conn->prepare("
        SELECT sr.title, sr.description, sr.expected_benefits, dn.document_number,
               u.name, u.lastname, u.employee_id, u.department, u.position, u.phone, u.email,
               dev.line_id AS dev_line_id, dev.name AS dev_name, dev.lastname AS dev_lastname
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.id
        LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
        LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
        LEFT JOIN users dev ON aa.assigned_developer_id = dev.id
        WHERE sr.id = ?
    ");
                $sr_stmt->execute([$request_id]);
                $sr = $sr_stmt->fetch(PDO::FETCH_ASSOC);

                if ($sr && !empty($sr['dev_line_id'])) {
                    // ส่ง Flex Bubble แทนข้อความปกติ
                    sendLinePushFlexToDev($sr);
                }
            }


            header("Location: seniorindex2.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>BobbyCareRemake</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../img/logo/bobby-icon.png" type="image/x-icon" />

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
            --primary-gradient: linear-gradient(135deg, #ffffff 0%, #afafafff 100%);
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

        .approval-summary {
            background: #f7fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .approval-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            background: white;
            border-left: 4px solid #48bb78;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #48bb78;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
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

        .approval-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-weight: 600;
        }

        .approve-option {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .approve-option:hover {
            border-color: #48bb78;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        .reject-option {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
        }

        .reject-option:hover {
            border-color: #f56565;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 101, 101, 0.3);
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .submit-btn {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            box-shadow: 0 8px 25px rgba(128, 90, 213, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(128, 90, 213, 0.4);
        }

        .back-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
            color: white;
        }

        .error-message {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            border-left: 4px solid #ef4444;
        }

        .budget-info {
            background: linear-gradient(135deg, #e6fffa, #b2f5ea);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 10px;
            border-left: 4px solid #38b2ac;
            display: inline-block;
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
            padding: 8px 12px;
            margin: 8px 0;
            border-left: 3px solid #f59e0b;
            display: inline-block;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .user-info-grid {
                grid-template-columns: 1fr;
            }

            .radio-group {
                flex-direction: column;
                gap: 10px;
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
                    <a href="seniorindex2.php" class="logo">
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
                            <a href="seniorindex2.php">
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

                        <li class="nav-item">
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i>
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
                        <a href="index2.php" class="logo">
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
                                        <span class="op-7">ผู้จัดการทั่วไปอาวุโสคุณ:</span>
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


                <div class="page-inner">
                    <!-- Header -->

                    <div class="glass-card p-4">
                        <?php if (!empty($error)): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>


                        <div class="">
                            <!-- บรรทัดแรก: เลขที่เอกสาร + วันที่ -->
                            <div class="d-flex justify-content-between text-muted mb-2 flex-wrap">

                                <div>
                                    <?php if (!empty($data['service_name'])): ?>
                                        <div class="text-secondary">
                                            <?php if ($data['service_category'] === 'development'): ?>
                                                <i class="fas fa-code me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tools me-1"></i>
                                            <?php endif; ?>
                                            <strong>ประเภทคำขอ: <?= htmlspecialchars($data['service_name']) ?></strong>
                                        </div>
                                    <?php endif; ?>


                                    <span class="me-3">
                                        <i class="fas fa-file-alt me-1"></i>
                                        เลขที่: <?= htmlspecialchars($data['document_number'] ?? '-') ?>
                                    </span>
                                </div>


                            </div>


                        </div>

                        <!-- ข้อมูลผู้ขอ -->
                        <div class="row g-3">
                            <div class="col-6">
                                <small class="text-muted">รหัสพนักงาน</small>
                                <div class="fw-bold"><?= htmlspecialchars($data['employee_id'] ?? 'ไม่ระบุ') ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">ชื่อ-นามสกุล</small>
                                <div class="fw-bold"><?= htmlspecialchars($data['requester_name'] . ' ' . $data['requester_lastname']) ?></div>
                            </div>

                            <div class="col-6">
                                <small class="text-muted">ตำแหน่ง</small>
                                <div class="fw-bold"><?= htmlspecialchars($data['position'] ?? 'ไม่ระบุ') ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">หน่วยงาน</small>
                                <div class="fw-bold"><?= htmlspecialchars($data['department'] ?? 'ไม่ระบุ') ?></div>
                            </div>

                            <div class="col-6">
                                <small class="text-muted">เบอร์โทร</small>
                                <div class="fw-bold"><?= htmlspecialchars($data['phone'] ?? 'ไม่ระบุ') ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">อีเมล</small>
                                <div class="fw-bold"><?= htmlspecialchars($data['email'] ?? 'ไม่ระบุ') ?></div>
                            </div>
                        </div>
                        <br>


                        <!-- Development Details -->
                        <?php if ($data['service_category'] === 'development'): ?>
                            <div>
                                <h5 class="fw-bold text-dark mb-2">
                                    หัวข้อ: <?= htmlspecialchars($data['title'] ?? '-') ?>
                                </h5>
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
                                        if (!empty($data[$key])):
                                    ?>
                                            <div class="col-md-6 mb-3">
                                                <strong><?= $label ?>:</strong><br>
                                                <?= nl2br(htmlspecialchars($data[$key])) ?>
                                            </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ประโยชน์ที่คาดว่าจะได้รับ -->
                        <?php if ($data['expected_benefits']): ?>
                            <h6 class="fw-bold text-success mb-2">
                                <i class="fas fa-bullseye me-2"></i>ประโยชน์ที่คาดว่าจะได้รับ
                                <p class="mb-0"><?= nl2br(htmlspecialchars($data['expected_benefits'])) ?></p>
                            </h6>
                        <?php endif; ?>


                        <!-- Priority & Estimate -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <!-- Priority (ซ้าย) -->
                            <div>
                                <span class="priority-badge priority-<?= $data['priority_level'] ?>">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    <?php
                                    $priorities = [
                                        'low' => 'ต่ำ',
                                        'medium' => 'ปานกลาง',
                                        'high' => 'สูง',
                                        'urgent' => 'เร่งด่วน'
                                    ];
                                    echo $priorities[$data['priority_level']] ?? 'ปานกลาง';
                                    ?>
                                </span>
                            </div>

                            <!-- Estimate Days (ขวา) -->
                            <?php if ($data['estimated_days']): ?>
                                <div class="estimate-info text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    ประมาณ <?= $data['estimated_days'] ?> วัน
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        // แสดงไฟล์แนบ
                        require_once __DIR__ . '/../includes/attachment_display.php';
                        displayAttachments($request_id);
                        ?>

                        <h3 class="fw-bold mb-3">
                            <i class="fas fa-route text-primary me-2"></i>สรุปการอนุมัติที่ผ่านมา
                        </h3>

                        <div class="approval-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <div class="step-title">ผู้จัดการฝ่าย - อนุมัติแล้ว</div>
                                <div class="step-details">
                                    <strong>โดย:</strong> <?= htmlspecialchars($data['div_mgr_name']) ?>
                                    <?php if ($data['div_mgr_reason']): ?>
                                        <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($data['div_mgr_reason']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="approval-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <div class="step-title">2. ผู้จัดการแผนก - อนุมัติแล้ว</div>
                                <div class="step-details">
                                    <strong>โดย:</strong> <?= htmlspecialchars($data['assignor_name']) ?>
                                    <br><strong>มอบหมายให้ผู้พัฒนา:</strong> <?= htmlspecialchars($data['dev_name'] . ' ' . $data['dev_lastname']) ?>
                                    <?php if (!empty($data['assignor_budget_approved'])): ?>
                                        <br><strong>งบประมาณที่ขอ :</strong> <?= htmlspecialchars($data['assignor_budget_approved']) ?>
                                    <?php endif; ?>
                                    <?php if ($data['assignor_reason']): ?>
                                        <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($data['assignor_reason']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="approval-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <div class="step-title">ผู้จัดการทั่วไป - อนุมัติแล้ว</div>
                                <div class="step-details">
                                    <strong>โดย:</strong> <?= htmlspecialchars($data['gm_name']) ?>
                                    <br><strong>วันที่อนุมัติ:</strong> <?= date('d/m/Y H:i', strtotime($data['gm_reviewed_at'])) ?>
                                    <?php if ($data['gm_reason']): ?>
                                        <br><strong>หมายเหตุ:</strong> <?= htmlspecialchars($data['gm_reason']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($data['gm_budget_approved'])): ?>
                                        <br><strong>งบประมาณที่อนุมัติ (GM):</strong> <?= htmlspecialchars($data['gm_budget_approved']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>


                        <form method="post" onsubmit="disableSubmitBtn()">


                            <div class="form-group">
                                <label>ผลการพิจารณา:</label>
                                <div class="radio-group">
                                    <label class="radio-option approve-option">
                                        <input type="radio" name="status" value="approved" required>
                                        <i class="fas fa-check-circle me-2"></i>
                                        อนุมัติขั้นสุดท้าย
                                    </label>
                                    <label class="radio-option reject-option">
                                        <input type="radio" name="status" value="rejected" required>
                                        <i class="fas fa-times-circle me-2"></i>
                                        ไม่อนุมัติ
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label for="reason">เหตุผล/ข้อเสนอแนะ:</label>
                                    <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="ระบุเหตุผลหรือข้อเสนอแนะ"></textarea>
                                </div>

                                <!-- <div class="form-group">
                                    <label for="final_notes">หมายเหตุสำหรับผู้พัฒนา:</label>
                                    <textarea name="final_notes" id="final_notes" class="form-control" rows="3" placeholder="ข้อแนะนำหรือคำแนะนำพิเศษสำหรับผู้พัฒนา"></textarea>
                                </div> -->

                            </div>


                            <button type="submit" id="submitBtn"
                                style="width:100%;padding:14px 0;font-size:1.1rem;
           border-radius:10px;font-weight:bold;
           background:linear-gradient(90deg,#6a11cb,#2575fc);
           color:#fff;border:none;box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                                <i class="fas fa-stamp me-2"></i>
                                ยืนยันการอนุมัติ
                            </button>


                        </form> <br>
                        <button type="button" onclick="window.history.back();"
                            style="width:100%;padding:14px 0;font-size:1.1rem;
           border-radius:10px;font-weight:bold;
           background:linear-gradient(90deg,#ff512f,#dd2476);
           color:#fff;border:none;box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            <i class="fas fa-arrow-left me-2"></i>
                            ย้อนกลับ
                        </button>

                    </div>

                </div>



            </div>
        </div>
    </div>

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




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script>
    // ===== แสดง/ซ่อน textarea เหตุผลตามการเลือก =====
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const form = this.closest('form');
            const textarea = form.querySelector('#reason');
            const label = form.querySelector('label[for="reason"]');

            if (this.value === 'rejected') {
                textarea.required = true;
                label.innerHTML = 'เหตุผลการไม่อนุมัติ: <span style="color: red;">*</span>';
                textarea.placeholder = 'กรุณาระบุเหตุผลการไม่อนุมัติ';
            } else {
                textarea.required = false;
                label.innerHTML = 'เหตุผล/ข้อเสนอแนะ:';
                textarea.placeholder = 'ระบุเหตุผลหรือข้อเสนอแนะ (ไม่บังคับ)';
            }
        });
    });

    // ===== SweetAlert ตอนกดปุ่มยืนยัน =====
    document.getElementById("submitBtn").addEventListener("click", function (e) {
        e.preventDefault(); // กัน submit ก่อน

        const form = this.closest("form"); // เก็บ form ไว้ก่อน

        swal("Good job!", "ขอบคุณที่ใช้บริการ BobbyCare ", {
            icon: "success",
            buttons: {
                confirm: {
                    className: "btn btn-success",
                },
            },
        }).then(() => {
            form.submit(); // ส่งฟอร์มจริงหลังจากกด OK
        });
    });
</script>





    <style>
        /* overlay ครอบทั้งหน้าตอนเมนูเปิด */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .25);
            z-index: 998;
            /* ให้อยู่ใต้ sidebar นิดเดียว */
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }
    </style>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        (function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            // ปุ่มที่ใช้เปิด/ปิดเมนู (ตามโค้ดคุณมีทั้งสองคลาส)
            const toggleBtns = document.querySelectorAll('.toggle-sidebar, .sidenav-toggler');

            // คลาสที่มักถูกเติมเมื่อ "เมนูเปิด" (เติมเพิ่มได้ถ้าโปรเจ็กต์คุณใช้ชื่ออื่น)
            const OPEN_CLASSES = ['nav_open', 'toggled', 'show', 'active'];

            // helper: เช็คว่าเมนูถือว่า "เปิด" อยู่ไหม
            function isSidebarOpen() {
                if (!sidebar) return false;
                // ถ้าบอดี้หรือไซด์บาร์มีคลาสในรายการนี้ตัวใดตัวหนึ่ง ให้ถือว่าเปิด
                const openOnBody = OPEN_CLASSES.some(c => document.body.classList.contains(c) || document.documentElement.classList.contains(c));
                const openOnSidebar = OPEN_CLASSES.some(c => sidebar.classList.contains(c));
                return openOnBody || openOnSidebar;
            }

            // helper: สั่งปิดเมนูแบบไม่ผูกกับไส้ในธีมมากนัก
            function closeSidebar() {
                // เอาคลาสเปิดออกจาก body/html และ sidebar (กันเหนียว)
                OPEN_CLASSES.forEach(c => {
                    document.body.classList.remove(c);
                    document.documentElement.classList.remove(c);
                    sidebar && sidebar.classList.remove(c);
                });
                overlay?.classList.remove('show');
            }

            // เมื่อกดปุ่ม toggle: ถ้าเปิดแล้วให้โชว์ overlay / ถ้าปิดก็ซ่อน
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // หน่วงนิดให้ธีมสลับคลาสเสร็จก่อน
                    setTimeout(() => {
                        if (isSidebarOpen()) {
                            overlay?.classList.add('show');
                        } else {
                            overlay?.classList.remove('show');
                        }
                    }, 10);
                });
            });

            // คลิกที่ overlay = ปิดเมนู
            overlay?.addEventListener('click', () => {
                closeSidebar();
            });

            // คลิกที่ใดก็ได้บนหน้า: ถ้านอก sidebar + นอกปุ่ม toggle และขณะ mobile → ปิดเมนู
            document.addEventListener('click', (e) => {
                // จำกัดเฉพาะจอเล็ก (คุณจะปรับ breakpoint เองก็ได้)
                if (window.innerWidth > 991) return;

                const clickedInsideSidebar = e.target.closest('.sidebar');
                const clickedToggle = e.target.closest('.toggle-sidebar, .sidenav-toggler');

                if (!clickedInsideSidebar && !clickedToggle && isSidebarOpen()) {
                    closeSidebar();
                }
            });

            // ปิดเมนูอัตโนมัติเมื่อ resize จากจอเล็กไปจอใหญ่ (กันค้าง)
            window.addEventListener('resize', () => {
                if (window.innerWidth > 991) closeSidebar();
            });
        })();
    </script>
</body>

</html>