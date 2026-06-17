<?php
/**
 * Card Controller — Upload, Scan, CRUD operations
 */

class CardController {
    /**
     * Show upload page
     */
    public static function uploadPage(): void {
        $view = 'cards/upload';
        $pageTitle = 'Scan Card';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Handle card image upload and AI scan
     */
    public static function scan(): void {
        // Validate CSRF
        if (!CSRF::validate()) {
            Response::json(['error' => 'Invalid request. Please refresh and try again.'], 403);
            return;
        }

        // Validate front image exists
        if (empty($_FILES['card_front']) || $_FILES['card_front']['error'] !== UPLOAD_ERR_OK) {
            Response::json(['error' => 'Please upload the front side of the card.'], 400);
            return;
        }

        // Upload front image
        $frontPath = ImageService::upload($_FILES['card_front'], 'front');
        if (!$frontPath) {
            Response::json(['error' => 'Failed to upload front image. Ensure it is a valid image under 10MB.'], 400);
            return;
        }

        // Upload back image (optional)
        $backPath = null;
        if (!empty($_FILES['card_back']) && $_FILES['card_back']['error'] === UPLOAD_ERR_OK) {
            $backPath = ImageService::upload($_FILES['card_back'], 'back');
        }

        // Call AI to extract data
        $frontAbsPath = ImageService::getAbsolutePath($frontPath);
        $backAbsPath = $backPath ? ImageService::getAbsolutePath($backPath) : null;
        
        $extracted = AIService::extractCardData($frontAbsPath, $backAbsPath);

        if (isset($extracted['error'])) {
            Response::json([
                'error' => $extracted['error'],
                'front_image' => $frontPath,
                'back_image' => $backPath
            ], 500);
            return;
        }

        // Return extracted data + image paths for the review form
        $extracted['card_front_image'] = $frontPath;
        $extracted['card_back_image'] = $backPath;
        
        Response::json([
            'success' => true,
            'data' => $extracted
        ]);
    }

    /**
     * Save reviewed card data to database
     */
    public static function save(): void {
        if (!CSRF::validate()) {
            Response::redirect('cards/upload', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $user = AuthMiddleware::user();
        $db = Database::getConnection();
        
        // Normalize phone numbers — strip spaces, dashes, brackets
        $normalizePhone = fn($p) => preg_replace('/[\s\-().]+/', '', $p ?? '');

        try {
            $db->beginTransaction();

            // 1. Create or find company
            $companyId = CompanyModel::findOrCreate([
                'name' => substr($_POST['company_name'] ?? 'Unknown Company', 0, 255),
                'website' => substr($_POST['company_website'] ?? '', 0, 500),
                'industry' => substr($_POST['company_industry'] ?? '', 0, 150),
                'address' => $_POST['address'] ?? '',
                'city' => substr($_POST['city'] ?? '', 0, 100),
                'state' => substr($_POST['state'] ?? '', 0, 100),
                'pincode' => substr($_POST['pincode'] ?? '', 0, 10),
                'country' => substr($_POST['country'] ?? 'India', 0, 100),
                'gst_number' => substr($_POST['gst_number'] ?? '', 0, 20),
                'notes' => $_POST['notes'] ?? ''
            ]);

            // 1.5 Check for duplicate contact
            $personName = substr($_POST['person_name'] ?? '', 0, 200);
            if (!empty($personName)) {
                $duplicate = Contact::findDuplicate($personName, $companyId, $user['id']);
                if ($duplicate) {
                    $db->rollBack();
                    
                    // Clean up newly uploaded files to prevent duplicates in the uploads folder
                    $frontImg = $_POST['card_front_image'] ?? '';
                    $backImg = $_POST['card_back_image'] ?? '';
                    foreach ([$frontImg, $backImg] as $img) {
                        if (!empty($img) && strpos($img, 'uploads/cards/') === 0 && strpos($img, '..') === false) {
                            $absPath = ImageService::getAbsolutePath($img);
                            if (file_exists($absPath)) {
                                @unlink($absPath);
                            }
                        }
                    }

                    Response::redirect('cards/' . $duplicate['id'], [
                        'type' => 'warning',
                        'message' => 'A card for this person at this company already exists. You have been redirected to the existing card.'
                    ]);
                    return;
                }
            }

            // 2. Create contact
            $contactId = Contact::create([
                'company_id'            => $companyId,
                'name'                  => $personName,
                'designation'           => substr($_POST['designation'] ?? '', 0, 200),
                'department_in_company' => substr($_POST['department'] ?? '', 0, 150),
                'phone_primary'         => substr($normalizePhone($_POST['phone_primary'] ?? ''), 0, 20),
                'phone_secondary'       => substr($normalizePhone($_POST['phone_secondary'] ?? ''), 0, 20),
                'email_primary'         => substr(strtolower(trim($_POST['email_primary'] ?? '')), 0, 255),
                'email_secondary'       => substr(strtolower(trim($_POST['email_secondary'] ?? '')), 0, 255),
                'linkedin_url'          => substr($_POST['linkedin_url'] ?? '', 0, 500),
                'card_front_image'      => $_POST['card_front_image'] ?? '',
                'card_back_image'       => $_POST['card_back_image'] ?? '',
                'added_by_user_id'      => $user['id'],
                'added_by_department_id'=> $user['department_id'],
                'ai_confidence_score'   => $_POST['confidence_score'] ?? null,
                'is_verified'           => isset($_POST['is_verified']) ? 1 : 0
            ]);

            // 3. Process products/services
            $products = $_POST['products_services'] ?? '';
            if (!empty($products)) {
                $productList = is_array($products) ? $products : explode(',', $products);
                foreach ($productList as $productName) {
                    $productName = trim($productName);
                    if (!empty($productName)) {
                        $productId = ProductServiceModel::findOrCreate($productName);
                        ProductServiceModel::linkToCompany($companyId, $productId);
                    }
                }
            }

            // 4. Process tags
            $tags = $_POST['tags'] ?? '';
            if (!empty($tags)) {
                $tagList = is_array($tags) ? $tags : explode(',', $tags);
                foreach ($tagList as $tagName) {
                    $tagName = trim($tagName);
                    if (!empty($tagName)) {
                        // Find or create tag
                        $stmt = $db->prepare("INSERT IGNORE INTO tags (name) VALUES (:name)");
                        $stmt->execute([':name' => strtolower($tagName)]);
                        
                        $stmt = $db->prepare("SELECT id FROM tags WHERE name = :name");
                        $stmt->execute([':name' => strtolower($tagName)]);
                        $tagId = $stmt->fetchColumn();
                        
                        if ($tagId) {
                            $stmt = $db->prepare("INSERT IGNORE INTO contact_tags (contact_id, tag_id) VALUES (:cid, :tid)");
                            $stmt->execute([':cid' => $contactId, ':tid' => $tagId]);
                        }
                    }
                }
            }

            $db->commit();

            // Audit log
            AuditLog::log('create', 'contact', $contactId, [], ['name' => $_POST['person_name']]);

            Response::redirect('cards/' . $contactId, [
                'type' => 'success',
                'message' => 'Card saved successfully!'
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Card save error: " . $e->getMessage());
            Response::redirect('cards/upload', [
                'type' => 'error',
                'message' => 'Failed to save card. Please try again.'
            ]);
        }
    }

    /**
     * List all cards with filters
     */
    public static function list(): void {
        $user = AuthMiddleware::user();
        $scope = $_GET['scope'] ?? 'all';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $userId = null;
        $deptId = null;

        if ($scope === 'mine') {
            $userId = $user['id'];
        }

        $isAdmin = ($user['role'] === 'admin');
        $result = Contact::getFiltered($userId, $deptId, $page, ITEMS_PER_PAGE, $user['id'], $isAdmin);
        $filterOptions = SearchService::getFilterOptions();
        
        $view = 'cards/list';
        $pageTitle = 'All Cards';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Check if the current logged-in user is authorized to view/access a contact card
     */
    private static function checkAccess(?array $contact, ?array $user): bool {
        if (!$contact) {
            return false;
        }
        
        $visibility = $contact['cards_visibility'] ?? 'public';
        if ($visibility === 'public') {
            return true;
        }
        
        // If private, only the uploader or an admin can access
        if ($user) {
            if ($user['role'] === 'admin' || $contact['added_by_user_id'] == $user['id']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Show single card detail
     */
    public static function detail(int $id): void {
        $contact = Contact::findById($id);
        $currentUser = AuthMiddleware::user();
        
        if (!self::checkAccess($contact, $currentUser)) {
            http_response_code(404);
            include VIEW_PATH . '/errors/404.php';
            return;
        }

        $products = [];
        if ($contact['company_id']) {
            $products = ProductServiceModel::getByCompany($contact['company_id']);
        }

        // Rating data
        $currentUser = AuthMiddleware::user();
        $userRating = Rating::getUserRating($id, $currentUser['id']);
        $ratingCount = (int)($contact['rating_count'] ?? 0);
        $ratingAvg = $contact['rating_avg'] ? round((float)$contact['rating_avg'], 1) : null;

        $view = 'cards/detail';
        $pageTitle = $contact['name'] . ' — ' . ($contact['company_name'] ?? '');
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Show edit page
     */
    public static function editPage(int $id): void {
        $contact = Contact::findById($id);
        $user = AuthMiddleware::user();
        
        if (!self::checkAccess($contact, $user)) {
            http_response_code(404);
            include VIEW_PATH . '/errors/404.php';
            return;
        }

        if ($user['role'] !== 'admin' && $contact['added_by_user_id'] != $user['id']) {
            Response::redirect("cards/{$id}", ['type' => 'error', 'message' => 'You do not have permission to edit this card.']);
            return;
        }

        $view = 'cards/edit';
        $pageTitle = 'Edit Card';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Update a card
     */
    public static function update(int $id): void {
        if (!CSRF::validate()) {
            Response::redirect("cards/{$id}/edit", ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $contact = Contact::findById($id);
        $user = AuthMiddleware::user();
        
        if (!self::checkAccess($contact, $user)) {
            Response::redirect("cards", ['type' => 'error', 'message' => 'Card not found.']);
            return;
        }

        if ($user['role'] !== 'admin' && $contact['added_by_user_id'] != $user['id']) {
            Response::redirect("cards/{$id}", ['type' => 'error', 'message' => 'You do not have permission to edit this card.']);
            return;
        }

        Contact::update($id, [
            ':name' => $_POST['person_name'] ?? '',
            ':designation' => $_POST['designation'] ?? '',
            ':department_in_company' => $_POST['department'] ?? '',
            ':phone_primary' => $_POST['phone_primary'] ?? '',
            ':phone_secondary' => $_POST['phone_secondary'] ?? '',
            ':email_primary' => $_POST['email_primary'] ?? '',
            ':email_secondary' => $_POST['email_secondary'] ?? '',
            ':linkedin_url' => $_POST['linkedin_url'] ?? '',
            ':is_verified' => isset($_POST['is_verified']) ? 1 : 0
        ]);

        AuditLog::log('update', 'contact', $id);
        Response::redirect("cards/{$id}", ['type' => 'success', 'message' => 'Card updated successfully!']);
    }

    /**
     * Soft delete a card
     */
    public static function delete(int $id): void {
        if (!CSRF::validate()) {
            Response::redirect("cards/{$id}", ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $contact = Contact::findById($id);
        $user = AuthMiddleware::user();

        if (!self::checkAccess($contact, $user)) {
            Response::redirect("cards", ['type' => 'error', 'message' => 'Card not found.']);
            return;
        }

        if ($user['role'] !== 'admin' && $contact['added_by_user_id'] != $user['id']) {
            Response::redirect("cards/{$id}", ['type' => 'error', 'message' => 'You do not have permission to delete this card.']);
            return;
        }

        Contact::delete($id);
        AuditLog::log('delete', 'contact', $id);
        Response::redirect('cards', ['type' => 'success', 'message' => 'Card deleted successfully.']);
    }

    /**
     * Verify a card
     */
    public static function verify(int $id): void {
        $user = AuthMiddleware::user();
        if ($user['role'] !== 'admin') {
            Response::redirect("cards/{$id}", ['type' => 'error', 'message' => 'Only administrators can verify cards.']);
            return;
        }

        $contact = Contact::findById($id);
        if (!self::checkAccess($contact, $user)) {
            Response::redirect("cards", ['type' => 'error', 'message' => 'Card not found.']);
            return;
        }

        Contact::verify($id);
        AuditLog::log('update', 'contact', $id, [], ['is_verified' => true]);
        Response::redirect("cards/{$id}", ['type' => 'success', 'message' => 'Card marked as verified!']);
    }

    /**
     * Handle rating submission (AJAX)
     */
    public static function rate(int $id): void {
        header('Content-Type: application/json');

        $contact = Contact::findById($id);
        $user = AuthMiddleware::user();

        if (!self::checkAccess($contact, $user)) {
            http_response_code(404);
            echo json_encode(['error' => 'Card not found.']);
            return;
        }

        $user = AuthMiddleware::user();
        $input = json_decode(file_get_contents('php://input'), true);
        $rating = (int)($input['rating'] ?? 0);

        if ($rating === 0) {
            // Remove rating
            Rating::remove($id, $user['id']);
            $updated = Contact::findById($id);
            echo json_encode([
                'success'      => true,
                'removed'      => true,
                'rating_avg'   => $updated['rating_avg'] ? round((float)$updated['rating_avg'], 1) : null,
                'rating_count' => (int)$updated['rating_count']
            ]);
            return;
        }

        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Rating must be between 1 and 5.']);
            return;
        }

        Rating::upsert($id, $user['id'], $rating);
        $updated = Contact::findById($id);

        echo json_encode([
            'success'      => true,
            'user_rating'  => $rating,
            'rating_avg'   => $updated['rating_avg'] ? round((float)$updated['rating_avg'], 1) : null,
            'rating_count' => (int)$updated['rating_count']
        ]);
    }

    /**
     * Export a contact as vCard (.vcf)
     */
    public static function vcard(int $id): void {
        $contact = Contact::findById($id);
        $currentUser = AuthMiddleware::user();

        if (!self::checkAccess($contact, $currentUser)) {
            http_response_code(404);
            include VIEW_PATH . '/errors/404.php';
            return;
        }

        // Log the export action
        AuditLog::log('export', 'contact', $id);

        // Clean names for file attachment name
        $safeName = preg_replace('/[^A-Za-z0-9]/', '_', $contact['name']);

        header('Content-Type: text/x-vcard; charset=utf-8; name="' . $safeName . '.vcf"');
        header("Content-Disposition: inline; filename=\"{$safeName}.vcf\"");

        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        $vcard .= "FN:" . $contact['name'] . "\r\n";
        
        // Split name into First and Last if possible
        $parts = explode(' ', $contact['name'], 2);
        $lastName = $parts[1] ?? '';
        $firstName = $parts[0] ?? '';
        $vcard .= "N:{$lastName};{$firstName};;;\r\n";

        if (!empty($contact['company_name'])) {
            $vcard .= "ORG:" . $contact['company_name'] . "\r\n";
        }
        if (!empty($contact['designation'])) {
            $vcard .= "TITLE:" . $contact['designation'] . "\r\n";
        }
        if (!empty($contact['phone_primary'])) {
            $vcard .= "TEL;TYPE=CELL,VOICE;TYPE=pref:" . $contact['phone_primary'] . "\r\n";
        }
        if (!empty($contact['phone_secondary'])) {
            $vcard .= "TEL;TYPE=WORK,VOICE:" . $contact['phone_secondary'] . "\r\n";
        }
        if (!empty($contact['email_primary'])) {
            $vcard .= "EMAIL;TYPE=PREF,INTERNET:" . $contact['email_primary'] . "\r\n";
        }
        if (!empty($contact['email_secondary'])) {
            $vcard .= "EMAIL;TYPE=WORK,INTERNET:" . $contact['email_secondary'] . "\r\n";
        }
        if (!empty($contact['linkedin_url'])) {
            $vcard .= "URL;TYPE=LinkedIn:" . $contact['linkedin_url'] . "\r\n";
        }
        
        $adr = [];
        $adr[] = ''; 
        $adr[] = ''; 
        $adr[] = str_replace(["\r", "\n"], ' ', $contact['company_address'] ?? ''); 
        $adr[] = $contact['company_city'] ?? ''; 
        $adr[] = ''; 
        $adr[] = ''; 
        $adr[] = 'India'; 
        $vcard .= "ADR;TYPE=WORK:;;" . implode(';', $adr) . "\r\n";

        $vcard .= "END:VCARD\r\n";

        echo $vcard;
        exit;
    }

    /**
     * Delete temporary uploaded files (for cancels or duplicates)
     */
    public static function cleanupTemp(): void {
        $frontImg = $_POST['front_image'] ?? '';
        $backImg = $_POST['back_image'] ?? '';

        $deletedCount = 0;
        foreach ([$frontImg, $backImg] as $img) {
            if (!empty($img)) {
                // Ensure it is inside uploads/cards/ to prevent directory traversal
                if (strpos($img, 'uploads/cards/') === 0 && strpos($img, '..') === false) {
                    $absPath = ImageService::getAbsolutePath($img);
                    if (file_exists($absPath)) {
                        if (@unlink($absPath)) {
                            $deletedCount++;
                        }
                    }
                }
            }
        }

        Response::json(['success' => true, 'deleted_count' => $deletedCount]);
    }
}
