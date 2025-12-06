document.addEventListener("DOMContentLoaded", () => {
    load_dog_list(1);

    const addBtn = document.querySelector(".add-btn");
    if (addBtn) {
        addBtn.addEventListener("click", () => {
            location.href = "dog-detail.html";
        });
    }
});

const dummy_dogs = [
    {
        dog_id: 1,
        dog_name: "구름",
        dog_breed: "말티즈",
        dog_age: 11,
        dog_gender: "암컷",
        dog_color: "흰색",
        dog_weight: "2.2kg",
        dog_status: "입양 완료",
        dog_img: "../dog-img/bichon-black.png",
        health_status: "입력"
    },
    {
        dog_id: 2,
        dog_name: "쪼꼬",
        dog_breed: "포메라니안",
        dog_age: 9,
        dog_gender: "암컷",
        dog_color: "갈색",
        dog_weight: "4.5kg",
        dog_status: "입양 완료",
        dog_img: "../dog-img/bichon-brown.png",
        health_status: "입력"
    },
    {
        dog_id: 3,
        dog_name: "까망",
        dog_breed: "푸들",
        dog_age: 4,
        dog_gender: "수컷",
        dog_color: "검정색",
        dog_weight: "3.5kg",
        dog_status: "공고 등록",
        dog_img: "../dog-img/bichon-gray.png",
        health_status: "미입력"
    },
    {
        dog_id: 4,
        dog_name: "두부",
        dog_breed: "비숑",
        dog_age: 2,
        dog_gender: "수컷",
        dog_color: "흰색",
        dog_weight: "3.1kg",
        dog_status: "보호 중",
        dog_img: "../dog-img/bulldog-black.png",
        health_status: "입력"
    },
    {
        dog_id: 5,
        dog_name: "몽이",
        dog_breed: "시츄",
        dog_age: 7,
        dog_gender: "암컷",
        dog_color: "베이지",
        dog_weight: "5.0kg",
        dog_status: "공고 등록",
        dog_img: "../dog-img/bulldog-white.png",
        health_status: "미입력"
    },
    {
        dog_id: 6,
        dog_name: "콩이",
        dog_breed: "보더콜리",
        dog_age: 3,
        dog_gender: "수컷",
        dog_color: "검정/흰색",
        dog_weight: "6.3kg",
        dog_status: "입양 완료",
        dog_img: "../dog-img/pomeranian-brown.png",
        health_status: "입력"
    }
];

function get_page_data(page, page_size = 5) {
    const total_items = dummy_dogs.length;
    const total_pages = Math.ceil(total_items / page_size);

    const start = (page - 1) * page_size;
    const end = start + page_size;

    const dogs = dummy_dogs.slice(start, end);

    return {
        page,
        total_pages,
        dogs
    };
}

function load_dog_list(page) {
    const data = get_page_data(page);
    render_dog_cards(data.dogs);
    render_pagination(data.page, data.total_pages);
}

function render_dog_cards(dogs) {
    const list = document.querySelector(".dog-list");
    list.innerHTML = "";

    dogs.forEach(dog => {
        const card = `
        <article class="dog-card" data-dog_id="${dog.dog_id}">
            <div class="dog-image-wrapper">
                <img src="${dog.dog_img}" alt="${dog.dog_name}" class="dog_img">
            </div>

            <div class="status-bar ${get_status_class(dog.dog_status)}">
                ${dog.dog_status}
            </div>

            <div class="dog-info">
                <div class="info-row"><span class="info-label">이름</span><span class="info-value">${dog.dog_name}</span></div>
                <div class="info-row"><span class="info-label">품종</span><span class="info-value">${dog.dog_breed}</span></div>
                <div class="info-row"><span class="info-label">나이</span><span class="info-value">${dog.dog_age}살</span></div>
                <div class="info-row"><span class="info-label">성별</span><span class="info-value">${dog.dog_gender}</span></div>
                <div class="info-row"><span class="info-label">색</span><span class="info-value">${dog.dog_color}</span></div>
                <div class="info-row"><span class="info-label">몸무게</span><span class="info-value">${dog.dog_weight}</span></div>
            </div>

            <button class="health-btn ${dog.health_status === '입력' ? 'health-complete' : 'health-missing'}"
                onclick="location.href='dog-report.html'"> ${dog.health_status === "입력" ? "건강정보 입력" : "건강정보 미입력"}
            </button>

            <div class="card-actions">
                <button class="small-btn edit-btn" onclick="location.href='dog-detail.html'">수정</button>
                <button class="small-btn delete-btn">삭제</button>
            </div>
        </article>
        `;
        list.insertAdjacentHTML("beforeend", card);
    });
}

function get_status_class(status) {
    if (status === "입양 완료") return "status-complete";
    if (status === "공고 등록") return "status-notice";
    if (status === "보호 중") return "status-protect";
    return "";
}

function render_pagination(current, total_pages) {
    const pagination = document.querySelector(".pagination");
    pagination.innerHTML = "";

    for (let i = 1; i <= total_pages; i++) {
        const btn = document.createElement("button");
        btn.classList.add("page-btn");
        btn.textContent = i;

        if (i === current) btn.classList.add("active");

        btn.addEventListener("click", () => load_dog_list(i));

        pagination.appendChild(btn);
    }
}
