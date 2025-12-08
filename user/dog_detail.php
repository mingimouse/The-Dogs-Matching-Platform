<?php
session_start();

// 1) 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='../login/user-login.html';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// 2) dog_id 파라미터 체크 (어떤 강아지인지)
if (!isset($_GET['dog_id'])) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$dog_id = (int)$_GET['dog_id'];

// 3) DB 접속
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    $msg = isset($e['message']) ? $e['message'] : 'DB 접속 오류';
    echo "<script>alert('DB 접속에 실패했습니다.\\n{$msg}'); history.back();</script>";
    exit;
}

/* 4) 사이드바용 회원 이름 가져오기 */
$sql_user = "
    SELECT name
    FROM USERS
    WHERE user_id = :user_id
";

$stmt_user = oci_parse($conn, $sql_user);
oci_bind_by_name($stmt_user, ':user_id', $user_id);

if (!oci_execute($stmt_user)) {
    $e = oci_error($stmt_user);
    oci_free_statement($stmt_user);
    oci_close($conn);
    echo "<script>alert('회원 정보를 불러오는 중 오류가 발생했습니다.'); history.back();</script>";
    exit;
}

$row_user = oci_fetch_assoc($stmt_user);
if (!$row_user) {
    oci_free_statement($stmt_user);
    oci_close($conn);
    echo "<script>alert('회원 정보를 찾을 수 없습니다. 다시 로그인해주세요.'); location.href='../login/user-login.html';</script>";
    exit;
}
$user_name = $row_user['NAME'];
oci_free_statement($stmt_user);

/*
  5) DOG + SHELTER + REPORT 정보 한 번에 가져오기
     REPORT 테이블:
       pavo, covid, heartworm, distemper CHAR(1) - 'Y' / 'N'
*/
$sql = "
    SELECT
        d.dog_id,
        d.name       AS dog_name,
        d.breed,
        d.age,
        d.gender,
        d.color,
        d.weight,
        d.image_url,
        s.name       AS shelter_name,
        r.pavo,
        r.covid,
        r.heartworm,
        r.distemper
    FROM DOG d
    JOIN SHELTER s
      ON d.shelter_id = s.shelter_id
    LEFT JOIN REPORT r
      ON r.dog_id = d.dog_id
    WHERE d.dog_id = :dog_id
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':dog_id', $dog_id);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('강아지 정보를 불러오는 중 오류가 발생했습니다.'); history.back();</script>";
    exit;
}

$row = oci_fetch_assoc($stmt);

