<?php
// notice_list.php : 보호소 공고 상태 관리

session_start();
header('Content-Type: text/html; charset=UTF-8');

// 1. 로그인 체크
if (!isset($_SESSION['shelter_id'])) {
    echo "<script>alert('로그인 후 이용해주세요.'); location.href='../login/shelter_login.html';</script>";
    exit;
}

$shelter_id   = $_SESSION['shelter_id'];
$shelter_name = $_SESSION['shelter_name'] ?? 'OOO 보호소';

// 2. DB 접속
$db_username = 'C093299';          // 계정에 맞게 수정
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die('DB 접속 실패: ' . htmlspecialchars($e['message']));
}

/* ==========================================================
   POST: 상태 저장 (보호 중 / 공고 등록 / 입양 완료)
   DOG.status : 'IN_CARE', 'AVAILABLE', 'ADOPTED'
   ========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusMap = $_POST['status'] ?? [];

    if (!empty($statusMap)) {
        try {
            foreach ($statusMap as $dogId => $status) {
                $dogId = (int)$dogId;
                $status = strtoupper(trim($status));

                // 허용 값 체크
                if (!in_array($status, ['IN_CARE', 'AVAILABLE', 'ADOPTED'], true)) {
                    continue;
                }

                $sql_update = "
                    UPDATE DOG
                    SET status = :status
                    WHERE dog_id = :dog_id
                      AND shelter_id = :sid
                ";
                $stmt_u = oci_parse($conn, $sql_update);
                oci_bind_by_name($stmt_u, ':status', $status);
                oci_bind_by_name($stmt_u, ':dog_id', $dogId);
                oci_bind_by_name($stmt_u, ':sid', $shelter_id);

                if (!oci_execute($stmt_u, OCI_NO_AUTO_COMMIT)) {
                    $e = oci_error($stmt_u);
                    oci_rollback($conn);
                    die('상태 저장 중 오류: ' . htmlspecialchars($e['message']));
                }
                oci_free_statement($stmt_u);
            }

            oci_commit($conn);
            echo "<script>alert('공고 상태가 저장되었습니다.'); location.href='notice_list.php';</script>";
            oci_close($conn);
            exit;

        } catch (Exception $ex) {
            oci_rollback($conn);
            oci_close($conn);
            $msg = htmlspecialchars($ex->getMessage());
            echo "<script>alert('저장 중 오류가 발생했습니다.\\n{$msg}'); history.back();</script>";
            exit;
        }
    }
}

/* ==========================================================
   GET: 페이지네이션 설정
   ========================================================== */
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// 전체 강아지 수 조회
$sql_count = "
    SELECT COUNT(*) AS total
    FROM DOG
    WHERE shelter_id = :sid
";
$stmt_count = oci_parse($conn, $sql_count);
oci_bind_by_name($stmt_count, ':sid', $shelter_id);
oci_execute($stmt_count);
$count_row = oci_fetch_assoc($stmt_count);
$total_items = $count_row['TOTAL'];
$total_pages = ceil($total_items / $items_per_page);
oci_free_statement($stmt_count);

/* ==========================================================
   GET: 목록 조회 (페이지네이션 적용)
   - 현재 보호소가 가진 강아지 + REPORT 유무
   ========================================================== */
$sql = "
    SELECT *
    FROM (
        SELECT
            d.dog_id,
            d.name,
            d.breed,
            d.status,
            CASE WHEN EXISTS (SELECT 1 FROM REPORT r WHERE r.dog_id = d.dog_id)
                 THEN 'Y' ELSE 'N'
            END AS has_report,
            ROW_NUMBER() OVER (ORDER BY d.dog_id DESC) AS rnum
        FROM DOG d
        WHERE d.shelter_id = :sid
    )
    WHERE rnum > :offset AND rnum <= :limit
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $shelter_id);
oci_bind_by_name($stmt, ':offset', $offset);
$limit = $offset + $items_per_page;
oci_bind_by_name($stmt, ':limit', $limit);
oci_execute($stmt);

// 행 번호용
$rows = [];
while ($row = oci_fetch_assoc($stmt)) {
    $rows[] = $row;
}
oci_free_statement($stmt);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>공고 관리</title>

    <!-- 기존 CSS -->
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="notice-list.css">

    <!-- Lucide CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

