<?php
session_start();

/* 1) 로그인 체크 */
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='../login/user-login.html';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

/* 2) dog_id 파라미터 체크 */
if (!isset($_GET['dog_id'])) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$dog_id = (int)$_GET['dog_id'];

/* 3) DB 접속 */
$db_username = 'C093299';
$db_password = 'TEST1234';
$db_conn_str = '203.249.87.57/orcl';

$conn = @oci_connect($db_username, $db_password, $db_conn_str, 'AL32UTF8');
if (!$conn) {
    $e   = oci_error();
    $msg = isset($e['message']) ? $e['message'] : 'DB 접속 오류';
    echo "<script>alert('DB 접속에 실패했습니다.\\n{$msg}'); history.back();</script>";
    exit;
}

/*
    ADOPTION_REQUEST DDL (참고)

    CREATE TABLE ADOPTION_REQUEST (
        request_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        dog_id     NUMBER(5)     NOT NULL,
        user_id    VARCHAR2(20)  NOT NULL,
        datetime   DATE DEFAULT SYSDATE NOT NULL,
        status     VARCHAR2(20) DEFAULT 'PENDING' NOT NULL,
        ...
        CONSTRAINT uq_request_dog_user UNIQUE (dog_id, user_id)
    );

    → request_id, datetime, status 는 DEFAULT/IDENTITY 이라서
      INSERT 시 dog_id, user_id 만 넣으면 됨.
*/

/* 4) INSERT 실행 */
$sql = "
    INSERT INTO ADOPTION_REQUEST (dog_id, user_id)
    VALUES (:dog_id, :user_id)
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':dog_id', $dog_id);
oci_bind_by_name($stmt, ':user_id', $user_id);

if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stmt);
    oci_rollback($conn);

    $msg = $e['message'] ?? '';

    // 이미 이 강아지에 대해 같은 회원이 신청한 경우
    // UNIQUE 제약조건 uq_request_dog_user 위반(ORA-00001)
    if (strpos($msg, 'UQ_REQUEST_DOG_USER') !== false ||
        strpos($msg, 'uq_request_dog_user') !== false) {

        oci_free_statement($stmt);
        oci_close($conn);

        echo "<script>
                alert('이미 이 강아지에 대해 입양 신청을 하셨습니다.');
                history.back();
              </script>";
        exit;
    }

    // 그 외 DB 오류
    $safe_msg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    oci_free_statement($stmt);
    oci_close($conn);

    echo "<script>
            alert('입양 신청 중 오류가 발생했습니다.\\n{$safe_msg}');
            history.back();
          </script>";
    exit;
}

/* 5) 커밋 후 마무리 */
oci_commit($conn);
oci_free_statement($stmt);
oci_close($conn);

// 성공 시 안내 후 결과 페이지로 이동 (원하면 dog_list.php로 바꿔도 됨)
echo "<script>
        alert('입양 신청이 완료되었습니다!\\n심사 결과는 \"입양 심사 결과\" 페이지에서 확인하실 수 있습니다.');
        location.href = 'adoption_result.php';
      </script>";
exit;
