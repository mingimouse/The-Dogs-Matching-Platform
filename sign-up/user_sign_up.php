<?php
// ============================
// DB 접속 정보 (환경에 맞게 수정)
// ============================
$db_username = "C093299";        // 오라클 계정
$db_password = "TEST1234"; // 오라클 비밀번호
$db_connection_string = "203.249.87.57/orcl"; // 호스트/서비스명

// 한글 깨짐 방지
mb_internal_encoding("UTF-8");

// 폼으로 직접 들어온 게 아니면 막기
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-sign-up.html');
    exit;
}

// 1) 폼 데이터 받기
$user_id       = trim($_POST['user_id'] ?? '');
$password      = trim($_POST['password'] ?? '');
$name          = trim($_POST['name'] ?? '');
$birth_year    = trim($_POST['birth_year'] ?? '');
$birth_month   = trim($_POST['birth_month'] ?? '');
$birth_day     = trim($_POST['birth_day'] ?? '');
$phone_input   = trim($_POST['phone'] ?? '');
$gender        = trim($_POST['gender'] ?? '');
$addr_city     = trim($_POST['addr_city'] ?? '');
$addr_district = trim($_POST['addr_district'] ?? '');

// 간단한 서버측 유효성 체크 (필요시 더 추가)
if ($user_id === '' || $password === '' || $name === '' ||
    $birth_year === '' || $birth_month === '' || $birth_day === '' ||
    $phone_input === '' || $gender === '' || $addr_city === '' || $addr_district === '') {
    exit('필수 값을 모두 입력해주세요.');
}

// 2) 생일 문자열 만들기 (YYYY-MM-DD)
$birth_month = str_pad($birth_month, 2, '0', STR_PAD_LEFT);
$birth_day   = str_pad($birth_day, 2, '0', STR_PAD_LEFT);
$birthdate_str = $birth_year . '-' . $birth_month . '-' . $birth_day;

// 3) 전화번호 하이픈 처리 (010-1234-5678 형식 맞추기)
$digits = preg_replace('/[^0-9]/', '', $phone_input); // 숫자만 추출
if (preg_match('/^010\d{8}$/', $digits)) {
    $phone = '010-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
} else {
    exit('전화번호는 010으로 시작하는 11자리여야 합니다. (예: 01012345678)');
}

// 4) 비밀번호 해시 (DB에는 해시값 저장)
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
    // auto-commit 끄기
    oci_execute(oci_parse($conn, "BEGIN NULL; END;"), OCI_NO_AUTO_COMMIT);

    // 5) user_id 중복 체크
    $sql_check_id = "SELECT COUNT(*) AS CNT FROM USERS WHERE user_id = :user_id";
    $stmt_check = oci_parse($conn, $sql_check_id);
    oci_bind_by_name($stmt_check, ':user_id', $user_id);
    oci_execute($stmt_check);
    $row = oci_fetch_assoc($stmt_check);

    if ($row['CNT'] > 0) {
        oci_rollback($conn);
        exit('이미 사용 중인 아이디입니다.');
    }

    // 6) REGION 테이블에서 region_id 찾기 (없으면 INSERT 후 가져오기) :contentReference[oaicite:1]{index=1}
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

    // 7) USERS 테이블 INSERT :contentReference[oaicite:2]{index=2}
    $sql_ins_user = "
        INSERT INTO USERS (user_id, password, name, birthdate, phone, gender, region_id)
        VALUES (:user_id, :password, :name, TO_DATE(:birthdate, 'YYYY-MM-DD'),
                :phone, :gender, :region_id)
    ";

    $stmt_ins_user = oci_parse($conn, $sql_ins_user);
    oci_bind_by_name($stmt_ins_user, ':user_id', $user_id);
    oci_bind_by_name($stmt_ins_user, ':password', $hashed_password);
    oci_bind_by_name($stmt_ins_user, ':name', $name);
    oci_bind_by_name($stmt_ins_user, ':birthdate', $birthdate_str);
    oci_bind_by_name($stmt_ins_user, ':phone', $phone);
    oci_bind_by_name($stmt_ins_user, ':gender', $gender);
    oci_bind_by_name($stmt_ins_user, ':region_id', $region_id);

    if (!oci_execute($stmt_ins_user, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt_ins_user);
        oci_rollback($conn);
        exit("회원 정보 저장 중 오류: " . $e['message']);
    }

    // 8) 커밋
    oci_commit($conn);

    // 9) 가입 완료 후 이동 (원하는 페이지로 수정)
    echo "<script>alert('회원가입이 완료되었습니다. 로그인 페이지로 이동합니다.');";
    echo "location.href='../index.html';</script>";
    exit;

} catch (Exception $e) {
    oci_rollback($conn);
    exit("오류가 발생했습니다: " . $e->getMessage());
} finally {
    oci_close($conn);
}