<div class="page-container">

    <!-- 사이드바 -->
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
            <button class="menu-btn" onclick="location.href='dog_list.php'">유기견 관리</button>
            <button class="menu-btn active" onclick="location.href='notice_list.php'">공고 관리</button>
        </nav>

        <form class="logout-btn" action="../login/logout.php" method="post">
            <button type="submit" class="logout-font">로그아웃</button>
        </form>
    </aside>

    <!-- 메인 영역 -->
    <main class="main-area">

        <!-- 전체를 감싸는 form (저장 버튼이 submit) -->
        <form method="post" action="notice_list.php">

            <table class="notice-table">
                <thead>
                <tr>
                    <th>목록</th>
                    <th>유기견 정보</th>
                    <th>보호 중</th>
                    <th>공고 등록</th>
                    <th>입양 완료</th>
                    <th>입양 심사</th>
                </tr>
                </thead>

                <tbody id="noticeBody">
                <?php
                foreach ($rows as $idx => $dog) {
                    $dogId   = $dog['DOG_ID'];
                    $name    = $dog['NAME'];
                    $breed   = $dog['BREED'];
                    $status  = $dog['STATUS'];      // IN_CARE / AVAILABLE / ADOPTED
                    $hasRep  = $dog['HAS_REPORT'];  // Y/N
                    // 목록 번호: 전체에서의 순서 계산
                    $listNo  = $total_items - $offset - $idx;

                    // 표기용 강아지 이름 (ex. 쪼꼬 (포메라니안))
                    $dogLabel = $name . ' (' . $breed . ')';
                    ?>
                    <!-- 숨겨진 input: 이 강아지의 최종 status 값 -->
                    <input type="hidden"
                           name="status[<?php echo $dogId; ?>]"
                           id="status-<?php echo $dogId; ?>"
                           value="<?php echo htmlspecialchars($status); ?>">

                    <tr data-dog-id="<?php echo $dogId; ?>">
                        <td><?php echo $listNo; ?></td>
                        <td><?php echo htmlspecialchars($dogLabel); ?></td>

                        <!-- 보호 중 -->
                        <td>
                            <div class="circle <?php echo ($status === 'IN_CARE') ? 'checked' : ''; ?>"
                                 data-dog-id="<?php echo $dogId; ?>"
                                 data-status="IN_CARE"></div>
                        </td>

                        <!-- 공고 등록 -->
                        <td>
                            <div class="circle <?php echo ($status === 'AVAILABLE') ? 'checked' : ''; ?>"
                                 data-dog-id="<?php echo $dogId; ?>"
                                 data-status="AVAILABLE"></div>
                        </td>

                        <!-- 입양 완료 -->
                        <td>
                            <div class="circle <?php echo ($status === 'ADOPTED') ? 'checked' : ''; ?>"
                                 data-dog-id="<?php echo $dogId; ?>"
                                 data-status="ADOPTED"></div>
                        </td>

                        <!-- 입양 심사 (아이콘 클릭 시 상세 페이지로 이동하도록 링크) -->
                        <td>
                            <a href="notice_detail.php?dog_id=<?php echo $dogId; ?>">
                                <i data-lucide="link" class="review-icon"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <div class="bottom-bar">

                <!-- ✅ 페이지네이션: 메인 영역 맨 아래로 -->
                <div class="pagination" id="pagination">
                    <?php
                    if ($total_pages > 1) {
                        for ($p = 1; $p <= $total_pages; $p++) {
                            $active_class = ($p == $page) ? "page-btn active" : "page-btn";
                            echo "<a class=\"$active_class\" href=\"notice_list.php?page=$p\">$p</a>";
                        }
                    }
                    ?>
                </div>

                <button type="submit" class="save-btn">저장</button>
            </div>

        </form>

    </main>
</div>	

<script>
    // 원래 notice-list.js에서 하던 역할을 이 안에서 DB 데이터 기준으로 수행

    document.addEventListener("DOMContentLoaded", () => {
        // 각 원(circle) 클릭 시, 같은 행의 다른 원들은 해제하고
        // 숨겨진 input(status-dogId)의 값을 바꿔준다.
        const circles = document.querySelectorAll(".circle");

        circles.forEach(circle => {
            circle.addEventListener("click", () => {
                const dogId  = circle.dataset.dogId;
                const status = circle.dataset.status;

                // 같은 강아지(dogId)를 가진 circle들에서 checked 제거
                document
                    .querySelectorAll(`.circle[data-dog-id="${dogId}"]`)
                    .forEach(c => c.classList.remove("checked"));

                // 클릭한 circle만 체크
                circle.classList.add("checked");

                // hidden input 값 업데이트
                const hidden = document.getElementById(`status-${dogId}`);
                if (hidden) {
                    hidden.value = status;
                }
            });
        });

        // Lucide 아이콘 렌더링
        lucide.createIcons();
    });
</script>

</body>
</html>
<?php
oci_close($conn);
?>