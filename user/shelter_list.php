<?php
// shelter_list.php

session_start();

/* ===== 1. DB 연결 (Oracle) ===== */
$db_username = "C093299";
$db_password = "TEST1234";
$db_dsn      = "203.249.87.57/orcl";

$conn = oci_connect($db_username, $db_password, $db_dsn, "AL32UTF8");
if (!$conn) {
    $e = oci_error();
    die("DB 연결 실패: " . $e['message']);
}

/* ===== 2. 파라미터 처리 (시/도, 구/군, 페이지) ===== */
$selected_city     = isset($_GET['city'])     ? trim($_GET['city'])     : "";
$selected_district = isset($_GET['district']) ? trim($_GET['district']) : "";
$page              = isset($_GET['page'])     ? (int)$_GET['page']      : 1;
if ($page < 1) $page = 1;

$rows_per_page = 4;                      // ✅ 한 페이지 5개
$offset        = ($page - 1) * $rows_per_page;

/* ===== 3. 시/도 셀렉트용 데이터 ===== */
$sql_city  = "SELECT DISTINCT city FROM REGION ORDER BY city";
$stmt_city = oci_parse($conn, $sql_city);

/* ===== 4. 구/군 셀렉트용 데이터 ===== */
if ($selected_city !== "") {
    $sql_district = "
        SELECT district
        FROM REGION
        WHERE city = :city
        ORDER BY district
    ";
    $stmt_district = oci_parse($conn, $sql_district);
    oci_bind_by_name($stmt_district, ":city", $selected_city);
} else {
    $sql_district = "
        SELECT DISTINCT district
        FROM REGION
        ORDER BY district
    ";
    $stmt_district = oci_parse($conn, $sql_district);
}

