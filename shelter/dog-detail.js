document.addEventListener("DOMContentLoaded", () => {
    const imageInput   = document.getElementById("dogImageInput");
    const uploadBtn    = document.getElementById("btnUploadImage");
    const previewImg   = document.getElementById("dogPreview");
    const healthBtn    = document.getElementById("btnEditHealth");
    const completeBtn  = document.getElementById("btnComplete");
    const form         = document.getElementById("dogDetailForm");

    uploadBtn.addEventListener("click", () => {
        imageInput.click();
    });

    imageInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            previewImg.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // 🔥 완료 버튼 클릭 → DB 저장 + 목록 페이지 이동
    completeBtn.addEventListener("click", async () => {
        const formData = new FormData(form);
        const dogData = Object.fromEntries(formData.entries());

        console.log("등록/수정할 유기견 데이터:", dogData);

        const response = await fetch("/api/dogs", {
            method: "POST",
            headers: { 
                "Content-Type": "application/json"
            },
            body: JSON.stringify(dogData)
        });

        if (response.ok) {
            alert("저장 완료!");
            location.href = "dog_list.html";
        } else {
            alert("저장 실패!");
            location.href = "dog-list.html"; //db연동 후에는 지우기!
            
        }
    });
});


document.addEventListener("DOMContentLoaded", () => {
    const genderButtons = document.querySelectorAll(".gender-box");
    const genderInput = document.getElementById("dog_gender");

    genderButtons.forEach(btn => {
        btn.addEventListener("click", () => {

            genderButtons.forEach(b => b.classList.remove("selected"));
            btn.classList.add("selected");

            genderInput.value = btn.dataset.value;
        });
    });
});
