// 입양 심사 결과 JavaScript

// 예시 입양 신청 데이터 (5개)
const adoptionResults = [
    {
        id: 1,
        shelterName: '포옹하우스',
        dogName: '장군',
        breed: '불독',
        applyDate: '2025.01.17 14:00',
        status: 'rejected' // 거절
    },
    {
        id: 2,
        shelterName: '희망의 집',
        dogName: '말콩',
        breed: '리트리버',
        applyDate: '2025.03.29 17:00',
        status: 'rejected' // 거절
    },
    {
        id: 3,
        shelterName: '러브테일',
        dogName: '까망',
        breed: '푸들',
        applyDate: '2025.06.12 09:00',
        status: 'rejected' // 거절
    },
    {
        id: 4,
        shelterName: '별빛쉼터',
        dogName: '쪼꼬',
        breed: '포메라니안',
        applyDate: '2025.09.05 11:00',
        status: 'pending' // 심사 중
    },
    {
        id: 5,
        shelterName: '해피포우스',
        dogName: '구름',
        breed: '말티즈',
        applyDate: '2025.12.04 15:00',
        status: 'approved' // 승인
    }
];

// 페이지네이션 설정
let currentPage = 1;
const itemsPerPage = 5; // 5개씩 표시

// 심사 결과 한글 텍스트
const statusText = {
    approved: '승인',
    pending: '심사 중',
    rejected: '거절'
};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    renderTable();
    renderPagination();
    
    // 완료 버튼 이벤트
    document.getElementById('completeBtn').addEventListener('click', handleComplete);
    
    // 로그아웃 버튼 이벤트
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
});

// 테이블 렌더링
function renderTable() {
    const tableBody = document.getElementById('resultTableBody');
    tableBody.innerHTML = '';
    
    // 배열을 역순으로 정렬 (최신 순)
    const reversedResults = [...adoptionResults].reverse();
    
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const currentResults = reversedResults.slice(start, end);
    
    if (currentResults.length === 0) {
        const row = tableBody.insertRow();
        const cell = row.insertCell();
        cell.colSpan = 6;
        cell.textContent = '입양 신청 내역이 없습니다.';
        cell.style.textAlign = 'center';
        cell.style.padding = '30px';
        cell.style.color = '#999';
        return;
    }
    
    currentResults.forEach((result, index) => {
        const row = tableBody.insertRow();
        
        // 목록 번호 (DESC 순서)
        const numberCell = row.insertCell();
        numberCell.textContent = adoptionResults.length - (start + index);
        
        // 보호소 이름
        const shelterCell = row.insertCell();
        shelterCell.textContent = result.shelterName;
        
        // 강아지 이름
        const dogNameCell = row.insertCell();
        dogNameCell.textContent = result.dogName;
        
        // 품종
        const breedCell = row.insertCell();
        breedCell.textContent = result.breed;
        
        // 신청일
        const dateCell = row.insertCell();
        dateCell.textContent = result.applyDate;
        
        // 심사 결과 (색상 적용)
        const statusCell = row.insertCell();
        const statusSpan = document.createElement('span');
        statusSpan.className = `status ${result.status}`;
        statusSpan.textContent = statusText[result.status];
        statusCell.appendChild(statusSpan);
    });
}

// 페이지네이션 렌더링
function renderPagination() {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    const totalPages = Math.ceil(adoptionResults.length / itemsPerPage);
    
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
            
            // 페이지 변경 시 테이블 최상단으로 스크롤
            document.querySelector('.table-container').scrollTop = 0;
        });
        
        pagination.appendChild(pageBtn);
    }
}

// 완료 버튼 처리
function handleComplete() {
    const confirmed = confirm('완료 하시겠습니까?');
    
    if (confirmed) {
        console.log('완료 처리');
        alert('완료되었습니다.');
        // 메인 페이지로 이동 또는 다른 페이지로 이동
        // window.location.href = '../index.html';
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