document.addEventListener("DOMContentLoaded", async () => {

    // Lucide 아이콘 렌더링
    if (window.lucide) {
        lucide.createIcons();
    } else {
        console.error("Lucide가 로드되지 않았습니다.");
    }
});