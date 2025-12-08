// shelter-list.js
// - 시/도 변경 시 구/군 옵션만 JS로 업데이트

document.addEventListener("DOMContentLoaded", () => {

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
    "대구광역시":    ["남구", "달서구", "달성군", "동구", "북구", "서구", "수성구", "중구"]
  };

  const citySelect     = document.getElementById("citySelect");
  const districtSelect = document.getElementById("districtSelect");

  // 시/도 선택 → 구/군 목록 채우기
  citySelect.addEventListener("change", () => {
    const selectedCity = citySelect.value;

    districtSelect.innerHTML = "";
    
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "전체";
    districtSelect.appendChild(defaultOption);

    if (districts[selectedCity]) {
      districts[selectedCity].forEach(gu => {
        const opt = document.createElement("option");
        opt.value = gu;
        opt.textContent = gu;
        districtSelect.appendChild(opt);
      });
    }
  });

});
