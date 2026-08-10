<?php
/**
 * MeterReadingModel gom toàn bộ nghiệp vụ ghi chỉ số điện/nước theo tháng.
 * Mục tiêu là giữ controller mỏng, còn view chỉ render dữ liệu đã được chuẩn hóa.
 */
class MeterReadingModel {
    /**
     * Chuẩn hóa tháng/năm để mọi nơi dùng chung một format an toàn.
     */
    public static function normalizePeriod($month = null, $year = null) {
        $resolvedMonth = (int)($month ?: date('n'));
        $resolvedYear = (int)($year ?: date('Y'));

        if ($resolvedMonth < 1 || $resolvedMonth > 12) {
            $resolvedMonth = (int)date('n');
        }
        if ($resolvedYear < 2000 || $resolvedYear > 2100) {
            $resolvedYear = (int)date('Y');
        }

        return [
            'month' => $resolvedMonth,
            'year' => $resolvedYear,
            'label' => str_pad((string)$resolvedMonth, 2, '0', STR_PAD_LEFT) . '/' . $resolvedYear,
            'start_date' => sprintf('%04d-%02d-01', $resolvedYear, $resolvedMonth),
            'end_date' => date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $resolvedYear, $resolvedMonth))),
        ];
    }

    /**
     * [TEAM-FIX][NHOM3] Trả dữ liệu bảng nhập chỉ số cho admin theo kỳ được chọn.
     * Bổ sung cờ allow_manual_old_index để mở ô nhập chỉ số cũ khi không có mốc tự động.
     */
    public static function getAdminMatrix($month, $year) {
        $period = self::normalizePeriod($month, $year);
        $rows = [];
        $serviceCatalog = [];
        foreach (RoomModel::getAll() as $room) {
            $roomId = (int)($room['id'] ?? 0);
            $contract = self::getApplicableContractForRoom($roomId, $period['month'], $period['year']);
            if (!$contract) {
                continue;
            }
            $services = self::getMeterServicesForRoom($roomId, $period['month'], $period['year']);
            if (empty($services)) {
                continue;
            }
            $cells = [];
            foreach ($services as $service) {
                $serviceId = (int)($service['id'] ?? 0);
                if ($serviceId <= 0) {
                    continue;
                }
                $serviceCatalog[$serviceId] = [
                    'id' => $serviceId,
                    'name' => $service['name'] ?? 'Dịch vụ',
                    'unit' => $service['unit'] ?? 'đơn vị',
                    'icon' => $service['icon'] ?? 'settings',
                    'price' => (float)($service['price'] ?? 0),
                ];
                $baseline = self::resolvePeriodBaseline($roomId, $service, $period['month'], $period['year']);
                $reading = self::getReadingByPeriod($roomId, $serviceId, $period['month'], $period['year']);
                $oldIndex = $reading ? (float)($reading['old_index'] ?? 0) : $baseline['old_index'];
                $newIndex = $reading ? (float)($reading['new_index'] ?? 0) : null;
                $consumption = $newIndex !== null ? max(0, $newIndex - (float)$oldIndex) : null;
                $amount = $consumption !== null ? $consumption * (float)($service['price'] ?? 0) : null;
                $cells[$serviceId] = [
                    'service_id' => $serviceId,
                    'service_name' => $service['name'] ?? 'Dịch vụ',
                    'service_icon' => $service['icon'] ?? 'settings',
                    'unit' => $service['unit'] ?? 'đơn vị',
                    'price' => (float)($service['price'] ?? 0),
                    'reading_id' => (int)($reading['id'] ?? 0),
                    'old_index' => $oldIndex,
                    'new_index' => $newIndex,
                    'consumption' => $consumption,
                    'amount' => $amount,
                    'baseline_note' => $baseline['note'],
                    'baseline_error' => $baseline['error'],
                    'baseline_source' => $baseline['source'],
                    'allow_manual_old_index' => (bool)($baseline['allow_manual_old_index'] ?? false),
                    'can_save' => $baseline['error'] === null,
                    'has_reading' => $reading !== null,
                ];
            }
            if (empty($cells)) {
                continue;
            }
            $rows[] = [
                'room_id' => $roomId,
                'room_name' => $room['name'] ?? 'Phòng',
                'area_name' => $room['area_name'] ?? ($room['building_name'] ?? 'Chưa có khu'),
                'floor_name' => $room['floor_name'] ?? 'Chưa có tầng',
                'floor_number' => (int)($room['floor_number'] ?? 0),
                'occupant_count' => RoomModel::countOccupants($roomId),
                'contract_id' => (int)($contract['id'] ?? 0),
                'contract_move_in_date' => $contract['move_in_date'] ?? null,
                'cells' => $cells,
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
        uasort($serviceCatalog, static fn($left, $right) => strcmp((string)($left['name'] ?? ''), (string)($right['name'] ?? '')));
        $lineCount = 0;
        foreach ($rows as $row) {
            $lineCount += count($row['cells'] ?? []);
        }
        return [
            'period' => $period,
            'rows' => $rows,
            'service_catalog' => array_values($serviceCatalog),
            'room_count' => count($rows),
            'line_count' => $lineCount,
            'completed_count' => self::countPeriodReadings($period['month'], $period['year']),
        ];
    }
    /**
     * [TEAM-FIX][NHOM3] Lưu nhiều chỉ số cùng lúc hoặc chỉ riêng một dòng phòng.
     * Hỗ trợ old_index nhập tay khi baseline không tự resolve được.
     */
    public static function saveReadings($month, $year, array $submittedReadings, array $options = []) {
        $period = self::normalizePeriod($month, $year);
        $targetRoomId = (int)($options['room_id'] ?? 0);
        $matrix = self::getAdminMatrix($period['month'], $period['year']);
        $rowsByRoomId = [];
        foreach ($matrix['rows'] as $row) {
            $roomId = (int)($row['room_id'] ?? 0);
            if ($targetRoomId > 0 && $roomId !== $targetRoomId) {
                continue;
            }
            $rowsByRoomId[$roomId] = $row;
        }
        if ($targetRoomId > 0 && !isset($rowsByRoomId[$targetRoomId])) {
            return [
                'saved_count' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'errors' => [
                    $targetRoomId => ['_room' => 'Phòng này không có dữ liệu công tơ hợp lệ trong kỳ đã chọn.'],
                ],
                'form_error' => 'Phòng được chọn không có dữ liệu công tơ để lưu.',
            ];
        }
        $savedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];
        $hasAnyInput = false;
        foreach ($rowsByRoomId as $roomId => $row) {
            $roomInput = $submittedReadings[$roomId] ?? [];
            if (!is_array($roomInput)) {
                continue;
            }
            foreach (($row['cells'] ?? []) as $serviceId => $cell) {
                $cellInput = $roomInput[$serviceId] ?? [];
                $rawNewValue = trim((string)($cellInput['new_index'] ?? ''));
                if ($rawNewValue === '') {
                    continue;
                }
                $hasAnyInput = true;
                if (!is_numeric($rawNewValue)) {
                    $errors[$roomId][$serviceId] = 'Chỉ số mới phải là số hợp lệ.';
                    continue;
                }
                if (!(bool)($cell['can_save'] ?? false)) {
                    $errors[$roomId][$serviceId] = $cell['baseline_error'] ?? 'Dòng này chưa có mốc chỉ số cũ hợp lệ.';
                    continue;
                }
                $oldIndex = (float)($cell['old_index'] ?? 0);
                if (!empty($cell['allow_manual_old_index']) && empty($cell['has_reading'])) {
                    $rawOldValue = trim((string)($cellInput['old_index'] ?? ''));
                    if ($rawOldValue !== '' && is_numeric($rawOldValue)) {
                        $oldIndex = (float)$rawOldValue;
                    }
                }
                $newIndex = (float)$rawNewValue;
                if ($newIndex < $oldIndex) {
                    $errors[$roomId][$serviceId] = 'Chỉ số mới (' . $newIndex . ') phải lớn hơn hoặc bằng chỉ số cũ (' . $oldIndex . ').';
                    continue;
                }
                $result = self::upsertReading(
                    $roomId,
                    (int)$serviceId,
                    $period['month'],
                    $period['year'],
                    $oldIndex,
                    $newIndex
                );
                $savedCount++;
                if ($result === 'created') {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }
        }
        return [
            'saved_count' => $savedCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'errors' => $errors,
            'form_error' => $hasAnyInput ? null : 'Vui lòng nhập ít nhất một chỉ số mới trước khi lưu.',
        ];
    }
    /**
     * Trả dữ liệu chỉ số của một phòng cho tenant trong kỳ được chọn.
     */
    public static function getTenantMonthlySummary($roomId, $month, $year) {
        $period = self::normalizePeriod($month, $year);
        $resolvedRoomId = (int)$roomId;
        $room = RoomModel::getById($resolvedRoomId);
        $readings = self::getReadingsByRoomAndPeriod($resolvedRoomId, $period['month'], $period['year']);
        $items = [];

        foreach ($readings as $reading) {
            $service = ServiceModel::getById((int)($reading['service_id'] ?? 0));
            if (!$service) {
                continue;
            }

            $oldIndex = (float)($reading['old_index'] ?? 0);
            $newIndex = (float)($reading['new_index'] ?? 0);
            $consumption = max(0, $newIndex - $oldIndex);
            $amount = $consumption * (float)($service['price'] ?? 0);
            $baseline = self::resolvePeriodBaseline($resolvedRoomId, $service, $period['month'], $period['year']);

            $items[] = [
                'service_id' => (int)($service['id'] ?? 0),
                'service_name' => $service['name'] ?? 'Dịch vụ',
                'service_icon' => $service['icon'] ?? 'settings',
                'unit' => $service['unit'] ?? 'đơn vị',
                'price' => (float)($service['price'] ?? 0),
                'old_index' => $oldIndex,
                'new_index' => $newIndex,
                'consumption' => $consumption,
                'amount' => $amount,
                'formula' => self::buildFormulaText($consumption, (float)($service['price'] ?? 0), $amount, $service['unit'] ?? 'đơn vị'),
                'baseline_note' => $baseline['source'] === 'contract_initial' ? $baseline['note'] : null,
            ];
        }

        usort($items, static fn($left, $right) => strcmp((string)($left['service_name'] ?? ''), (string)($right['service_name'] ?? '')));

        return [
            'period' => $period,
            'room' => $room,
            'items' => $items,
            'history' => self::getRoomConsumptionHistory($resolvedRoomId, 6),
            'has_readings' => !empty($items),
        ];
    }

    /**
     * Lấy lịch sử tiêu thụ 6 kỳ gần nhất để view tenant dựng biểu đồ mini.
     */
    public static function getRoomConsumptionHistory($roomId, $limit = 6) {
        $resolvedRoomId = (int)$roomId;
        $rawRows = self::getAllReadingsByRoom($resolvedRoomId);
        $grouped = [];

        foreach ($rawRows as $reading) {
            $service = ServiceModel::getById((int)($reading['service_id'] ?? 0));
            if (!$service) {
                continue;
            }

            $serviceId = (int)($service['id'] ?? 0);
            $consumption = max(0, (float)($reading['new_index'] ?? 0) - (float)($reading['old_index'] ?? 0));
            if (!isset($grouped[$serviceId])) {
                $grouped[$serviceId] = [
                    'service_id' => $serviceId,
                    'service_name' => $service['name'] ?? 'Dịch vụ',
                    'unit' => $service['unit'] ?? 'đơn vị',
                    'icon' => $service['icon'] ?? 'settings',
                    'points' => [],
                    'max_consumption' => 0,
                ];
            }

            $grouped[$serviceId]['points'][] = [
                'month' => (int)($reading['month'] ?? 0),
                'year' => (int)($reading['year'] ?? 0),
                'label' => str_pad((string)($reading['month'] ?? 0), 2, '0', STR_PAD_LEFT) . '/' . ($reading['year'] ?? ''),
                'consumption' => $consumption,
                'amount' => $consumption * (float)($service['price'] ?? 0),
            ];
            $grouped[$serviceId]['max_consumption'] = max($grouped[$serviceId]['max_consumption'], $consumption);
        }

        foreach ($grouped as &$serviceHistory) {
            usort($serviceHistory['points'], static function ($left, $right) {
                $leftOrder = ((int)$left['year'] * 100) + (int)$left['month'];
                $rightOrder = ((int)$right['year'] * 100) + (int)$right['month'];
                return $rightOrder <=> $leftOrder;
            });

            $serviceHistory['points'] = array_slice($serviceHistory['points'], 0, max(1, (int)$limit));
            $serviceHistory['points'] = array_reverse($serviceHistory['points']);
        }
        unset($serviceHistory);

        uasort($grouped, static fn($left, $right) => strcmp((string)($left['service_name'] ?? ''), (string)($right['service_name'] ?? '')));
        return array_values($grouped);
    }

    /**
     * Lấy danh sách dịch vụ tính theo chỉ số đang áp dụng cho một phòng.
     * Bao gồm dịch vụ bắt buộc, dịch vụ gán riêng và dịch vụ đã có bản ghi của kỳ đó.
     */
    public static function getMeterServicesForRoom($roomId, $month = null, $year = null) {
        $resolvedRoomId = (int)$roomId;
        if ($resolvedRoomId <= 0) {
            return [];
        }

        $serviceMap = [];
        $requiredServices = array_filter(
            ServiceModel::getAll([
                'applies_to' => 'room',
                'required_only' => true,
            ]),
            static fn($service) => ($service['billing_mode'] ?? '') === 'meter'
        );

        foreach ($requiredServices as $service) {
            $serviceMap[(int)($service['id'] ?? 0)] = $service;
        }

        foreach (ServiceModel::getAssignmentsByRoom($resolvedRoomId) as $service) {
            if (($service['billing_mode'] ?? '') !== 'meter') {
                continue;
            }
            $serviceMap[(int)($service['id'] ?? 0)] = $service;
        }

        if ($month !== null && $year !== null) {
            foreach (self::getReadingsByRoomAndPeriod($resolvedRoomId, (int)$month, (int)$year) as $reading) {
                $service = ServiceModel::getById((int)($reading['service_id'] ?? 0));
                if ($service && ($service['billing_mode'] ?? '') === 'meter') {
                    $serviceMap[(int)($service['id'] ?? 0)] = $service;
                }
            }
        }

        uasort($serviceMap, static fn($left, $right) => strcmp((string)($left['name'] ?? ''), (string)($right['name'] ?? '')));
        return array_values($serviceMap);
    }

    /**
     * Tìm chỉ số của đúng phòng/dịch vụ/kỳ.
     */
    public static function getReadingByPeriod($roomId, $serviceId, $month, $year) {
        $resolvedRoomId = (int)$roomId;
        $resolvedServiceId = (int)$serviceId;
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                'SELECT * FROM meter_readings WHERE room_id = ? AND service_id = ? AND month = ? AND year = ? LIMIT 1',
                [$resolvedRoomId, $resolvedServiceId, $resolvedMonth, $resolvedYear]
            );
            return $row ? self::normalizeReadingRow($row) : null;
        }

        foreach (Database::getTable('meter_readings') as $row) {
            if (
                (int)($row['room_id'] ?? 0) === $resolvedRoomId
                && (int)($row['service_id'] ?? 0) === $resolvedServiceId
                && (int)($row['month'] ?? 0) === $resolvedMonth
                && (int)($row['year'] ?? 0) === $resolvedYear
            ) {
                return self::normalizeReadingRow($row);
            }
        }

        return null;
    }

    /**
     * Trả danh sách chỉ số của một phòng trong đúng kỳ được chọn.
     */
    public static function getReadingsByRoomAndPeriod($roomId, $month, $year) {
        $resolvedRoomId = (int)$roomId;
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                'SELECT * FROM meter_readings WHERE room_id = ? AND month = ? AND year = ? ORDER BY service_id ASC',
                [$resolvedRoomId, $resolvedMonth, $resolvedYear]
            );
            return array_map([self::class, 'normalizeReadingRow'], $rows);
        }

        $rows = array_filter(Database::getTable('meter_readings'), static function ($row) use ($resolvedRoomId, $resolvedMonth, $resolvedYear) {
            return (int)($row['room_id'] ?? 0) === $resolvedRoomId
                && (int)($row['month'] ?? 0) === $resolvedMonth
                && (int)($row['year'] ?? 0) === $resolvedYear;
        });

        usort($rows, static fn($left, $right) => (int)($left['service_id'] ?? 0) <=> (int)($right['service_id'] ?? 0));
        return array_map([self::class, 'normalizeReadingRow'], array_values($rows));
    }

    /**
     * Tìm chỉ số tháng trước để làm mốc cho tháng hiện tại.
     */
    public static function getPreviousPeriodReading($roomId, $serviceId, $month, $year) {
        $previous = self::getPreviousPeriod($month, $year);
        return self::getReadingByPeriod($roomId, $serviceId, $previous['month'], $previous['year']);
    }

    /**
     * [TEAM-FIX][NHOM3] Tính chỉ số cũ cho một phòng/dịch vụ/kỳ.
     * Thêm nhánh manual_fallback: có hợp đồng nhưng thiếu mốc -> cho nhập tay old_index thay vì khóa ô.
     */
    public static function resolvePeriodBaseline($roomId, array $service, $month, $year) {
        $resolvedRoomId = (int)$roomId;
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;
        $serviceId = (int)($service['id'] ?? 0);
        $existing = self::getReadingByPeriod($resolvedRoomId, $serviceId, $resolvedMonth, $resolvedYear);
        if ($existing) {
            return [
                'old_index' => (float)($existing['old_index'] ?? 0),
                'source' => 'existing',
                'note' => 'Kỳ này đã có chỉ số được lưu trước đó.',
                'error' => null,
            ];
        }
        $previousReading = self::getPreviousPeriodReading($resolvedRoomId, $serviceId, $resolvedMonth, $resolvedYear);
        if ($previousReading) {
            return [
                'old_index' => (float)($previousReading['new_index'] ?? 0),
                'source' => 'previous',
                'note' => 'Tự lấy chỉ số chốt của tháng trước làm mốc cũ.',
                'error' => null,
            ];
        }
        $contract = self::getApplicableContractForRoom($resolvedRoomId, $resolvedMonth, $resolvedYear);
        if (!$contract) {
            return [
                'old_index' => null,
                'source' => 'missing_contract',
                'note' => null,
                'error' => 'Không tìm thấy hợp đồng phù hợp với kỳ này để xác định mốc ban đầu.',
            ];
        }
        $contractStartMonth = (int)date('n', strtotime((string)($contract['move_in_date'] ?? $contract['contract_date'] ?? 'now')));
        $contractStartYear = (int)date('Y', strtotime((string)($contract['move_in_date'] ?? $contract['contract_date'] ?? 'now')));
        $initialIndex = self::resolveContractInitialIndex($contract, $service);
        if ($contractStartMonth === $resolvedMonth && $contractStartYear === $resolvedYear) {
            if ($initialIndex !== null) {
                return [
                    'old_index' => (float)$initialIndex,
                    'source' => 'contract_initial',
                    'note' => 'Mốc ban đầu: ' . self::formatNumber($initialIndex) . ' (từ hợp đồng).',
                    'error' => null,
                ];
            }
            return [
                'old_index' => null,
                'source' => 'missing_initial',
                'note' => null,
                'error' => 'Tháng đầu chưa có chỉ số đầu kỳ trong hợp đồng. Hãy nhập tay chỉ số cũ ở ô màu vàng.',
                'allow_manual_old_index' => true,
            ];
        }
        return [
            'old_index' => null,
            'source' => 'manual_fallback',
            'note' => 'Không có mốc tự động (tháng trước thiếu chỉ số). Nhập tay chỉ số cũ ở ô màu vàng.',
            'error' => null,
            'allow_manual_old_index' => true,
        ];
    }
    /**
     * Tìm hợp đồng áp dụng cho phòng trong đúng kỳ đang thao tác.
     * Nếu trùng nhiều hợp đồng, ưu tiên hợp đồng có ngày vào ở gần kỳ nhất.
     */
    public static function getApplicableContractForRoom($roomId, $month, $year) {
        $resolvedRoomId = (int)$roomId;
        $period = self::normalizePeriod($month, $year);

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "
                SELECT *
                FROM contracts
                WHERE room_id = ?
                  AND move_in_date <= ?
                  AND (move_out_date IS NULL OR move_out_date >= ?)
                ORDER BY move_in_date DESC, id DESC
                LIMIT 1
                ",
                [$resolvedRoomId, $period['end_date'], $period['start_date']]
            );

            return $row ? $row : null;
        }

        $contracts = array_filter(Database::getTable('contracts'), static function ($contract) use ($resolvedRoomId, $period) {
            $moveInDate = (string)($contract['move_in_date'] ?? '');
            $moveOutDate = $contract['move_out_date'] ?? null;

            return (int)($contract['room_id'] ?? 0) === $resolvedRoomId
                && $moveInDate !== ''
                && $moveInDate <= $period['end_date']
                && ($moveOutDate === null || $moveOutDate === '' || $moveOutDate >= $period['start_date']);
        });

        usort($contracts, static function ($left, $right) {
            $moveInCompare = strcmp((string)($right['move_in_date'] ?? ''), (string)($left['move_in_date'] ?? ''));
            if ($moveInCompare !== 0) {
                return $moveInCompare;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return $contracts[0] ?? null;
    }

    /**
     * Ghi mới hoặc cập nhật lại chỉ số một dòng theo UNIQUE(room_id, service_id, month, year).
     */
    private static function upsertReading($roomId, $serviceId, $month, $year, $oldIndex, $newIndex) {
        $existing = self::getReadingByPeriod($roomId, $serviceId, $month, $year);
        $payload = [
            'room_id' => (int)$roomId,
            'service_id' => (int)$serviceId,
            'month' => (int)$month,
            'year' => (int)$year,
            'old_index' => (float)$oldIndex,
            'new_index' => (float)$newIndex,
        ];

        if ($existing) {
            Database::update(
                'meter_readings',
                [
                    'old_index' => (float)$oldIndex,
                    'new_index' => (float)$newIndex,
                ],
                'id = :id',
                ['id' => (int)($existing['id'] ?? 0)]
            );
            return 'updated';
        }

        Database::insert('meter_readings', $payload);
        return 'created';
    }

    /**
     * Đếm số dòng chỉ số đã được lưu trong một kỳ để hiển thị KPI nhanh.
     */
    private static function countPeriodReadings($month, $year) {
        $resolvedMonth = (int)$month;
        $resolvedYear = (int)$year;

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                'SELECT COUNT(*) AS total FROM meter_readings WHERE month = ? AND year = ?',
                [$resolvedMonth, $resolvedYear]
            );
            return (int)($row['total'] ?? 0);
        }

        return count(array_filter(Database::getTable('meter_readings'), static function ($reading) use ($resolvedMonth, $resolvedYear) {
            return (int)($reading['month'] ?? 0) === $resolvedMonth
                && (int)($reading['year'] ?? 0) === $resolvedYear;
        }));
    }

    /**
     * Lấy toàn bộ lịch sử chỉ số của một phòng, phục vụ biểu đồ tenant.
     */
    private static function getAllReadingsByRoom($roomId) {
        $resolvedRoomId = (int)$roomId;

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                'SELECT * FROM meter_readings WHERE room_id = ? ORDER BY year DESC, month DESC, service_id ASC',
                [$resolvedRoomId]
            );
            return array_map([self::class, 'normalizeReadingRow'], $rows);
        }

        $rows = array_filter(Database::getTable('meter_readings'), static fn($reading) => (int)($reading['room_id'] ?? 0) === $resolvedRoomId);
        usort($rows, static function ($left, $right) {
            $leftOrder = ((int)($left['year'] ?? 0) * 100) + (int)($left['month'] ?? 0);
            $rightOrder = ((int)($right['year'] ?? 0) * 100) + (int)($right['month'] ?? 0);
            if ($leftOrder !== $rightOrder) {
                return $rightOrder <=> $leftOrder;
            }

            return (int)($left['service_id'] ?? 0) <=> (int)($right['service_id'] ?? 0);
        });

        return array_map([self::class, 'normalizeReadingRow'], array_values($rows));
    }

    /**
     * Suy ra field chỉ số đầu kỳ trong hợp đồng dựa trên bản chất dịch vụ.
     * Đây là cách vá an toàn khi schema hiện tại chưa map service -> contract field một cách tường minh.
     */
    private static function resolveContractInitialIndex(array $contract, array $service) {
        $field = self::resolveInitialIndexField($service);
        if ($field === null) {
            return null;
        }

        return isset($contract[$field]) && $contract[$field] !== null
            ? (float)$contract[$field]
            : null;
    }

    /**
     * Suy đoán dịch vụ đang dùng mốc điện hay nước.
     */
