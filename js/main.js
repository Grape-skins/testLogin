// 动态更新提交按钮文本
const loginRadio = document.getElementById('login');
const registerRadio = document.getElementById('register');
const submitText = document.getElementById('submit-text');
const passwordStrength = document.getElementById('password-strength');
const emailValidation = document.getElementById('email-validation');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');

function updateSubmitText() {
    if (loginRadio.checked) {
        submitText.textContent = '登录';
        passwordStrength.classList.remove('show');
    } else {
        submitText.textContent = '注册';
    }
}

// 密码强度检查
function checkPasswordStrength(password) {
    if (password.length === 0) {
        passwordStrength.classList.remove('show');
        return;
    }
    
    let strength = 0;
    let strengthText = '';
    let strengthClass = 'strength-weak'; // 设置默认值
    
    // 长度检查
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    
    // 包含数字
    if (/\d/.test(password)) strength++;
    
    // 包含字母
    if (/[a-zA-Z]/.test(password)) strength++;
    
    // 包含特殊字符
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    
    if (password.length < 6) {
        strengthText = '密码太短（至少需要6个字符）';
        strengthClass = 'strength-weak';
    } else if (strength <= 2) {
        strengthText = '密码强度：弱';
        strengthClass = 'strength-weak';
    } else if (strength <= 3) {
        strengthText = '密码强度：中等';
        strengthClass = 'strength-medium';
    } else {
        strengthText = '密码强度：强';
        strengthClass = 'strength-strong';
    }
    
    passwordStrength.textContent = strengthText;
    passwordStrength.className = `password-strength show ` + strengthClass;
}

// 邮箱格式验证
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.length > 0 && !emailRegex.test(email)) {
        emailValidation.classList.add('show');
    } else {
        emailValidation.classList.remove('show');
    }
}

// 事件监听
loginRadio.addEventListener('change', updateSubmitText);
registerRadio.addEventListener('change', updateSubmitText);

passwordInput.addEventListener('input', function() {
    if (registerRadio.checked) {
        checkPasswordStrength(this.value);
    }
});

emailInput.addEventListener('blur', function() {
    validateEmail(this.value);
});

emailInput.addEventListener('input', function() {
    if (emailValidation.classList.contains('show')) {
        validateEmail(this.value);
    }
});

// 模式切换时的处理
registerRadio.addEventListener('change', function() {
    if (this.checked && passwordInput.value) {
        checkPasswordStrength(passwordInput.value);
    }
});

// 表单提交验证
document.querySelector('form').addEventListener('submit', function(e) {
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    
    // 邮箱格式验证
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        emailValidation.classList.add('show');
        emailInput.focus();
        return;
    }
    
    // 密码长度验证
    if (password.length < 6) {
        e.preventDefault();
        alert('密码长度至少需要6个字符');
        passwordInput.focus();
        return;
    }
    
    // 提交动画
    const submitBtn = document.querySelector('.submit-btn');
    submitBtn.style.transform = 'scale(0.95)';
    setTimeout(() => {
        submitBtn.style.transform = '';
    }, 150);
});

// 输入框焦点效果
const inputs = document.querySelectorAll('.form-input');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = '';
    });
}); 