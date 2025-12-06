<?php
// 한글 깨짐 방지
header('Content-Type: text/html; charset=UTF-8');

// 1. POST 방식 요청인지 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('잘못된 접근입니다.'); location.href='../index.html';</script>";
    exit;
}

// 2. 폼 데이터 받기 (name 속성은 user-sign-up.html 기준으로 작성)
$user_id       = trim($_POST['user_id']      ?? '');
$password      = $_POST['password']         ?? '';
$name          = trim($_POST['name']        ?? '');
$birth_year    = trim($_POST['birth_year']  ?? '');
$birth_month   = trim($_POST['birth_month'] ?? '');
$birth_day     = trim($_POST['birth_day']   ?? '');
$phone         = trim($_POST['phone']       ?? '');
$gender        = $_POST['gender']           ?? '';
$addr_city     = trim($_POST['addr_city']   ?? '');
$addr_district = trim($_POST['addr_district'] ?? '');

// 3. 필수값 체크
if (
    $user_id === '' || $password === '' || $name === '' ||
    $birth_year === '' || $birth_month === '' || $birth_day === '' ||
    $phone === '' || $gender === '' || $addr_city === '' || $addr_district === ''
) {
    echo "<script>alert('입력되지 않은 값이 있습니다. 모든 필드를 채워주세요.'); history.back();</script>";
    exit;
}

// 4. 생년월일을 YYYY-MM-DD 문자열로 조합
$birth_month = str_pad($birth_month, 2, '0', STR_PAD_LEFT);
$birth_day   = str_pad($birth_day,   2, '0', STR_PAD_LEFT);
$birthdate_str = $birth_year . '-' . $birth_month . '-' . $birth_day;

// 5. 비밀번호 해시 (USERS.password 길이 100 → 보통 해시 저장용)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 6. Oracle DB 접속 정보 (★ 민기가 직접 수정해야 하는 부분)
$db_username = 'C093299';            // sqlplus 아이디
$db_password = 'TEST1234';  // sqlplus 비밀번호
$db_conn_str = '203.249.87.57/orcl'; // 호스트/서비스명

// 7. Oracle 접속
$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    $msg = isset($e['message']) ? $e['message'] : 'DB 접속 오류';
    echo "<script>alert('DB 접속에 실패했습니다.\\n{$msg}'); history.back();</script>";
    exit;
}

try {
    // 자동 커밋 막고 수동 커밋/롤백 사용
    // 8. REGION에서 (city, district)로 region_id 조회
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
        throw new Exception('거주지(region) 조회 중 오류가 발생했습니다: ' . $e['message']);
    }

    $row = oci_fetch_assoc($stmt_region_select);

    if ($row) {
        // 이미 REGION에 존재하는 경우 → 그 region_id 사용
        $region_id = $row['REGION_ID'];
    } else {
        // REGION에 없으면 새로 INSERT 후 생성된 region_id 사용
        // REGION.region_id 는 IDENTITY이므로 city, district만 넣으면 자동 생성
        $sql_region_insert = "
            INSERT INTO REGION (city, district)
            VALUES (:city, :district)
            RETURNING region_id INTO :region_id
        ";

        $stmt_region_insert = oci_parse($conn, $sql_region_insert);
        oci_bind_by_name($stmt_region_insert, ':city', $addr_city);
        oci_bind_by_name($stmt_region_insert, ':district', $addr_district);

        // 반환받을 변수 미리 선언
        $region_id = 0;
        // 네 번째 인자(길이)는 숫자라 크게 의미 없어서 10 정도만 줌
        oci_bind_by_name($stmt_region_insert, ':region_id', $region_id, 10);

        if (!oci_execute($stmt_region_insert, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt_region_insert);

            // 혹시 동시에 같은 city/district가 들어와서 UNIQUE 제약 위반되면
            // ORA-00001 발생할 수 있음 → 그때는 다시 SELECT 해서 region_id 가져오기
            if (strpos($e['message'], 'ORA-00001') !== false) {
                // UNIQUE (city, district) 위반 → 이미 누가 먼저 INSERT 한 상황
                // 다시 SELECT 해서 region_id 얻어오기
                oci_execute($stmt_region_select, OCI_NO_AUTO_COMMIT);
                $row2 = oci_fetch_assoc($stmt_region_select);
                if ($row2) {
                    $region_id = $row2['REGION_ID'];
                } else {
                    throw new Exception('거주지 정보 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
                }
            } else {
                throw new Exception('거주지(region) 등록 중 오류가 발생했습니다: ' . $e['message']);
            }
        }

        oci_free_statement($stmt_region_insert);
    }

    oci_free_statement($stmt_region_select);

    // 9. USERS 테이블에 회원 정보 INSERT
    $sql_user_insert = "
        INSERT INTO USERS (
            user_id, password, name, birthdate, phone, gender, region_id
        ) VALUES (
            :user_id,
            :password,
            :name,
            TO_DATE(:birthdate, 'YYYY-MM-DD'),
            :phone,
            :gender,
            :region_id
        )
    ";

    $stmt_user_insert = oci_parse($conn, $sql_user_insert);

    oci_bind_by_name($stmt_user_insert, ':user_id',    $user_id);
    oci_bind_by_name($stmt_user_insert, ':password',   $hashed_password);
    oci_bind_by_name($stmt_user_insert, ':name',       $name);
    oci_bind_by_name($stmt_user_insert, ':birthdate',  $birthdate_str);
    oci_bind_by_name($stmt_user_insert, ':phone',      $phone);
    oci_bind_by_name($stmt_user_insert, ':gender',     $gender);
    oci_bind_by_name($stmt_user_insert, ':region_id',  $region_id);

    if (!oci_execute($stmt_user_insert, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt_user_insert);

        // ORA-00001 → PK/UNIQUE 중복 (user_id 중복 가능성)
        if (strpos($e['message'], 'ORA-00001') !== false) {
            throw new Exception('이미 사용 중인 아이디입니다. 다른 아이디를 사용해주세요.');
        } else {
            throw new Exception('회원가입 중 오류가 발생했습니다: ' . $e['message']);
        }
    }

    // 모든 작업 성공 → 커밋
    oci_commit($conn);

    oci_free_statement($stmt_user_insert);
    oci_close($conn);

    echo "<script>alert('회원가입이 완료되었습니다.'); location.href='../index.html';</script>";
    exit;

} catch (Exception $ex) {
    // 문제 생기면 롤백 & 연결 종료
    oci_rollback($conn);
    oci_close($conn);

    $err_msg = $ex->getMessage();
    echo "<script>alert('{$err_msg}'); history.back();</script>";
    exit;
}
?>
