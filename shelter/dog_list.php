<?php
// dog_list.php : 내 보호소의 강아지 목록 페이지

session_start();
header('Content-Type: text/html; charset=UTF-8');

// 1) 로그인 체크
if (!isset($_SESSION['shelter_id'])) {
    echo "<script>alert('로그인 후 이용해주세요.'); location.href='../login/shelter_login.html';</script>";
    exit;
}

$shelter_id   = $_SESSION['shelter_id'];
$shelter_name = $_SESSION['shelter_name'] ?? 'OOO 보호소';

// 2) DB 연결
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die('DB 접속 실패: ' . htmlspecialchars($e['message']));
}

// 3) 강아지 목록 조회 (REPORT 존재 여부도 함께)
$sql = "
    SELECT
        d.dog_id,
        d.name,
        d.breed,
        d.age,
        d.gender,
        d.color,
        d.weight,
        d.image_url,
        d.status,
        CASE 
            WHEN EXISTS (SELECT 1 FROM REPORT r WHERE r.dog_id = d.dog_id)
            THEN 'Y' ELSE 'N'
        END AS has_report
    FROM DOG d
    WHERE d.shelter_id = :sid
    ORDER BY d.dog_id DESC
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $shelter_id);
oci_execute($stmt);

// 카드 렌더링용 헬퍼 함수
function getStatusTextClass($status) {
    // DDL 기준: 'AVAILABLE', 'ADOPTED', 'IN_CARE'
    switch ($status) {
        case 'ADOPTED':
            return ['입양 완료', 'status-complete'];
        case 'AVAILABLE':
            return ['공고 등록', 'status-notice'];
        case 'IN_CARE':
        default:
            return ['보호 중', 'status-protect'];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>유기견 관리</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dog-list.css">
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
        <!-- 강아지 카드 리스트 -->
        <section class="dog-list">
            <?php while ($row = oci_fetch_assoc($stmt)): ?>
                <?php
                $dog_id   = $row['DOG_ID'];
                $name     = $row['NAME'];
                $breed    = $row['BREED'];
                $age      = $row['AGE'];    // NUMBER(3)
                $gender   = $row['GENDER']; // 'M' / 'F'
                $color    = $row['COLOR'];
                $weight   = $row['WEIGHT']; // NUMBER(5,1)
                $imageUrl = $row['IMAGE_URL'];

                // 기본 이미지
                if (!$imageUrl) {
                    $imageUrl = '../img/bichon.png';
                }

                list($statusText, $statusClass) = getStatusTextClass($row['STATUS']);
                $hasReport = ($row['HAS_REPORT'] === 'Y');

                $genderText = ($gender === 'M') ? '수컷' : (($gender === 'F') ? '암컷' : '');
                ?>
                <div class="dog-card">

                    <div class="dog-image-wrapper">
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="dog_img" alt="강아지 사진">
                    </div>

                    <div class="status-bar <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($statusText); ?>
                    </div>

                    <div class="dog-info">
                        <div class="info-row">
                            <div class="info-label">이름</div>
                            <div class="info-value"><?php echo htmlspecialchars($name); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">나이</div>
                            <div class="info-value"><?php echo htmlspecialchars($age); ?>세</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">품종</div>
                            <div class="info-value"><?php echo htmlspecialchars($breed); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">성별</div>
                            <div class="info-value"><?php echo htmlspecialchars($genderText); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">색</div>
                            <div class="info-value"><?php echo htmlspecialchars($color); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">몸무게</div>
                            <div class="info-value"><?php echo htmlspecialchars($weight); ?>kg</div>
                        </div>
                    </div>

                    <!-- 건강정보 버튼 (REPORT 여부로 색상 다르게) -->
                    <button
                            class="health-btn <?php echo $hasReport ? 'health-complete' : 'health-missing'; ?>"
                            onclick="location.href='dog-report.html?dog_id=<?php echo $dog_id; ?>'">
                        <?php echo $hasReport ? '건강정보 입력' : '건강정보 미입력'; ?>
                    </button>

                    <!-- 수정 / 삭제 -->
                    <div class="card-actions">
                        <button class="small-btn edit-btn"
                                onclick="location.href='dog_detail.php?dog_id=<?php echo $dog_id; ?>'">
                            수정
                        </button>

                        <form action="dog_save.php" method="post"
                              onsubmit="return confirm('정말 삭제하시겠습니까?');">
                            <input type="hidden" name="mode" value="delete">
                            <input type="hidden" name="dog_id" value="<?php echo $dog_id; ?>">
                            <button type="submit" class="small-btn delete-btn">삭제</button>
                        </form>
                    </div>

                </div>
            <?php endwhile; ?>
        </section>

        <!-- 하단 바: 페이지네이션은 DB 연동 버전에선 생략 (원하면 나중에 구현) -->
        <section class="bottom-bar">
            <div class="pagination"></div>
            <button type="button" class="add-btn" onclick="location.href='dog_detail.php'">추가</button>
        </section>
    </main>
</div>

</body>
</html>
<?php
oci_free_statement($stmt);
oci_close($conn);
?>
