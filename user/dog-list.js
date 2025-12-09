document.addEventListener("DOMContentLoaded", () => {
    loadDogs();
    document.getElementById("searchBtn").addEventListener("click", () => loadDogs(1));
});

async function loadDogs(page = 1) {
    const breed  = document.getElementById("breedSelect").value;
    const color  = document.getElementById("colorSelect").value;
    const gender = document.getElementById("genderSelect").value;

    const params = new URLSearchParams({
        mode:  "list",
        page,          // ✅ 여기서 page 잘 나가고 있고
        breed,
        color,
        gender
    });

    const res  = await fetch("dog_list.php?" + params.toString());
    const data = await res.json();

    if (data.error) {
        alert("조회 오류: " + data.error);
        return;
    }

    renderDogCards(data.dogs);

    const limit = data.limit || 8;   // PHP에서 안 보내면 기본 8
    renderPagination(data.total, page, limit);
}

function renderDogCards(dogs) {
    const grid = document.getElementById("dogGrid");
    grid.innerHTML = "";

    if (!dogs || dogs.length === 0) {
        grid.innerHTML = "<p>조회된 유기견이 없습니다.</p>";
        return;
    }

    dogs.forEach(dog => {
        const card = document.createElement("div");
        card.classList.add("dog-card");

        // gender: 'M'/'F' → 한글
        let genderText = "";
        if (dog.GENDER === "M") genderText = "수컷";
        else if (dog.GENDER === "F") genderText = "암컷";

        card.innerHTML = `
            <div class="dog-image-wrapper">
                <img src="${dog.IMAGE_URL || '../img/no-image.png'}" class="dog-img" alt="강아지 사진">
            </div>

            <div class="dog-info">
                <h3 class="dog-info">${dog.NAME}</h3>
                <p class="dog-info">${dog.BREED} · ${genderText} · ${dog.AGE}세</p>
                <button class="detail-btn"
                        onclick="location.href='dog_detail.php?dog_id=${dog.DOG_ID}'">
                    상세보기
                </button>
            </div>
        `;
        grid.appendChild(card);
    });
}

function renderPagination(total, currentPage, limit) {
    const container = document.getElementById("pagination");
    container.innerHTML = "";

    const totalPages = Math.ceil(total / limit);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.classList.add("page-btn");
        if (i === currentPage) btn.classList.add("active");

        btn.textContent = i;
        btn.onclick = () => loadDogs(i);

        container.appendChild(btn);
    }
}
