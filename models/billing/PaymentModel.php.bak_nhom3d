<?php
/**
 * PaymentModel gom toàn bộ nghiệp vụ hóa đơn/thanh toán theo tháng.
 * Mục tiêu là giữ controller mỏng, view chỉ render dữ liệu đã được chuẩn hóa sẵn.
 */
class PaymentModel {
    private const STATUS_META = [
        'unpaid' => [
            'label' => 'Chưa trả',
            'badge_class' => 'bg-amber-100 text-amber-700',
        ],
        'paid' => [
            'label' => 'Đã trả',
            'badge_class' => 'bg-green-100 text-green-700',
        ],
    ];

    /**
     * Chuẩn hóa tháng/năm để mọi màn dùng chung một format an toàn.
     */
    public static function normalizePeriod($month = null, $year = null) {
        return MeterReadingModel::normalizePeriod($month, $year);
    }

    /**
     * Trả metadata trạng thái thanh toán để view không hard-code text/màu nhiều nơi.
     */
    public static function getStatusMeta($status) {
        return self::STATUS_META[$status] ?? self::STATUS_META['unpaid'];
    }

    /**
     * Lấy danh sách tenant đang ở trong một phòng để phục vụ chọn người trả tiền.
     */
    public static function getRoomTenants($roomId) {
        $resolvedRoomId = (int)$roomId;
        if ($resolvedRoomId <= 0) {
            return [];
        }

        $tenants = array_values(array_filter(
            UserModel::getAll(),
            static function ($user) use ($resolvedRoomId) {
                return (int)($user['role'] ?? 0) === 0 && (int)($user['room_id'] ?? 0) === $resolvedRoomId;
            }
        ));

        usort($tenants, static fn($left, $right) => strcmp((string)($left['full_name'] ?? ''), (string)($right['full_name'] ?? '')));
        return $tenants;
    }

    /**
     * Tổng hợp nhanh trạng thái hóa đơn của các phòng đang có người ở trong kỳ.
     */
    public static function getRoomInvoiceOverview($month, $year, array $filters = []) {
        $period = self::normalizePeriod($month, $year);
        $rows = [];

        foreach (self::getBillableRooms($period['month'], $period['year'], $filters) as $room) {
            $roomId = (int)($room['id'] ?? 0);
            $existingPayment = self::getPaymentByRoomAndPeriod($roomId, $period['month'], $period['year']);
            $preview = self::buildInvoicePreview($roomId, $period['month'], $period['year']);
            $status = $existingPayment['status'] ?? 'unpaid';

            $rows[] = [
                'room_id' => $roomId,
                'room_name' => $room['name'] ?? 'Phòng',
                'area_id' => (int)($room['area_id'] ?? 0),
                'area_name' => $room['area_name'] ?? 'Chưa có khu',
                'floor_id' => (int)($room['floor_id'] ?? 0),
                'floor_name' => $room['floor_name'] ?? 'Chưa có tầng',
                'floor_number' => (int)($room['floor_number'] ?? 0),
                'occupant_count' => count($preview['tenants'] ?? []),
                'tenants' => $preview['tenants'] ?? [],
                'existing_payment_id' => (int)($existingPayment['id'] ?? 0),
                'existing_payment_status' => $status,
                'existing_payment_meta' => self::getStatusMeta($status),
                'preview_total' => (float)($preview['total_amount'] ?? 0),
                'preview_item_count' => count($preview['items'] ?? []),
                'can_generate' => (bool)($preview['can_generate'] ?? false),
                'preview_errors' => $preview['errors'] ?? [],
                'preview_warnings' => $preview['warnings'] ?? [],
            ];
        }

        usort($rows, static function ($left, $right) {
            $areaCompare = strcmp((string)($left['area_name'] ?? ''), (string)($right['area_name'] ?? ''));
            if ($areaCompare !== 0) {
                return $areaCompare;
            }

            $floorCompare = (int)($left['floor_number'] ?? 0) <=> (int)($right['floor_number'] ?? 0);
            if ($floorCompare !== 0) {
                return $floorCompare;
            }

            return strcmp((string)($left['room_name'] ?? ''), (string)($right['room_name'] ?? ''));
        });

        return $rows;
    }

