/**
 * Validation dùng chung cho mọi form tạo/sửa tài khoản (register, đổi mật khẩu,
 * reset mật khẩu, hồ sơ tenant, thêm/sửa tài khoản admin).
 * Rule đồng nhất với models/user/UserModel.php.
 * Email: chỉ chấp nhận @gmail.com (đuôi luôn .com).
 */

function validateEmailStrict(email) {
    if (!email) return { valid: true }; // Email không bắt buộc
    email = email.trim();
    if (email.length > 254) return { valid: false, message: 'Email không được vượt quá 254 ký tự.' };
    if (email.split('@').length !== 2) return { valid: false, message: 'Email phải có đúng một dấu @.' };
    if (email.includes(' ')) return { valid: false, message: 'Email không được chứa khoảng trắng.' };

    var parts = email.split('@');
    var localPart = parts[0];
    var domain = parts[1];

    if (!localPart || !domain) return { valid: false, message: 'Email không hợp lệ.' };

    // Local-part checks (phần trước @)
    if (localPart.length > 64) return { valid: false, message: 'Phần trước @ không được vượt quá 64 ký tự.' };
    if (localPart[0] === '.' || localPart[localPart.length - 1] === '.') return { valid: false, message: 'Phần trước @ không được bắt đầu hoặc kết thúc bằng dấu chấm.' };
    if (localPart.includes('..')) return { valid: false, message: 'Phần trước @ không được có hai dấu chấm liên tiếp.' };
    if (!/^[A-Za-z0-9._%+-]+$/.test(localPart)) return { valid: false, message: 'Phần trước @ chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, . _ % + -' };

    // Domain checks (phần sau @)
    if (domain.length > 255) return { valid: false, message: 'Tên miền không được vượt quá 255 ký tự.' };
    if (!domain.includes('.')) return { valid: false, message: 'Tên miền phải có ít nhất một dấu chấm.' };
    if (domain[0] === '.' || domain[domain.length - 1] === '.') return { valid: false, message: 'Tên miền không được bắt đầu hoặc kết thúc bằng dấu chấm.' };
    if (domain.includes('..')) return { valid: false, message: 'Tên miền không được có hai dấu chấm liên tiếp.' };

    // Check each domain label (parts separated by dots)
    var domainLabels = domain.split('.');
    for (var i = 0; i < domainLabels.length; i++) {
        var label = domainLabels[i];
        if (!label) return { valid: false, message: 'Tên miền có nhãn rỗng.' };
        if (label.length > 63) return { valid: false, message: 'Nhãn tên miền không được vượt quá 63 ký tự.' };
        if (label[0] === '-' || label[label.length - 1] === '-') return { valid: false, message: 'Nhãn tên miền không được bắt đầu hoặc kết thúc bằng dấu gạch ngang.' };
        if (!/^[A-Za-z0-9-]+$/.test(label)) return { valid: false, message: 'Tên miền chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, dấu gạch ngang.' };
    }

    // TLD check (last label)
    var tld = domainLabels[domainLabels.length - 1];
    if (!/^[a-zA-Z]{2,63}$/.test(tld)) return { valid: false, message: 'Đuôi tên miền (TLD) phải từ 2-63 ký tự chữ.' };
    if (domain.toLowerCase() === 'localhost') return { valid: false, message: 'Không chấp nhận localhost.' };

    // Chỉ chấp nhận email @gmail.com (đuôi luôn .com)
    if (domain.toLowerCase() !== 'gmail.com') return { valid: false, message: 'Email phải có đuôi @gmail.com.' };

    return { valid: true };
}

function normalizePhoneInput(rawPhone) {
    if (!rawPhone) return null;
    var phone = rawPhone.replace(/\s+/g, '');
    if (!/^[0-9+]+$/.test(phone)) return null;
    var plusPos = phone.indexOf('+');
    if (plusPos !== -1 && plusPos !== 0) return null;

    if (phone.startsWith('+84')) {
        var suffix = phone.substring(3);
        if (suffix.length !== 9 || !/^\d+$/.test(suffix)) return null;
        if (suffix[0] === '0') return null;
        return '0' + suffix;
    }

    if (phone.startsWith('84') && !phone.startsWith('+')) {
        var suffix = phone.substring(2);
        if (suffix.length !== 9 || !/^\d+$/.test(suffix)) return null;
        if (suffix[0] === '0') return null;
        return '0' + suffix;
    }

    if (phone.startsWith('0')) {
        if (phone.length !== 10 || !/^\d+$/.test(phone)) return null;
        return phone;
    }

    return null;
}

function validateFullName(value) {
    if (!value) return 'Vui lòng nhập họ và tên.';
    if (!value.trim()) return 'Họ và tên không được chỉ chứa khoảng trắng.';
    if (value.length > 100) return 'Họ và tên không được vượt quá 100 ký tự.';
    if (!/^[\p{L}\p{N}\s\-'\.]+$/u.test(value.trim())) return 'Họ và tên chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, khoảng trắng, dấu gạch ngang, dấu chấm, dấu nháy đơn.';
    return '';
}

function validatePassword(value) {
    if (!value) return 'Vui lòng nhập mật khẩu.';
    if (value.length < 6) return 'Mật khẩu phải có ít nhất 6 ký tự.';
    if (!/[A-Za-z]/.test(value)) return 'Mật khẩu phải chứa ít nhất 1 chữ cái.';
    if (!/\d/.test(value)) return 'Mật khẩu phải chứa ít nhất 1 số.';
    return '';
}

function validateConfirmPassword(confirmValue, passwordValue) {
    if (!confirmValue) return 'Vui lòng xác nhận mật khẩu.';
    if (confirmValue !== passwordValue) return 'Xác nhận mật khẩu chưa khớp.';
    return '';
}