/* ===== 5. 보호소 목록 조회용 where 절 구성 ===== */
$where = [];
if ($selected_city !== "") {
    $where[] = "r.city = :city_filter";
}
if ($selected_district !== "") {
    $where[] = "r.district = :district_filter";
}
$where_clause = "";
if (count($where) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

/* ===== 6. 전체 행 수 (번호 & 페이지 계산용) ===== */
$sql_count = "
    SELECT COUNT(*) AS cnt
    FROM SHELTER s
        JOIN REGION r ON s.region_id = r.region_id
    $where_clause
";
$stmt_count = oci_parse($conn, $sql_count);
if ($selected_city !== "") {
    oci_bind_by_name($stmt_count, ":city_filter", $selected_city);
}
if ($selected_district !== "") {
    oci_bind_by_name($stmt_count, ":district_filter", $selected_district);
}
oci_execute($stmt_count);
$row_count   = oci_fetch_assoc($stmt_count);
$total_rows  = (int)$row_count['CNT'];
$total_pages = max(1, ceil($total_rows / $rows_per_page));

/* ===== 7. 실제 보호소 목록 (ROW_NUMBER로 페이징) ===== */
$sql_list = "
    SELECT *
    FROM (
        SELECT
            ROW_NUMBER() OVER (ORDER BY s.name ASC) AS rn,
            s.name       AS shelter_name,
            s.phone      AS phone,
            s.open_time  AS open_time,
            s.close_time AS close_time,
            r.city       AS city,
            r.district   AS district,
            s.detail     AS detail
        FROM SHELTER s
            JOIN REGION r ON s.region_id = r.region_id
        $where_clause
    )
    WHERE rn BETWEEN :start_row AND :end_row
";
$stmt_list = oci_parse($conn, $sql_list);
if ($selected_city !== "") {
    oci_bind_by_name($stmt_list, ":city_filter", $selected_city);
}
if ($selected_district !== "") {
    oci_bind_by_name($stmt_list, ":district_filter", $selected_district);
}
$start_row = $offset + 1;
$end_row   = $offset + $rows_per_page;
oci_bind_by_name($stmt_list, ":start_row", $start_row);
oci_bind_by_name($stmt_list, ":end_row",   $end_row);
oci_execute($stmt_list);

/* ===== 8. 로그인한 사용자 이름 (예시) ===== */
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "ㅇㅇㅇ";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보호소 조회</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="shelter-list.css">
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
                <button class="menu-item" onclick="location.href='dog_list.php'">유기견 조회</button>
                <button class="menu-item active" onclick="location.href='shelter_list.php'">보호소 조회</button>
                <button class="menu-item" onclick="location.href='adoption_result.php'">입양 심사 결과</button>
            </nav>

            <button class="logout-btn" id="logoutBtn"
                    onclick="if(confirm('로그아웃 하시겠습니까?')) location.href='../login/logout.php';">
                로그아웃
            </button>
        </aside>

    <!-- 오른쪽 메인 콘텐츠 -->
    <main class="main-content">
        <!-- 검색 필터 -->
        <form class="search-filter" method="get" action="shelter_list.php">
            <select class="filter-select" id="citySelect" name="city">
                <option value="">전체</option>
                <?php
                oci_execute($stmt_city);
                while ($c = oci_fetch_assoc($stmt_city)) {
                    $city     = $c['CITY'];
                    $selected = ($city === $selected_city) ? "selected" : "";
                    echo "<option value=\"".htmlspecialchars($city)."\" $selected>"
                        .htmlspecialchars($city)."</option>";
                }
                ?>
            </select>

            <select class="filter-select" id="districtSelect" name="district">
                <option value="">전체</option>
                <?php
                oci_execute($stmt_district);
                while ($d = oci_fetch_assoc($stmt_district)) {
                    $district = $d['DISTRICT'];
                    if ($district === null) continue;
                    $selected = ($district === $selected_district) ? "selected" : "";
                    echo "<option value=\"".htmlspecialchars($district)."\" $selected>"
                        .htmlspecialchars($district)."</option>";
                }
                ?>
            </select>

            <button class="search-btn" id="searchBtn" type="submit">조회</button>
        </form>

        <!-- 테이블 + 페이지네이션 래퍼 -->

            <!-- 보호소 테이블 -->
            <div class="table-container">
                <table class="shelter-table">
                    <thead>
                    <tr>
                        <th>목록</th>
                        <th>보호소 이름</th>
                        <th>위치</th>
                        <th>전화 번호</th>
                        <th>운영 시간</th>
                    </tr>
                    </thead>
                    <tbody id="shelterTableBody">
                    <?php
                    if ($total_rows == 0) {
                        echo "<tr>";
                        echo "<td colspan='5' style='text-align:center; padding:30px; color:#999;'>검색 결과가 없습니다.</td>";
                        echo "</tr>";
                    } else {
                        $row_index = 0;
                        while ($row = oci_fetch_assoc($stmt_list)) {
                            $row_index++;

                            // 전체 개수 기준 역순 번호
                            $number = $total_rows - ($offset + $row_index - 1);

                            $name      = $row['SHELTER_NAME'];
                            $city      = $row['CITY'];
                            $district  = $row['DISTRICT'];
                            $detail    = $row['DETAIL'];
                            $phone     = $row['PHONE'];
                            $open_time = $row['OPEN_TIME'];
                            $close_time= $row['CLOSE_TIME'];

                            $location  = trim($city . ' ' . $district . ' ' . $detail);
                            $hours     = $open_time . " ~ " . $close_time;

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($number) . "</td>";
                            echo "<td>" . htmlspecialchars($name) . "</td>";
                            echo "<td>" . htmlspecialchars($location) . "</td>";
                            echo "<td>" . htmlspecialchars($phone) . "</td>";
                            echo "<td>" . htmlspecialchars($hours) . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- ✅ 페이지네이션: 메인 영역 맨 아래로 -->
            <div class="pagination" id="pagination">
                <?php
                if ($total_pages > 1) {
                    for ($p = 1; $p <= $total_pages; $p++) {
                        $query = http_build_query([
                            'city'     => $selected_city,
                            'district' => $selected_district,
                            'page'     => $p
                        ]);
                        $active_class = ($p == $page) ? "page-btn active" : "page-btn";
                        echo "<a class=\"$active_class\" href=\"shelter_list.php?$query\">$p</a>";
                    }
                }
                ?>
            </div>
        
    </main>
</div>
    <script src="dog-detail.js"></script>
</body>
</html>
<?php
/* ===== 자원 정리 ===== */
oci_free_statement($stmt_city);
oci_free_statement($stmt_district);
oci_free_statement($stmt_count);
oci_free_statement($stmt_list);
oci_close($conn);
?>
