<?php
function displayAttachments($service_request_id, $show_download = true) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM request_attachments WHERE service_request_id = ? ORDER BY uploaded_at");
    $stmt->execute([$service_request_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($attachments)) {
        return;
    }
    
    echo '<div class="">';
    echo '<h5 class="fw-bold mb-3"><i class="fas fa-paperclip me-2 text-primary"></i>ไฟล์แนบ (' . count($attachments) . ' ไฟล์)</h5>';
    echo '<div class="row g-3">';
    
    foreach ($attachments as $file) {
        $file_extension = strtolower($file['file_type']);
        $file_size = formatFileSize($file['file_size']);
        $file_url = "../uploads/" . htmlspecialchars($file['stored_filename']);
        
        echo '<div class="col-md-6 col-lg-4">';
        echo '<div class="attachment-card h-100">';

        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            // 🖼 แสดงเป็นรูป preview
            echo '<a href="'.$file_url.'" target="_blank">';
            echo '<img src="'.$file_url.'" alt="'.htmlspecialchars($file['original_filename']).'" class="img-fluid rounded shadow-sm mb-2" style="max-height:200px; object-fit:cover;">';
            echo '</a>';
        } else {
            // 📄 ไฟล์อื่น แสดง icon
            $icon_info = getFileIcon($file_extension);
            echo '<div class="attachment-icon ' . $icon_info['class'] . '">';
            echo '<i class="' . $icon_info['icon'] . '"></i>';
            echo '</div>';
        }

        // 📌 ชื่อไฟล์ + ขนาด
        echo '<div class="attachment-info">';
        echo '<div class="attachment-name" title="' . htmlspecialchars($file['original_filename']) . '">';
        echo htmlspecialchars(truncateFilename($file['original_filename'], 25));
        echo '</div>';
        echo '<div class="attachment-size">' . $file_size . '</div>';
        echo '</div>';
        
        if ($show_download) {
            echo '<div class="attachment-actions">';
            
            // ปุ่มดูไฟล์ (เฉพาะไฟล์ที่เปิดได้)
            if (in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'])) {
               
                echo '</a>';
            }
            
            // ปุ่มดาวน์โหลด
            echo '<a href="../includes/file_download.php?id=' . $file['id'] . '" class="btn btn-success btn-sm" title="ดาวน์โหลด">';
            echo '<i class="fas fa-download"></i>';
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

function getFileIcon($extension) {
    $icons = [
        'pdf' => ['icon' => 'fas fa-file-pdf', 'class' => 'pdf'],
        'jpg' => ['icon' => 'fas fa-file-image', 'class' => 'image'],
        'jpeg' => ['icon' => 'fas fa-file-image', 'class' => 'image'],
        'png' => ['icon' => 'fas fa-file-image', 'class' => 'image'],
        'gif' => ['icon' => 'fas fa-file-image', 'class' => 'image'],
        'doc' => ['icon' => 'fas fa-file-word', 'class' => 'document'],
        'docx' => ['icon' => 'fas fa-file-word', 'class' => 'document'],
        'txt' => ['icon' => 'fas fa-file-alt', 'class' => 'text'],
        'zip' => ['icon' => 'fas fa-file-archive', 'class' => 'archive'],
        'rar' => ['icon' => 'fas fa-file-archive', 'class' => 'archive']
    ];
    
    return $icons[$extension] ?? ['icon' => 'fas fa-file', 'class' => 'other'];
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function truncateFilename($filename, $length) {
    if (strlen($filename) <= $length) return $filename;
    
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    $max_name_length = $length - strlen($extension) - 4; // 4 for "..." and "."
    
    if ($max_name_length > 0) {
        return substr($name, 0, $max_name_length) . '...' . $extension;
    }
    
    return substr($filename, 0, $length - 3) . '...';
}
?>

<style>
.attachments-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    border-left: 4px solid #0d6efd;
}

.attachment-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.attachment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.attachment-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin-bottom: 10px;
}

.attachment-icon.pdf { background: linear-gradient(135deg, #dc3545, #c82333); }
.attachment-icon.image { background: linear-gradient(135deg, #28a745, #20c997); }
.attachment-icon.document { background: linear-gradient(135deg, #007bff, #0056b3); }
.attachment-icon.text { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
.attachment-icon.archive { background: linear-gradient(135deg, #fd7e14, #e55a00); }
.attachment-icon.other { background: linear-gradient(135deg, #6c757d, #545b62); }

.attachment-info {
    flex-grow: 1;
    margin-bottom: 10px;
}

.attachment-name {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
    word-break: break-word;
}

.attachment-size {
    font-size: 0.85rem;
    color: #6c757d;
}

.attachment-actions {
    display: flex;
    gap: 5px;
}

@media (max-width: 768px) {
    .attachment-card {
        margin-bottom: 15px;
    }
}
</style>
