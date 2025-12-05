// 폼 제출 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // 입력 값 검증
            if (username.trim() === '') {
                alert('아이디를 입력해주세요.');
                return;
            }
            
            if (password.trim() === '') {
                alert('비밀번호를 입력해주세요.');
                return;
            }
            
            // 로그인 처리 (실제 구현 시 서버와 통신)
            console.log('로그인 시도:', {
                username: username,
                password: password,
                type: document.querySelector('.login-title').textContent
            });
            
            // 성공 메시지 (실제로는 서버 응답에 따라 처리)
            alert('로그인 성공!');
            
            // 폼 초기화
            loginForm.reset();
        });
    }
    
    // 입력 필드에 포커스 효과 추가
    const inputs = document.querySelectorAll('.login-form input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#4a90e2';
        });
        
        input.addEventListener('blur', function() {
            this.style.borderColor = '#333';
        });
    });
});