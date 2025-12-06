// 보호소 조회 JavaScript

// 예시 보호소 데이터
const shelters = [
    {
        id: 1,
        name: '포옹하우스',
        location: '서울 구로구 신도림로 87',
        phone: '02-000-0000',
        hours: '08:00 ~ 19:00',
        city: '서울',
        district: '구로구'
    },
    {
        id: 2,
        name: '희망의 집',
        location: '대구 북구 동천로 156',
        phone: '053-000-0000',
        hours: '10:00 ~ 19:00',
        city: '대구',
        district: '북구'
    },
    {
        id: 3,
        name: '러브테일',
        location: '경기도 광주시 태성로 130',
        phone: '031-000-0000',
        hours: '08:00 ~ 17:00',
        city: '경기도',
        district: '광주시'
    },
    {
        id: 4,
        name: '별빛쉼터',
        location: '서울 노원구 동일로 207길 17',
        phone: '02-000-0000',
        hours: '09:00 ~ 18:00',
        city: '서울',
        district: '노원구'
    },
    {
        id: 5,
        name: '해피포우스',
        location: '세종시 조치원읍 세종로 2639',
        phone: '044-860-0000',
        hours: '09:00 ~ 18:00',
        city: '세종시',
        district: '조치원읍'
    },
    {
        id: 6,
        name: '사랑보호소',
        location: '서울 강남구 테헤란로 123',
        phone: '02-111-1111',
        hours: '09:00 ~ 18:00',
        city: '서울',
        district: '강남구'
    },
    {
        id: 7,
        name: '행복동물쉼터',
        location: '경기도 수원시 영통구 월드컵로 45',
        phone: '031-222-2222',
        hours: '10:00 ~ 19:00',
        city: '경기도',
        district: '수원시'
    },
    {
        id: 8,
        name: '희망보호소',
        location: '대구 달서구 와룡로 234',
        phone: '053-333-3333',
        hours: '08:00 ~ 17:00',
        city: '대구',
        district: '달서구'
    }
];

// 페이지네이션 설정
let currentPage = 1;
const itemsPerPage = 5;
let filteredShelters = [...shelters];

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    renderTable();
    renderPagination();
    
    // 검색 버튼 이벤트
    document.getElementById('searchBtn').addEventListener('click', handleSearch);
    
    // 로그아웃 버튼 이벤트
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
});

// 검색 처리
function handleSearch() {
    const city = document.getElementById('citySelect').value;
    const district = document.getElementById('districtSelect').value;
    
    filteredShelters = shelters.filter(shelter => {
        const cityMatch = !city || shelter.city === city;
        const districtMatch = !district || shelter.district === district;
        return cityMatch && districtMatch;
    });
    
    currentPage = 1;
    renderTable();
    renderPagination();
}

// 테이블 렌더링
function renderTable() {
    const tableBody = document.getElementById('shelterTableBody');
    tableBody.innerHTML = '';
    
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const currentShelters = filteredShelters.slice(start, end);
    
    if (currentShelters.length === 0) {
        const row = tableBody.insertRow();
        const cell = row.insertCell();
        cell.colSpan = 5;
        cell.textContent = '검색 결과가 없습니다.';
        cell.style.textAlign = 'center';
        cell.style.padding = '30px';
        cell.style.color = '#999';
        return;
    }
    
    currentShelters.forEach((shelter, index) => {
        const row = tableBody.insertRow();
        
        const numberCell = row.insertCell();
        // DESC 순서: 전체 개수에서 현재 인덱스를 빼서 역순으로 표시
        numberCell.textContent = filteredShelters.length - (start + index);
        
        const nameCell = row.insertCell();
        nameCell.textContent = shelter.name;
        
        const locationCell = row.insertCell();
        locationCell.textContent = shelter.location;
        
        const phoneCell = row.insertCell();
        phoneCell.textContent = shelter.phone;
        
        const hoursCell = row.insertCell();
        hoursCell.textContent = shelter.hours;
    });
}

// 페이지네이션 렌더링
function renderPagination() {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    const totalPages = Math.ceil(filteredShelters.length / itemsPerPage);
    
    if (totalPages <= 1) return;
    
    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'page-btn';
        pageBtn.textContent = i;
        
        if (i === currentPage) {
            pageBtn.classList.add('active');
        }
        
        pageBtn.addEventListener('click', () => {
            currentPage = i;
            renderTable();
            renderPagination();
        });
        
        pagination.appendChild(pageBtn);
    }
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