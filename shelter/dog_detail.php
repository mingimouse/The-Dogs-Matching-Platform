<?php
// dog_detail.php : 강아지 등록 / 수정 화면

session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['shelter_id'])) {
    echo "<script>alert('로그인 후 이용해주세요.'); location.href='../login/shelter_login.html';</script>";
    exit;
}

$shelter_id   = $_SESSION['shelter_id'];
$shelter_name = $_SESSION['shelter_name'] ?? 'OOO 보호소';

// DB 연결
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die('DB 접속 실패: ' . htmlspecialchars($e['message']));
}

// 수정 모드인지 확인
$dog_id   = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
$is_edit  = $dog_id > 0;

$dog_name   = '';
$dog_breed  = '';
$dog_age    = '';
$dog_gender_kor = ''; // '수컷' / '암컷'
$dog_color  = '';
$dog_weight = '';
$image_url  = '../img/bichon.png';  // 기본 이미지

if ($is_edit) {
    $sql = "
        SELECT dog_id, name, breed, age, gender, color, weight, image_url
        FROM DOG
        WHERE dog_id = :dog_id
          AND shelter_id = :sid
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':dog_id', $dog_id);
    oci_bind_by_name($stmt, ':sid', $shelter_id);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$row) {
        echo "<script>alert('해당 유기견 정보를 찾을 수 없습니다.'); location.href='dog_list.php';</script>";
        exit;
    }

    $dog_name  = $row['NAME'];
    $dog_breed = $row['BREED'];
    $dog_age   = $row['AGE'];            // 숫자 그대로 입력칸에
    $dog_color = $row['COLOR'];
    $dog_weight = $row['WEIGHT'];        // 숫자 그대로
    $image_url  = $row['IMAGE_URL'] ?: $image_url;

    // 성별 매핑
    if ($row['GENDER'] === 'M') $dog_gender_kor = '수컷';
    elseif ($row['GENDER'] === 'F') $dog_gender_kor = '암컷';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>유기견 상세 정보</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dog-detail.css">
</head>
<body>

<div class="page-container">

    <aside class="sidebar">
        <div class="sidebar-logo-box">
            <a href="shelter_info.php">
                <img src="../img/shelter.png" class="sidebar-logo" alt="로고">
            </a>
        </div>

        <div class="sidebar-shelter-name" id="sidebarShelterName">
            <?php echo htmlspecialchars($shelter_name); ?>
        </div>

        <nav class="sidebar-menu">
            <button class="menu-btn" onclick="location.href='shelter_edit.php'">회원정보 수정</button>
            <button class="menu-btn active" onclick="location.href='dog_list.php'">유기견 관리</button>
            <button class="menu-btn" onclick="location.href='notice_list.php'">공고 관리</button>
        </nav>

        <form class="logout-btn" action="../login/logout.php" method="post">
            <button type="submit" class="logout-font">로그아웃</button>
        </form>
    </aside>

    <main class="main-area">

        <!-- 등록/수정 폼 -->
        <form class="dog-detail-card" id="dogDetailForm"
              action="dog_save.php" method="post" enctype="multipart/form-data">

            <input type="hidden" name="mode" value="<?php echo $is_edit ? 'update' : 'insert'; ?>">
            <input type="hidden" name="dog_id" value="<?php echo $dog_id; ?>">
            <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($image_url); ?>">
            <input type="hidden" id="dog_gender" name="dog_gender"
                   value="<?php echo htmlspecialchars($dog_gender_kor); ?>">

            <section class="dog-detail-left">
                <div class="dog-detail-image-wrapper">
                    <img src="<?php echo htmlspecialchars($image_url); ?>"
                         alt="강아지 사진" id="dogPreview">
                </div>

                <input type="file" id="dogImageInput" name="dog_image" accept="image/*" hidden>

                <button type="button" class="btn-yellow" id="btnUploadImage">
                    사진 등록
                </button>
                <a href="dog_report.php<?php echo $is_edit ? '?dog_id='.$dog_id : ''; ?>">
                    <button type="button" class="btn-navy" id="btnEditHealth">
                        건강 정보 수정
                    </button>
                </a>
            </section>

            <section class="dog-detail-right">
                <div class="field-row">
                    <label for="dog_name">이름</label>
                    <input type="text" id="dog_name" name="dog_name"
                           value="<?php echo htmlspecialchars($dog_name); ?>"
                           placeholder="이름을 입력하세요">
                </div>

                <div class="field-row">
                    <label for="dog_breed">품종</label>
                    <input type="text" id="dog_breed" name="dog_breed"
                           value="<?php echo htmlspecialchars($dog_breed); ?>"
                           placeholder="예시: 포메라니안">
                </div>

                <div class="field-row">
                    <label for="dog_age">나이</label>
                    <input type="text" id="dog_age" name="dog_age"
                           value="<?php echo htmlspecialchars($dog_age); ?>"
                           placeholder="예시: 5 (숫자만 입력하세요)">
                </div>

                <div class="field-row gender-row">
                    <label>성별</label>

                    <div class="gender-box-group full-width">
                        <button type="button"
                                class="gender-box gender-left <?php echo ($dog_gender_kor === '수컷') ? 'selected' : ''; ?>"
                                data-value="수컷">수컷</button>
                        <button type="button"
                                class="gender-box gender-right <?php echo ($dog_gender_kor === '암컷') ? 'selected' : ''; ?>"
                                data-value="암컷">암컷</button>
                    </div>
                </div>

                <div class="field-row">
                    <label for="dog_color">색</label>
                    <input type="text" id="dog_color" name="dog_color"
                           value="<?php echo htmlspecialchars($dog_color); ?>"
                           placeholder="예시: 검정색">
                </div>

                <div class="field-row">
                    <label for="dog_weight">몸무게</label>
                    <input type="text" id="dog_weight" name="dog_weight"
                           value="<?php echo htmlspecialchars($dog_weight); ?>"
                           placeholder="예시: 3.2">
                </div>
            </section>

        </form>

        <!-- 폼 밖에 있지만 submit 시키는 버튼 -->
        <button type="submit" form="dogDetailForm" class="btn-complete" id="btnComplete">
            완료
        </button>

    </main>
</div>

<!-- 이미지 미리보기 + 성별 버튼 전용 JS (DB 저장은 기본 form submit) -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const imageInput  = document.getElementById("dogImageInput");
        const uploadBtn   = document.getElementById("btnUploadImage");
        const previewImg  = document.getElementById("dogPreview");

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

        // 성별 선택
        const genderButtons = document.querySelectorAll(".gender-box");
        const genderInput   = document.getElementById("dog_gender");

        genderButtons.forEach(btn => {
            btn.addEventListener("click", () => {
                genderButtons.forEach(b => b.classList.remove("selected"));
                btn.classList.add("selected");
                genderInput.value = btn.dataset.value; // '수컷' / '암컷'
            });
        });
    });
</script>

</body>
</html>
<?php
oci_close($conn);
?>
