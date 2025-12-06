/* ===========================
   2) 페이지당 보여줄 개수 설정
   =========================== */
const ITEMS_PER_PAGE = 4; // ← 4개씩 끊기!



/* ===========================
   3) 페이지 로딩 시 실행
   =========================== */
document.addEventListener("DOMContentLoaded", () => {
    loadPage(1);
});



/* ===========================
   4) 페이지 렌더링 함수
   =========================== */
function loadPage(page) {
    const listContainer = document.querySelector(".dog-list");
    listContainer.innerHTML = ""; // 초기화

    const start = (page - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;

    const currentItems = dogData.slice(start, end);

    currentItems.forEach(dog => {
        listContainer.innerHTML += generateDogCard(dog);
    });

    renderPagination(page);
}



/* ===========================
   5) 카드 HTML 생성 함수
   =========================== */
function generateDogCard(dog) {
    return `
    <div class="dog-card">

        <div class="dog-image-wrapper">
            <img src="${dog.img}" class="dog_img">
        </div>

        <div class="status-bar ${dog.statusClass}">
            ${dog.statusText}
        </div>

        <div class="dog-info">
            <div class="info-row">
                <div class="info-label">이름</div>
                <div class="info-value">${dog.name}</div>
            </div>
            <div class="info-row">
                <div class="info-label">나이</div>
                <div class="info-value">${dog.age}</div>
            </div>
            <div class="info-row">
                <div class="info-label">품종</div>
                <div class="info-value">${dog.breed}</div>
            </div>
        </div>

        <button 
        class="health-btn ${dog.health_status === '입력' ? 'health-complete' : 'health-missing'}"
        onclick="location.href='dog-report.html'"
        >
        ${dog.health_status === '입력' ? '건강정보 입력' : '건강정보 미입력'}
        </button>


        <div class="card-actions">
            <button class="small-btn edit-btn" onclick="location.href='dog-detail.html'">수정</button>
            <button class="small-btn delete-btn">삭제</button>
        </div>

    </div>
    `;
}



/* ===========================
   6) 페이지네이션 생성 함수
   =========================== */
function renderPagination(currentPage) {
    const totalItems = dogData.length;
    const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE);

    const pagination = document.querySelector(".pagination");
    pagination.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
        pagination.innerHTML += `
            <button class="page-btn ${i === currentPage ? 'active' : ''}"
                    onclick="loadPage(${i})">
                ${i}
            </button>
        `;
    }
}
