<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = $_GET['request_id'] ?? null;

if (!$request_id) {
    header("Location: track_status.php");
    exit();
}

// ดึงข้อมูลงานที่เสร็จแล้ว
$stmt = $conn->prepare("
    SELECT 
        t.*,
        sr.title,
        sr.description,
        sr.user_id,
        dev.name as dev_name,
        dev.lastname as dev_lastname,
        ur.id as review_id,
        ur.status as review_status
    FROM tasks t
    JOIN service_requests sr ON t.service_request_id = sr.id
    JOIN users dev ON t.developer_user_id = dev.id
    LEFT JOIN user_reviews ur ON t.id = ur.task_id
    WHERE sr.id = ? AND sr.user_id = ? AND t.task_status = 'completed'
");
$stmt->execute([$request_id, $user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo "ไม่พบงานที่ต้องรีวิว หรือคุณไม่มีสิทธิ์เข้าถึง";
    exit();
}

// ตรวจสอบว่ารีวิวแล้วหรือยัง
if ($task['review_id'] && $task['review_status'] !== 'pending_review') {
    echo "งานนี้ได้รับการรีวิวแล้ว";
    exit();
}

// ส่งรีวิว
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $review_comment = trim($_POST['review_comment']);
    $action = $_POST['action']; // 'accept' หรือ 'revision'
    $revision_notes = trim($_POST['revision_notes'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = "กรุณาให้คะแนน 1-5 ดาว";
    } elseif ($action === 'revision' && empty($revision_notes)) {
        $error = "กรุณาระบุรายละเอียดที่ต้องการแก้ไข";
    } else {
        try {
            $conn->beginTransaction();
            
            $review_status = $action === 'accept' ? 'accepted' : 'revision_requested';
            
            // บันทึกรีวิว
            if ($task['review_id']) {
                // อัปเดตรีวิวที่มีอยู่
                $stmt = $conn->prepare("
                    UPDATE user_reviews 
                    SET rating = ?, review_comment = ?, status = ?, revision_notes = ?, reviewed_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$rating, $review_comment, $review_status, $revision_notes, $task['review_id']]);
            } else {
                // สร้างรีวิวใหม่
                $stmt = $conn->prepare("
                    INSERT INTO user_reviews (task_id, user_id, rating, review_comment, status, revision_notes, reviewed_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$task['id'], $user_id, $rating, $review_comment, $review_status, $revision_notes]);
            }
            
            // อัปเดตสถานะงาน
            if ($action === 'accept') {
                $update_task = $conn->prepare("UPDATE tasks SET task_status = 'accepted' WHERE id = ?");
                $update_task->execute([$task['id']]);
                
                $update_sr = $conn->prepare("UPDATE service_requests SET developer_status = 'accepted' WHERE id = ?");
                $update_sr->execute([$task['service_request_id']]);
            } else {
                $update_task = $conn->prepare("UPDATE tasks SET task_status = 'revision_requested' WHERE id = ?");
                $update_task->execute([$task['id']]);
                
                $update_sr = $conn->prepare("UPDATE service_requests SET developer_status = 'revision_requested' WHERE id = ?");
                $update_sr->execute([$task['service_request_id']]);
            }
            
            // บันทึก log
            $log_stmt = $conn->prepare("
                INSERT INTO task_status_logs (task_id, old_status, new_status, changed_by, notes) 
                VALUES (?, 'completed', ?, ?, ?)
            ");
            $log_notes = $review_comment . ($revision_notes ? ' | ต้องแก้ไข: ' . $revision_notes : '');
            $log_stmt->execute([$task['id'], $review_status, $user_id, $log_notes]);
            
            $conn->commit();
            header("Location: track_status.php?success=1");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีวิวงาน - BobbyCareDev</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffffff 0%, #341355 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header h1 {
            color: #4a5568;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .task-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 5px solid #4299e1;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            align-items: flex-start;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 130px;
            margin-right: 15px;
        }

        .info-value {
            color: #2d3748;
            flex: 1;
            line-height: 1.5;
        }

        .review-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
        }

        .star-rating {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            justify-content: center;
        }

        .star {
            font-size: 2.5rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .star:hover,
        .star.active {
            color: #f6ad55;
            transform: scale(1.1);
        }

        .rating-text {
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            color: #4a5568;
        }

        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
        }

        textarea:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .action-section {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            min-width: 160px;
            justify-content: center;
        }

        .btn-accept {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-revision {
            background: linear-gradient(135deg, #f6ad55, #ed8936);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 173, 85, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .revision-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #fef5e7;
            border-radius: 10px;
            border-left: 4px solid #f6ad55;
        }

        .revision-section.show {
            display: block;
        }

        .back-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .success-message {
            background: #c6f6d5;
            color: #2f855a;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }

            .star {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> รีวิวและประเมินงาน</h1>
            <p>ให้คะแนนและความเห็นเกี่ยวกับงานที่ได้รับ</p>
        </div>

        <div class="content-card">
            <a href="index2.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> กลับรายการ
            </a>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="task-info">
                <h3 style="margin-bottom: 20px; color: #2d3748;">
                    <i class="fas fa-info-circle"></i> ข้อมูลงานที่เสร็จแล้ว
                </h3>

                <div class="info-row">
                    <div class="info-label">หัวข้องาน:</div>
                    <div class="info-value"><strong><?= htmlspecialchars($task['title']) ?></strong></div>
                </div>

                <div class="info-row">
                    <div class="info-label">รายละเอียด:</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($task['description'])) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">ผู้พัฒนา:</div>
                    <div class="info-value">
                        <i class="fas fa-user-cog"></i> 
                        <?= htmlspecialchars($task['dev_name'] . ' ' . $task['dev_lastname']) ?>
                    </div>
                </div>
<!-- 
                <div class="info-row">
                    <div class="info-label">เริ่มงาน:</div>
                    <div class="info-value">
                        <i class="fas fa-play-circle"></i>
                        <?= $task['started_at'] ? date('d/m/Y H:i', strtotime($task['started_at'])) : 'ไม่ระบุ' ?>
                    </div>
                </div> -->

                <div class="info-row">
                    <div class="info-label">เสร็จงาน:</div>
                    <div class="info-value">
                        <i class="fas fa-check-circle"></i>
                        <?= date('d/m/Y H:i', strtotime($task['completed_at'])) ?>
                    </div>
                </div>

                <?php if ($task['developer_notes']): ?>
                <div class="info-row">
                    <div class="info-label">หมายเหตุจาก Dev:</div>
                    <div class="info-value">
                        <div style="background: #e6fffa; padding: 10px; border-radius: 6px; border-left: 3px solid #38b2ac;">
                            <?= nl2br(htmlspecialchars($task['developer_notes'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <form method="post" class="review-form" id="reviewForm">
                <h3 style="margin-bottom: 25px; color: #2d3748; text-align: center;">
                    <i class="fas fa-clipboard-check"></i> ประเมินคุณภาพงาน
                </h3>

                <div class="form-group">
                    <label>ให้คะแนนงาน (1-5 ดาว):</label>
                    <div class="star-rating">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <div class="rating-text" id="ratingText">กรุณาเลือกคะแนน</div>
                    <input type="hidden" name="rating" id="rating" required>
                </div>

                <div class="form-group">
                    <label for="review_comment">ความเห็นและข้อเสนอแนะ:</label>
                    <textarea 
                        name="review_comment" 
                        id="review_comment" 
                        placeholder="แสดงความเห็นเกี่ยวกับงานที่ได้รับ เช่น คุณภาพงาน ความถูกต้อง ความสมบูรณ์ หรือข้อเสนอแนะอื่นๆ..." 
                        required
                    ></textarea>
                </div>

                <div class="revision-section" id="revisionSection">
                    <h4 style="margin-bottom: 15px; color: #d69e2e;">
                        <i class="fas fa-edit"></i> รายละเอียดที่ต้องการแก้ไข
                    </h4>
                    <textarea 
                        name="revision_notes" 
                        id="revision_notes" 
                        placeholder="ระบุรายละเอียดที่ต้องการให้แก้ไข เช่น ข้อผิดพลาด ส่วนที่ต้องปรับปรุง หรือความต้องการเพิ่มเติม..."
                        rows="4"
                    ></textarea>
                </div>

                <div class="action-section">
                    <h4 style="margin-bottom: 20px; text-align: center; color: #4a5568;">
                        <i class="fas fa-decision"></i> การตัดสินใจ
                    </h4>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-accept" onclick="submitReview('accept')">
                            <i class="fas fa-thumbs-up"></i> ยอมรับงาน
                        </button>
                        <button type="button" class="btn btn-revision" onclick="toggleRevision()">
                            <i class="fas fa-redo"></i> ขอแก้ไขงาน
                        </button>
                    </div>
                </div>

                <input type="hidden" name="action" id="action">
            </form>
        </div>
    </div>

    <script>
        // จัดการ star rating
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const ratingText = document.getElementById('ratingText');
        
        const ratingTexts = {
            1: '⭐ ต้องปรับปรุง',
            2: '⭐⭐ พอใช้',
            3: '⭐⭐⭐ ดี',
            4: '⭐⭐⭐⭐ ดีมาก',
            5: '⭐⭐⭐⭐⭐ ยอดเยี่ยม'
        };
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                ratingText.textContent = ratingTexts[rating];
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const rating = this.dataset.rating;
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#f6ad55';
                    } else {
                        s.style.color = '#e2e8f0';
                    }
                });
            });
        });
        
        document.querySelector('.star-rating').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#f6ad55';
                } else {
                    s.style.color = '#e2e8f0';
                }
            });
        });

        // จัดการการแสดง revision section
        function toggleRevision() {
            const revisionSection = document.getElementById('revisionSection');
            const revisionNotes = document.getElementById('revision_notes');
            
            if (revisionSection.classList.contains('show')) {
                // ถ้าแสดงอยู่แล้ว ให้ส่งฟอร์ม
                submitReview('revision');
            } else {
                // ถ้ายังไม่แสดง ให้แสดง section
                revisionSection.classList.add('show');
                revisionNotes.required = true;
                revisionNotes.focus();
                
                // เปลี่ยนข้อความปุ่ม
                event.target.innerHTML = '<i class="fas fa-paper-plane"></i> ส่งคำขอแก้ไข';
            }
        }

        // ส่งฟอร์มรีวิว
        function submitReview(action) {
            const rating = ratingInput.value;
            const comment = document.getElementById('review_comment').value.trim();
            
            if (!rating) {
                alert('กรุณาให้คะแนนงาน');
                return;
            }
            
            if (!comment) {
                alert('กรุณาใส่ความเห็นเกี่ยวกับงาน');
                return;
            }
            
            if (action === 'revision') {
                const revisionNotes = document.getElementById('revision_notes').value.trim();
                if (!revisionNotes) {
                    alert('กรุณาระบุรายละเอียดที่ต้องการแก้ไข');
                    return;
                }
            }
            
            const confirmMessage = action === 'accept' 
                ? 'ยืนยันการยอมรับงานนี้?' 
                : 'ยืนยันการขอแก้ไขงาน?';
                
            if (confirm(confirmMessage)) {
                document.getElementById('action').value = action;
                document.getElementById('reviewForm').submit();
            }
        }
    </script>
</body>
</html>