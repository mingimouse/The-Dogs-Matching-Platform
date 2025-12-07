<?php
// dog_report.php : 예방접종(Health Report) 조회 + 저장

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
$db_password = 'TEST1234';           // 민기 계정에 맞게 수정
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die('DB 접속 실패: ' . htmlspecialchars($e['message']));
}

// 공통: dog_id 가져오기
$dog_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dog_id = isset($_POST['dog_id']) ? (int)$_POST['dog_id'] : 0;
} else {
    $dog_id = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
}

if ($dog_id <= 0) {
    echo "<script>alert('유기견을 먼저 등록해주세요.'); location.href='dog_list.php';</script>";
    oci_close($conn);
    exit;
}

// 3) 이 dog_id가 현재 로그인한 보호소가 가진 강아지인지 검증
$sql_check_dog = "
    SELECT shelter_id, name
    FROM DOG
    WHERE dog_id = :dog_id
";
$stmt_check = oci_parse($conn, $sql_check_dog);
oci_bind_by_name($stmt_check, ':dog_id', $dog_id);
oci_execute($stmt_check);
$dog_row = oci_fetch_assoc($stmt_check);
oci_free_statement($stmt_check);

if (!$dog_row || $dog_row['SHELTER_ID'] !== $shelter_id) {
    echo "<script>alert('해당 유기견 정보에 접근할 수 없습니다.'); location.href='dog_list.php';</script>";
    oci_close($conn);
    exit;
}
$dog_name = $dog_row['NAME'];

// --------------------------
// 4) POST: 저장 처리
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 폼 값(Y/N) 받기 – 잘못된 값이면 기본 N
    $pavo      = ($_POST['pavo']      ?? 'N') === 'Y' ? 'Y' : 'N';
    $covid     = ($_POST['covid']     ?? 'N') === 'Y' ? 'Y' : 'N';
    $heartworm = ($_POST['heartworm'] ?? 'N') === 'Y' ? 'Y' : 'N';
    $distemper = ($_POST['distemper'] ?? 'N') === 'Y' ? 'Y' : 'N';

    // REPORT 존재 여부 확인
    $sql_exists = "SELECT COUNT(*) AS CNT FROM REPORT WHERE dog_id = :dog_id";
    $stmt_ex = oci_parse($conn, $sql_exists);
    oci_bind_by_name($stmt_ex, ':dog_id', $dog_id);
    oci_execute($stmt_ex);
    $row_ex = oci_fetch_assoc($stmt_ex);
    $exists = ($row_ex['CNT'] > 0);
    oci_free_statement($stmt_ex);

    if ($exists) {
        // UPDATE
        $sql_u = "
            UPDATE REPORT
            SET pavo = :pavo,
                covid = :covid,
                heartworm = :heartworm,
                distemper = :distemper
            WHERE dog_id = :dog_id
        ";
        $stmt_u = oci_parse($conn, $sql_u);
        oci_bind_by_name($stmt_u, ':pavo',      $pavo);
        oci_bind_by_name($stmt_u, ':covid',     $covid);
        oci_bind_by_name($stmt_u, ':heartworm', $heartworm);
        oci_bind_by_name($stmt_u, ':distemper', $distemper);
        oci_bind_by_name($stmt_u, ':dog_id',    $dog_id);

        if (!oci_execute($stmt_u, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt_u);
            oci_rollback($conn);
            die('REPORT 업데이트 실패: ' . htmlspecialchars($e['message']));
        }
        oci_free_statement($stmt_u);

    } else {
        // INSERT
        $sql_i = "
            INSERT INTO REPORT (dog_id, pavo, covid, heartworm, distemper)
            VALUES (:dog_id, :pavo, :covid, :heartworm, :distemper)
        ";
        $stmt_i = oci_parse($conn, $sql_i);
        oci_bind_by_name($stmt_i, ':dog_id',    $dog_id);
        oci_bind_by_name($stmt_i, ':pavo',      $pavo);
        oci_bind_by_name($stmt_i, ':covid',     $covid);
        oci_bind_by_name($stmt_i, ':heartworm', $heartworm);
        oci_bind_by_name($stmt_i, ':distemper', $distemper);

        if (!oci_execute($stmt_i, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt_i);
            oci_rollback($conn);
            die('REPORT 입력 실패: ' . htmlspecialchars($e['message']));
        }
        oci_free_statement($stmt_i);
    }

    oci_commit($conn);
    oci_close($conn);

    echo "<script>alert('예방접종 정보가 저장되었습니다.'); location.href='dog_detail.php?dog_id={$dog_id}';</script>";
    exit;
}

