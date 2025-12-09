<?php
// adoption_result.php
// 입양 심사 결과 페이지 (USER용) - 페이지네이션 구현

session_start();
header('Content-Type: text/html; charset=UTF-8');

// -------------------------
// 1. 로그인 여부 확인
// -------------------------
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인 후 이용 가능합니다.'); location.href='../login/user_login.php';</script>";
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '회원';

// -------------------------
// 2. 페이지네이션 설정
// -------------------------
$items_per_page = 5; // 페이지당 표시할 항목 수
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// -------------------------
// 3. Oracle DB 접속
// -------------------------
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    echo "<p>DB 접속에 실패했습니다.</p>";
    echo "<pre>" . htmlspecialchars($e['message']) . "</pre>";
    exit;
}

// -------------------------
// 4. 전체 레코드 수 조회
// -------------------------
$count_sql = "
    SELECT COUNT(*) AS total_count
    FROM ADOPTION_REQUEST ar
    WHERE ar.user_id = :user_id
";

$count_stid = oci_parse($conn, $count_sql);
oci_bind_by_name($count_stid, ':user_id', $user_id);
oci_execute($count_stid);
$count_row = oci_fetch_assoc($count_stid);
$total_items = $count_row['TOTAL_COUNT'];
oci_free_statement($count_stid);

// 전체 페이지 수 계산
$total_pages = ceil($total_items / $items_per_page);

// -------------------------
// 5. 페이지네이션을 적용한 데이터 조회
// -------------------------
$sql = "
    SELECT *
    FROM (
        SELECT
            ar.request_id,
            s.name AS shelter_name,
            d.name AS dog_name,
            d.breed AS dog_breed,
            TO_CHAR(ar.datetime, 'YYYY.MM.DD HH24:MI') AS req_datetime,
            ar.status,
            ROW_NUMBER() OVER (ORDER BY ar.datetime DESC, ar.request_id DESC) AS rnum
        FROM ADOPTION_REQUEST ar
            JOIN DOG d     ON ar.dog_id = d.dog_id
            JOIN SHELTER s ON d.shelter_id = s.shelter_id
        WHERE ar.user_id = :user_id
    )
    WHERE rnum > :offset AND rnum <= :limit
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':user_id', $user_id);
oci_bind_by_name($stid, ':offset', $offset);
$limit = $offset + $items_per_page;
oci_bind_by_name($stid, ':limit', $limit);

oci_execute($stid);

$requests = [];
while ($row = oci_fetch_assoc($stid)) {
    $requests[] = $row;
}

oci_free_statement($stid);
oci_close($conn);

// -------------------------
// 6. 심사 상태를 한글 + 색상으로 변환하는 함수
// -------------------------
function get_status_text_and_color($status)
{
    switch ($status) {
        case 'APPROVED':
            return ['승인', '#1e73ff'];
        case 'PENDING':
            return ['심사 중', '#1e9b4c'];
        case 'REJECTED':
            return ['거절', '#e53935'];
        default:
            return [$status, '#333'];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>입양 심사 결과</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="adoption-result.css">
</head>
<body>
<div class="container">
    <!-- 왼쪽 사이드바 -->
    <aside class="sidebar">
        <div class="profile-section">
            <img src="../img/user.png" alt="사용자 아이콘" class="profile-icon">
            <h2 class="profile-name"><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?> 님</h2>
            <div class="divider"></div>
        </div>

        <nav class="menu">
            <button class="menu-item" onclick="location.href='user_profile.php'">회원 정보 수정</button>
            <button class="menu-item" onclick="location.href='dog_list.php'">유기견 조회</button>
            <button class="menu-item" onclick="location.href='shelter_list.php'">보호소 조회</button>
            <button class="menu-item active" onclick="location.href='adoption_result.php'">입양 심사 결과</button>
        </nav>

        <button class="logout-btn" id="logoutBtn"
                onclick="if(confirm('로그아웃 하시겠습니까?')) location.href='../login/logout.php';">
            로그아웃
        </button>
    </aside>

    <!-- 오른쪽 메인 콘텐츠 -->
    <main class="main-content">
 	    <div class="table-container">
                <table class="result-table">
                    <thead>
                    <tr>
                        <th>목록</th>
                        <th>보호소 이름</th>
                        <th>강아지 이름</th>
                        <th>품종</th>
                        <th>신청일</th>
                        <th>심사 결과</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px 0;">
                                입양 신청 내역이 없습니다.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        // 전체 목록에서의 시작 번호 계산
                        $start_num = $total_items - $offset;
                        foreach ($requests as $index => $req):
                            $no = $start_num - $index;
                            list($status_text, $status_color) = get_status_text_and_color($req['STATUS']);
                            ?>
                            <tr>
                                <td><?php echo $no; ?></td>
                                <td><?php echo htmlspecialchars($req['SHELTER_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($req['DOG_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($req['DOG_BREED']); ?></td>
                                <td><?php echo htmlspecialchars($req['REQ_DATETIME']); ?></td>
                                <td style="font-weight:600; color: <?php echo $status_color; ?>;">
                                    <?php echo htmlspecialchars($status_text); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
	    </div>
        
        <!-- ✅ 페이지네이션: 메인 영역 맨 아래로 -->
        <div class="pagination" id="pagination">
            <?php
            if ($total_pages > 1) {
                for ($p = 1; $p <= $total_pages; $p++) {
                    $active_class = ($p == $page) ? "page-btn active" : "page-btn";
                    echo "<a class=\"$active_class\" href=\"adoption_result.php?page=$p\">$p</a>";
                }
            }
            ?>
        </div>

    </main>
</div>
</body>
</html>