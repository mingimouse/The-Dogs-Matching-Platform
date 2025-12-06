<?php
// ============================
// DB 접속 정보 (환경에 맞게 수정)
// ============================
$db_username = "C289003";        // 오라클 계정
$db_password = "C289003"; // 오라클 비밀번호
$db_connection_string = "203.249.87.57/orcl"; // 호스트/서비스명

mb_internal_encoding("UTF-8");

// 폼으로 직접 들어온 게 아니면 막기
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shelter-sign-up.html');
    exit;
}

// 1) 폼 데이터 받기
$username        = trim($_POST['username'] ?? '');
$password        = trim($_POST['password'] ?? '');
$shelter_name    = trim($_POST['name'] ?? '');
$contact_input   = trim($_POST['contact'] ?? '');
$addr_city       = trim($_POST['addr_city'] ?? '');
$addr_district   = trim($_POST['addr_district'] ?? '');
$location_detail = trim($_POST['location_detail'] ?? '');
$start_time      = trim($_POST['start_time'] ?? '');
$end_time        = trim($_POST['end_time'] ?? '');

// 필수값 체크
if ($username === '' || $password === '' || $shelter_name === '' ||
    $contact_input === '' || $addr_city === '' || $addr_district === '' ||
    $location_detail === '' || $start_time === '' || $end_time === '') {
    exit('필수 값을 모두 입력해주세요.');
}

// 2) 연락처 숫자만 추출 + 간단 검증
$digits = preg_replace('/[^0-9]/', '', $contact_input);
if (strlen($digits) < 9 || strlen($digits) > 11) {
    exit('전화번호 형식이 올바르지 않습니다.');
}
// DB에는 하이픈 없이 숫자만 저장 (DDL에 맞게 변경 가능)
$contact = $digits;

// 3) 영업시간 형식 검증 (HH:MM)
if (!preg_match('/^\d{2}:\d{2}$/', $start_time) ||
    !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
    exit('영업시간 형식이 올바르지 않습니다. (예: 09:00)');
}

// 4) 비밀번호 해시
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ============================
// Oracle DB 연결
// ============================
$conn = @oci_connect($db_username, $db_password, $db_connection_string, "AL32UTF8");

if (!$conn) {
    $e = oci_error();
    exit("DB 연결 실패: " . $e['message']);
}

try {
    // 5) 보호소 아이디 중복 체크 (테이블/컬럼명은 DDL에 맞게 수정)
    $sql_check_id = "SELECT COUNT(*) AS CNT FROM SHELTER WHERE shelter_id = :id";
    $stmt_check = oci_parse($conn, $sql_check_id);
    oci_bind_by_name($stmt_check, ':id', $username);
    oci_execute($stmt_check);
    $row = oci_fetch_assoc($stmt_check);

    if ($row['CNT'] > 0) {
        exit('이미 사용 중인 아이디입니다.');
    }

    // 6) REGION 테이블에서 region_id 찾기 (없으면 INSERT 후 받기)
    $region_id = null;

    // 6-1) 기존 region 조회
    $sql_sel_region = "
        SELECT region_id
        FROM REGION
        WHERE city = :city AND district = :district
    ";
    $stmt_sel_region = oci_parse($conn, $sql_sel_region);
    oci_bind_by_name($stmt_sel_region, ':city', $addr_city);
    oci_bind_by_name($stmt_sel_region, ':district', $addr_district);
    oci_execute($stmt_sel_region);

    $row_region = oci_fetch_assoc($stmt_sel_region);
    if ($row_region) {
        $region_id = $row_region['REGION_ID'];
    } else {
        // 6-2) 없으면 새로 INSERT + RETURNING region_id
        $sql_ins_region = "
            INSERT INTO REGION (city, district)
            VALUES (:city, :district)
            RETURNING region_id INTO :region_id
        ";
        $stmt_ins_region = oci_parse($conn, $sql_ins_region);
        oci_bind_by_name($stmt_ins_region, ':city', $addr_city);
        oci_bind_by_name($stmt_ins_region, ':district', $addr_district);
        oci_bind_by_name($stmt_ins_region, ':region_id', $region_id, 32);

        if (!oci_execute($stmt_ins_region, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt_ins_region);
            oci_rollback($conn);
            exit("지역 정보 저장 중 오류: " . $e['message']);
        }
    }

    // 7) SHELTER 테이블 INSERT
    //  👉 테이블/컬럼명은 DDL에 맞게 수정해서 사용하면 됨
    //     예시 DDL 가정:
    //     SHELTER(shelter_id, password, shelter_name,
    //             phone, address_detail, region_id,
    //             open_time, close_time)
    //
    //     open_time/close_time 이 VARCHAR2(5) 이라면 그대로 바인딩,
    //     DATE 타입이면 TO_DATE(:open_time, 'HH24:MI') 로 바꿔줘.
    $sql_ins_shelter = "
        INSERT INTO SHELTER (
            shelter_id, password, shelter_name,
            phone, detail, region_id,
            open_time, close_time
        )
        VALUES (
            :shelter_id, :password, :shelter_name,
            :phone, :detail, :region_id,
            :open_time, :close_time
        )
    ";

    $stmt_ins_shelter = oci_parse($conn, $sql_ins_shelter);
    oci_bind_by_name($stmt_ins_shelter, ':shelter_id', $username);
    oci_bind_by_name($stmt_ins_shelter, ':password', $hashed_password);
    oci_bind_by_name($stmt_ins_shelter, ':shelter_name', $shelter_name);
    oci_bind_by_name($stmt_ins_shelter, ':phone', $contact);
    oci_bind_by_name($stmt_ins_shelter, ':detail', $location_detail);
    oci_bind_by_name($stmt_ins_shelter, ':region_id', $region_id);
    oci_bind_by_name($stmt_ins_shelter, ':open_time', $start_time);
    oci_bind_by_name($stmt_ins_shelter, ':close_time', $end_time);

    if (!oci_execute($stmt_ins_shelter, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt_ins_shelter);
        oci_rollback($conn);
        exit("보호소 정보 저장 중 오류: " . $e['message']);
    }

    // 8) 커밋
    oci_commit($conn);

    // 9) 가입 완료 후 이동
    echo "<script>alert('보호소 회원가입이 완료되었습니다. 메인 페이지로 이동합니다.');";
    header("Location: ../index.html");
    exit;

} catch (Exception $e) {
    oci_rollback($conn);
    exit("오류가 발생했습니다: " . $e->getMessage());
} finally {
    oci_close($conn);
}
