// shelter-info.js

document.addEventListener("DOMContentLoaded", async () => {
  const nameEl   = document.getElementById("sidebarShelterName");
  const phoneEl  = document.getElementById("shelterPhone");
  const addrEl   = document.getElementById("shelterAddress");
  const timeEl   = document.getElementById("shelterTime");

  try {
    // ✔ 실제 API 엔드포인트로 수정하면 됨
    const res = await fetch("/api/shelter/me");

    if (!res.ok) {
      throw new Error("Shelter API 요청 실패");
    }

    const data = await res.json();

    // ✔ 보호소명
    if (nameEl && data.shelterName) {
      nameEl.textContent = data.shelterName;
    }

    // ✔ 연락처
    if (phoneEl && data.phone) {
      phoneEl.textContent = data.phone;
    }

    // ✔ 주소 (addr1 + addr2 두 줄)
    if (addrEl && (data.addr1 || data.addr2)) {
      const line1 = data.addr1 ?? "";
      const line2 = data.addr2 ?? "";
      addrEl.innerHTML = `${line1}<br>${line2}`;
    }

    // ✔ 영업시간 (openTime ~ closeTime)
    if (timeEl && (data.openTime || data.closeTime)) {
      const open = data.openTime ?? "";
      const close = data.closeTime ?? "";
      timeEl.textContent = `${open} ~ ${close}`;
    }

  } catch (err) {
    console.error("보호소 정보 로딩 오류:", err);

    // 실패 시 fallback 메시지 주고 싶다면
    // timeEl.textContent = "정보 불러오기 실패";
  }
});
