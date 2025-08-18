<?php  
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assignor') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_requests.php");
    exit();
}


// ฟังก์ชันส่งข้อความเข้า LINE Official Account
 function sendLinePushFlex($toUserId, $req) {
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
                    "text" => "📑 มีคำขอใหม่รออนุมัติ",
                    "weight" => "bold",
                    "size" => "lg",
                    "align" => "center",
                    "color" => "#ffffffff" 
                ],
                [
                    "type" => "text",
                    "text" => $req['document_number'] ?? "-",
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
                ["type" => "text", "text" => "📌 เรื่อง: {$req['title']}", "wrap" => true, "weight" => "bold", "size" => "sm", "color" => "#333333"],
                ["type" => "text", "text" => "📝 {$req['description']}", "wrap" => true, "size" => "sm", "color" => "#666666"],
                ["type" => "text", "text" => "✨ ประโยชน์: {$req['expected_benefits']}", "wrap" => true, "size" => "sm", "color" => "#32CD32"],
                ["type" => "separator", "margin" => "md"],
                ["type" => "text", "text" => "ผู้ขอบริการ : {$req['name']} {$req['lastname']}", "size" => "sm", "color" => "#000000"],
                ["type" => "text", "text" => "🆔 {$req['employee_id']} | 🏢 {$req['department']}", "size" => "sm", "color" => "#444444"]
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
                        "uri" => "http://yourdomain/gmapprover/view.php?id={$req['request_id']}"
                    ]
                ]
            ],
              "backgroundColor" => "#5677fc"
        ]
    ];

    $flexMessage = [
        "type" => "flex",
        "altText" => "📑 มีคำขอใหม่รออนุมัติ",
        "contents" => $bubble
    ];

    $data = [
        "to" => $toUserId,
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





$assignor_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'];
$status = $_POST['status'];
$reason = $_POST['reason'] ?? '';
$assigned_developer_id = $_POST['assigned_developer_id'] ?? null;
$priority_level = $_POST['priority_level'] ?? 'medium';
$estimated_days = $_POST['estimated_days'] ?? null;
$deadline = $_POST['deadline'] ?? null;
$budget_approved = $_POST['budget_approved'] ?? null; // ✅ เพิ่ม budget_approved

// ✅ ลบ: development_service_id
// ✅ ลบการ validate development_service_id
if ($status === 'rejected' && trim($reason) === '') {
    $_SESSION['error'] = "กรุณาระบุเหตุผลเมื่อไม่อนุมัติ";
    header("Location: view_requests.php");
    exit();
}

if ($status === 'approved' && !$assigned_developer_id) {
    $_SESSION['error'] = "กรุณาเลือกผู้พัฒนาที่จะมอบหมายงาน";
    header("Location: view_requests.php");
    exit();
}

try {
    $conn->beginTransaction();



    
       // ✅ บันทึกการอนุมัติพร้อม budget_approved
    $stmt = $conn->prepare("
        INSERT INTO assignor_approvals (
            service_request_id, assignor_user_id, assigned_developer_id,
            status, reason, estimated_days, priority_level, budget_approved, reviewed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        assigned_developer_id = VALUES(assigned_developer_id),
        status = VALUES(status), 
        reason = VALUES(reason), 
        estimated_days = VALUES(estimated_days),
        priority_level = VALUES(priority_level),
        budget_approved = VALUES(budget_approved),
        reviewed_at = NOW()
    ");
    $stmt->execute([
        $request_id, 
        $assignor_id, 
        $assigned_developer_id,
        $status, 
        $reason, 
        $estimated_days, 
        $priority_level,
        $budget_approved
    ]);

    // ✅ ลบส่วนนี้: ไม่ต้องอัปเดต service_id อีกแล้ว
    // if ($status === 'approved' && $development_service_id) {
    //     $stmt = $conn->prepare("UPDATE service_requests SET service_id = ? WHERE id = ?");
    //     $stmt->execute([$development_service_id, $request_id]);
    // }

    // อัปเดตสถานะใน service_requests
    $new_status = $status === 'approved' ? 'gm_review' : 'rejected';
    $current_step = $status === 'approved' ? 'assignor_approved' : 'assignor_rejected';
    
    $stmt = $conn->prepare("
        UPDATE service_requests 
        SET status = ?, current_step = ?, priority = ?, estimated_days = ?, deadline = ? 
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $current_step, $priority_level, $estimated_days, $deadline, $request_id]);

    // บันทึก log
    $stmt = $conn->prepare("
        INSERT INTO document_status_logs (
            service_request_id, step_name, status, reviewer_id, reviewer_role, notes
        ) VALUES (?, 'assignor_review', ?, ?, 'assignor', ?)
    ");
    $stmt->execute([$request_id, $status, $assignor_id, $reason]);

    $conn->commit();
    $_SESSION['success'] = "บันทึกผลการพิจารณาเรียบร้อยแล้ว";




// === ส่งแจ้งเตือน LINE Official Account ไปยัง GM Approver === 
if ($status === 'approved') {
    // ดึงข้อมูล request
    $req_stmt = $conn->prepare("
        SELECT sr.id as request_id, sr.title, sr.description, sr.expected_benefits, dn.document_number,
               u.name, u.lastname, u.employee_id, u.department
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.id
        LEFT JOIN document_numbers dn ON sr.id = dn.service_request_id
        WHERE sr.id = ?
    ");
    $req_stmt->execute([$request_id]);
    $req = $req_stmt->fetch(PDO::FETCH_ASSOC);

    if ($req) {
        // ดึง GM Approver ทั้งหมด
        $gm_stmt = $conn->prepare("SELECT line_id FROM users WHERE role = 'gmapprover' AND is_active = 1");
        $gm_stmt->execute();
        $gms = $gm_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($gms as $gm) {
            if (!empty($gm['line_id'])) {
                // ✅ ส่ง Flex message แทน text ธรรมดา
                sendLinePushFlex($gm['line_id'], $req);
            }
        }
    }
}




} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header("Location: view_requests2.php");
exit();
?>
