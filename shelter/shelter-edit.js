document.addEventListener("DOMContentLoaded", () => {
  // --- 1) 시/도 / 구/군 드롭다운 ---
  const districts = {
    "서울특별시": [
      "강남구", "강동구", "강북구", "강서구",
      "관악구", "광진구", "구로구", "금천구",
      "노원구", "도봉구", "동대문구", "동작구",
      "마포구", "서대문구", "서초구", "성동구",
      "성북구", "송파구", "양천구", "영등포구",
      "용산구", "은평구", "종로구", "중구", "중랑구"
    ],
    "경기도 수원시": ["장안구", "권선구", "팔달구", "영통구"],
    "경기도 고양시": ["덕양구", "일산동구", "일산서구"],
    "대구광역시": ["남구", "달서구", "달성군", "동구", "북구", "서구", "수성구", "중구"]
  };

  const citySelect = document.getElementById("addr_city");
  const districtSelect = document.getElementById("addr_district");

  if (citySelect && districtSelect) {
    citySelect.innerHTML = '<option value="" disabled selected>시 / 도</option>';
    Object.keys(districts).forEach((city) => {
      citySelect.innerHTML += `<option value="${city}">${city}</option>`;
    });

    districtSelect.innerHTML = '<option value="" disabled selected>구 / 군</option>';

    citySelect.addEventListener("change", () => {
      const selectedCity = citySelect.value;
      const guList = districts[selectedCity] || [];

      districtSelect.innerHTML = '<option value="" disabled selected>구 / 군</option>';
      guList.forEach((gu) => {
        districtSelect.innerHTML += `<option value="${gu}">${gu}</option>`;
      });
    });
  }

  // --- 2) DB에서 보호소 정보 가져오기 ---
  loadShelterInfo();

  // --- 3) 폼 제출 (수정) ---
  const form = document.getElementById("shelterEditForm");
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      console.log("수정할 보호소 데이터:", data);

      try {
        const res = await fetch("/api/shelter/me", {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data)
        });

        if (res.ok) {
          alert("수정이 완료되었습니다.");
          location.href = "shelter-info.html";   // ✅ 수정 후 이동
        } else {
          alert("수정에 실패했습니다.");
        }
      } catch (err) {
        console.error(err);
        alert("수정 중 오류가 발생했습니다.");
      }
    });
  }

  // --- 4) 탈퇴 버튼 ---
  const deleteBtn = document.getElementById("btnDelete");
  if (deleteBtn) {
    deleteBtn.addEventListener("click", async () => {
      if (!confirm("정말 탈퇴하시겠습니까?")) return;

      try {
        // 실제 탈퇴 API가 있으면 여기서 호출
        // await fetch("/api/shelter/me", { method: "DELETE" });

        alert("탈퇴가 완료되었습니다.");
        location.href = "../index.html";   // ✅ 탈퇴 후 메인으로
      } catch (err) {
        console.error(err);
        alert("탈퇴 처리 중 오류가 발생했습니다.");
      }
    });
  }
});

// 보호소 정보 로딩
async function loadShelterInfo() {
  const usernameInput = document.getElementById("username");
  const shelterNameInput = document.getElementById("shelter_name");
  const contactInput = document.getElementById("contact");
  const citySelect = document.getElementById("addr_city");
  const districtSelect = document.getElementById("addr_district");
  const detailInput = document.getElementById("location_detail");
  const startTimeInput = document.getElementById("start_time");
  const endTimeInput = document.getElementById("end_time");

  try {
    const res = await fetch("/api/shelter/me");
    if (!res.ok) throw new Error();

    const shelter = await res.json();

    if (usernameInput) usernameInput.value = shelter.username || "";
    if (shelterNameInput) shelterNameInput.value = shelter.shelter_name || "";
    if (contactInput) contactInput.value = shelter.contact || "";

    if (citySelect && districtSelect && shelter.addr_city) {
      citySelect.value = shelter.addr_city;
      const event = new Event("change");
      citySelect.dispatchEvent(event);

      if (shelter.addr_district) {
        districtSelect.value = shelter.addr_district;
      }
    }

    if (detailInput) detailInput.value = shelter.location_detail || "";
    if (startTimeInput) startTimeInput.value = shelter.start_time || "";
    if (endTimeInput) endTimeInput.value = shelter.end_time || "";
  } catch (e) {
    // DB 연동 전 더미데이터
    if (usernameInput) usernameInput.value = "madlife0120";
    if (shelterNameInput) shelterNameInput.value = "밍기보호센터";
  }
}

// 보호소 정보 로딩 (DB 연동 시 여기만 실제 컬럼명에 맞게 수정)
async function loadShelterInfo() {
  const usernameInput    = document.getElementById("username");        // 아이디 (표시만)
  const shelterNameInput = document.getElementById("shelter_name");    // 보호소명 (표시만)
  const contactInput     = document.getElementById("contact");         // 연락처
  const citySelect       = document.getElementById("addr_city");       // 시/도
  const districtSelect   = document.getElementById("addr_district");   // 구/군
  const detailInput      = document.getElementById("location_detail"); // 상세주소
  const startTimeInput   = document.getElementById("start_time");      // 영업 시작
  const endTimeInput     = document.getElementById("end_time");        // 영업 종료

  try {
    // ✅ 실제로는 여기서 로그인된 보호소 한 건을 조회하는 API 사용
    const res = await fetch("/api/shelter/me");
    if (!res.ok) throw new Error();

    const shelter = await res.json();

    // 1) 아이디 / 보호소명
    if (usernameInput)    usernameInput.value    = shelter.username      || "";
    if (shelterNameInput) shelterNameInput.value = shelter.shelter_name  || "";

    // 2) 연락처
    if (contactInput)     contactInput.value     = shelter.contact       || "";

    // 3) 시/도 + 구/군
    if (citySelect && districtSelect && shelter.addr_city) {
      // 시/도 선택
      citySelect.value = shelter.addr_city;

      // 시/도 바뀐 걸 강제로 트리거해서 구/군 옵션 채우기
      const event = new Event("change");
      citySelect.dispatchEvent(event);

      // 구/군 선택
      if (shelter.addr_district) {
        districtSelect.value = shelter.addr_district;
      }
    }

    // 4) 상세 주소
    if (detailInput)      detailInput.value      = shelter.location_detail || "";

    // 5) 영업시간 (time input 은 "HH:MM" 형식이어야 함)
    if (startTimeInput)   startTimeInput.value   = shelter.start_time   || "";
    if (endTimeInput)     endTimeInput.value     = shelter.end_time     || "";

  } catch (e) {
    console.error("shelter 정보 불러오기 실패:", e);

    // 🔹 DB 연동 전 테스트용 더미 데이터
    if (usernameInput)    usernameInput.value    = "madlife0120";
    if (shelterNameInput) shelterNameInput.value = "밍기보호센터";
    if (contactInput)     contactInput.value     = "010-1234-5678";

    if (citySelect && districtSelect) {
      citySelect.value = "대구광역시";
      const event = new Event("change");
      citySelect.dispatchEvent(event);
      districtSelect.value = "북구";
    }

    if (detailInput)    detailInput.value    = "동천로 OOO-OO";
    if (startTimeInput) startTimeInput.value = "09:00";
    if (endTimeInput)   endTimeInput.value   = "18:00";
  }
}
