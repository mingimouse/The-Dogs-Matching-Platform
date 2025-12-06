document.addEventListener("DOMContentLoaded", () => {

    const completeBtn = document.getElementById("completeBtn");

    completeBtn.addEventListener("click", async () => {

        //(1) 심사 결과 수집 ======================
        const selectedRow = document.querySelector(".selected-row");
        const applicantId = selectedRow.dataset.applicantId;

        // 선택된 라디오 버튼
        const selectedRadio = selectedRow.querySelector("input[type='radio']:checked");
        if (!selectedRadio) {
            alert("심사 결과를 선택하세요.");
            return;
        }

        const result = selectedRadio.value;
        // ===============================================


        //(2) 서버(DB) 저장 요청 ======================
        try {
            const response = await fetch("/api/notice/saveResult", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    applicant_id: applicantId,
                    result: result
                }),
            });

            if (!response.ok) {
                alert("저장 실패! 서버 오류가 발생했습니다.");
                return;
            }

            // 성공 메시지
            alert("심사 결과 저장 완료!");

            //(3) 공고 목록으로 이동 ====================
            location.href = "notice-list.html";
            // ================================================

        } 
        catch (error) {
            console.error("저장 중 오류:", error);
            alert("저장 중 문제가 발생했습니다.");
        }
    });
});
