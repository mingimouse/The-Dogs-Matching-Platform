<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// 디버그용 (문제 있으면 에러 화면에서 바로 보이게)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. POST 요청인지 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('잘못된 접근입니다.(PHP)'); location.href='../index.html';</script>";
    exit;
}

// 2. 폼 데이터 받기
$shelter_id = trim($_POST['shelter_id'] ?? '');
$password   = $_POST['password']      ?? '';

if ($shelter_id === '' || $password === '') {
    echo "<script>alert('아이디/비밀번호를 입력하세요.'); history.back();</script>";
    exit;
}

// 3. DB 접속 정보 (★ 비밀번호는 네 계정으로 수정)
$db_username = 'C093299';
$db_password = 'TEST1234';  // 네가 회원가입에서 쓰는 거랑 동일하게 맞추기
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    $msg = $e['message'] ?? 'DB 접속 오류';
    echo "<script>alert('DB 접속 실패(PHP): \\n{$msg}'); history.back();</script>";
    exit;
}

// 4. 해당 아이디 조회
$sql = "
    SELECT shelter_id, password, name
    FROM SHELTER
    WHERE shelter_id = :sid
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $shelter_id);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    $msg = $e['message'] ?? '쿼리 실행 오류';
    echo "<script>alert('쿼리 오류(PHP): \\n{$msg}'); history.back();</script>";
    exit;
}

$row = oci_fetch_assoc($stmt);

if (!$row) {
    // 5-1. 아이디 없음
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('존재하지 않는 아이디'); history.back();</script>";
    exit;
}

// 5-2. 비밀번호 확인
$db_hash = $row['PASSWORD'];   // SHELTER 테이블에 저장된 해시값

// ★ 해시 비교
if (!password_verify($password, $db_hash)) {
    oci_free_statement($stmt);
    oci_close($conn);
    echo "<script>alert('올바르지 않은 비밀번호'); history.back();</script>";
    exit;
}

// 6. 여기까지 왔으면 진짜 로그인 성공
$_SESSION['shelter_id']   = $row['SHELTER_ID'];
$_SESSION['shelter_name'] = $row['NAME'];

oci_free_statement($stmt);
oci_close($conn);

echo "<script>alert('로그인 성공!'); window.location.href = '../shelter/shelter_info.php';</script>";
exit;
?>
