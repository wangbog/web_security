# 安全问题：php代码sql注入问题浅析及解决

PHP作为弱类型语言，在早期Web程序由于编写不规范，较容易出现SQL注入问题。本文提供几个示例程序简单介绍SQL注入是如何产生的，以及常见的修复方法。本文用到的代码，可参考：[sqltest.php](https://github.com/wangbog/php_sql_injection/blob/master/sqltest.php)

### 测试1：理想中的正常情况，即预期根据用户输入查询数据库表，假定无SQL注入的情况
先看代码，页面表单部分：
```
<td>查询用户名(字符类型):<input type="text" name="name1" size="100" value="Tony"><td>
<td><input type="submit" name="test1" value="查询"><td>
```
后台代码部分：
```
$user = $_GET["name1"];
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");
$sql = "SELECT * FROM person WHERE name = '$user'";
echo "\n测试1结果：\n";
echo $sql . "\n";
$result = $conn->query($sql);
echo__result($result);
$conn->close();
```
这是我们理想中的正常情况，使用字符串拼接的方式，将用户在页面通过GET方式提交的name1变量，作为SQL查询的条件。这里假定用户输入的是字符串"Tony"（无双引号），那么拼接的SQL语句见下面的运行结果，一切看上去都是正常的，我们也可以查询到名为Tony的这个人物。

测试1结果：
```
SELECT * FROM person WHERE name = 'Tony'
id: 4 - Name: Tony - Address: No.1 Building
```

### 测试2：最简单的注入方法
上面的例子看上去很好，但其实是存在SQL注入漏洞的，如果黑客在输入框中巧妙地构造一下提交的数据，比如改为
```
aaa' OR 1=1 #
```
首先使用aaa'闭合之前的查询，再用OR 1=1实现永远满足的条件，#是为了把后面的SQL语句标为注释。后台代码部分没有任何改动，我们发现测试2的结果会变为如下的样子，发现这张person表被该用户攻破了，所有数据都被显示了出来。
```
SELECT * FROM person WHERE name = 'aaa' OR 1=1 #'
id: 1 - Name: 王博 - Address: 理科一号楼
id: 2 - Name: 张三 - Address: 理科二号楼
id: 3 - Name: 李四 - Address: 理科三号楼
id: 4 - Name: Tony - Address: No.1 Building
id: 5 - Name: Amy - Address: No.2 Building
```

那么要如何修复这个问题呢？可以参考这段代码，将通过$_GET获取到的用户输入变量，利用real_escape_string()方法，过滤掉其中的特殊字符。
```
$user = $conn->real_escape_string($user);
$sql = "SELECT * FROM person WHERE name = '$user'";
```
请注意这里的SQL语句，其中黑客输入的单引号被转义了，语句和查询结果如下。这样查询到0条结果，是安全的。
```
SELECT * FROM person WHERE name = 'aaa\' OR 1=1 #'
0 results
```

> 特别注意！！！

>  咱们这里使用的mysqli::real_escape_string()为新版本PHP提供给mysqli的方法，该方法是安全的，它会保证考虑到了当前连接使用的charset。有些老版本的PHP，使用mysql_real_escape_string()方法可以达到类似的目的（该方法在PHP 5.5之后的版本已过期，并在PHP 7中被移除），但千万记得该方法不能保证客户端和mysql服务端是用的同一套charset，因此黑客可能会利用不同的charset之间的转换机制构造某些更特殊的输入参数提交给服务端，进而攻破real_escape_string()的保护。因此建议使用该方法的用户，参考这篇补充材料中的说明，进一步地防护：https://stackoverflow.com/a/12118602


### 测试3：数字类型参数的注入
上面咱们讲了字符类型参数的注入，这里再看一下数字类型参数的注入。我们的代码改了一下：
```
<td>查询id(数字类型):<input type="text" name="id3" size="100" value="1"><td>
<td><input type="submit" name="test3" value="查询"><td>
```
这里提交的是数字类型的id3变量了。后台代码也调整如下：
```
$id = $_GET["id3"];
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");
$id = $conn->real_escape_string($id);
$sql = "SELECT * FROM person WHERE id = $id";
echo "\n测试3：real_escape_string()已经无用了，因为SQL语句中根本没有特殊字符\n";
echo $sql . "\n";
$result = $conn->query($sql);
echo__result($result);
$conn->close();
```
如果黑客在输入框中这样构造提交的数据
```
1 OR 1=1
```
我们发现，上述的mysqli::real_escape_string()方法就不管用了，因为这里就没有特殊字符，运行结果如下：
```
SELECT * FROM person WHERE id = 1 OR 1=1
id: 1 - Name: 王博 - Address: 理科一号楼
id: 2 - Name: 张三 - Address: 理科二号楼
id: 3 - Name: 李四 - Address: 理科三号楼
id: 4 - Name: Tony - Address: No.1 Building
id: 5 - Name: Amy - Address: No.2 Building
```
那么对于数字类型的注入，如何解决呢？最简单的方案是：后台处理时转型为数字类型即可，如：
```
$id = (int)$_GET["id3"];
//$id = $conn->real_escape_string($id);
```
mysqli::real_escape_string()方法也无需调用了(留在这里也无妨)。结果是安全的：
```
SELECT * FROM person WHERE id = 1
id: 1 - Name: 王博 - Address: 理科一号楼
```

### 测试4：解决方案大招 - mysqli prepared statement
以上方案虽然可以解决问题，但并不是主流的方案，而且这种添加转换的方式也很容易遗忘。其实目前主流的解决方案是使用prepared statement进行预编译，这里mysql会帮我们处理好剩下的一切。

还是看代码，这里bind_param方法，第一个参数为类型，i代表数字类型。具体取值范围大家参考一下官方文档：https://www.php.net/manual/en/mysqli-stmt.bind-param.php
```
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
```
运行结果是安全的：
```
SELECT * FROM person WHERE id = ?
id: 1 - Name: 王博 - Address: 理科一号楼
```

### 测试5：解决方案大招 - PDO prepared statement
PDO是当前流行的另外一大数据库连接方式（关于mysqli vs PDO的对比，大家可以自行查阅资料，不在本文讨论范围），也可以使用prepared statement。代码如下：
```
$id = $_GET["id5"];
$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
$sql = "SELECT * FROM person WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam ( ':id' ,  $id );
echo "\n测试5结果：\n";
echo $sql . "\n";
$stmt->execute();
$result = $stmt->fetchAll();
print_r($result);
$conn = null;
```
运行结果也是安全的：
```
SELECT * FROM person WHERE id = :id
Array
(
    [0] => Array
        (
            [id] => 1
            [0] => 1
            [name] => 王博
            [1] => 王博
            [address] => 理科一号楼
            [2] => 理科一号楼
        )

)
```

### 测试6：不完善的改法

网上经常有介绍一种防SQL注入的做法，即输入参数过滤掉诸如SELECT、UNION等关键字。注：为了演示方便，此测试有一个小前提，即后端代码允许一次执行多条SQL语句，即执行的查询语句为$conn->multi_query($sql)。如：

```
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
```

还是以上面的数字类型id的SQL查询语句为例，假如黑客构造的输入参数为：

```
1 union select * from person
```

这样是可以通过联合查询查出全部数据的，最终的SQL语句为：

```
SELECT * FROM person WHERE id = 1 union select * from person
```

过滤关键字的防范手段，可以类似这样（为了展示只过滤了select和union，并且只过滤了小写字母。真实情况中应过滤所有可能的关键词，并考虑大小写情况）：

```
$id = $_GET["id6"];
$id = filterSQL($id);
……
function filterSQL($param)
{
    if (empty($param)) return false;
    $param = str_replace('select', "hacker", $param);
    $param = str_replace('union', "hacker", $param);
    return $param;
}
```

这样看上去可以解决该问题，返回结果报错：

```
SELECT * FROM person WHERE id = 1 hacker hacker * from person
error 1064 : You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'hacker hacker * from person' at line 1
```

但是，黑客其实可以利用SQL自身的PREPARE机制和SQL自带的CONCAT函数，将select等关键字拆分（命中不了上述filterSQL函数的过滤规则了），然后执行这个prepare statement！比如这样构造输入参数：

```
1;set @sqlcmd = CONCAT('sele', 'ct * from person'); prepare stmt_hack from @sqlcmd;execute stmt_hack;
```

则最终的SQL语句如下，是可以查询出所有数据的！

```
SELECT * FROM person WHERE id = 1;set @sqlcmd = CONCAT('sele', 'ct * from person'); prepare stmt_hack from @sqlcmd;execute stmt_hack;
```

所以，这种过滤SQL关键词的方法，不能很好地防范所有SQL注入问题。

### 补充教程：使用SQLMAP自动发现注入漏洞！！

上面讲的是手工发现SQL注入点，结合代码进行修复的过程。如果您想知道如何自动检测自己的网站是否可能有SQL注入漏洞，这里介绍一下SQLMAP。SQLMAP是著名的SQL注入自动探测工具，其实质是把我们上面讲的规则做了自动化，当然不只是我们讲的这些，它包含了大量的SQL注入数据库，功能非常强大。

**注意：请不要随意使用SQLMAP，在未通知目标网站管理员的情况下使用SQLMAP是违法行为！！！**

首先，需要人工访问一下目标网站，找到POST或GET获取用户提交参数的页面，这一步主要依赖经验的积累（白盒测试的话就简单了）。然后使用sqlmap探测该url是否存在注入点：
```
python sqlmap.py -u "http://localhost/sqltest.php?test1=查询&&name1=Tony" --level 5
```
结果看出，确实存在注入点，另外它把我们使用的PHP和数据库版本号都获取到了：
```
[15:15:40] [INFO] testing connection to the target URL
sqlmap resumed the following injection point(s) from stored session:
---
Parameter: name1 (GET)
    Type: AND/OR time-based blind
    Title: MySQL >= 5.0.12 AND time-based blind
    Payload: test1=查询&&name1=Tony' AND SLEEP(5) AND 'LWXo'='LWXo

    Type: UNION query
    Title: Generic UNION query (NULL) - 3 columns
    Payload: test1=查询&&name1=Tony' UNION ALL SELECT CONCAT(0x71717a7871,0x4d6b7a474276646c426b51737776786762645069785077794d58707a545865706467485076535645,0x717a6a7671),NULL,NULL-- pmJX
---
[15:15:40] [INFO] the back-end DBMS is MySQL
web application technology: PHP 7.3.4, Apache 2.4.39
back-end DBMS: MySQL >= 5.0.12
```
然后就可以获取我有哪些张表了：
```
python sqlmap.py -u "http://localhost/sqltest.php?test1=查询&&name1=Tony" --tables
```
结果可以看到我有哪些数据库table：
```
Database: mytest
[1 table]
+----------------------------------------------------+
| person                                             |
+----------------------------------------------------+
```
好了，后面再获取表中的内容也是类似的操作步骤，我们就不一一介绍了。SQLMAP功能很强大，对于登录用户的页面，它还支持预埋一些cookie再进行SQL注入测试。

可以看到我们这段代码还是很危险的，黑客可以轻易地获取、甚至篡改、删除我们数据库表中的内容。所有SQL注入类的问题，还是尽快修复吧！

### 总结及补充
*  SQL注入需要在编写代码时就注意，尽量使用prepared statement
*  注意有些过时的API可能有被利用的风险，应尽早使用新的方案
*  其它语言如Java：Java语言是一种强类型语言，通常收到一个用户提交的参数后我们都会将其转换为对应的类型，这样可以避免很多问题的发生。但并不代表Java就一定安全，SQL注入Java后台代码的原理与上面咱们讲的PHP语言的，原理上是一致的，因此编程时同样需要注意。Java主流使用的MyBatis框架，可以很好地支持prepared statement， 同样推荐使用；Hibernate框架本身对SQL注入防护也比较好，可以考虑使用positional parameter或者named parameter来避免注入问题。