    /**
     * Dựng preview hóa đơn chi tiết cho một phòng trước khi chốt tạo.
     */
    public static function buildInvoicePreview($roomId, $month, $year) {
        $period = self::normalizePeriod($month, $year);
        $room = RoomModel::getById((int)$roomId);

        if (!$room) {
            return [
                'period' => $period,
                'room' => null,
                'tenants' => [],
                'items' => [],
                'errors' => ['Phòng không tồn tại hoặc đã bị xóa.'],
                'warnings' => [],
                'total_amount' => 0.0,
                'existing_payment' => null,
                'can_generate' => false,
                'billing_contract' => null,
            ];
        }

        $tenants = self::getRoomTenants((int)($room['id'] ?? 0));
        $contracts = self::getContractsByRoomAndPeriod((int)($room['id'] ?? 0), $period['month'], $period['year']);
        $existingPayment = self::getPaymentByRoomAndPeriod((int)($room['id'] ?? 0), $period['month'], $period['year']);
        $billingContract = self::resolveBillingContract($contracts, $room);
        $errors = [];
        $warnings = [];
        $items = [];
        $totalAmount = 0.0;

        if (empty($tenants) && empty($contracts)) {
            $errors[] = 'Phòng này chưa có người ở hoặc chưa có hợp đồng áp dụng trong kỳ đã chọn.';
        }

        if ($existingPayment) {
            $errors[] = 'Đã có hóa đơn tháng này.';
        }

        $rentPrice = self::resolveRoomRentPrice($room, $billingContract, $contracts);
        if ($rentPrice <= 0) {
            $errors[] = 'Không xác định được tiền phòng hợp lệ để lập hóa đơn.';
        } else {
            $items[] = self::buildItemRow(
                null,
                'Tiền phòng',
                $rentPrice,
                1,
                'fixed',
                'Tiền phòng tháng ' . $period['label']
            );
        }

        $occupantCount = count($tenants);
        foreach (self::getInvoiceRoomServices((int)($room['id'] ?? 0)) as $service) {
            $servicePreview = self::buildServiceItemPreview($room, $service, $period['month'], $period['year'], $occupantCount);

            if (!empty($servicePreview['errors'])) {
                foreach ($servicePreview['errors'] as $error) {
                    $errors[] = $error;
                }
                continue;
            }

            if (!empty($servicePreview['warnings'])) {
                foreach ($servicePreview['warnings'] as $warning) {
                    $warnings[] = $warning;
                }
            }

            if (!empty($servicePreview['item'])) {
                $items[] = $servicePreview['item'];
            }
        }

        foreach ($tenants as $tenant) {
            foreach (ServiceModel::getByUser((int)($tenant['id'] ?? 0)) as $service) {
                $personalItem = self::buildPersonalServiceItemPreview($tenant, $service, $period['month'], $period['year']);
                if ($personalItem !== null) {
                    $items[] = $personalItem;
                }
            }
        }

        foreach ($items as $item) {
            $totalAmount += (float)($item['amount'] ?? 0);
        }

        if ($totalAmount <= 0) {
            $errors[] = 'Tổng tiền hóa đơn phải lớn hơn 0.';
        }

        return [
            'period' => $period,
            'room' => $room,
            'tenants' => $tenants,
            'contracts' => $contracts,
            'billing_contract' => $billingContract,
            'items' => $items,
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'total_amount' => $totalAmount,
            'existing_payment' => $existingPayment,
            'can_generate' => empty($errors),
        ];
    }

