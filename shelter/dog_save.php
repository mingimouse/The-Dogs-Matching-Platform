<?php
// dog_save.php : 강아지 등록 / 수정 / 삭제 처리

session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['shelter_id'])) {
    echo "<script>alert('로그인 후 이용해주세요.'); location.href='../login/shelter_login.html';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('잘못된 요청입니다.'); location.href='dog_list.php';</script>";
    exit;
}

$shelter_id = $_SESSION['shelter_id'];
$mode       = $_POST['mode'] ?? 'insert';

// DB 연결
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die('DB 접속 실패: ' . htmlspecialchars($e['message']));
}

// ===== 삭제 처리 =====
if ($mode === 'delete') {
    $dog_id = isset($_POST['dog_id']) ? (int)$_POST['dog_id'] : 0;
    if ($dog_id <= 0) {
        echo "<script>alert('삭제할 대상을 찾을 수 없습니다.'); history.back();</script>";
        exit;
    }

    $sql = "DELETE FROM DOG WHERE dog_id = :dog_id AND shelter_id = :sid";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':dog_id', $dog_id);
    oci_bind_by_name($stmt, ':sid', $shelter_id);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        oci_rollback($conn);
        echo "<script>alert('삭제 중 오류가 발생했습니다.'); history.back();</script>";
        exit;
    }

    oci_commit($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    echo "<script>alert('삭제되었습니다.'); location.href='dog_list.php';</script>";
    exit;
}

// ===== 등록/수정 공통 파라미터 =====
$dog_id          = isset($_POST['dog_id']) ? (int)$_POST['dog_id'] : 0;
$name            = trim($_POST['dog_name']  ?? '');
$breed           = trim($_POST['dog_breed'] ?? '');
$age_raw         = trim($_POST['dog_age']   ?? '');
$gender_kor      = trim($_POST['dog_gender'] ?? '');
$color           = trim($_POST['dog_color'] ?? '');
$weight_raw      = trim($_POST['dog_weight'] ?? '');
$current_img_url = trim($_POST['current_image_url'] ?? '');

// 필수값 체크 (간단히)
if ($name === '' || $breed === '' || $age_raw === '' || $gender_kor === '' || $weight_raw === '') {
    echo "<script>alert('이름, 품종, 나이, 성별, 몸무게는 필수 입력입니다.'); history.back();</script>";
    exit;
}

// 숫자 처리 (나이: 정수, 몸무게: 소수 1자리)
$age = preg_replace('/[^0-9]/', '', $age_raw);
$age = ($age === '') ? null : (int)$age;

$weight_str = preg_replace('/[^0-9\.]/', '', $weight_raw);
$weight = ($weight_str === '') ? null : (float)$weight_str;

if ($age === null || $weight === null) {
    echo "<script>alert('나이와 몸무게는 숫자만 입력해주세요.'); history.back();</script>";
    exit;
}

// 성별 매핑
if ($gender_kor === '수컷') {
    $gender = 'M';
} elseif ($gender_kor === '암컷') {
    $gender = 'F';
} else {
    echo "<script>alert('성별 선택이 올바르지 않습니다.'); history.back();</script>";
    exit;
}

// 상태 기본값 (처음엔 보호 중)
$status = 'IN_CARE';

// ===== 이미지 업로드 처리 =====
$image_url_db = $current_img_url ?: null;

if (isset($_FILES['dog_image']) && $_FILES['dog_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../dog-img';

    // 확장자 추출
    $orig_name = $_FILES['dog_image']['name'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        echo "<script>alert('이미지 파일(jpg, png, gif, webp)만 업로드 가능합니다.'); history.back();</script>";
        exit;
    }

    // 파일명 생성
    $new_name = 'dog_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $target_fs_path = $upload_dir . '/' . $new_name;   // 서버 경로
    $web_path       = '../dog-img/' . $new_name;       // 브라우저에서 사용할 경로

    if (!move_uploaded_file($_FILES['dog_image']['tmp_name'], $target_fs_path)) {
        echo "<script>alert('이미지 업로드에 실패했습니다.'); history.back();</script>";
        exit;
    }

    $image_url_db = $web_path;
}

// ===== INSERT / UPDATE 구분 =====
try {
    if ($mode === 'insert') {

        $sql = "
            INSERT INTO DOG
                (shelter_id, name, breed, age, gender, color, weight, image_url, status)
            VALUES
                (:sid, :name, :breed, :age, :gender, :color, :weight, :image_url, :status)
        ";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':sid',        $shelter_id);
        oci_bind_by_name($stmt, ':name',       $name);
        oci_bind_by_name($stmt, ':breed',      $breed);
        oci_bind_by_name($stmt, ':age',        $age);
        oci_bind_by_name($stmt, ':gender',     $gender);
        oci_bind_by_name($stmt, ':color',      $color);
        oci_bind_by_name($stmt, ':weight',     $weight);
        oci_bind_by_name($stmt, ':image_url',  $image_url_db);
        oci_bind_by_name($stmt, ':status',     $status);

    } elseif ($mode === 'update' && $dog_id > 0) {

        $sql = "
            UPDATE DOG
            SET
                name      = :name,
                breed     = :breed,
                age       = :age,
                gender    = :gender,
                color     = :color,
                weight    = :weight,
                image_url = :image_url
            WHERE dog_id = :dog_id
              AND shelter_id = :sid
        ";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':name',       $name);
        oci_bind_by_name($stmt, ':breed',      $breed);
        oci_bind_by_name($stmt, ':age',        $age);
        oci_bind_by_name($stmt, ':gender',     $gender);
        oci_bind_by_name($stmt, ':color',      $color);
        oci_bind_by_name($stmt, ':weight',     $weight);
        oci_bind_by_name($stmt, ':image_url',  $image_url_db);
        oci_bind_by_name($stmt, ':dog_id',     $dog_id);
        oci_bind_by_name($stmt, ':sid',        $shelter_id);

    } else {
        echo "<script>alert('잘못된 요청입니다.'); history.back();</script>";
        exit;
    }

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        oci_rollback($conn);
        throw new Exception($e['message']);
    }

    oci_commit($conn);
    oci_free_statement($stmt);
    oci_close($conn);

    $msg = ($mode === 'insert') ? '등록이 완료되었습니다.' : '수정이 완료되었습니다.';
    echo "<script>alert('{$msg}'); location.href='dog_list.php';</script>";
    exit;

} catch (Exception $e) {
    oci_rollback($conn);
    oci_close($conn);
    $err = htmlspecialchars($e->getMessage());
    echo "<script>alert('저장 중 오류가 발생했습니다.\\n{$err}'); history.back();</script>";
    exit;
}
?>