/**
 * Suy đoán dịch vụ đang dùng mốc điện hay nước.
 * 
 * LƯU Ý: Đây là giải pháp tạm thời. Giải pháp đúng là thêm cột
 * `meter_type ENUM('electricity','water','other')` vào bảng `services`.
 * Khi đó, hàm này chỉ cần đọc trực tiếp cột đó.
 */
private static function resolveInitialIndexField(array $service) {
        switch ((string)($service['kind'] ?? 'other')) {
            case 'electricity':
                return 'initial_electricity_index';
            case 'water':
                return 'initial_water_index';
            default:
                return null;
        }
    }

    private static function getPreviousPeriod($month, $year) {
        $current = DateTime::createFromFormat('Y-n-j', (int)$year . '-' . (int)$month . '-1');
        $current = $current ?: new DateTime(date('Y-m-01'));
        $current->modify('-1 month');

        return [
            'month' => (int)$current->format('n'),
            'year' => (int)$current->format('Y'),
        ];
    }

    /**
     * Chuẩn hóa một bản ghi meter_readings để mọi nơi có cùng kiểu dữ liệu.
     */
    private static function normalizeReadingRow(array $row) {
        return [
            'id' => (int)($row['id'] ?? 0),
            'room_id' => (int)($row['room_id'] ?? 0),
            'service_id' => (int)($row['service_id'] ?? 0),
            'month' => (int)($row['month'] ?? 0),
            'year' => (int)($row['year'] ?? 0),
            'old_index' => (float)($row['old_index'] ?? 0),
            'new_index' => (float)($row['new_index'] ?? 0),
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    /**
     * Tạo chuỗi công thức rõ ràng để tenant hiểu tiền được tính như thế nào.
     */
    private static function buildFormulaText($consumption, $price, $amount, $unit) {
        return self::formatNumber($consumption) . ' ' . trim((string)$unit)
            . ' x ' . number_format((float)$price, 0, ',', '.')
            . 'đ = ' . number_format((float)$amount, 0, ',', '.') . 'đ';
    }

    /**
     * Format số gọn cho chỉ số tiêu thụ và mốc công tơ.
     */
    private static function formatNumber($value) {
        $number = (float)$value;
        if (floor($number) == $number) {
            return number_format($number, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    /**
     * Kiểm tra một chuỗi có chứa ít nhất một từ khóa hay không.
     */
    private static function containsAny($text, array $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($text, mb_strtolower((string)$keyword, 'UTF-8'), 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }
}
