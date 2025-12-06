// 입양 희망자 회원정보 수정 페이지 JavaScript

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 기존 데이터 불러오기 (예시 데이터)
    loadUserData();
    
    // 폼 제출 이벤트
    const form = document.getElementById('userProfileForm');
    form.addEventListener('submit', handleSubmit);
    
    // 탈퇴 버튼 이벤트
    const deleteBtn = document.getElementById('deleteBtn');
    deleteBtn.addEventListener('click', handleDelete);
    
    // 로그아웃 버튼 이벤트
    const logoutBtn = document.getElementById('logoutBtn');
    logoutBtn.addEventListener('click', handleLogout);
    
    // 성별 버튼 이벤트 (수정 가능)
    const maleBtn = document.getElementById('maleBtn');
    const femaleBtn = document.getElementById('femaleBtn');
    maleBtn.addEventListener('click', () => selectGender('male'));
    femaleBtn.addEventListener('click', () => selectGender('female'));
    
    // 전화번호 입력 포맷팅
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', formatPhoneNumber);
});

// 기존 사용자 데이터 불러오기
function loadUserData() {
    // 실제로는 서버에서 데이터를 가져옴
    const userData = {
        userId: 'user001',
        name: 'ㅇㅇㅇ',
        phone: '010-9876-5432',
        birthYear: '1990',
        birthMonth: '05',
        birthDay: '15',
        residence1: '경기도',
        residence2: '수원시',
        gender: 'male' // 'male' or 'female'
    };
    
    // 폼에 데이터 채우기
    document.getElementById('userId').value = userData.userId;
    document.getElementById('name').value = userData.name;
    document.getElementById('phone').value = userData.phone;
    document.getElementById('birthYear').value = userData.birthYear;
    document.getElementById('birthMonth').value = userData.birthMonth;
    document.getElementById('birthDay').value = userData.birthDay;
    document.getElementById('residence1').value = userData.residence1;
    document.getElementById('residence2').value = userData.residence2;
    
    // 성별 선택
    if (userData.gender === 'male') {
        document.getElementById('maleBtn').classList.add('selected');
    } else {
        document.getElementById('femaleBtn').classList.add('selected');
    }
    
    // 프로필 이름 업데이트
    document.querySelector('.profile-name').textContent = userData.name + '님';
}

// 전화번호 포맷팅
function formatPhoneNumber(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    
    if (value.length <= 3) {
        e.target.value = value;
    } else if (value.length <= 7) {
        e.target.value = value.slice(0, 3) + '-' + value.slice(3);
    } else if (value.length <= 11) {
        e.target.value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7);
    } else {
        e.target.value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
    }
}

// 성별 선택 함수
function selectGender(gender) {
    const maleBtn = document.getElementById('maleBtn');
    const femaleBtn = document.getElementById('femaleBtn');
    
    maleBtn.classList.remove('selected');
    femaleBtn.classList.remove('selected');
    
    if (gender === 'male') {
        maleBtn.classList.add('selected');
    } else {
        femaleBtn.classList.add('selected');
    }
}

// 폼 제출 처리
function handleSubmit(e) {
    e.preventDefault();
    
    // 입력 값 가져오기
    const formData = {
        userId: document.getElementById('userId').value,
        password: document.getElementById('password').value,
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        birthYear: document.getElementById('birthYear').value,
        birthMonth: document.getElementById('birthMonth').value,
        birthDay: document.getElementById('birthDay').value,
        residence1: document.getElementById('residence1').value,
        residence2: document.getElementById('residence2').value,
        gender: document.getElementById('maleBtn').classList.contains('selected') ? 'male' : 'female'
    };
    
    // 유효성 검사
    if (!formData.phone) {
        alert('전화번호를 입력해주세요.');
        return;
    }
    
    if (!formData.residence1 || !formData.residence2) {
        alert('거주지를 모두 입력해주세요.');
        return;
    }
    
    // 서버로 데이터 전송 (실제 구현 시)
    console.log('수정된 데이터:', formData);
    
    // 성공 메시지
    alert('회원정보가 수정되었습니다.');
    
    // 비밀번호 필드 초기화
    document.getElementById('password').value = '';
}

// 회원 탈퇴 처리
function handleDelete() {
    const confirmed = confirm('정말로 탈퇴하시겠습니까?\n탈퇴 시 모든 정보가 삭제되며 복구할 수 없습니다.');
    
    if (confirmed) {
        const doubleConfirm = confirm('다시 한 번 확인합니다. 탈퇴하시겠습니까?');
        
        if (doubleConfirm) {
            // 실제로는 서버에 탈퇴 요청
            console.log('회원 탈퇴 처리');
            alert('회원 탈퇴가 완료되었습니다.');
            
            // 메인 페이지로 이동
            window.location.href = '../index.html';
        }
    }
}

// 로그아웃 처리
function handleLogout() {
    const confirmed = confirm('로그아웃 하시겠습니까?');
    
    if (confirmed) {
        // 실제로는 세션 종료 처리
        console.log('로그아웃 처리');
        alert('로그아웃 되었습니다.');
        
        // 메인 페이지로 이동
        window.location.href = '../index.html';
    }
}