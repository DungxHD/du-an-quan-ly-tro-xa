<?php
/**
 * Cấu hình quy tắc đánh giá phòng:
 * - số ngày ở tối thiểu trước khi được đánh giá,
 * - thời gian được sửa đánh giá kể từ khi gửi.
 */
class CommentModerationModel {
    /**
     * Lấy toàn bộ setting đánh giá đã ép kiểu sẵn.
     */
    public static function getSettings() {
        return [
            'min_days_to_review' => max(0, (int)SettingModel::get('min_days_to_review', '15')),
            'comment_edit_hours' => max(1, (int)SettingModel::get('comment_edit_hours', '24')),
        ];
    }
}