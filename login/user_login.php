<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', 1); //개발할 때, 에러를 화면에 보이게.

// 1. POST 요청인지 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('잘못된 접근입니다.(PHP)'); location.href='../index.html';</script>";
    exit;
}

// 2. 폼 데이터 받기
$user_id  = trim($_POST['user_id'] ?? '');
$password = $_POST['password']     ?? '';

if ($user_id === '' || $password === '') {
    echo "<script>alert('아이디/비밀번호를 입력하세요.'); history.back();</script>";
    exit;
}

// 3. DB 접속
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');

if (!$conn) {
    $e   = oci_error();
    $msg = $e['message'] ?? 'DB 접속 오류';
    echo "<script>alert('DB 접속 실패(PHP): \\n{$msg}'); history.back();</script>";
    exit;
}

// 4. 해당 아이디 조회
$sql = "
    SELECT user_id, password, name
    FROM USERS
    WHERE user_id = :sid
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $user_id);

if (!oci_execute($stmt)) {
    $e   = oci_error($stmt);
    $msg = $e['message'] ?? '쿼리 실행 오류';
    echo "<script>alert('쿼리 오류(PHP): \\n{$msg}'); history.back();</script>";
    exit;
}

$row = oci_fetch_assoc($stmt);

if (!$row) {
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('존재하지 않는 아이디'); history.back();</script>";
    exit;
}

// 5. 비밀번호 확인
$db_hash = $row['PASSWORD'];

// 🔥 여기서 DB에 해시 저장 여부에 따라 방식 달라짐
// (1) 해시 저장했다면:
if (!password_verify($password, $db_hash)) {
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('올바르지 않은 비밀번호'); history.back();</script>";
    exit;
}

/*
// (2) 만약 DB에 평문 비밀번호 저장해둔 상태라면, 임시로 이렇게 테스트 가능
if ($password !== $db_hash) {
    ...
}
*/

// 6. 로그인 성공
$_SESSION['user_id']   = $row['USER_ID'];  // 컬럼명 수정
$_SESSION['user_name'] = $row['NAME'];

oci_free_statement($stmt);
oci_close($conn);

echo "<script>alert('로그인 성공!'); window.location.href = '../user/dog_list.php';</script>";
exit;
?>
