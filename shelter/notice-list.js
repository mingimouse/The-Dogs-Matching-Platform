document.addEventListener("DOMContentLoaded", () => {
    load_notices(1);
});

/* ------- 더미 데이터 ------- */
const notice_data = [
    { id: 5, name: "장군 (불독)",       adopt: false, notice: false, protect: true,  review_icon: "../img/review.png" },
    { id: 4, name: "말콩 (리트리버)",   adopt: false, notice: true,  protect: false, review_icon: "../img/review.png" },
    { id: 3, name: "까망 (푸들)",       adopt: false, notice: true,  protect: false, review_icon: "../img/review.png" },
    { id: 2, name: "쪼꼬 (포메라니안)", adopt: true,  notice: false, protect: false, review_icon: "../img/review.png" },
    { id: 1, name: "구름 (말티즈)",     adopt: true,  notice: false, protect: false, review_icon: "../img/review.png" }
];

function get_page_data(page, size = 5) {
    const total_pages = Math.ceil(notice_data.length / size);
    const start = (page - 1) * size;
    return {
        page,
        total_pages,
        rows: notice_data.slice(start, start + size)
    };
}

function load_notices(page) {
    const data = get_page_data(page);
    render_rows(data.rows);
    render_pagination(data.page, data.total_pages);
}

function render_rows(rows) {
    const body = document.getElementById("noticeBody");
    body.innerHTML = "";

    rows.forEach(row => {
        const tr = document.createElement("tr");

        tr.innerHTML = `
            <td>${row.id}</td>
            <td>${row.name}</td>
            <td><div class="circle ${row.adopt   ? "checked" : ""}" data-type="adopt"></div></td>
            <td><div class="circle ${row.notice  ? "checked" : ""}" data-type="notice"></div></td>
            <td><div class="circle ${row.protect ? "checked" : ""}" data-type="protect"></div></td>
            <td>
                <a href="notice-detail.html">
                    <img src="../img/review.png" class="review-icon" alt="심사 아이콘">
                </a>
            </td>
        `;

        // 한 행당 상태 하나만 선택
        const circles = tr.querySelectorAll(".circle");
        circles.forEach(circle => {
            circle.addEventListener("click", () => {
                if (circle.classList.contains("checked")) return;
                circles.forEach(c => c.classList.remove("checked"));
                circle.classList.add("checked");
            });
        });

        body.appendChild(tr);
    });
}


/* ===== pagination (dog-list랑 동일) ===== */
function render_pagination(current, total) {
    const pagination = document.querySelector(".pagination");
    pagination.innerHTML = "";

    for (let i = 1; i <= total; i++) {
        const btn = document.createElement("button");
        btn.classList.add("page-btn");
        btn.textContent = i;

        if (i === current) btn.classList.add("active");

        btn.addEventListener("click", () => load_notices(i));

        pagination.appendChild(btn);
    }
}
