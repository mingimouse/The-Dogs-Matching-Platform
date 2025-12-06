// 유기견 상세정보 모달 JavaScript

// 예시 유기견 데이터 (실제로는 URL 파라미터나 전달받은 데이터 사용)
const dogDetailData = {
    id: 1,
    name: '쪼꼬',
    shelter: '희망보호소',
    breed: '포메라니안',
    age: '9살',
    gender: '암컷',
    color: '갈색',
    weight: '4.5kg',
    image: '../img/dog1.png',
    diseases: {
        parvo: 'O',
        corona: 'O',
        heartworm: 'X',
        distemper: 'O'
    }
};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 강아지 데이터 로드
    loadDogData();
    
    // 취소 버튼 이벤트
    const cancelBtn = document.getElementById('cancelBtn');
    cancelBtn.addEventListener('click', closeModal);
    
    // 입양 신청 버튼 이벤트
    const adoptBtn = document.getElementById('adoptBtn');
    adoptBtn.addEventListener('click', handleAdopt);
    
    // 로그아웃 버튼 이벤트
    const logoutBtn = document.getElementById('logoutBtn');
    logoutBtn.addEventListener('click', handleLogout);
});

// 강아지 데이터 로드
function loadDogData() {
    // 실제로는 URL 파라미터나 localStorage에서 데이터를 가져옴
    // 예: const urlParams = new URLSearchParams(window.location.search);
    // const dogId = urlParams.get('id');
    
    const data = dogDetailData;
    
    // 기본 정보 설정
    document.getElementById('dogImage').src = data.image;
    document.getElementById('dogImage').alt = data.name;
    document.getElementById('dogName').textContent = data.name;
    document.getElementById('shelterName').textContent = data.shelter;
    document.getElementById('breed').textContent = data.breed;
    document.getElementById('age').textContent = data.age;
    document.getElementById('gender').textContent = data.gender;
    document.getElementById('color').textContent = data.color;
    document.getElementById('weight').textContent = data.weight;
    
    // 질병 정보 설정
    document.getElementById('parvo').textContent = data.diseases.parvo;
    document.getElementById('corona').textContent = data.diseases.corona;
    document.getElementById('heartworm').textContent = data.diseases.heartworm;
    document.getElementById('distemper').textContent = data.diseases.distemper;
    
    // 질병 정보에 따라 색상 변경 (양성이면 빨간색)
    applyDiseaseColors(data.diseases);
}

// 질병 정보 색상 적용
function applyDiseaseColors(diseases) {
    const diseaseIds = ['parvo', 'corona', 'heartworm', 'distemper'];
    
    diseaseIds.forEach(id => {
        const cell = document.getElementById(id);
        if (diseases[id] === 'X') {
            cell.style.color = '#111';
            cell.style.fontWeight = '700';
        } else {
            cell.style.color = '#111';
            cell.style.fontWeight = '500';
        }
    });
}

// 모달 닫기
function closeModal() {
    // 실제로는 모달을 숨기거나 이전 페이지로 이동
    // 여기서는 dog_list.html로 이동
    window.location.href = 'dog_list.html';
}

// 로그아웃 처리
function handleLogout() {
    const confirmed = confirm('로그아웃 하시겠습니까?');
    
    if (confirmed) {
        console.log('로그아웃 처리');
        alert('로그아웃 되었습니다.');
        window.location.href = '../index.html';
    }
}

// 입양 신청 처리
function handleAdopt() {
    const dogName = document.getElementById('dogName').textContent;
    
    const confirmed = confirm(`${dogName}에 대한 입양 신청을 하시겠습니까?`);
    
    if (confirmed) {
        console.log('입양 신청:', dogDetailData);
        alert('입양 신청이 완료되었습니다!\n심사 결과는 "입양 심사 결과" 페이지에서 확인하실 수 있습니다.');
        
        // 실제로는 서버에 입양 신청 데이터 전송
        // 입양 신청 후 유기견 조회 페이지로 이동
        window.location.href = 'dog_list.html';
    }
}

// URL 파라미터에서 강아지 ID 가져오기 (실제 사용 시)
function getDogIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

// 서버에서 강아지 데이터 가져오기 (실제 사용 시)
async function fetchDogData(dogId) {
    try {
        // const response = await fetch(`/api/dogs/${dogId}`);
        // const data = await response.json();
        // return data;
        
        // 현재는 예시 데이터 반환
        return dogDetailData;
    } catch (error) {
        console.error('강아지 데이터 로드 실패:', error);
        alert('강아지 정보를 불러오는데 실패했습니다.');
        return null;
    }
}