<?php
session_start();

// 1. 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='../login/user-login.html';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Oracle DB 접속 정보 (★ 비밀번호는 민기 계정에 맞게 수정)
$db_username = 'C093299';             // sqlplus 아이디
$db_password = 'TEST1234';   // sqlplus 비밀번호
$db_conn_str = '203.249.87.57/orcl';  // 호스트/서비스명

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    $msg = isset($e['message']) ? $e['message'] : 'DB 접속 오류';
    echo "<script>alert('DB 접속에 실패했습니다.\\n{$msg}'); history.back();</script>";
    exit;
}

// 3. USERS + REGION 조인해서 정보 가져오기
//    DDL 기준 컬럼 구조 참고: USERS, REGION
$sql = "
    SELECT 
        u.name     AS user_name,
        u.phone    AS user_phone,
        r.city     AS city,
        r.district AS district
    FROM USERS u
    JOIN REGION r
      ON u.region_id = r.region_id
    WHERE u.user_id = :user_id
";


$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('회원 정보를 불러오는 중 오류가 발생했습니다.'); history.back();</script>";
    exit;
}

$row = oci_fetch_assoc($stmt);

if (!$row) {
    // 혹시라도 삭제된 계정으로 로그인한 경우
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('회원 정보를 찾을 수 없습니다. 다시 로그인해주세요.'); location.href='../login/user-login.html';</script>";
    exit;
}

// PHP 변수에 담기
$user_name  = $row['USER_NAME'];
$user_phone = $row['USER_PHONE'];

oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>유기견 조회</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dog-list.css">
</head>
<body>
    <div class="container">
        <!-- 왼쪽 사이드바 -->
        <aside class="sidebar">
            <div class="profile-section">
                <img src="../img/user.png" alt="사용자 아이콘" class="profile-icon">
                <h2 class="profile-name"><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?> 님
            </h2>
                <div class="divider"></div>
            </div>

            <nav class="menu">
                <button class="menu-item" onclick="location.href='user_profile.php'">회원정보수정</button>
                <button class="menu-item active" onclick="location.href='dog_list.php'">유기견조회</button>
                <button class="menu-item" onclick="location.href='shelter_list.php'">보호소조회</button>
                <button class="menu-item" onclick="location.href='adoption_result.php'">입양심사결과</button>
            </nav>

            <button class="logout-btn" id="logoutBtn"
                    onclick="if(confirm('로그아웃 하시겠습니까?')) location.href='../login/logout.php';">
                로그아웃
            </button>

        </aside>

        <!-- 오른쪽 메인 콘텐츠 -->
        <main class="main-content">
            <!-- 검색 필터 영역 -->
            <div class="search-filter">
                <select id="breedSelect" class="filter-select">
                    <option value="">품종</option>
                    <option value="포메라니안">포메라니안</option>
                    <option value="말티즈">말티즈</option>
                    <option value="푸들">푸들</option>
                    <option value="리트리버">리트리버</option>
                    <option value="불독">불독</option>
                    <option value="비숑">비숑</option>
                    <option value="닥스훈트">닥스훈트</option>
                    <option value="시바">시바</option>
                    <option value="웰시코기">웰시코기</option>
                </select>
                
                <select id="colorSelect" class="filter-select">
                    <option value="">색</option>
                    <option value="갈색">갈색</option>
                    <option value="흰색">흰색</option>
                    <option value="검정">검정</option>
                </select>
                
                <select id="genderSelect" class="filter-select">
                    <option value="">성별</option>
                    <option value="수컷">수컷</option>
                    <option value="암컷">암컷</option>
                </select>
                
                <button class="search-btn" id="searchBtn">🔍</button>
            </div>

            <!-- 유기견 카드 그리드 -->
            <div class="dog-grid" id="dogGrid">
                <!-- JavaScript로 동적 생성 -->
            </div>

            <!-- 페이지네이션 -->
            <div class="pagination" id="pagination">
                <!-- JavaScript로 동적 생성 -->
            </div>
        </main>
    </div>

    <script src="dog-list.js"></script>
</body>
</html>