<?php
/**
 * upload.php - مكتبة رفع الصور الآمنة
 * تستبدل نظام Base64 السابق
 */

if (!function_exists('uploadImage')) {
    /**
     * يرفع صورة من $_FILES إلى المجلد المحدد
     * @param array $file  العنصر من $_FILES
     * @param string $subDir  ads | avatars | chat | commission
     * @return array ['success' => bool, 'path' => string, 'message' => string]
     */
    function uploadImage($file, $subDir = 'ads') {
        $allowedTypes = explode(',', env('ALLOWED_IMAGE_TYPES', 'image/jpeg,image/png,image/webp,image/gif'));
        $maxSize = (int) env('MAX_UPLOAD_SIZE', 5242880);

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'لم يتم رفع أي ملف'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'خطأ في الرفع (' . $file['error'] . ')'];
        }
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'حجم الملف يتجاوز الحد المسموح به (' . round($maxSize/1048576, 1) . ' MB)'];
        }

        // التحقق من النوع الفعلي
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) {
            return ['success' => false, 'message' => 'نوع الملف غير مدعوم. مسموح: JPG, PNG, WebP, GIF'];
        }

        // امتداد آمن
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];
        $ext = $extMap[$mime] ?? 'jpg';

        $uploadDir = dirname(__DIR__, 2) . '/uploads/' . $subDir;
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

        $filename = $subDir . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'فشل حفظ الملف على السيرفر'];
        }

        // المسار النسبي للتخزين في DB
        $relativePath = 'uploads/' . $subDir . '/' . $filename;
        return ['success' => true, 'path' => $relativePath, 'message' => 'تم الرفع بنجاح'];
    }
}

if (!function_exists('uploadBase64Image')) {
    /**
     * يرفع صورة Base64 (للتوافق مع كود الـ frontend القديم)
     * يقبل: data:image/png;base64,iVBORw0K...
     */
    function uploadBase64Image($dataUrl, $subDir = 'ads') {
        if (empty($dataUrl) || !is_string($dataUrl)) {
            return ['success' => false, 'message' => 'بيانات غير صالحة'];
        }

        // إذا كان مسار جاهز (موجود مسبقاً) — أرجعه كما هو
        if (strpos($dataUrl, 'uploads/') === 0 || strpos($dataUrl, 'http') === 0) {
            return ['success' => true, 'path' => $dataUrl];
        }

        if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,(.+)$/', $dataUrl, $m)) {
            return ['success' => false, 'message' => 'صيغة Base64 غير صالحة'];
        }

        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $data = base64_decode($m[2], true);
        if ($data === false) {
            return ['success' => false, 'message' => 'فشل فك ترميز الصورة'];
        }

        $maxSize = (int) env('MAX_UPLOAD_SIZE', 5242880);
        if (strlen($data) > $maxSize) {
            return ['success' => false, 'message' => 'حجم الصورة كبير جداً'];
        }

        $uploadDir = dirname(__DIR__, 2) . '/uploads/' . $subDir;
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

        $filename = $subDir . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = $uploadDir . '/' . $filename;

        if (file_put_contents($destination, $data) === false) {
            return ['success' => false, 'message' => 'فشل حفظ الصورة'];
        }

        $relativePath = 'uploads/' . $subDir . '/' . $filename;
        return ['success' => true, 'path' => $relativePath];
    }
}

if (!function_exists('deleteUploadedFile')) {
    function deleteUploadedFile($relativePath) {
        if (empty($relativePath)) return false;
        // أمان: تأكد من المسار داخل uploads/
        if (strpos($relativePath, 'uploads/') !== 0) return false;
        $full = dirname(__DIR__, 2) . '/' . $relativePath;
        if (file_exists($full) && is_file($full)) {
            return @unlink($full);
        }
        return false;
    }
}

if (!function_exists('imageUrl')) {
    /**
     * تحويل مسار صورة من DB إلى URL كامل صالح للعرض
     */
    function imageUrl($path) {
        if (empty($path)) return '';
        if (strpos($path, 'http') === 0) return $path; // URL كامل
        if (strpos($path, 'data:') === 0) return $path; // Base64 (قديم)
        // مسار نسبي → نحوّله ليصبح صالحاً للوصول
        return '../' . ltrim($path, '/');
    }
}
