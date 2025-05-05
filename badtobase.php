<?php
// Если файл не является валидным XML
//Проверим нет ли этой книги в базе
$sqlcheck = "SELECT * FROM badbooks WHERE path = ? OR title = ?";
$stmtcheck = $pdo->prepare($sqlcheck);
$stmtcheck->execute([$filePath, $fileName]);
$check = $stmtcheck->fetch(PDO::FETCH_ASSOC);
if ($check) {
    echo "Книга уже есть в базе";
}
else{
    $sql = "INSERT INTO badbooks(tit le,path,error) VALUES(?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fileName, $filePath, $e->getMessage()]);
    echo "Добавлена";
}
?>