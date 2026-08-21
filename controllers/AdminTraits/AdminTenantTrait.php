<?php
/**
 * AdminTenantTrait - Quản lý người thuê: list, gán phòng, yêu cầu thuê, ở ghép, tài khoản
 */
trait AdminTenantTrait
{
    // ==========================================
    // TENANTS LIST & ASSIGN
    // ==========================================

    public function tenants(): void
    {
        $tenants = array_values(array_filter(UserModel::getAll(), fn($u) => ($u['role'] ?? 0) === 0));

        $rooms = array_values(array_filter(array_map(fn($r) => [
            'occupant_count' => RoomModel::countOccupants($r['id'] ?? 0),
            'available_slots' => max(0, (int)($r['max_occupancy'] ?? 0) - RoomModel::countOccupants($r['id'] ?? 0)),
        ] + $r, RoomModel::getAll(['status' => 'available'])), fn($r) => ($r['available_slots'] ?? 0) > 0));

        $tenantMessage = pullFlash('admin_tenant_message');
        $tenantError = pullFlash('admin_tenant_error');
        $oldInput = pullFlash('admin_tenant_old', []);
        $assignmentForm = array_merge(['user_id' => 0, 'room_id' => 0], is_array($oldInput) ? $oldInput : []);
        $pageTitle = 'Quản lý Người thuê - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/tenants.php';
    }