// --------------------------
// 5) GET: 화면 출력용 데이터 조회
// --------------------------

// 기본값: 전부 미접종(N)
$pavo      = 'N';
$covid     = 'N';
$heartworm = 'N';
$distemper = 'N';

// REPORT 있으면 값 가져오기
$sql_r = "
    SELECT pavo, covid, heartworm, distemper
    FROM REPORT
    WHERE dog_id = :dog_id
";
$stmt_r = oci_parse($conn, $sql_r);
oci_bind_by_name($stmt_r, ':dog_id', $dog_id);
oci_execute($stmt_r);
$row_r = oci_fetch_assoc($stmt_r);
oci_free_statement($stmt_r);

if ($row_r) {
    $pavo      = $row_r['PAVO'];
    $covid     = $row_r['COVID'];
    $heartworm = $row_r['HEARTWORM'];
    $distemper = $row_r['DISTEMPER'];
}

// 이 아래부터는 화면 렌더링 (기존 dog-report.html + PHP)
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>예방접종 관리</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dog-report.css">
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
            <button class="menu-btn" onclick="location.href='shelter-edit.html'">회원정보 수정</button>
            <button class="menu-btn active" onclick="location.href='dog_list.php'">유기견 관리</button>
            <button class="menu-btn" onclick="location.href='notice-list.html'">공고 관리</button>
        </nav>

        <form class="logout-btn" action="../login/logout.php" method="post">
            <button type="submit" class="logout-font">로그아웃</button>
        </form>
    </aside>

    <!-- 메인 영역 -->
    <main class="main-area">

        <div class="report-header-title">
            예방접종 현황 (<?php echo htmlspecialchars($dog_name); ?>)
        </div>

        <!-- 저장용 폼 -->
        <form action="dog_report.php?dog_id=<?php echo $dog_id; ?>" method="post">
            <input type="hidden" name="dog_id" value="<?php echo $dog_id; ?>">

            <section class="report-card">
                <table class="vaccine-table">
                    <thead>
                    <tr>
                        <th class="col-name"></th>
                        <th class="col-status">접종</th>
                        <th class="col-status">미접종</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="vaccine-name">파보</td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="pavo" value="Y"
                                    <?php if ($pavo === 'Y') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="pavo" value="N"
                                    <?php if ($pavo === 'N') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <td class="vaccine-name">코로나</td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="covid" value="Y"
                                    <?php if ($covid === 'Y') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="covid" value="N"
                                    <?php if ($covid === 'N') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <td class="vaccine-name">심장사상충</td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="heartworm" value="Y"
                                    <?php if ($heartworm === 'Y') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="heartworm" value="N"
                                    <?php if ($heartworm === 'N') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <td class="vaccine-name">홍역</td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="distemper" value="Y"
                                    <?php if ($distemper === 'Y') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                        <td class="status-cell">
                            <label class="status-label">
                                <input type="radio" name="distemper" value="N"
                                    <?php if ($distemper === 'N') echo 'checked'; ?>>
                                <span class="status-circle"></span>
                            </label>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </section>

            <div class="bottom-save">
                <button type="submit" class="save-btn" id="saveBtn">저장</button>
            </div>
        </form>

    </main>
</div>

</body>
</html>
<?php
oci_close($conn);
?>
