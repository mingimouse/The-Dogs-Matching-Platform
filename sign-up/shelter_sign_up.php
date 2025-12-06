<?php
// 한글 깨짐 방지
header('Content-Type: text/html; charset=UTF-8');

// 1. POST 방식 요청인지 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('잘못된 접근입니다.'); location.href='../index.html';</script>";
    exit;
}

// 2. 폼 데이터 받기 (shelter-sign-up.html 의 name 기준)
$shelter_id      = trim($_POST['username']        ?? '');  // 보호소 아이디
$password        = $_POST['password']             ?? '';   // 비밀번호
$shelter_name    = trim($_POST['shelter_name']    ?? '');  // 보호소명
$phone           = trim($_POST['contact']         ?? '');  // 연락처
$addr_city       = trim($_POST['addr_city']       ?? '');  // 시/도
$addr_district   = trim($_POST['addr_district']   ?? '');  // 시/군/구
$location_detail = trim($_POST['location_detail'] ?? '');  // 상세주소
$open_time       = trim($_POST['start_time']      ?? '');  // 영업 시작 시간
$close_time      = trim($_POST['end_time']        ?? '');  // 영업 종료 시간

// 3. 필수값 체크
if (
    $shelter_id === '' || $password === '' || $shelter_name === '' ||
    $phone === '' || $addr_city === '' || $addr_district === '' ||
    $location_detail === '' || $open_time === '' || $close_time === ''
) {
    echo "<script>alert('입력되지 않은 값이 있습니다. 모든 필드를 채워주세요.'); history.back();</script>";
    exit;
}

// 4. 전화번호 형식 간단 체크 (010-1234-5678)
//    DDL에서도 같은 형식으로 CHECK 걸려 있어서, 여기서도 한 번 더 확인해줌.
if (!preg_match('/^010-[0-9]{4}-[0-9]{4}$/', $phone)) {
    echo "<script>alert('전화번호는 010-1234-5678 형식으로 입력해주세요.'); history.back();</script>";
    exit;
}

// 5. <input type="time"> 값은 'HH:MM' 형식으로 들어옴 → 그대로 사용
//    (DDL에서 open_time, close_time은 VARCHAR2(5) 이고, REGEXP로 HH:MM 체크)

// 6. 비밀번호 해시 (길이 100 → 해시 저장용)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 7. Oracle DB 접속 정보  ★ 이 부분은 민기가 직접 계정/비번 맞게 수정
$db_username = 'C093299';            // sqlplus 아이디
$db_password = 'TEST1234';  // sqlplus 비밀번호
$db_conn_str = '203.249.87.57/orcl'; // 호스트/서비스명

// 8. Oracle 접속
$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    $msg = isset($e['message']) ? $e['message'] : 'DB 접속 오류';
    echo "<script>alert('DB 접속에 실패했습니다.\\n{$msg}'); history.back();</script>";
    exit;
}

try {
    // 자동 커밋 막고 수동 커밋/롤백 사용

    // 9. REGION에서 (city, district)로 region_id 조회
    $sql_region_select = "
        SELECT region_id
        FROM REGION
        WHERE city = :city
          AND district = :district
    ";

    $stmt_region_select = oci_parse($conn, $sql_region_select);
    oci_bind_by_name($stmt_region_select, ':city', $addr_city);
    oci_bind_by_name($stmt_region_select, ':district', $addr_district);

    if (!oci_execute($stmt_region_select, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt_region_select);
        throw new Exception('지역 조회 중 오류가 발생했습니다: ' . $e['message']);
    }

    $row = oci_fetch_assoc($stmt_region_select);

    if ($row) {
        // 이미 REGION에 있는 경우
        $region_id = $row['REGION_ID'];
    } else {
        // REGION에 없으면 새로 INSERT + 생성된 region_id 받기
        $sql_region_insert = "
            INSERT INTO REGION (city, district)
            VALUES (:city, :district)
            RETURNING region_id INTO :region_id
        ";

        $stmt_region_insert = oci_parse($conn, $sql_region_insert);
        oci_bind_by_name($stmt_region_insert, ':city', $addr_city);
        oci_bind_by_name($stmt_region_insert, ':district', $addr_district);

        $region_id = 0;
        oci_bind_by_name($stmt_region_insert, ':region_id', $region_id, 10);

        if (!oci_execute($stmt_region_insert, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt_region_insert);

            // UNIQUE (city, district) 충돌 시 (동시에 같은 지역 INSERT 하는 경우)
            if (strpos($e['message'], 'ORA-00001') !== false) {
                // 다시 SELECT 해서 region_id 가져오기
                oci_execute($stmt_region_select, OCI_NO_AUTO_COMMIT);
                $row2 = oci_fetch_assoc($stmt_region_select);
                if ($row2) {
                    $region_id = $row2['REGION_ID'];
                } else {
                    throw new Exception('지역 정보 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
                }
            } else {
                throw new Exception('지역 등록 중 오류가 발생했습니다: ' . $e['message']);
            }
        }

        oci_free_statement($stmt_region_insert);
    }

    oci_free_statement($stmt_region_select);

    // 10. SHELTER 테이블에 보호소 정보 INSERT
    // DDL 기준: SHELTER(shelter_id, password, name, phone, open_time, close_time, region_id, detail)
    $sql_shelter_insert = "
        INSERT INTO SHELTER (
            shelter_id, password, name, phone,
            open_time, close_time, region_id, detail
        ) VALUES (
            :shelter_id,
            :password,
            :name,
            :phone,
            :open_time,
            :close_time,
            :region_id,
            :detail
        )
    ";

    $stmt_shelter_insert = oci_parse($conn, $sql_shelter_insert);

    oci_bind_by_name($stmt_shelter_insert, ':shelter_id', $shelter_id);
    oci_bind_by_name($stmt_shelter_insert, ':password',   $hashed_password);
    oci_bind_by_name($stmt_shelter_insert, ':name',       $shelter_name);
    oci_bind_by_name($stmt_shelter_insert, ':phone',      $phone);
    oci_bind_by_name($stmt_shelter_insert, ':open_time',  $open_time);
    oci_bind_by_name($stmt_shelter_insert, ':close_time', $close_time);
    oci_bind_by_name($stmt_shelter_insert, ':region_id',  $region_id);
    oci_bind_by_name($stmt_shelter_insert, ':detail',     $location_detail);

    if (!oci_execute($stmt_shelter_insert, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt_shelter_insert);

        // ORA-00001 → PK/UNIQUE 중복 (shelter_id 중복 가능성)
        if (strpos($e['message'], 'ORA-00001') !== false) {
            throw new Exception('이미 사용 중인 보호소 아이디입니다. 다른 아이디를 사용해주세요.');
        } else {
            throw new Exception('보호소 회원가입 중 오류가 발생했습니다: ' . $e['message']);
        }
    }

    // 11. 모든 작업 성공 → 커밋
    oci_commit($conn);

    oci_free_statement($stmt_shelter_insert);
    oci_close($conn);

    echo "<script>alert('보호소 회원가입이 완료되었습니다.'); location.href='../index.html';</script>";
    exit;

} catch (Exception $ex) {
    // 문제 발생 시 롤백 후 에러 메시지 띄우기
    oci_rollback($conn);
    oci_close($conn);

    $err_msg = $ex->getMessage();
    echo "<script>alert('{$err_msg}'); history.back();</script>";
    exit;
}
?>