if (!$row) {
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('해당 강아지 정보를 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

/* ===========================
   REPORT 값(Y/N)을 O/X로 변환
   =========================== */

// REPORT 원본 Y/N 값
$pavo_yn      = $row['PAVO']      ?? null;
$covid_yn     = $row['COVID']     ?? null;
$heart_yn     = $row['HEARTWORM'] ?? null;
$dist_yn      = $row['DISTEMPER'] ?? null;

// Y/N → O/X 변환 함수
function yn_to_ox($v) {
    $v = strtoupper(trim((string)$v));  // 안전하게 변환

    if ($v === 'Y') return 'O';  // Y → O
    return 'X';                   // 그 외 전부 → X
}

// 변환된 값
$pavo_ox  = yn_to_ox($pavo_yn);
$covid_ox = yn_to_ox($covid_yn);
$heart_ox = yn_to_ox($heart_yn);
$dist_ox  = yn_to_ox($dist_yn);

/* ===========================
   나머지 DOG / SHELTER 정보
   =========================== */

$dog_name     = $row['DOG_NAME'];
$breed        = $row['BREED'];
$age          = $row['AGE'];
$gender_code  = $row['GENDER'];      // 'M' or 'F'
$color        = $row['COLOR'];
$weight       = $row['WEIGHT'];
$image_url    = $row['IMAGE_URL'];
$shelter_name = $row['SHELTER_NAME'];

// 성별 한글
$gender_text = ($gender_code === 'M') ? '수컷' : '암컷';

// 강아지 이미지 경로 결정
$dog_image_src = "../img/dog1.png";   // 기본 이미지

if (!empty($image_url)) {
    // 절대/루트 경로 또는 http로 시작하면 그대로 사용
    if (strpos($image_url, 'http') === 0 || $image_url[0] === '/') {
        $dog_image_src = $image_url;
    } else {
        // 파일명만 들어있으면 img 폴더 기준으로 사용
        $dog_image_src = "../img/" . $image_url;
    }
}

oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>유기견 상세정보</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dog-detail.css">
</head>
<body>
    <div class="container">
        <!-- 왼쪽 사이드바 -->
        <aside class="sidebar">
            <div class="profile-section">
                <img src="../img/user.png" alt="사용자 아이콘" class="profile-icon">
                <h2 class="profile-name">
                    <?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?> 님
                </h2>
                <div class="divider"></div>
            </div>

            <nav class="menu">
                <button class="menu-item" onclick="location.href='user_profile.php'">회원 정보 수정</button>
                <button class="menu-item active" onclick="location.href='dog_list.php'">유기견 조회</button>
                <button class="menu-item" onclick="location.href='shelter_list.php'">보호소 조회</button>
                <button class="menu-item" onclick="location.href='adoption_result.php'">입양 심사 결과</button>
            </nav>

            <button class="logout-btn"
                    onclick="if(confirm('로그아웃 하시겠습니까?')) location.href='../login/logout.php';">
                로그아웃
            </button>
        </aside>

        <!-- 오른쪽 메인 콘텐츠 -->
        <main class="main-content">
            <!-- 모달 카드 -->
            <div class="modal-content">
                <div class="modal-body">
                    <!-- 상단: 강아지 이름 -->
                    <div class="dog-name-header">
                        <h2 class="dog-title">
                            <?= htmlspecialchars($dog_name, ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                    </div>

                    <!-- 메인 영역 -->
                    <div class="modal-main">
                        <!-- 왼쪽: 이미지 + 질병 정보 -->
                        <div class="left-section">
                            <div class="dog-image-section">
                                <img
                                    src="<?= htmlspecialchars($dog_image_src, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="강아지"
                                    class="dog-detail-image"
                                >
                            </div>

                            <!-- 질병 정보 테이블 (REPORT 기반 O/X) -->
                            <div class="disease-section">
                                <table class="disease-table">
                                    <thead>
                                        <tr>
                                            <th>파보</th>
                                            <th>코로나</th>
                                            <th>심장사상충</th>
                                            <th>홍역</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
						<td><span class="<?= $pavo_ox === 'O' ? 'yes' : 'no' ?>"><?= $pavo_ox ?></span></td>
						<td><span class="<?= $covid_ox === 'O' ? 'yes' : 'no' ?>"><?= $covid_ox ?></span></td>
						<td><span class="<?= $heart_ox === 'O' ? 'yes' : 'no' ?>"><?= $heart_ox ?></span></td>
						<td><span class="<?= $dist_ox === 'O' ? 'yes' : 'no' ?>"><?= $dist_ox ?></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 오른쪽: 텍스트 정보 -->
                        <div class="right-section">
                            <div class="info-grid">
                                <div class="info-row">
                                    <span class="info-label">보호소명</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($shelter_name, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">품종</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($breed, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">나이</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($age, ENT_QUOTES, 'UTF-8') ?>살
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">성별</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($gender_text, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">색</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">몸무게</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($weight, ENT_QUOTES, 'UTF-8') ?>kg
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /.modal-main -->
                </div> <!-- /.modal-body -->
            </div> <!-- /.modal-content -->

            <!-- 하단 버튼 -->
            <div class="button-group">
                <!-- 닫기: 직전 페이지로 -->
                <button class="cancel-btn" onclick="history.back();">닫기</button>

                <!-- 입양 신청: adoption_request_insert.php로 dog_id 전달 -->
                <button
                    class="adopt-btn"
                    onclick="location.href='adoption_request_insert.php?dog_id=<?= $dog_id ?>';"
                >
                    입양 신청
                </button>
            </div>
        </main>
    </div>
</body>
</html>