    /**
     * Tạo hóa đơn cho một phòng hoặc toàn bộ phòng đang ở trong kỳ.
     */
    public static function generateInvoices($month, $year, $roomId = null, array $filters = []) {
        $period = self::normalizePeriod($month, $year);
        $targetRoomId = (int)$roomId;
        $targetRooms = [];

        if ($targetRoomId > 0) {
            $targetRooms[] = RoomModel::getById($targetRoomId);
        } else {
            $targetRooms = self::getBillableRooms($period['month'], $period['year'], $filters);
        }

        $targetRooms = array_values(array_filter($targetRooms));
        if (empty($targetRooms)) {
            throw new RuntimeException('Không có phòng nào phù hợp để tạo hóa đơn trong kỳ đã chọn.');
        }

        $created = [];
        $skippedExisting = [];
        $blocked = [];
        $connection = Database::hasConnection() ? Database::getInstance() : null;
        $useTransaction = $connection instanceof PDO;

        if ($useTransaction) {
            $connection->beginTransaction();
        }

        try {
            foreach ($targetRooms as $room) {
                $preview = self::buildInvoicePreview((int)($room['id'] ?? 0), $period['month'], $period['year']);
                $roomName = $room['name'] ?? 'Phòng';

                if (!empty($preview['existing_payment'])) {
                    $skippedExisting[] = $roomName;
                    continue;
                }

                if (empty($preview['can_generate'])) {
                    $blocked[] = $roomName . ': ' . implode(' | ', $preview['errors'] ?? []);
                    continue;
                }

                $paymentId = self::insertPaymentFromPreview($preview);
                $created[] = [
                    'payment_id' => $paymentId,
                    'room_name' => $roomName,
                ];
            }

            if ($useTransaction) {
                $connection->commit();
            }
        } catch (Throwable $exception) {
            if ($useTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }

        return [
            'period' => $period,
            'created' => $created,
            'created_count' => count($created),
            'skipped_existing' => $skippedExisting,
            'skipped_existing_count' => count($skippedExisting),
            'blocked' => $blocked,
            'blocked_count' => count($blocked),
        ];
    }

    /**
     * Lấy danh sách hóa đơn đã tạo để admin lọc và theo dõi thanh toán.
     */
    public static function getInvoices(array $filters = []) {
        $period = self::normalizePeriod($filters['month'] ?? null, $filters['year'] ?? null);
        $payments = self::getPaymentsByPeriod($period['month'], $period['year']);
        $rows = [];

        foreach ($payments as $payment) {
            $invoice = self::hydratePayment($payment);
            if (!$invoice) {
                continue;
            }

            if (!empty($filters['status']) && ($invoice['status'] ?? '') !== $filters['status']) {
                continue;
            }
            if (!empty($filters['area_id']) && (int)($invoice['room']['area_id'] ?? 0) !== (int)$filters['area_id']) {
                continue;
            }
            if (!empty($filters['floor_id']) && (int)($invoice['room']['floor_id'] ?? 0) !== (int)$filters['floor_id']) {
                continue;
            }

            $rows[] = $invoice;
        }

        usort($rows, static function ($left, $right) {
            $statusPriority = ($left['status'] ?? 'unpaid') <=> ($right['status'] ?? 'unpaid');
            if ($statusPriority !== 0) {
                return $statusPriority;
            }

            $areaCompare = strcmp((string)($left['room']['area_name'] ?? ''), (string)($right['room']['area_name'] ?? ''));
            if ($areaCompare !== 0) {
                return $areaCompare;
            }

            $floorCompare = (int)($left['room']['floor_number'] ?? 0) <=> (int)($right['room']['floor_number'] ?? 0);
            if ($floorCompare !== 0) {
                return $floorCompare;
            }

            return strcmp((string)($left['room']['name'] ?? ''), (string)($right['room']['name'] ?? ''));
        });

        return $rows;
    }

    /**
     * Thống kê doanh thu đã thu theo từng tháng trong năm từ `payments.status = paid`.
     * Luôn trả đủ 12 tháng để view/chart render ổn định kể cả khi không có dữ liệu.
     */
    public static function getRevenueByMonth($year) {
        $resolvedYear = max(2000, (int)$year);
        $monthlyMap = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyMap[$month] = [
                'month' => $month,
                'label' => 'Tháng ' . $month,
                'total_amount' => 0.0,
                'paid_invoice_count' => 0,
            ];
        }

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                "
                SELECT
                    month,
                    COALESCE(SUM(amount), 0) AS total_amount,
                    COUNT(*) AS paid_invoice_count
                FROM payments
                WHERE status = 'paid' AND year = ?
                GROUP BY month
                ORDER BY month ASC
                ",
                [$resolvedYear]
            );
        } else {
            $rows = [];
            foreach (Database::getTable('payments') as $payment) {
                if ((string)($payment['status'] ?? '') !== 'paid' || (int)($payment['year'] ?? 0) !== $resolvedYear) {
                    continue;
                }

                $month = (int)($payment['month'] ?? 0);
                if ($month < 1 || $month > 12) {
                    continue;
                }

                if (!isset($rows[$month])) {
                    $rows[$month] = [
                        'month' => $month,
                        'total_amount' => 0.0,
                        'paid_invoice_count' => 0,
                    ];
                }

                $rows[$month]['total_amount'] += (float)($payment['amount'] ?? 0);
                $rows[$month]['paid_invoice_count']++;
            }

            ksort($rows);
            $rows = array_values($rows);
        }

        foreach ($rows as $row) {
            $month = (int)($row['month'] ?? 0);
            if ($month < 1 || $month > 12) {
                continue;
            }

            $monthlyMap[$month]['total_amount'] = round((float)($row['total_amount'] ?? 0), 2);
            $monthlyMap[$month]['paid_invoice_count'] = (int)($row['paid_invoice_count'] ?? 0);
        }

        $normalizedRows = array_values($monthlyMap);
        $yearTotal = array_reduce(
            $normalizedRows,
            static fn($carry, $row) => $carry + (float)($row['total_amount'] ?? 0),
            0.0
        );
        $paidInvoiceCount = array_reduce(
            $normalizedRows,
            static fn($carry, $row) => $carry + (int)($row['paid_invoice_count'] ?? 0),
            0
        );

        return [
            'year' => $resolvedYear,
            'rows' => $normalizedRows,
            'year_total' => round($yearTotal, 2),
            'paid_invoice_count' => $paidInvoiceCount,
            'available_years' => self::getPaymentYears(),
        ];
    }

    /**
     * Lấy chi tiết một hóa đơn để admin/tenant xem bảng phân rã từ snapshot `payment_items`.
     */
    public static function getInvoiceById($paymentId) {
        $payment = self::getPaymentById((int)$paymentId);
        return $payment ? self::hydratePayment($payment) : null;
    }

    /**
     * Lấy hóa đơn theo phòng và kỳ để tenant cùng phòng cùng nhìn thấy một trạng thái chung.
     */
    public static function getInvoiceForRoomAndPeriod($roomId, $month, $year) {
        $payment = self::getPaymentByRoomAndPeriod((int)$roomId, (int)$month, (int)$year);
        return $payment ? self::hydratePayment($payment) : null;
    }

    /**
     * Tenant tự thanh toán hóa đơn của phòng mình.
     */
    public static function payInvoiceAsTenant($paymentId, $payerUserId, $roomId) {
        $invoice = self::getInvoiceById((int)$paymentId);
        if (!$invoice) {
            throw new RuntimeException('Hóa đơn không tồn tại hoặc đã bị xóa.');
        }
        if ((int)($invoice['room']['id'] ?? 0) !== (int)$roomId) {
            throw new RuntimeException('Bạn không có quyền thanh toán hóa đơn của phòng khác.');
        }

        self::markPaymentAsPaid((int)$paymentId, (int)$payerUserId);
        return self::getInvoiceById((int)$paymentId);
    }

    /**
     * Admin xác nhận thanh toán tiền mặt và gán người trả thực tế cho hóa đơn.
     */
    public static function confirmPayment($paymentId, $payerUserId) {
        $invoice = self::getInvoiceById((int)$paymentId);
        if (!$invoice) {
            throw new RuntimeException('Hóa đơn không tồn tại hoặc đã bị xóa.');
        }

        $payerId = (int)$payerUserId;
        $tenants = self::getRoomTenants((int)($invoice['room']['id'] ?? 0));
        $payerIds = array_map(static fn($tenant) => (int)($tenant['id'] ?? 0), $tenants);

        if ($payerId <= 0 || !in_array($payerId, $payerIds, true)) {
            throw new RuntimeException('Người trả tiền phải là tenant đang ở trong đúng phòng của hóa đơn.');
        }

        self::markPaymentAsPaid((int)$paymentId, $payerId);
        return self::getInvoiceById((int)$paymentId);
    }

    /**
     * Dựng danh sách phòng có thể lập hóa đơn theo kỳ, hỗ trợ lọc khu/tầng.
     */
    private static function getBillableRooms($month, $year, array $filters = []) {
        $rooms = [];

        foreach (RoomModel::getAll() as $room) {
            $roomId = (int)($room['id'] ?? 0);
            $tenants = self::getRoomTenants($roomId);
            $contracts = self::getContractsByRoomAndPeriod($roomId, $month, $year);

            if (empty($tenants) && empty($contracts)) {
                continue;
            }
            if (!empty($filters['area_id']) && (int)($room['area_id'] ?? 0) !== (int)$filters['area_id']) {
                continue;
            }
            if (!empty($filters['floor_id']) && (int)($room['floor_id'] ?? 0) !== (int)$filters['floor_id']) {
                continue;
            }

            $rooms[] = $room;
        }

        return $rooms;
    }

    /**
     * Lấy tất cả hợp đồng còn hiệu lực của một phòng trong kỳ để suy ra tiền phòng và danh sách cư dân.
     */
    private static function getContractsByRoomAndPeriod($roomId, $month, $year) {
        $resolvedRoomId = (int)$roomId;
        $period = self::normalizePeriod($month, $year);

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                "
                SELECT *
                FROM contracts
                WHERE room_id = ?
                  AND move_in_date <= ?
                  AND (move_out_date IS NULL OR move_out_date >= ?)
                ORDER BY move_in_date DESC, id DESC
                ",
                [$resolvedRoomId, $period['end_date'], $period['start_date']]
            );

            return $rows;
        }

        $rows = array_filter(Database::getTable('contracts'), static function ($contract) use ($resolvedRoomId, $period) {
            $moveInDate = (string)($contract['move_in_date'] ?? '');
            $moveOutDate = $contract['move_out_date'] ?? null;

            return (int)($contract['room_id'] ?? 0) === $resolvedRoomId
                && $moveInDate !== ''
                && $moveInDate <= $period['end_date']
                && ($moveOutDate === null || $moveOutDate === '' || $moveOutDate >= $period['start_date']);
        });

        usort($rows, static function ($left, $right) {
            $moveInCompare = strcmp((string)($right['move_in_date'] ?? ''), (string)($left['move_in_date'] ?? ''));
            if ($moveInCompare !== 0) {
                return $moveInCompare;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return array_values($rows);
    }

    /**
     * Chọn hợp đồng tham chiếu để snapshot tiền phòng khi schema hiện tại đang lập hóa đơn theo phòng.
     */
    private static function resolveBillingContract(array $contracts, array $room) {
        if (count($contracts) === 1 && (float)($contracts[0]['rent_price'] ?? 0) > 0) {
            return $contracts[0];
        }

        if ((float)($room['price'] ?? 0) > 0) {
            return null;
        }

        foreach ($contracts as $contract) {
            if ((float)($contract['rent_price'] ?? 0) > 0) {
                return $contract;
            }
        }

        return null;
    }

/**
 * Chốt giá tiền phòng theo rule:
 * 1. Nếu có hợp đồng active → tổng rent_price của tất cả hợp đồng.
 * 2. Nếu không có hợp đồng → fallback về rooms.price.
 * 
 * Lý do: Hợp đồng là SSOT. rooms.price chỉ là giá niêm yết,
 * không phản ánh thỏa thuận thực tế với từng tenant.
 */
private static function resolveRoomRentPrice(array $room, $billingContract, array $contracts) {
    // Ưu tiên 1: Tổng rent_price từ tất cả hợp đồng active trong kỳ
    $totalContractPrice = 0.0;
    $hasActiveContract = false;
    foreach ($contracts as $contract) {
        $rentPrice = (float)($contract['rent_price'] ?? 0);
        if ($rentPrice > 0) {
            $totalContractPrice += $rentPrice;
            $hasActiveContract = true;
        }
    }
    if ($hasActiveContract && $totalContractPrice > 0) {
        return $totalContractPrice;
    }

    // Ưu tiên 2: Fallback về giá niêm yết của phòng
    $roomPrice = (float)($room['price'] ?? 0);
    if ($roomPrice > 0) {
        return $roomPrice;
    }

    return 0.0;
}

    /**
     * Gom các dịch vụ theo phòng cần được đưa vào hóa đơn tháng.
     */
    private static function getInvoiceRoomServices($roomId) {
        $serviceMap = [];

        foreach (ServiceModel::getAll([
            'applies_to' => 'room',
            'required_only' => true,
        ]) as $service) {
            $service['quantity'] = max(1, (int)($service['quantity'] ?? 1));
            $serviceMap[(int)($service['id'] ?? 0)] = $service;
        }

        foreach (ServiceModel::getAssignmentsByRoom((int)$roomId) as $service) {
            $service['quantity'] = max(1, (int)($service['quantity'] ?? 1));
            $serviceMap[(int)($service['id'] ?? 0)] = $service;
        }

        uasort($serviceMap, static fn($left, $right) => strcmp((string)($left['name'] ?? ''), (string)($right['name'] ?? '')));
        return array_values($serviceMap);
    }

    /**
     * Dựng preview cho một dòng dịch vụ áp dụng theo phòng.
     */
    private static function buildServiceItemPreview(array $room, array $service, $month, $year, $occupantCount) {
        $serviceId = (int)($service['id'] ?? 0);
        $serviceName = $service['name'] ?? 'Dịch vụ';
        $quantity = max(1, (float)($service['quantity'] ?? 1));
        $unitPrice = PriceChangeModel::getEffectivePriceForPeriod(
            $serviceId,
            (int)$month,
            (int)$year,
            (float)($service['price'] ?? 0)
        );
        $billingMode = $service['billing_mode'] ?? 'fixed';
        $warnings = [];
        $errors = [];

        switch ($billingMode) {
            case 'meter':
                $reading = MeterReadingModel::getReadingByPeriod((int)($room['id'] ?? 0), $serviceId, (int)$month, (int)$year);
                if (!$reading) {
                    $errors[] = $serviceName . ': chưa có chỉ số của kỳ này nên không thể tạo hóa đơn.';
                    break;
                }

                $consumption = max(0, (float)($reading['new_index'] ?? 0) - (float)($reading['old_index'] ?? 0));
                return [
                    'item' => self::buildItemRow(
                        $serviceId,
                        $serviceName,
                        $unitPrice,
                        $consumption,
                        'meter',
                        self::formatNumber($reading['old_index'] ?? 0) . ' -> ' . self::formatNumber($reading['new_index'] ?? 0)
                    ),
                    'warnings' => [],
                    'errors' => [],
                ];

            case 'per_person':
                if ($occupantCount <= 0) {
                    $warnings[] = $serviceName . ': phòng chưa có tenant đang gán trực tiếp nên số người được tính là 0.';
                }
                $resolvedQuantity = $quantity * max(0, (int)$occupantCount);
                return [
                    'item' => self::buildItemRow(
                        $serviceId,
                        $serviceName,
                        $unitPrice,
                        $resolvedQuantity,
                        'per_person',
                        self::formatNumber($quantity) . ' x ' . max(0, (int)$occupantCount) . ' người'
                    ),
                    'warnings' => $warnings,
                    'errors' => [],
                ];

            case 'per_unit':
            case 'fixed':
            default:
                return [
                    'item' => self::buildItemRow(
                        $serviceId,
                        $serviceName,
                        $unitPrice,
                        $quantity,
                        $billingMode,
                        'Số lượng áp dụng: ' . self::formatNumber($quantity)
                    ),
                    'warnings' => [],
                    'errors' => [],
                ];
        }

        return [
            'item' => null,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * Dựng preview cho một dòng dịch vụ cá nhân của từng tenant trong phòng.
     */
    private static function buildPersonalServiceItemPreview(array $tenant, array $service, $month, $year) {
        $serviceId = (int)($service['id'] ?? 0);
        $serviceName = trim(($service['name'] ?? 'Dịch vụ') . ' - ' . ($tenant['full_name'] ?? 'Tenant'));
        $unitPrice = PriceChangeModel::getEffectivePriceForPeriod(
            $serviceId,
            (int)$month,
            (int)$year,
            (float)($service['price'] ?? 0)
        );
        $baseQuantity = max(1, (float)($service['quantity'] ?? 1));
        $billingMode = $service['billing_mode'] ?? 'fixed';

        switch ($billingMode) {
            case 'per_person':
                $resolvedQuantity = $baseQuantity;
                break;
            case 'per_unit':
            case 'fixed':
            default:
                $resolvedQuantity = $baseQuantity;
                break;
        }

        return self::buildItemRow(
            $serviceId,
            $serviceName,
            $unitPrice,
            $resolvedQuantity,
            $billingMode,
            'Dịch vụ cá nhân của ' . ($tenant['full_name'] ?? 'Tenant')
        );
    }

    /**
     * Chuẩn hóa một dòng snapshot để lưu vào `payment_items` và render thống nhất ở view.
     */
    private static function buildItemRow($serviceId, $itemName, $unitPrice, $quantity, $billingMode, $note = '') {
        $resolvedQuantity = max(0, (float)$quantity);
        $resolvedUnitPrice = (float)$unitPrice;

        return [
            'service_id' => $serviceId !== null ? (int)$serviceId : null,
            'item_name' => trim((string)$itemName),
            'unit_price' => $resolvedUnitPrice,
            'quantity' => $resolvedQuantity,
            'amount' => round($resolvedUnitPrice * $resolvedQuantity, 2),
            'billing_mode' => $billingMode,
            'note' => trim((string)$note),
        ];
    }

    /**
     * Lưu `payments` + `payment_items` từ một preview đã validate xong.
     */
    private static function insertPaymentFromPreview(array $preview) {
        $roomId = (int)($preview['room']['id'] ?? 0);
        $period = $preview['period'] ?? self::normalizePeriod(null, null);

        $paymentId = (int)Database::insert('payments', [
            'room_id' => $roomId,
            'contract_id' => !empty($preview['billing_contract']['id']) ? (int)$preview['billing_contract']['id'] : null,
            'user_id' => null,
            'month' => (int)($period['month'] ?? date('n')),
            'year' => (int)($period['year'] ?? date('Y')),
            'amount' => (float)($preview['total_amount'] ?? 0),
            'status' => 'unpaid',
            'paid_at' => null,
        ]);

        foreach ($preview['items'] as $item) {
            Database::insert('payment_items', [
                'payment_id' => $paymentId,
                'service_id' => $item['service_id'],
                'item_name' => $item['item_name'] ?? '',
                'unit_price' => (float)($item['unit_price'] ?? 0),
                'quantity' => (float)($item['quantity'] ?? 0),
                'amount' => (float)($item['amount'] ?? 0),
                'billing_mode' => $item['billing_mode'] ?? 'fixed',
            ]);
        }

        return $paymentId;
    }

    /**
     * Chuyển một hóa đơn sang trạng thái đã trả.
     */
    private static function markPaymentAsPaid($paymentId, $payerUserId) {
        $payment = self::getPaymentById((int)$paymentId);
        if (!$payment) {
            throw new RuntimeException('Hóa đơn không tồn tại.');
        }
        if (($payment['status'] ?? 'unpaid') === 'paid') {
            throw new RuntimeException('Hóa đơn này đã được thanh toán trước đó.');
        }

        Database::update(
            'payments',
            [
                'status' => 'paid',
                'user_id' => (int)$payerUserId,
                'paid_at' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => (int)$paymentId]
        );
    }

    /**
     * Lấy một hóa đơn theo `room_id + month + year`.
     */
    private static function getPaymentByRoomAndPeriod($roomId, $month, $year) {
        $resolvedRoomId = (int)$roomId;
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                'SELECT * FROM payments WHERE room_id = ? AND month = ? AND year = ? LIMIT 1',
                [$resolvedRoomId, $resolvedMonth, $resolvedYear]
            );

            return $row ?: null;
        }

        foreach (Database::getTable('payments') as $payment) {
            if (
                (int)($payment['room_id'] ?? 0) === $resolvedRoomId
                && (int)($payment['month'] ?? 0) === $resolvedMonth
                && (int)($payment['year'] ?? 0) === $resolvedYear
            ) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * Lấy một hóa đơn theo ID gốc.
     */
    private static function getPaymentById($paymentId) {
        $resolvedPaymentId = (int)$paymentId;
        if ($resolvedPaymentId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            return Database::fetchOne('SELECT * FROM payments WHERE id = ? LIMIT 1', [$resolvedPaymentId]);
        }

        return Database::find('payments', $resolvedPaymentId);
    }

    /**
     * Lấy danh sách hóa đơn của một kỳ để admin render bảng tổng.
     */
    private static function getPaymentsByPeriod($month, $year) {
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;

        if (Database::hasConnection()) {
            return Database::fetchAll(
                'SELECT * FROM payments WHERE month = ? AND year = ? ORDER BY created_at DESC, id DESC',
                [$resolvedMonth, $resolvedYear]
            );
        }

        $rows = array_filter(Database::getTable('payments'), static function ($payment) use ($resolvedMonth, $resolvedYear) {
            return (int)($payment['month'] ?? 0) === $resolvedMonth
                && (int)($payment['year'] ?? 0) === $resolvedYear;
        });

        usort($rows, static function ($left, $right) {
            $createdCompare = strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
            if ($createdCompare !== 0) {
                return $createdCompare;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return array_values($rows);
    }

    /**
     * Lấy danh sách năm đang có hóa đơn để render filter năm gọn và đúng dữ liệu.
     */
    private static function getPaymentYears() {
        if (Database::hasConnection()) {
            $rows = Database::fetchAll('SELECT DISTINCT year FROM payments ORDER BY year DESC');
            $years = array_map(static fn($row) => (int)($row['year'] ?? 0), $rows);
        } else {
            $years = array_map(static fn($row) => (int)($row['year'] ?? 0), Database::getTable('payments'));
            $years = array_values(array_unique(array_filter($years)));
            rsort($years, SORT_NUMERIC);
        }

        $currentYear = (int)date('Y');
        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
            rsort($years, SORT_NUMERIC);
        }

        return array_values(array_filter($years, static fn($year) => $year > 0));
    }

    /**
     * Lấy các dòng snapshot của một hóa đơn.
     */
    private static function getPaymentItems($paymentId) {
        $resolvedPaymentId = (int)$paymentId;

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                'SELECT * FROM payment_items WHERE payment_id = ? ORDER BY id ASC',
                [$resolvedPaymentId]
            );
        } else {
            $rows = array_values(array_filter(Database::getTable('payment_items'), static fn($row) => (int)($row['payment_id'] ?? 0) === $resolvedPaymentId));
            usort($rows, static fn($left, $right) => (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0));
        }

        return array_map([self::class, 'normalizePaymentItemRow'], $rows);
    }

    /**
     * Bơm đầy đủ room, payer, items và metadata để view chỉ render.
     */
    private static function hydratePayment(array $payment) {
        $normalized = self::normalizePaymentRow($payment);
        $room = RoomModel::getById((int)($normalized['room_id'] ?? 0));
        if (!$room) {
            return null;
        }

        $payer = !empty($normalized['user_id']) ? UserModel::getById((int)$normalized['user_id']) : null;
        $items = self::getPaymentItems((int)($normalized['id'] ?? 0));
        $tenants = self::getRoomTenants((int)($room['id'] ?? 0));
        $statusMeta = self::getStatusMeta($normalized['status'] ?? 'unpaid');

        return [
            'id' => (int)($normalized['id'] ?? 0),
            'room_id' => (int)($normalized['room_id'] ?? 0),
            'room' => $room,
            'contract_id' => !empty($normalized['contract_id']) ? (int)$normalized['contract_id'] : null,
            'user_id' => !empty($normalized['user_id']) ? (int)$normalized['user_id'] : null,
            'payer' => $payer,
            'tenants' => $tenants,
            'month' => (int)($normalized['month'] ?? 0),
            'year' => (int)($normalized['year'] ?? 0),
            'period_label' => str_pad((string)($normalized['month'] ?? 0), 2, '0', STR_PAD_LEFT) . '/' . ($normalized['year'] ?? ''),
            'amount' => (float)($normalized['amount'] ?? 0),
            'status' => $normalized['status'] ?? 'unpaid',
            'status_meta' => $statusMeta,
            'paid_at' => $normalized['paid_at'] ?? null,
            'created_at' => $normalized['created_at'] ?? null,
            'items' => $items,
            'item_count' => count($items),
        ];
    }

    /**
     * Chuẩn hóa dữ liệu hóa đơn để kiểu dữ liệu nhất quán giữa DB thật và fallback.
     */
    private static function normalizePaymentRow(array $row) {
        return [
            'id' => (int)($row['id'] ?? 0),
            'room_id' => (int)($row['room_id'] ?? 0),
            'contract_id' => isset($row['contract_id']) && $row['contract_id'] !== null ? (int)$row['contract_id'] : null,
            'user_id' => isset($row['user_id']) && $row['user_id'] !== null ? (int)$row['user_id'] : null,
            'month' => (int)($row['month'] ?? 0),
            'year' => (int)($row['year'] ?? 0),
            'amount' => (float)($row['amount'] ?? 0),
            'status' => in_array(($row['status'] ?? 'unpaid'), ['unpaid', 'paid'], true) ? $row['status'] : 'unpaid',
            'paid_at' => $row['paid_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    /**
     * Chuẩn hóa một dòng `payment_items`.
     */
    private static function normalizePaymentItemRow(array $row) {
        return [
            'id' => (int)($row['id'] ?? 0),
            'payment_id' => (int)($row['payment_id'] ?? 0),
            'service_id' => isset($row['service_id']) && $row['service_id'] !== null ? (int)$row['service_id'] : null,
            'item_name' => trim((string)($row['item_name'] ?? '')),
            'unit_price' => (float)($row['unit_price'] ?? 0),
            'quantity' => (float)($row['quantity'] ?? 0),
            'amount' => (float)($row['amount'] ?? 0),
            'billing_mode' => $row['billing_mode'] ?? 'fixed',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    /**
     * Format số gọn cho phần mô tả preview.
     */
    private static function formatNumber($value) {
        $number = (float)$value;
        if (floor($number) == $number) {
            return number_format($number, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
