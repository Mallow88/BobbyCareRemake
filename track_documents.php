<?php
session_start();
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
$conditions = [];
$params = [];
$requests = []; // เริ่มต้นด้วยอาร์เรย์ว่าง

// ตรวจสอบว่ามีการค้นหาหรือไม่
$hasSearch = !empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['priority']) || !empty($_GET['document_number']);

if ($hasSearch) {
    if ($user_id) {
        $conditions[] = "sr.user_id = ?";
        $params[] = $user_id;
    }

    if (!empty($_GET['search'])) {
        $conditions[] = "(sr.title LIKE ? OR dn.document_number LIKE ?)";
        $params[] = '%' . $_GET['search'] . '%';
        $params[] = '%' . $_GET['search'] . '%';
    }

    if (!empty($_GET['status'])) {
        $conditions[] = "sr.status = ?";
        $params[] = $_GET['status'];
    }

    if (!empty($_GET['priority'])) {
        $conditions[] = "sr.priority = ?";
        $params[] = $_GET['priority'];
    }

    if (!empty($_GET['document_number'])) {
        $conditions[] = "dn.document_number LIKE ?";
        $params[] = '%' . $_GET['document_number'] . '%';
    }

    $sql = "
    SELECT 
        sr.*, 
        COUNT(DISTINCT ra.id) AS attachment_count,   
        dn.document_number,
        dn.warehouse_number,
        dn.code_name,
        dn.year,
        dn.month,
        dn.running_number,
        dn.created_at AS document_created_at,

        s.name AS service_name,
        s.category AS service_category,

        dma.status AS div_mgr_status,
        dma.reason AS div_mgr_reason,
        dma.reviewed_at AS div_mgr_reviewed_at,
        div_mgr.name AS div_mgr_name,
        div_mgr.lastname AS div_mgr_lastname,

        aa.status AS assignor_status,
        aa.reason AS assignor_reason,
        aa.estimated_days,
        aa.priority_level,
        aa.reviewed_at AS assignor_reviewed_at,
        assignor.name AS assignor_name,
        assignor.lastname AS assignor_lastname,
        dev.name AS dev_name,
        dev.lastname AS dev_lastname,

        gma.status AS gm_status,
        gma.reason AS gm_reason,
        gma.budget_approved,
        gma.reviewed_at AS gm_reviewed_at,
        gm.name AS gm_name,
        gm.lastname AS gm_lastname,

        sgma.status AS senior_gm_status,
        sgma.reason AS senior_gm_reason,
        sgma.final_notes AS senior_gm_final_notes,
        sgma.reviewed_at AS senior_gm_reviewed_at,
        senior_gm.name AS senior_gm_name,
        senior_gm.lastname AS senior_gm_lastname,

        t.id AS task_id,
        t.task_status,
        t.progress_percentage,
        t.started_at AS task_started_at,
        t.completed_at AS task_completed_at,
        t.developer_notes,

        ur.rating,
        ur.review_comment,
        ur.status AS review_status,
        ur.reviewed_at AS user_reviewed_at

    FROM service_requests sr
    LEFT JOIN request_attachments ra ON sr.id = ra.service_request_id
    LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
    LEFT JOIN services s ON sr.service_id = s.id

    LEFT JOIN div_mgr_approvals dma ON sr.id = dma.service_request_id
    LEFT JOIN users div_mgr ON dma.div_mgr_user_id = div_mgr.id

    LEFT JOIN assignor_approvals aa ON sr.id = aa.service_request_id
    LEFT JOIN users assignor ON aa.assignor_user_id = assignor.id
    LEFT JOIN users dev ON aa.assigned_developer_id = dev.id

    LEFT JOIN gm_approvals gma ON sr.id = gma.service_request_id
    LEFT JOIN users gm ON gma.gm_user_id = gm.id

    LEFT JOIN senior_gm_approvals sgma ON sr.id = sgma.service_request_id
    LEFT JOIN users senior_gm ON sgma.senior_gm_user_id = senior_gm.id

    LEFT JOIN tasks t ON sr.id = t.service_request_id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    LEFT JOIN task_subtasks ts ON t.id = ts.task_id
    ";

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " GROUP BY sr.id ORDER BY sr.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เตรียม statement สำหรับ subtasks ล่วงหน้า
    $sub_stmt = $conn->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY step_order ASC");

    // ดึง subtasks เพิ่มเติมถ้ามี task_id
    foreach ($requests as &$request) {
        $task_id = $request['task_id'];

        if ($task_id) {
            $sub_stmt->execute([$task_id]);
            $request['subtasks'] = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $request['subtasks'] = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบค้นหาเอกสาร - BobbyCareDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-light: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            color: #333;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .header-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            box-shadow: var(--shadow-light);
        }

        .icon-circle {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4a5568;
            margin: 0;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 15px 50px 15px 20px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.2rem;
        }

        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            border: 2px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        var(--primary-gradient) border-box;
            color: #667eea;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .filter-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .welcome-section {
            text-align: center;
            padding: 80px 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: var(--shadow-light);
        }

        .welcome-icon {
            font-size: 5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 20px;
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            color: #718096;
            margin-bottom: 40px;
        }

        .search-tips {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 30px;
            border-radius: 20px;
            text-align: left;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .search-tips h4 {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-tips ul {
            list-style: none;
            padding: 0;
        }

        .search-tips li {
            color: #718096;
            margin-bottom: 12px;
            padding-left: 30px;
            position: relative;
            line-height: 1.6;
        }

        .search-tips li:before {
            content: "✨";
            position: absolute;
            left: 0;
            top: 0;
        }

        .request-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .document-info {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .document-number {
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
        }

        .service-info {
            background: linear-gradient(135deg, #e6fffa 0%, #f0fff4 100%);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #38b2ac;
        }

        .request-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .request-description {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-low {
            background: #e6fffa;
            color: #38b2ac;
        }

        .priority-medium {
            background: #fef5e7;
            color: #d69e2e;
        }

        .priority-high {
            background: #fed7d7;
            color: #e53e3e;
        }

        .priority-urgent {
            background: #fbb6ce;
            color: #b83280;
        }

        .attachment-info {
            background: #f7fafc;
            padding: 8px 15px;
            border-radius: 10px;
            color: #718096;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 15px;
        }

        .request-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .no-results {
            text-align: center;
            padding: 80px 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: var(--shadow-light);
        }

        .no-results-icon {
            font-size: 4rem;
            color: #a0aec0;
            margin-bottom: 30px;
        }

        .no-results h3 {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .no-results p {
            color: #718096;
            font-size: 1.1rem;
        }

        .loading-spinner {
            text-align: center;
            padding: 60px 20px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-icon {
                font-size: 4rem;
            }
            
            .request-meta {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .glass-card {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-lg my-5">
        <!-- Header -->
        <div class="header-card p-4 mb-4 fade-in">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle me-3">
                            <i class="fas fa-search text-white fs-3"></i>
                        </div>
                        <div>
                            <h1 class="page-title mb-1">ระบบค้นหาเอกสาร</h1>
                            <p class="text-muted mb-0 fs-6">ค้นหาและจัดการเอกสารของคุณได้อย่างรวดเร็วและง่ายดาย</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-row gap-2 justify-content-end">
                        <a href="index.php" class="btn btn-outline-gradient">
                            <i class="fas fa-home me-2"></i>หน้าหลัก
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="glass-card p-4 mb-4 fade-in">
            <form method="GET" id="searchForm">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" 
                                   name="search" 
                                   class="form-control search-input" 
                                   placeholder="ค้นหาชื่อเอกสาร, เลขที่เอกสาร หรือคำสำคัญ..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   id="searchInput">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" 
                               name="document_number" 
                               class="form-control search-input" 
                               placeholder="ค้นหาเลขที่เอกสาร..." 
                               value="<?= htmlspecialchars($_GET['document_number'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-gradient w-100">
                            <i class="fas fa-search me-2"></i>ค้นหา
                        </button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="track_documents.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-refresh me-2"></i>ล้างการค้นหา
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <?php if (!$hasSearch): ?>
            <!-- Welcome Section -->
            <div class="welcome-section fade-in">
                <div class="welcome-icon">
                    <i class="fas fa-file-search"></i>
                </div>
                <h2 class="welcome-title">ยินดีต้อนรับสู่ระบบค้นหาเอกสาร</h2>
                <p class="welcome-subtitle">เริ่มต้นการค้นหาโดยกรอกข้อมูลในช่องค้นหาด้านบน</p>
                
                <div class="search-tips">
                    <h4>
                        <i class="fas fa-lightbulb text-warning"></i>
                        เคล็ดลับการค้นหา
                    </h4>
                    <ul>
                        <li>ใช้คำสำคัญที่เกี่ยวข้องกับชื่อเอกสารหรือเนื้อหา</li>
                        <li>ค้นหาด้วยเลขที่เอกสารเพื่อผลลัพธ์ที่แม่นยำ</li>
                        <li>ใช้ตัวกรองสถานะและความเร่งด่วนเพื่อจำกัดผลการค้นหา</li>
                        <li>สามารถค้นหาด้วยชื่อผู้สร้างเอกสารได้</li>
                        <li>ใช้คำค้นหาสั้นๆ เพื่อผลลัพธ์ที่หลากหลาย</li>
                    </ul>
                </div>
            </div>
        <?php elseif (empty($requests)): ?>
            <!-- No Results -->
            <div class="no-results fade-in">
                <div class="no-results-icon">
                    <i class="fas fa-search-minus"></i>
                </div>
                <h3>ไม่พบเอกสารที่ตรงกับการค้นหา</h3>
                <p>ลองปรับเปลี่ยนคำค้นหาหรือตัวกรอง หรือตรวจสอบการสะกดคำ</p>
                <div class="mt-4">
                    <a href="track_documents.php" class="btn btn-outline-gradient me-3">
                        <i class="fas fa-refresh me-2"></i>ค้นหาใหม่
                    </a>
                    <a href="create.php" class="btn btn-gradient">
                        <i class="fas fa-plus me-2"></i>สร้างเอกสารใหม่
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Search Results -->
            <div class="glass-card p-4 fade-in">
                <div class="mb-4">
                    <h4 class="fw-bold d-flex align-items-center">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        ผลการค้นหา (<?= count($requests) ?> รายการ)
                    </h4>
                    <p class="text-muted mb-0">พบเอกสารที่ตรงกับเงื่อนไขการค้นหาของคุณ</p>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($requests as $req): ?>
                        <div class="col-12">
                            <div class="request-card">
                                <!-- Document Number -->
                                <?php if (!empty($req['document_number'])): ?>
                                    <div class="document-info">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="document-number">
                                                    <i class="fas fa-file-alt me-2"></i>
                                                    เลขที่เอกสาร: <?= htmlspecialchars($req['document_number']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    สร้างเมื่อ: <?= $req['document_created_at'] ? date('d/m/Y H:i', strtotime($req['document_created_at'])) : 'ไม่ระบุ' ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    รหัสคลัง: <?= htmlspecialchars($req['warehouse_number'] ?? '') ?> |
                                                    แผนก: <?= htmlspecialchars($req['code_name'] ?? '') ?> |
                                                    Running: <?= htmlspecialchars($req['running_number'] ?? '') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Service Info -->
                                <?php if ($req['service_name']): ?>
                                    <div class="service-info">
                                        <strong>
                                            <i class="fas fa-<?= $req['service_category'] === 'development' ? 'code' : 'tools' ?> me-2"></i>
                                            <?= htmlspecialchars($req['service_name']) ?>
                                        </strong>
                                        <span class="badge bg-info ms-2"><?= htmlspecialchars($req['service_category']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <div class="request-title"><?= htmlspecialchars($req['title']) ?></div>
                                        <div class="request-description">
                                            <?= nl2br(htmlspecialchars(substr($req['description'], 0, 200))) ?>
                                            <?= strlen($req['description']) > 200 ? '...' : '' ?>
                                        </div>
                                    </div>

                                    <div class="ms-3 text-end">
                                        <?php
                                        $status_labels = [
                                            'pending' => 'รอดำเนินการ',
                                            'div_mgr_review' => 'รอผู้จัดการฝ่ายพิจารณา',
                                            'assignor_review' => 'รอผู้จัดการแผนกพิจารณา',
                                            'gm_review' => 'รอผู้จัดการทั่วไปพิจารณา',
                                            'senior_gm_review' => 'รอผู้จัดการอาวุโสพิจารณา',
                                            'approved' => 'อนุมัติแล้ว',
                                            'rejected' => 'ไม่อนุมัติ',
                                            'in_progress' => 'กำลังดำเนินการ',
                                            'completed' => 'เสร็จสิ้น'
                                        ];
                                        $status_class = in_array($req['status'], ['approved', 'completed']) ? 'status-approved' : ($req['status'] === 'rejected' ? 'status-rejected' : 'status-pending');
                                        ?>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_labels[$req['status']] ?? $req['status'] ?>
                                        </span>

                                        <?php if ($req['priority']): ?>
                                            <div class="mt-2">
                                                <?php
                                                $priority_labels = [
                                                    'low' => 'ต่ำ',
                                                    'medium' => 'ปานกลาง',
                                                    'high' => 'สูง',
                                                    'urgent' => 'เร่งด่วน'
                                                ];
                                                ?>
                                                <span class="priority-badge priority-<?= $req['priority'] ?>">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    <?= $priority_labels[$req['priority']] ?? 'ปานกลาง' ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($req['attachment_count'] > 0): ?>
                                    <div class="attachment-info">
                                        <i class="fas fa-paperclip me-1"></i>
                                        <?= $req['attachment_count'] ?> ไฟล์แนบ
                                    </div>
                                <?php endif; ?>

                                <div class="request-meta">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        ส่งคำขอ: <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                        <?php if ($req['updated_at'] !== $req['created_at']): ?>
                                            <span class="ms-3">
                                                <i class="fas fa-edit me-1"></i>
                                                อัปเดต: <?= date('d/m/Y H:i', strtotime($req['updated_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="ms-3">
                                            <i class="fa-solid fa-hourglass-start me-1"></i>
                                            จะเสร็จสิ้นภายใน:
                                            <?php if (!empty($req['estimated_days'])): ?>
                                                <?= htmlspecialchars($req['estimated_days']) ?> วัน
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#status_<?= $req['id'] ?>" aria-expanded="false">
                                            <i class="fas fa-eye me-1"></i>ดูรายละเอียด
                                        </button>
                                        <?php if (in_array($req['status'], ['pending', 'rejected'])): ?>
                                            <a href="edit.php?id=<?= $req['id'] ?>" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit me-1"></i>แก้ไข
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Detailed Status Section (Collapsed) -->
                                <div class="collapse mt-4" id="status_<?= $req['id'] ?>">
                                    <div class="card card-body">
                                        <h5 class="fw-bold mb-4">
                                            <i class="fas fa-route me-2 text-primary"></i>ขั้นตอนการอนุมัติและดำเนินการ
                                        </h5>
                                        <!-- Timeline content would go here - keeping original timeline code -->
                                        <div class="text-muted">
                                            <p>รายละเอียดขั้นตอนการอนุมัติและสถานะปัจจุบันของเอกสาร</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form on filter change
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('searchForm');
            const selects = form.querySelectorAll('select');
            
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // Add loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังค้นหา...';
                    submitBtn.disabled = true;
                    
                    // Submit form
                    setTimeout(() => {
                        form.submit();
                    }, 300);
                });
            });

            // Real-time search with debounce
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        form.submit();
                    }
                }, 800);
            });

            // Smooth scroll for collapsed sections
            const collapseElements = document.querySelectorAll('.collapse');
            collapseElements.forEach(function(element) {
                element.addEventListener('shown.bs.collapse', function() {
                    this.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                });
            });
        });
    </script>
</body>
</html>