    public function addTenant(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-tenants');
        verify_csrf();

        $userId = (int)($_POST['user_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $oldInput = ['user_id' => $userId, 'room_id' => $roomId];

        if ($userId <= 0) { setFlash('admin_tenant_error', 'Chọn tenant.'); setFlash('admin_tenant_old', $oldInput); redirectTo('admin-tenants'); }
        if ($roomId <= 0) { setFlash('admin_tenant_error', 'Chọn phòng trống.'); setFlash('admin_tenant_old', $oldInput); redirectTo('admin-tenants'); }

        try {
            UserModel::assignToRoom($userId, $roomId);
            setFlash('admin_tenant_message', 'Đã gán tenant vào phòng.');
            redirectTo('admin-tenants');
        } catch (Throwable $e) {
            setFlash('admin_tenant_error', $e->getMessage());
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
    }

    // ==========================================
    // RENT & ROOMMATE REQUESTS
    // ==========================================

    public function rentRequests(): void
    {
        [$rentFilter, $rentKeyword, $roommateFilter, $roommateKeyword] = $this->resolveRentRequestFilters();

        $rentParams = ['status' => $rentFilter === 'all' ? '' : $rentFilter];
        if ($rentKeyword) $rentParams['keyword'] = $rentKeyword;
        $requests = RentalRequestModel::getAllWithDetails($rentParams);

        $pendingRentCount = count(RentalRequestModel::getAllWithDetails(['status' => 'pending']));

        $roommateParams = ['status' => $roommateFilter === 'all' ? '' : $roommateFilter];
        if ($roommateKeyword) $roommateParams['keyword'] = $roommateKeyword;
        $roommateRequests = RoommateRequestModel::getAll($roommateParams);
        $pendingRoommateCount = RoommateRequestModel::countPendingAdmin();

        $message = pullFlash('rent_request_message', '');
        $error = pullFlash('rent_request_error', '');
        $roommateMessage = pullFlash('roommate_admin_message', '');
        $roommateError = pullFlash('roommate_admin_error', '');
        $pageTitle = 'Yêu cầu thuê & ở ghép - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/rent_requests.php';
    }

    public function rentRequestsFilterApi(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        [$rentFilter, $rentKeyword, $roommateFilter, $roommateKeyword] = $this->resolveRentRequestFilters();

        $rentParams = ['status' => $rentFilter === 'all' ? '' : $rentFilter];
        if ($rentKeyword) $rentParams['keyword'] = $rentKeyword;
        $requests = RentalRequestModel::getAllWithDetails($rentParams);

        $roommateParams = ['status' => $roommateFilter === 'all' ? '' : $roommateFilter];
        if ($roommateKeyword) $roommateParams['keyword'] = $roommateKeyword;
        $roommateRequests = RoommateRequestModel::getAll($roommateParams);

        echo json_encode([
            'success' => true,
            'rent' => ['html' => $this->renderRentRequestItems(RentalRequestModel::getAllWithDetails(['status' => $rentFilter === 'all' ? '' : $rentFilter, 'keyword' => $rentKeyword])), 'total' => count($requests)],
            'roommate' => ['html' => $this->renderRoommateRequestItems(RoommateRequestModel::getAll(['status' => $roommateFilter === 'all' ? '' : $roommateFilter, 'keyword' => $roommateKeyword])), 'total' => count($roommateRequests)],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function resolveRentRequestFilters(): array
    {
        $rentFilter = trim($_GET['rent_filter'] ?? 'all');
        if (!in_array($rentFilter, ['all','pending','approved','rejected'], true)) $rentFilter = 'all';

        $roommateFilter = trim($_GET['roommate_filter'] ?? 'all');
        if (!in_array($roommateFilter, ['all','pending_admin','approved','rejected'], true)) $roommateFilter = 'all';

        return [$rentFilter, trim($_GET['rent_keyword'] ?? ''), $roommateFilter, trim($_GET['roommate_keyword'] ?? '')];
    }

    private function renderRentRequestItems(array $requests): string
    {
        $html = '';
        foreach ($requests as $req) { ob_start(); require BASE_PATH . 'views/admin/tenants/partials/rent_request_item.php'; $html .= ob_get_clean(); }
        return $html;
    }

    private function renderRoommateRequestItems(array $requests): string
    {
        $html = '';
        foreach ($requests as $rr) { ob_start(); require BASE_PATH . 'views/admin/tenants/partials/roommate_request_item.php'; $html .= ob_get_clean(); }
        return $html;
    }

    // ==========================================
    // RENTAL REQUEST ACTIONS
    // ==========================================

    public function approveRentRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RentalRequestModel::getById((int)($_POST['request_id'] ?? 0));
        if (!$req || ($req['status']??'') !== 'pending') { setFlash('rent_request_error', $req ? 'Đã xử lý.' : 'Không tồn tại.'); redirectTo('admin-rent-requests'); }

        $userId = (int)$req['user_id']; $roomId = (int)$req['room_id'];
        $room = RoomModel::getById($roomId);
        if (!$room || !empty(UserModel::getById($userId)['room_id']) || RoomModel::countOccupants($roomId) + 1 > max(1, (int)($room['max_occupancy']??1))) {
            setFlash('rent_request_error', 'Phòng hết chỗ hoặc user đã có phòng.');
            redirectTo('admin-rent-requests');
        }

        try {
            UserModel::assignToRoom($userId, $roomId);
            RentalRequestModel::setStatus((int)$req['id'], 'approved', 'Đã duyệt.');
            NotificationModel::create(['user_id'=>$userId,'type'=>'general','title'=>'Yêu cầu thuê được duyệt','content'=>'Chúc mừng! Yêu cầu thuê phòng "'.($room['name']??'').'" đã được duyệt. Ngày vào ở: '.date('d/m/Y',strtotime($req['move_in_date']??date('Y-m-d'))).'.']);
            setFlash('rent_request_message', 'Đã duyệt và gán phòng "'.($room['name']??'').'".');
        } catch (Throwable $e) { setFlash('rent_request_error', 'Lỗi: '.$e->getMessage()); }
        redirectTo('admin-rent-requests');
    }

    public function rejectRentRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RentalRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req || ($req['status']??'') !== 'pending') { setFlash('rent_request_error', $req?'Đã xử lý.':'Không tồn tại.'); redirectTo('admin-rent-requests'); }
        RentalRequestModel::setStatus((int)$req['id'], 'rejected');
        $room = RoomModel::getById((int)($req['room_id']??0));
        NotificationModel::create(['user_id'=>(int)$req['user_id'],'type'=>'general','title'=>'Yêu cầu thuê bị từ chối','content'=>'Yêu cầu thuê phòng "'.($room['name']??'').'" bị từ chối. Bạn có thể gửi yêu cầu phòng khác.']);
        setFlash('rent_request_message', 'Đã từ chối.');
        redirectTo('admin-rent-requests');
    }

    public function confirmRentRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RentalRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req || ($req['status']??'') !== 'pending') { setFlash('rent_request_error', $req?'Đã xử lý.':'Không tồn tại.'); redirectTo('admin-rent-requests'); }
        $room = RoomModel::getById((int)($req['room_id']??0));
        if (!$room || ($room['status']??'') !== 'available') { setFlash('rent_request_error', 'Phòng không còn trống.'); redirectTo('admin-rent-requests'); }

        $deposit = (float)($_POST['deposit']??0);
        if ($deposit <= 0) { setFlash('rent_request_error', 'Nhập tiền cọc > 0.'); redirectTo('admin-rent-requests'); }

        RentalRequestModel::confirmByAdmin((int)$req['id'], $deposit);
        $tenant = UserModel::getById((int)$req['user_id']);
        $roomName = $room['name']??'';
        $moveIn = trim($req['move_in_date']??'') ?: date('Y-m-d');
        NotificationModel::create(['user_id'=>(int)$req['user_id'],'type'=>'rental_request','title'=>'Yêu cầu thuê được chấp nhận','content'=>'Yêu cầu thuê phòng "'.$roomName.'" đã được chấp nhận. Thanh toán tiền cọc '.number_format($deposit,0,',','.').'đ để giữ phòng đến '.date('d/m/Y',strtotime($moveIn)).'.','link'=>'?page=request-rent&id='.(int)$req['room_id']]);
        setFlash('rent_request_message', 'Đã xác nhận yêu cầu thuê "'.($tenant['full_name']??'').'" cọc '.number_format($deposit,0,',','.').'đ.');
        redirectTo('admin-rent-requests');
    }

    public function cancelRentRequestAdmin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RentalRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req || ($req['status']??'') !== 'pending') { setFlash('rent_request_error', $req?'Đã xử lý.':'Không tồn tại.'); redirectTo('admin-rent-requests'); }
        RentalRequestModel::cancelByAdmin((int)$req['id']);
        $room = RoomModel::getById((int)($req['room_id']??0));
        $tenant = UserModel::getById((int)$req['user_id']);
        NotificationModel::create(['user_id'=>(int)$req['user_id'],'type'=>'general','title'=>'Tài khoản hủy đăng ký thuê','content'=>'Tài khoản '.($tenant['full_name']??'').' đã hủy đăng ký thuê phòng "'.($room['name']??'').'". Phòng vẫn trống.']);
        setFlash('rent_request_message', 'Đã hủy yêu cầu của "'.($tenant['full_name']??'').'".');
        redirectTo('admin-rent-requests');
    }

    public function paidRentRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RentalRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req || ($req['status']??'') !== 'pending') { setFlash('rent_request_error', $req?'Đã xử lý.':'Không tồn tại.'); redirectTo('admin-rent-requests'); }

        $userId = (int)$req['user_id']; $roomId = (int)$req['room_id'];
        $room = RoomModel::getById($roomId);
        if (!$room || !empty(UserModel::getById($userId)['room_id']) || RoomModel::countOccupants($roomId)+1 > max(1,(int)($room['max_occupancy']??1))) {
            setFlash('rent_request_error', 'Phòng hết chỗ hoặc user đã có phòng.'); redirectTo('admin-rent-requests');
        }

        $moveIn = trim($req['move_in_date']??'') ?: date('Y-m-d');
        $deposit = (float)($req['deposit']??0) ?: (float)($room['price']??0);
        try {
            Database::update('users',['room_id'=>$roomId],'id=:id',['id'=>$userId]);
            RoomModel::syncRoomStatus($roomId);
            RentalRequestModel::markPaid((int)$req['id']);
            $tenant = UserModel::getById($userId);
            foreach (UserModel::getAll() as $admin) if (($admin['role']??1)===1) NotificationModel::create(['user_id'=>(int)$admin['id'],'type'=>'rental_request','title'=>'Tiền cọc đã thanh toán','content'=>($tenant['full_name']??'Người thuê').' đã thanh toán '.number_format($deposit,0,',','.').'đ cho phòng "'.($room['name']??'').'".','link'=>'?page=admin-rent-requests&rent_filter=approved']);
            NotificationModel::create(['user_id'=>$userId,'type'=>'general','title'=>'Chào mừng đến phòng '.($room['name']??''),'content'=>'Bạn đã thanh toán '.number_format($deposit,0,',','.').'đ và là người thuê phòng "'.($room['name']??'').'" từ '.date('d/m/Y',strtotime($moveIn)).'.']);
            setFlash('rent_request_message', 'Người thuê "'.($tenant['full_name']??'').'" đã thanh toán '.number_format($deposit,0,',','.').'đ và được thêm vào phòng.'.($room['name']??''));
        } catch (Throwable $e) { setFlash('rent_request_error', 'Lỗi: '.$e->getMessage()); }
        redirectTo('admin-rent-requests');
    }

    public function roommateRequests(): void { redirectTo('admin-rent-requests'); }

    public function approveRoommate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RoommateRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req || ($req['status']??'') !== 'pending_admin') { setFlash('roommate_admin_error', $req?'Không ở trạng thái chờ.':'Không tồn tại.'); redirectTo('admin-rent-requests'); }

        $requesterId = (int)$req['requester_id']; $hostUserId = (int)$req['host_user_id']; $roomId = (int)$req['room_id'];
        $room = RoomModel::getById($roomId);
        if (!$room || !empty(UserModel::getById($requesterId)['room_id']) || RoomModel::countOccupants($roomId) >= max(1,(int)($room['max_occupancy']??1))) { setFlash('roommate_admin_error', 'Phòng hết chỗ hoặc người B đã có phòng.'); redirectTo('admin-rent-requests'); }

        try {
            UserModel::assignToRoom($requesterId, $roomId, true);
            RoommateRequestModel::setStatus((int)$req['id'], 'approved');
            NotificationModel::create(['user_id'=>$requesterId,'type'=>'general','title'=>'Yêu cầu ở ghép được duyệt','content'=>'Admin đã duyệt ở ghép tại phòng '.($room['name']??'').'.']);
            NotificationModel::create(['user_id'=>$hostUserId,'type'=>'general','title'=>'Yêu cầu mời được duyệt','content'=>'Admin đã duyệt mời '.(UserModel::getById($requesterId)['full_name']??'').' ở ghép tại '.($room['name']??'').'.']);
            setFlash('roommate_admin_message', 'Đã duyệt ở ghép.');
        } catch (Throwable $e) { setFlash('roommate_admin_error', 'Lỗi: '.$e->getMessage()); }
        redirectTo('admin-rent-requests');
    }

    public function rejectRoommate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RoommateRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req || ($req['status']??'') !== 'pending_admin') { setFlash('roommate_admin_error', $req?'Không ở trạng thái chờ.':'Không tồn tại.'); redirectTo('admin-rent-requests'); }
        $note = trim($_POST['admin_note']??'') ?: 'Admin chưa phản hồi lý do.';
        $requesterId = (int)$req['requester_id']; $hostUserId = (int)$req['host_user_id']; $roomId = (int)$req['room_id'];
        RoommateRequestModel::setStatus((int)$req['id'], 'rejected', $note);
        NotificationModel::create(['user_id'=>$hostUserId,'type'=>'general','title'=>'Mời ở ghép bị từ chối','content'=>'Admin từ chối mời ở ghép tại '.(RoomModel::getById($roomId)['name']??'').'. Lý do: '.$note]);
        NotificationModel::create(['user_id'=>$requesterId,'type'=>'general','title'=>'Yêu cầu ở ghép bị từ chối','content'=>'Admin từ chối ở ghép tại '.(RoomModel::getById($roomId)['name']??'').'. Lý do: '.$note]);
        setFlash('roommate_admin_message', 'Đã từ chối.');
        redirectTo('admin-rent-requests');
    }

    public function vetoRoommate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rent-requests');
        verify_csrf();
        $req = RoommateRequestModel::getById((int)($_POST['request_id']??0));
        if (!$req) { setFlash('roommate_admin_error', 'Không tồn tại.'); redirectTo('admin-rent-requests'); }
        $status = (string)$req['status'];
        $requesterId = (int)$req['requester_id']; $roomId = (int)$req['room_id'];

        if ($status === 'approved') { setFlash('roommate_admin_error', 'Không thể gỡ người đã duyệt.'); redirectTo('admin-rent-requests'); }
        elseif ($status === 'pending_admin') { RoommateRequestModel::setStatus((int)$req['id'], 'admin_rejected'); NotificationModel::create(['user_id'=>$requesterId,'type'=>'general','title'=>'Yêu cầu ở ghép bị admin từ chối','content'=>'Admin đã từ chối yêu cầu ở ghép của bạn.']); setFlash('roommate_admin_message', 'Đã từ chối.'); }
        else { setFlash('roommate_admin_error', 'Đã xử lý.'); }
        redirectTo('admin-rent-requests');
    }

    // ==========================================
    // ACCOUNTS MANAGEMENT
    // ==========================================

    public function accounts(): void
    {
        [$admins, $users, $allUsersStatus, $pagedUsers, $totalUsers, $totalPages, $page, $keyword, $statusFilter, $perPage] = $this->resolveAccountQuery();

        $buildUrl = fn($pageNumber, $statusOverride = null) => BASE_URL . '?' . http_build_query(array_filter([
            'page' => 'admin-accounts', 'search' => $keyword, 'status' => $statusOverride ?? $statusFilter,
            'p' => $pageNumber > 1 ? $pageNumber : null,
        ], fn($v) => $v!=='' && $v!==null));

        $accountMessage = pullFlash('admin_account_message');
        $accountError = pullFlash('admin_account_error');
        $oldInput = pullFlash('admin_account_old', []);
        $accountForm = array_merge(['full_name'=>'','phone'=>'','email'=>''], is_array($oldInput)?$oldInput:[]);
        $pageTitle = 'Quản lý tài khoản - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/accounts.php';
    }

    public function accountsFilterApi(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        [$admins, $users, $allUsersStatus, $pagedUsers, $totalUsers, $totalPages, $page, $keyword, $statusFilter, $perPage] = $this->resolveAccountQuery();
        echo json_encode(['success'=>true,'rowsHtml'=>$this->renderAccountRowsHtml($pagedUsers),'paginationHtml'=>$this->renderAccountPaginationHtml($totalPages,$page,$keyword,$statusFilter),'total'=>$totalUsers,'totalPages'=>$totalPages,'page'=>$page], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function resolveAccountQuery(): array
    {
        $keyword = trim($_GET['search']??'');
        $statusFilter = trim($_GET['status']??'all'); if (!in_array($statusFilter,['all','renting','not_renting'],true)) $statusFilter='all';
        $page = max(1,(int)($_GET['p']??1)); $perPage = 10;

        $admins = []; $users = [];
        foreach (UserModel::getAll() as $u) {
            $isAdmin = ($u['role']??0)===1;
            $u['account_status'] = $isAdmin ? 'admin' : (!empty($u['room_id'])?'renting':'not_renting');
            if ($isAdmin) $admins[]=$u; else $users[]=$u;
        }
        $allUsersStatus = array_map(fn($u)=>['account_status'=>(string)($u['account_status']??'not_renting')], $users);

        $filtered = $users;
        if ($keyword!=='') { $nk=mb_strtolower($keyword); $filtered=array_values(array_filter($filtered,fn($u)=>mb_strpos(mb_strtolower($u['full_name']??''),$nk)!==false)); }
        if ($statusFilter==='renting') $filtered=array_values(array_filter($filtered,fn($u)=>($u['account_status']??'')==='renting'));
        elseif ($statusFilter==='not_renting') $filtered=array_values(array_filter($filtered,fn($u)=>($u['account_status']??'')==='not_renting'));

        $totalUsers = count($filtered);
        $totalPages = max(1,(int)ceil($totalUsers/10));
        $page = min($page,$totalPages);
        $pagedUsers = array_slice($filtered,($page-1)*10,10);

        return [$admins,$users,$allUsersStatus,$pagedUsers,$totalUsers,$totalPages,$page,$keyword,$statusFilter,10];
    }

    private function renderAccountRowsHtml(array $users): string
    {
        $html=''; foreach($users as $u){ ob_start(); require BASE_PATH.'views/admin/system/partials/account_row.php'; $html.=ob_get_clean(); }
        return $html;
    }

    private function renderAccountPaginationHtml(int $totalPages, int $page, string $keyword, string $statusFilter): string
    {
        if ($totalPages <= 1) return '';
        $buildUrl = fn($p)=>BASE_URL.'?'.http_build_query(array_filter(['page'=>'admin-accounts','search'=>$keyword,'status'=>$statusFilter,'p'=>$p>1?$p:null],fn($v)=>$v!==''&&$v!==null));
        ob_start(); require BASE_PATH.'views/admin/system/partials/account_pagination.php'; return ob_get_clean();
    }

    public function saveAccount(): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST') redirectTo('admin-accounts');
        verify_csrf();
        $p = ['full_name'=>trim($_POST['full_name']??''), 'phone'=>trim($_POST['phone']??''), 'email'=>mb_strtolower(trim($_POST['email']??'')), 'password'=>(string)($_POST['password']??'')];
        setFlash('admin_account_old',$p);

        if ($err=UserModel::validateFullName($p['full_name'])) { setFlash('admin_account_error',$err); redirectTo('admin-accounts'); }
        $np = UserModel::normalizePhone($p['phone']); if (!$np) { setFlash('admin_account_error','Số điện thoại không hợp lệ (0xxxxxxxxx).'); redirectTo('admin-accounts'); }
        if (UserModel::phoneExists($np)) { setFlash('admin_account_error','SĐT đã đăng ký.'); redirectTo('admin-accounts'); }
        if ($p['email']!=='' && !UserModel::validateEmailStrict($p['email'])) { setFlash('admin_account_error','Email sai định dạng.'); redirectTo('admin-accounts'); }
        if ($p['email']!=='' && UserModel::emailExists($p['email'])) { setFlash('admin_account_error','Email đã đăng ký.'); redirectTo('admin-accounts'); }
        if ($err=UserModel::validatePassword($p['password'])) { setFlash('admin_account_error',$err); redirectTo('admin-accounts'); }

        try {
            UserModel::create(['full_name'=>$p['full_name'],'phone'=>$np,'email'=>$p['email'],'password'=>$p['password'],'role'=>0]);
            setFlash('admin_account_message','Đã thêm tài khoản "'.$p['full_name'].'"');
        } catch (Throwable $e) { setFlash('admin_account_error','Lỗi: '.$e->getMessage()); }
        redirectTo('admin-accounts');
    }

    public function deleteAccount(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST') redirectTo('admin-accounts');
        verify_csrf();
        $user = UserModel::getById($id);
        if (!$user) { setFlash('admin_account_error','Không tồn tại.'); redirectTo('admin-accounts'); }
        if (($user['role']??0)===1) { setFlash('admin_account_error','Không xóa được admin.'); redirectTo('admin-accounts'); }
        if (!empty($user['room_id'])) { setFlash('admin_account_error','Không xóa được tài khoản đang thuê "'.e($user['room_name']??'').'".'); redirectTo('admin-accounts'); }

        $conn = Database::hasConnection() ? Database::getInstance() : null;
        $tx = $conn instanceof PDO;
        if ($tx) $conn->beginTransaction();

        try {
            Database::query('DELETE FROM payment_items WHERE payment_id IN (SELECT id FROM payments WHERE user_id=?)',[$id]);
            Database::query('DELETE FROM payments WHERE user_id=?',[$id]);
            Database::query('DELETE FROM notifications WHERE user_id=?',[$id]);
            Database::query('DELETE FROM notification_reads WHERE user_id=?',[$id]);
            Database::query('DELETE FROM comments WHERE user_id=?',[$id]);
            Database::query('DELETE FROM comment_reports WHERE user_id=?',[$id]);
            Database::query('DELETE FROM feedbacks WHERE user_id=?',[$id]);
            Database::query('DELETE FROM rental_requests WHERE user_id=?',[$id]);
            Database::query('DELETE FROM roommate_requests WHERE host_user_id=?',[$id]);
            Database::query('DELETE FROM user_services WHERE user_id=?',[$id]);
            Database::query('DELETE FROM password_resets WHERE user_id=?',[$id]);
            Database::query('DELETE FROM users WHERE id=:id',['id'=>$id]);

            if ($tx && $conn->inTransaction()) $conn->commit();
            setFlash('admin_account_message','Đã xóa tài khoản "'.e($user['full_name']??'').'"');
        } catch (Throwable $e) { if($tx&&$conn->inTransaction()) $conn->rollBack(); setFlash('admin_account_error','Lỗi: '.$e->getMessage()); }
        redirectTo('admin-accounts');
    }

    public function updateAccount(): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST') redirectTo('admin-accounts');
        verify_csrf();
        $id = (int)($_POST['id']??0);
        $user = $id>0?UserModel::getById($id):null;
        if (!$user || ($user['role']??0)===1) { setFlash('admin_account_error',$user?'Không sửa được admin.':'Không tồn tại.'); redirectTo('admin-accounts'); }

        $p = ['full_name'=>trim($_POST['full_name']??''),'phone'=>trim($_POST['phone']??''),'email'=>mb_strtolower(trim($_POST['email']??'')),'password'=>(string)($_POST['password']??'')];
        setFlash('admin_account_old',array_merge($p,['id'=>$id]));

        if ($err=UserModel::validateFullName($p['full_name'])) { setFlash('admin_account_error',$err); redirectTo('admin-accounts'); }
        $np=UserModel::normalizePhone($p['phone']); if(!$np) { setFlash('admin_account_error','SĐT không hợp lệ.'); redirectTo('admin-accounts'); }
        if (UserModel::phoneExists($np) && $np!==($user['phone']??'')) { setFlash('admin_account_error','SĐT đã đăng ký.'); redirectTo('admin-accounts'); }
        if ($p['email']!=='' && !UserModel::validateEmailStrict($p['email'])) { setFlash('admin_account_error','Email sai.'); redirectTo('admin-accounts'); }
        if ($p['email']!=='' && UserModel::emailExists($p['email']) && $p['email']!==($user['email']??'')) { setFlash('admin_account_error','Email đã đăng ký.'); redirectTo('admin-accounts'); }
        if ($p['password']!=='' && ($err=UserModel::validatePassword($p['password']))) { setFlash('admin_account_error',$err); redirectTo('admin-accounts'); }

        try {
            $ud = ['full_name'=>$p['full_name'],'phone'=>$np,'email'=>$p['email']];
            if ($p['password']!=='') $ud['password']=$p['password'];
            UserModel::update($id,$ud);
            setFlash('admin_account_message','Đã cập nhật tài khoản "'.$p['full_name'].'"');
        } catch (Throwable $e) { setFlash('admin_account_error','Lỗi: '.$e->getMessage()); }
        redirectTo('admin-accounts');
    }
}