document.addEventListener("DOMContentLoaded", () => {
  const yearSelect = document.getElementById("birth_year");
  const monthSelect = document.getElementById("birth_month");
  const daySelect = document.getElementById("birth_day");

  // 일 기본값
  if (daySelect) {
    daySelect.innerHTML = '<option value="" disabled selected>일</option>';
  }

  const currentYear = new Date().getFullYear();

  // 연도: 1950 ~ 현재
  yearSelect.innerHTML = '<option value="">연도</option>';
  for (let y = currentYear; y >= 1950; y--) {
    yearSelect.innerHTML += `<option value="${y}">${y}년</option>`;
  }

  // 월: 1 ~ 12
  monthSelect.innerHTML = '<option value="">월</option>';
  for (let m = 1; m <= 12; m++) {
    monthSelect.innerHTML += `<option value="${m}">${m}월</option>`;
  }

  // 연/월에 따라 일수 동적 변경
  function updateDays() {
    const year = parseInt(yearSelect.value, 10);
    const month = parseInt(monthSelect.value, 10);

    if (!year || !month) {
      daySelect.innerHTML = '<option value="" disabled selected>일</option>';
      return;
    }

    const daysInMonth = new Date(year, month, 0).getDate();
    daySelect.innerHTML = '<option value="" disabled selected>일</option>';

    for (let d = 1; d <= daysInMonth; d++) {
      daySelect.innerHTML += `<option value="${d}">${d}일</option>`;
    }
  }

  yearSelect.addEventListener("change", updateDays);
  monthSelect.addEventListener("change", updateDays);

  // 거주지 데이터
  const districts = {
    "서울특별시": [
      "강남구", "강동구", "강북구", "강서구",
      "관악구", "광진구", "구로구", "금천구",
      "노원구", "도봉구", "동대문구", "동작구",
      "마포구", "서대문구", "서초구", "성동구",
      "성북구", "송파구", "양천구", "영등포구",
      "용산구", "은평구", "종로구", "중구", "중랑구"
    ],
    "경기도 수원시": [
      "장안구", "권선구", "팔달구", "영통구"
    ],
    "경기도 고양시": [
      "덕양구", "일산동구", "일산서구"
    ],
    "대구광역시": [
      "남구", "달서구", "달성군", "동구",
      "북구", "서구", "수성구", "중구"
    ]
  };

  const citySelect = document.getElementById("addr_city");
  const districtSelect = document.getElementById("addr_district");

  // 시/도 옵션 채우기
  citySelect.innerHTML = '<option value="" disabled selected>시 / 도</option>';
  Object.keys(districts).forEach((city) => {
    citySelect.innerHTML += `<option value="${city}">${city}</option>`;
  });

  // 구/군 기본값
  districtSelect.innerHTML = '<option value="" disabled selected>구 / 군</option>';

  // 시/도 선택 시 구/군 목록 변경
  citySelect.addEventListener("change", () => {
    const selectedCity = citySelect.value;
    const guList = districts[selectedCity];

    districtSelect.innerHTML = '<option value="" disabled selected>구 / 군</option>';

    guList.forEach((gu) => {
      districtSelect.innerHTML += `<option value="${gu}">${gu}</option>`;
    });
  });
});
