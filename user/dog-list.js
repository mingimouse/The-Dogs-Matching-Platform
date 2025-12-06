// 유기견 조회 페이지 JavaScript

// 예시 유기견 데이터 (실제로는 서버에서 가져옴)
const dogsData = [
    { id: 1, name: '쪼꼬', shelter: '희망보호소', breed: '포메라니안', color: '갈색', gender: '암컷', image: '../img/dog1.png' },
    { id: 2, name: '구름', shelter: '희망보호소', breed: '말티즈', color: '흰색', gender: '수컷', image: '../img/dog2.jpg' },
    { id: 3, name: '까망', shelter: '별빛쉼터', breed: '푸들', color: '검정', gender: '수컷', image: '../img/dog3.jpg' },
    { id: 4, name: '말콩', shelter: '별빛쉼터', breed: '리트리버', color: '갈색', gender: '수컷', image: '../img/dog4.jpg' },
    { id: 5, name: '장군', shelter: '소망쉼터', breed: '불독', color: '검정', gender: '수컷', image: '../img/dog5.jpg' },
    { id: 6, name: '아리', shelter: '소망쉼터', breed: '비숑', color: '흰색', gender: '암컷', image: '../img/dog6.jpg' },
    { id: 7, name: '맥스', shelter: '달빛보호소', breed: '닥스훈트', color: '갈색', gender: '수컷', image: '../img/dog7.jpg' },
    { id: 8, name: '천둥', shelter: '달빛보호소', breed: '시바', color: '갈색', gender: '수컷', image: '../img/dog8.jpg' },
    { id: 9, name: '봄이', shelter: '행복쉼터', breed: '포메라니안', color: '흰색', gender: '암컷', image: '../img/dog9.jpg' },
    { id: 10, name: '초코', shelter: '행복쉼터', breed: '푸들', color: '갈색', gender: '수컷', image: '../img/dog10.jpg' },
    { id: 11, name: '망고', shelter: '사랑보호소', breed: '웰시코기', color: '갈색', gender: '수컷', image: '../img/dog11.jpg' },
    { id: 12, name: '구름이', shelter: '사랑보호소', breed: '말티즈', color: '흰색', gender: '암컷', image: '../img/dog12.jpg' },
    { id: 13, name: '뽀삐', shelter: '새별쉼터', breed: '비숑', color: '흰색', gender: '암컷', image: '../img/dog13.jpg' }
];

// 전역 변수
let currentPage = 1;
let filteredDogs = [...dogsData];
const itemsPerPage = 8; // 페이지당 8개

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 유기견 목록 렌더링
    renderDogs();
    
    // 검색 버튼 이벤트
    const searchBtn = document.getElementById('searchBtn');
    searchBtn.addEventListener('click', handleSearch);
    
    // 드롭다운 변경 시 자동 검색
    const selects = document.querySelectorAll('.filter-select');
    selects.forEach(select => {
        select.addEventListener('change', handleSearch);
    });
    
    // 로그아웃 버튼 이벤트
    const logoutBtn = document.getElementById('logoutBtn');
    logoutBtn.addEventListener('click', handleLogout);
});

// 검색 처리
function handleSearch() {
    const breed = document.getElementById('breedSelect').value.toLowerCase().trim();
    const color = document.getElementById('colorSelect').value.toLowerCase().trim();
    const gender = document.getElementById('genderSelect').value.toLowerCase().trim();
    
    // 필터링
    filteredDogs = dogsData.filter(dog => {
        const matchBreed = !breed || dog.breed.toLowerCase().includes(breed);
        const matchColor = !color || dog.color.toLowerCase().includes(color);
        const matchGender = !gender || dog.gender.toLowerCase().includes(gender);
        
        return matchBreed && matchColor && matchGender;
    });
    
    // 첫 페이지로 이동
    currentPage = 1;
    renderDogs();
}

// 유기견 목록 렌더링
function renderDogs() {
    const dogGrid = document.getElementById('dogGrid');
    
    // 현재 페이지에 표시할 데이터 계산
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const currentDogs = filteredDogs.slice(startIndex, endIndex);
    
    // 유기견 카드 생성
    dogGrid.innerHTML = '';
    
    if (currentDogs.length === 0) {
        dogGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #999; padding: 40px;">검색 결과가 없습니다.</p>';
    } else {
        currentDogs.forEach(dog => {
            const card = createDogCard(dog);
            dogGrid.appendChild(card);
        });
    }
    
    // 페이지네이션 렌더링
    renderPagination();
}

// 유기견 카드 생성
function createDogCard(dog) {
    const card = document.createElement('div');
    card.className = 'dog-card';
    
    card.innerHTML = `
        <img src="${dog.image}" alt="${dog.name}" class="dog-image" onerror="this.src='../img/default-dog.jpg'">
        <div class="dog-name">${dog.name}</div>
        <div class="dog-shelter">(${dog.shelter})</div>
        <button class="detail-btn" onclick="showDetail(${dog.id})">상세 정보</button>
    `;
    
    return card;
}

// 페이지네이션 렌더링
function renderPagination() {
    const pagination = document.getElementById('pagination');
    const totalPages = Math.ceil(filteredDogs.length / itemsPerPage);
    
    pagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'page-btn';
        if (i === currentPage) {
            pageBtn.classList.add('active');
        }
        pageBtn.textContent = i;
        pageBtn.addEventListener('click', () => {
            currentPage = i;
            renderDogs();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        pagination.appendChild(pageBtn);
    }
}

// 상세 정보 보기
function showDetail(dogId) {
    const dog = dogsData.find(d => d.id === dogId);
    console.log('상세 정보:', dog);
    // 실제로는 모달을 열거나 상세 페이지로 이동
    alert(`${dog.name}의 상세 정보를 표시합니다.\n(모달 페이지는 다음에 구현)`);
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