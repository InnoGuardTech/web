<?php
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($method === 'GET') {
    // Get ad details if ID is provided
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $db->prepare("
            SELECT a.*, u.name as userName, u.phone as userPhone, u.rating as userRating, u.joinedDate 
            FROM ads a 
            JOIN users u ON a.userId = u.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $ad = $stmt->fetch();
        
        if ($ad) {
            // Update views
            $db->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$id]);
            
            // Format data
            $ad['images'] = json_decode($ad['images'], true) ?: [];
            $ad['specifications'] = json_decode($ad['specifications'], true) ?: [];
            $ad['formattedPrice'] = formatPrice($ad['price']);
            $ad['formattedDate'] = formatArabicDate($ad['createdAt']);
            
            // Check if favorited by current user
            $ad['isFavorite'] = false;
            if (isset($_SESSION['user_id'])) {
                $favStmt = $db->prepare("SELECT 1 FROM favorites WHERE userId = ? AND adId = ?");
                $favStmt->execute([$_SESSION['user_id'], $id]);
                if ($favStmt->fetchColumn()) $ad['isFavorite'] = true;
            }
            
            // Fetch Comments
            $comStmt = $db->prepare("SELECT id, username, content, createdAt FROM comments WHERE adId = ? ORDER BY createdAt ASC");
            $comStmt->execute([$id]);
            $ad['comments'] = array_map(function($c) {
                return [
                    'id' => $c['id'],
                    'username' => $c['username'],
                    'content' => $c['content'],
                    'date' => formatArabicDate($c['createdAt'])
                ];
            }, $comStmt->fetchAll());
            
            jsonSuccess($ad);
        } else {
            jsonError('الإعلان غير موجود', 404);
        }
    } elseif ($action === 'favorites') {
        requireAuth();
        $stmt = $db->prepare("
            SELECT a.*, u.name as userName 
            FROM favorites f 
            JOIN ads a ON f.adId = a.id 
            JOIN users u ON a.userId = u.id 
            WHERE f.userId = ? 
            ORDER BY f.createdAt DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $ads = $stmt->fetchAll();
        
        $formattedAds = array_map(function($ad) {
            $images = json_decode($ad['images'], true);
            $firstImage = (!empty($images) && is_array($images)) ? $images[0] : 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80';
            
            return [
                'id' => $ad['id'],
                'title' => $ad['title'],
                'price' => formatPrice($ad['price']),
                'city' => $ad['city'],
                'category' => getCategoryName($ad['category']),
                'icon' => getCategoryIcon($ad['category']),
                'image' => $firstImage,
                'userName' => $ad['userName'],
                'date' => formatArabicDate($ad['createdAt'])
            ];
        }, $ads);

        jsonSuccess($formattedAds);
    } else {
        // List ads with filters
        $catFilter = $_GET['cat'] ?? 'all';
        $cityFilter = $_GET['city'] ?? 'الكل';
        $searchQuery = $_GET['q'] ?? '';
        $brandFilter = $_GET['brand'] ?? '';

        $query = "SELECT a.*, u.name as userName FROM ads a JOIN users u ON a.userId = u.id WHERE 1=1";
        $params = [];

        if ($catFilter !== 'all') {
            $query .= " AND a.category = ?";
            $params[] = $catFilter;
        }

        if ($cityFilter !== 'الكل') {
            $query .= " AND a.city = ?";
            $params[] = $cityFilter;
        }

        if (!empty($brandFilter) && $brandFilter !== 'all') {
            $query .= " AND a.carBrand = ?";
            $params[] = $brandFilter;
        }

        if (!empty($searchQuery)) {
            $query .= " AND (a.title LIKE ? OR a.description LIKE ?)";
            $params[] = "%$searchQuery%";
            $params[] = "%$searchQuery%";
        }

        $query .= " ORDER BY a.isPinned DESC, a.createdAt DESC LIMIT 50";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $ads = $stmt->fetchAll();

        // Format ads for JSON
        $formattedAds = array_map(function($ad) {
            $images = json_decode($ad['images'], true);
            $firstImage = (!empty($images) && is_array($images)) ? $images[0] : 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80';
            
            return [
                'id' => $ad['id'],
                'title' => $ad['title'],
                'price' => formatPrice($ad['price']),
                'city' => $ad['city'],
                'category' => getCategoryName($ad['category']),
                'icon' => getCategoryIcon($ad['category']),
                'image' => $firstImage,
                'userName' => $ad['userName'],
                'date' => formatArabicDate($ad['createdAt']),
                'isPinned' => $ad['isPinned']
            ];
        }, $ads);

        jsonSuccess($formattedAds);
    }
} elseif ($method === 'POST') {
    if ($action === 'toggle_favorite') {
        requireAuth();
        $adId = intval($input['ad_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        
        $check = $db->prepare("SELECT id FROM favorites WHERE userId = ? AND adId = ?");
        $check->execute([$userId, $adId]);
        if ($check->fetchColumn()) {
            $db->prepare("DELETE FROM favorites WHERE userId = ? AND adId = ?")->execute([$userId, $adId]);
            jsonSuccess(['isFavorite' => false], 'تم الإزالة من المفضلة');
        } else {
            $db->prepare("INSERT INTO favorites (userId, adId) VALUES (?, ?)")->execute([$userId, $adId]);
            jsonSuccess(['isFavorite' => true], 'تمت الإضافة للمفضلة');
        }
    } elseif ($action === 'delete') {
        requireAuth();
        $adId = intval($input['ad_id'] ?? 0);
        
        $stmt = $db->prepare("SELECT userId, images FROM ads WHERE id = ?");
        $stmt->execute([$adId]);
        $ad = $stmt->fetch();
        
        if (!$ad) jsonError('الإعلان غير موجود', 404);
        
        if ($ad['userId'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
            jsonError('غير مصرح لك بحذف هذا الإعلان', 403);
        }
        
        $images = json_decode($ad['images'], true) ?: [];
        foreach ($images as $img) {
            if (strpos($img, '../uploads/') === 0) {
                $filePath = __DIR__ . '/' . $img;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
        
        $db->prepare("DELETE FROM ads WHERE id = ?")->execute([$adId]);
        jsonSuccess([], 'تم حذف الإعلان بنجاح');
    } elseif ($action === 'add_comment') {
        requireAuth();
        $adId = intval($input['ad_id'] ?? 0);
        $content = sanitize($input['content'] ?? '');
        
        if (empty($content)) jsonError('لا يمكن ترك التعليق فارغاً');
        
        $db->prepare("INSERT INTO comments (username, content, adId) VALUES (?, ?, ?)")
           ->execute([$_SESSION['user_name'], $content, $adId]);
           
        // Notify ad owner
        $stmt = $db->prepare("SELECT userId FROM ads WHERE id = ?");
        $stmt->execute([$adId]);
        $ownerId = $stmt->fetchColumn();
        
        if ($ownerId && $ownerId != $_SESSION['user_id']) {
            $db->prepare("INSERT INTO notifications (userId, title, content) VALUES (?, 'رد جديد', ?)")
               ->execute([$ownerId, "هناك رد جديد على إعلانك من {$_SESSION['user_name']}"]);
        }
           
        jsonSuccess([], 'تم إضافة الرد بنجاح');
    }

    requireAuth(); // Must be logged in to post
    
    $title = sanitize($input['title'] ?? '');
    $description = sanitize($input['description'] ?? '');
    $category = sanitize($input['category'] ?? '');
    $city = sanitize($input['city'] ?? '');
    $price = !empty($input['price']) ? floatval($input['price']) : null;
    
    $imagesArray = $input['images_base64'] ?? [];
    $savedImages = [];
    if (!is_array($imagesArray) || count($imagesArray) == 0) {
        $savedImages = ['https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80'];
    } else {
        foreach ($imagesArray as $base64) {
            if (strpos($base64, 'http') === 0) {
                $savedImages[] = $base64;
                continue;
            }
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
                
                $data = base64_decode($base64);
                if ($data === false) continue;
                
                $filename = uniqid('ad_') . '.' . $type;
                $filepath = __DIR__ . '/../uploads/ads/' . $filename;
                
                if (file_put_contents($filepath, $data)) {
                    $savedImages[] = '../uploads/ads/' . $filename;
                }
            }
        }
        if (empty($savedImages)) {
            $savedImages = ['https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80'];
        }
    }
    
    $carBrand = null;
    $carYear = null;
    $carTransmission = null;
    $carMileage = null;
    $propertyType = null;
    $propertyRooms = null;
    $propertyContract = null;
    $specifications = [];

    if ($category === 'cars') {
        $carBrand = sanitize($input['carBrand'] ?? 'الكل');
        $carYear = sanitize($input['carYear'] ?? 'الكل');
        $carTransmission = sanitize($input['carTransmission'] ?? 'الكل');
        $carMileage = !empty($input['carMileage']) ? intval($input['carMileage']) : 0;
        
        $specifications = [
            'الماركة' => $carBrand,
            'سنة الصنع' => $carYear,
            'ناقل الحركة' => ($carTransmission === 'auto' ? 'أوتوماتيك' : 'عادي'),
            'الممشى' => number_format($carMileage) . " كم"
        ];
    } elseif ($category === 'realestate') {
        $propertyType = sanitize($input['propertyType'] ?? 'الكل');
        $propertyRooms = sanitize($input['propertyRooms'] ?? 'الكل');
        $propertyContract = sanitize($input['propertyContract'] ?? 'الكل');
        
        $pt = ['villa' => 'فيلا فاخرة', 'apartment' => 'شقة راقية', 'land' => 'أرض سكنية'];
        $specifications = [
            'نوع العقار' => ($pt[$propertyType] ?? $propertyType),
            'عدد الغرف' => $propertyRooms . " غرف",
            'نوع العقد' => ($propertyContract === 'sell' ? 'بيع' : 'إيجار')
        ];
    }

    if (isset($input['spec_key']) && isset($input['spec_val']) && is_array($input['spec_key']) && is_array($input['spec_val'])) {
        $keys = $input['spec_key'];
        $vals = $input['spec_val'];
        for ($i = 0; $i < count($keys); $i++) {
            $k = sanitize($keys[$i]);
            $v = sanitize($vals[$i]);
            if (!empty($k) && !empty($v)) {
                $specifications[$k] = $v;
            }
        }
    }

    if (empty($title) || empty($description) || $category === 'all' || $city === 'الكل') {
        jsonError('الرجاء كتابة العنوان والتفاصيل واختيار القسم والمدينة بدقة');
    }

    try {
        $stmt = $db->prepare("INSERT INTO ads (title, description, category, city, price, images, specifications, userId, carBrand, carYear, carTransmission, carMileage, propertyType, propertyRooms, propertyContract) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title, $description, $category, $city, $price, 
            json_encode($savedImages), json_encode($specifications), 
            $_SESSION['user_id'], $carBrand, $carYear, $carTransmission, 
            $carMileage, $propertyType, $propertyRooms, $propertyContract
        ]);
        
        $newAdId = $db->lastInsertId();
        jsonSuccess(['id' => $newAdId], 'تم نشر الإعلان بنجاح');
    } catch (Exception $e) {
        jsonError('حدث خطأ أثناء حفظ الإعلان: ' . $e->getMessage(), 500);
    }
}

jsonError('طلب غير صالح');
