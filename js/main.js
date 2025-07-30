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
if (loginRadio) loginRadio.addEventListener('change', updateSubmitText);
if (registerRadio) registerRadio.addEventListener('change', updateSubmitText);

if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        if (registerRadio && registerRadio.checked) {
            checkPasswordStrength(this.value);
        }
    });
}

if (emailInput) {
    emailInput.addEventListener('blur', function() {
        validateEmail(this.value);
    });

    emailInput.addEventListener('input', function() {
        if (emailValidation.classList.contains('show')) {
            validateEmail(this.value);
        }
    });
}

// 模式切换时的处理
if (registerRadio) {
    registerRadio.addEventListener('change', function() {
        if (this.checked && passwordInput && passwordInput.value) {
            checkPasswordStrength(passwordInput.value);
        }
    });
}

// 表单提交验证
const loginForm = document.querySelector('form');
if (loginForm && emailInput && passwordInput) {
    loginForm.addEventListener('submit', function(e) {
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
        if (submitBtn) {
            submitBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
                submitBtn.style.transform = '';
            }, 150);
        }
    });
}

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

// 隐藏表单提交函数 - 用于URL参数隐藏
function submitHiddenForm(params) {
    const hiddenForm = document.getElementById('hiddenForm');
    if (hiddenForm) {
        const hiddenPage = document.getElementById('hiddenPage');
        const hiddenPerPage = document.getElementById('hiddenPerPage');
        const hiddenSearchEmail = document.getElementById('hiddenSearchEmail');
        
        if (hiddenPage) hiddenPage.value = params.page || 1;
        if (hiddenPerPage) hiddenPerPage.value = params.per_page || 20;
        if (hiddenSearchEmail) hiddenSearchEmail.value = params.search_email || '';
        
        // 添加提交动画
        const pageLinks = document.querySelectorAll('.page-link');
        pageLinks.forEach(link => {
            if (link.textContent == params.page) {
                link.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    link.style.transform = '';
                }, 150);
            }
        });
        
        hiddenForm.submit();
    }
}

// 用户列表页面功能
document.addEventListener('DOMContentLoaded', function() {
    // 检查是否在用户列表页面
    const userListContainer = document.querySelector('.user-list-container');
    if (!userListContainer) return;
    
    // 每页显示条数变化时自动提交
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            // 更新隐藏字段
            const hiddenPerPage = document.getElementById('hiddenPerPage');
            if (hiddenPerPage) {
                hiddenPerPage.value = this.value;
            }
            
            // 重置页码为1
            const hiddenPage = document.getElementById('hiddenPage');
            if (hiddenPage) {
                hiddenPage.value = 1;
            }
            
            // 获取当前搜索词
            const searchInput = document.getElementById('search_email');
            const hiddenSearchEmail = document.getElementById('hiddenSearchEmail');
            if (searchInput && hiddenSearchEmail) {
                hiddenSearchEmail.value = searchInput.value;
            }
            
            // 提交隐藏表单
            const hiddenForm = document.getElementById('hiddenForm');
            if (hiddenForm) {
                hiddenForm.submit();
            }
        });
    }
    
    // 搜索框回车提交 - 优化版本
    const searchInput = document.getElementById('search_email');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // 确保表单提交到正确的URL
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
        
        // 添加搜索框焦点效果
        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#667eea';
            this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
        });
        
        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    }
    
    // 表格行悬停效果
    const userRows = document.querySelectorAll('.user-row');
    userRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // 分页链接点击效果
    const pageLinks = document.querySelectorAll('.page-link');
    pageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.classList.contains('active')) {
                e.preventDefault();
                return;
            }
            
            // 添加点击动画
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // 搜索按钮点击效果
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    }
    
    // 清除按钮点击效果
    const clearBtn = document.querySelector('.clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault(); // 阻止默认的链接跳转
            
            // 清空搜索框
            if (searchInput) {
                searchInput.value = '';
                searchInput.style.background = ''; // 清除背景色
            }
            
            // 重置每页显示数量为默认值
            if (perPageSelect) {
                perPageSelect.value = '20';
            }
            
            // 重置隐藏字段
            const hiddenSearchEmail = document.getElementById('hiddenSearchEmail');
            if (hiddenSearchEmail) {
                hiddenSearchEmail.value = '';
            }
            
            const hiddenPage = document.getElementById('hiddenPage');
            if (hiddenPage) {
                hiddenPage.value = '1';
            }
            
            const hiddenPerPage = document.getElementById('hiddenPerPage');
            if (hiddenPerPage) {
                hiddenPerPage.value = '20';
            }
            
            // 提交表单以刷新页面
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
            
            // 添加点击动画
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    }
    
    // 添加搜索框的实时反馈
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (value.length > 0) {
                this.style.background = 'rgba(102, 126, 234, 0.05)';
            } else {
                this.style.background = '';
            }
        });
    }
}); 