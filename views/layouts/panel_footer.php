</main>
</div>
<?php if (!empty($panelPageScripts)): ?>
<?= $panelPageScripts ?>
<?php endif; ?>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<?php if (($panelTheme ?? '') === 'admin'): ?>
<script>
(function() {
    // Auto-add confirm dialog to admin forms that don't have one
    document.querySelectorAll('form[method="POST"]').forEach(function(form) {
        // Skip if already has onsubmit handler
        if (form.onsubmit) return;

        var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitBtn) return;

        var btnText = (submitBtn.textContent || submitBtn.value || '').toLowerCase();
        var isDelete = btnText.includes('xóa') || btnText.includes('delete') || btnText.includes('hủy') || btnText.includes('cancel');
        var isApprove = btnText.includes('duyệt') || btnText.includes('approve');
        var isReject = btnText.includes('từ chối') || btnText.includes('reject');
        var isVeto = btnText.includes('veto') || btnText.includes('gỡ');
        var isAddEdit = btnText.includes('thêm') || btnText.includes('add') || btnText.includes('sửa')
            || btnText.includes('edit') || btnText.includes('update') || btnText.includes('cập nhật')
            || btnText.includes('lưu') || btnText.includes('save') || btnText.includes('tạo')
            || btnText.includes('create') || btnText.includes('gửi');
        var isAction = isDelete || isApprove || isReject || isVeto || isAddEdit;

        if (isAction) {
            var messages = {
                delete: 'Bạn chắc chắn muốn xóa?',
                approve: 'Xác nhận duyệt?',
                reject: 'Xác nhận từ chối?',
                veto: 'Xác nhận veto và gỡ?',
                addEdit: 'Xác nhận thực hiện thao tác này?'
            };
            var msg = isDelete ? messages.delete
                : (isApprove ? messages.approve
                    : (isReject ? messages.reject
                        : (isVeto ? messages.veto : messages.addEdit)));
            form.addEventListener('submit', function(e) {
                if (!confirm(msg)) {
                    e.preventDefault();
                }
            });
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
