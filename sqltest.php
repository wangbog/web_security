</pre>
<pre class="php" name="code"><!DOCTYPE html>
<html><body>
	<form method="GET" action="">
        <table>
            <tr>
                <td>测试1<td>
                <td>查询用户名(字符类型):<input type="text" name="name1" size="100" value="Tony"><td>
                <td><input type="submit" name="test1" value="查询"><td>
                <td>正常情况：预期根据用户输入查询用户，无SQL注入的情况<td>
            <tr>
            <tr>
                <td>测试2<td>
                <td>查询用户名(字符类型):<input type="text" name="name2" size="100" value="aaa' OR 1=1 #"><td>
                <td><input type="submit" name="test2" value="查询"><td>
                <td>注入：最简单的注入方法，1=1永远满足，#是为了把后面的SQL语句标为注释<td>
            <tr>
            <tr>
                <td>测试3<td>
                <td>查询id(数字类型):<input type="text" name="id3" size="100" value="1 OR 1=1"><td>
                <td><input type="submit" name="test3" value="查询"><td>
                <td>注入：最简单的注入方法（数字类型），1=1永远满足，#都省了<td>
            <tr>
            <tr>
                <td>测试4<td>
                <td>查询id(数字类型):<input type="text" name="id4" size="100" value="1 OR 1=1"><td>
                <td><input type="submit" name="test4" value="查询"><td>
                <td>推荐解决方案：使用mysqli prepared statement<td>
            <tr>
            <tr>
                <td>测试5<td>
                <td>查询id(数字类型):<input type="text" name="id5" size="100" value="1 OR 1=1"><td>
                <td><input type="submit" name="test5" value="查询"><td>
                <td>推荐解决方案：使用PDO prepared statement<td>
            <tr>
            <tr>
                <td>测试6<td>
                <td>查询id(数字类型):
                    <input type="text" name="id6" size="100" value="1 union select * from person"><br>
                    <input type="text" name="id6a" size="100"
                           value="1;set @sqlcmd = CONCAT('sele', 'ct * from person'); prepare stmt_hack from @sqlcmd;execute stmt_hack;"><td>
                <td><input type="submit" name="test6" value="查询"><td>
                <td>注入：企图过滤SQL关键字（过滤掉了）<td>
            <tr>
        </table>

	</form>
	<?php
    $servername = "localhost";
    $username = "root";
    $password = "zaq1@WSX";
    $dbname = "mytest";

    if (isset($_GET['test1'])) {
        $user = $_GET["name1"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE name = '$user'";
        echo "\n测试1结果：\n";
        echo $sql . "\n";
        $result = $conn->query($sql);
        echo__result($result);
        $conn->close();
    }
    if (isset($_GET['test2'])) {
        $user = $_GET["name2"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE name = '$user'";
        echo "\n测试2结果：\n";
        echo $sql . "\n";
        $result = $conn->query($sql);
        echo__result($result);
        $conn->close();

        $user = $_GET["name2"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $user = $conn->real_escape_string($user);
        $sql = "SELECT * FROM person WHERE name = '$user'";
        echo "\n测试2解决：使用real_escape_string()过滤特殊字符\n";
        echo $sql . "\n";
        $result = $conn->query($sql);
        echo__result($result);
        $conn->close();
    }
    if (isset($_GET['test3'])) {
        $id = $_GET["id3"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE id = $id";
        echo "\n测试3结果：\n";
        echo $sql . "\n";
        $result = $conn->query($sql);
        echo__result($result);
        $conn->close();

        $id = $_GET["id3"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $id = $conn->real_escape_string($id);
        $sql = "SELECT * FROM person WHERE id = $id";
        echo "\n测试3解决：real_escape_string()已经无用了，因为SQL语句中根本没有特殊字符\n";
        echo $sql . "\n";
        $result = $conn->query($sql);
        echo__result($result);
        $conn->close();

        $id = (int)$_GET["id3"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE id = $id";
        echo "\n测试3解决：比较简单的方案，是将数字类型的值转为int\n";
        echo $sql . "\n";
        $result = $conn->query($sql);
        echo__result($result);
        $conn->close();
    }
    if (isset($_GET['test4'])) {
        $id = $_GET["id4"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        echo "\n测试4结果：\n";
        echo $sql . "\n";
        $stmt->execute();
        $result = $stmt->get_result();
        echo__result($result);
        $conn->close();
    }
    if (isset($_GET['test5'])) {
        $id = $_GET["id5"];
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $sql = "SELECT * FROM person WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        echo "\n测试5结果：\n";
        echo $sql . "\n";
        $stmt->execute();
        $result = $stmt->fetchAll();
        print_r($result);
        $conn = null;
    }
    if (isset($_GET['test6'])) {
        $id = $_GET["id6"];
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE id = $id";
        echo "\n测试6结果：\n";
        echo $sql . "\n";
        $result = $conn->multi_query($sql);
        if ($result) {
            do {
                if ($mysqli_result = $conn->use_result()) {
                    print_r($mysqli_result->fetch_all(MYSQLI_ASSOC));
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            echo "error " . $conn->errno . " : " . $conn->error;
        }
        $conn->close();

        $id = $_GET["id6"];
        $id = filterSQL($id);
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE id = $id";
        echo "\n测试6解决（常规方法）：\n";
        echo $sql . "\n";
        $result = $conn->multi_query($sql);
        if ($result) {
            do {
                if ($mysqli_result = $conn->use_result()) {
                    print_r($mysqli_result->fetch_all(MYSQLI_ASSOC));
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            echo "error " . $conn->errno . " : " . $conn->error;
        }
        $conn->close();

        $id = $_GET["id6a"];
        $id = filterSQL($id);
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8");
        $sql = "SELECT * FROM person WHERE id = $id";
        echo "\n\n测试6结果(通过构造prepare语句)：\n";
        echo $sql . "\n";
        $result = $conn->multi_query($sql);
        if ($result) {
            do {
                if ($mysqli_result = $conn->use_result()) {
                    print_r($mysqli_result->fetch_all(MYSQLI_ASSOC));
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            echo "error " . $conn->errno . " : " . $conn->error;
        }
        $conn->close();
    }


    function echo__result($result)
    {
        if (!empty($result) && $result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                echo "id: " . $row["id"] . " - Name: " . $row["name"] . " - Address: " . $row["address"] . "<br>";
            }
        } else {
            echo "0 results";
        }
    }


    function filterSQL($param)
    {
        if (empty($param)) return false;
        $param = str_replace('select', "hacker", $param);
        $param = str_replace('union', "hacker", $param);
        return $param;
    }

    ?>

</body></html>
