<?
// Доступ к БД
$sql_name="cl80228_redirect";
$sql_host="localhost";
$sql_user="cl80228_redirect";
$sql_password="1111";
$sql_table="redirector_links";
// Подключение к БД
$mysqli = new mysqli($sql_host, $sql_user, $sql_password);
if ($mysqli->connect_errno) {
    echo "Ошибка подключения к серверу: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
} else {
    $mysqli->select_db("cl80228_redirect");
}
// Отборажение страницы
function page($content='') 
{
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
    <html>
        <head>
            <meta content="text/html; charset=utf-8" http-equiv="content-type">
            <meta http-equiv="content-language" content="ru">
            <link href="style.css" rel="stylesheet">
            <script type="text/javascript" src="/jquery-3.2.1.min.js"></script>
            <script type="text/javascript" src="/script.js"></script>
            <title>Сокращение ссылок</title>
        </head>
        <body>
            <div class="container">
    ';
       
        echo '<H2>Сокращение ссылок</H2>
        		<form action="/" method="post">
                <input type="hidden" name="do" value="add">
                <input type="text" class="url" name="url" value="">
                <input type="text" class="short_url" name="short_url" value="">
                <input type="submit" class="submit" value="Сгенерировать">
            </form>';
             echo $content;
            '</div>
        </body>
    </html>';
}
// Перевод из обычного представления в ссылочное
function dec2link($id) 
{
    $digits='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $link='';
    do { 
        $dig=$id%62;
        $link=$digits[$dig].$link;
        $id=floor($id/62);
    } while($id!=0);
    return $link;
}
// Перевод из ссылочного представление в обчное
function link2dec($link) 
{
	$digits=Array('0'=>0,  '1'=>1,  '2'=>2,  '3'=>3,  '4'=>4,  '5'=>5,  '6'=>6,  '7'=>7,  '8'=>8,  '9'=>9,
              	  'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15, 'g'=>16, 'h'=>17, 'i'=>18, 'j'=>19,
                  'k'=>20, 'l'=>21, 'm'=>22, 'n'=>23, 'o'=>24, 'p'=>25, 'q'=>26, 'r'=>27, 's'=>28, 't'=>29,
                  'u'=>30, 'v'=>31, 'w'=>32, 'x'=>33, 'y'=>34, 'z'=>35, 'A'=>36, 'B'=>37, 'C'=>38, 'D'=>39,
                  'E'=>40, 'F'=>41, 'G'=>42, 'H'=>43, 'I'=>44, 'J'=>45, 'K'=>46, 'L'=>47, 'M'=>48, 'N'=>49,
                  'O'=>50, 'P'=>51, 'Q'=>52, 'R'=>53, 'S'=>54, 'T'=>55, 'U'=>56, 'V'=>57, 'W'=>58, 'X'=>59,
                  'Y'=>60, 'Z'=>61);
	$id=0;
    for ($i=0; $i<strlen($link); $i++) {
    	$id+=$digits[$link[(strlen($link)-$i-1)]]*pow(62,$i);
    }
    	return $id;
	}
//Поиск анадогичной ссылки и редирект на оригинальный url	
if(isset($_GET['link'])) {
    $link=trim($_GET['link']);
    if ($link) {
    	$result=$mysqli->query("SELECT * FROM `".$sql_table."` WHERE `code`='".$link."'");
		$row=mysqli_fetch_assoc($result);
        if (isset($row['link_url'])) {
            	$content='<meta http-equiv="Refresh" content="0; url='.htmlspecialchars($row['link_url']).'">';
            	$content.='<script type="text/javascript">document.location.href=unescape("'.rawurlencode($row['link_url']).'");</script>';
            	page($content);
        } else {
            	$content='<div class="error">Ошибка! Cсылка не найдена</div><br>';
            	page($content);
        }
    } else {
        $content='<div class="error">Ошибка</div><br>';
        page($content);
    }
// Добавление ссылки в БД
} elseif(isset($_POST['do']) && $_POST['do']=='add') {
    if(isset($_POST['url'])) {
        $link_url=trim($_POST['url']);
        if ($link_url) {
            if (!preg_match('#^[a-z]{3,}\:#',$link_url)) {
                $link_url='http://'.$link_url;
            }
            $short_url=$_POST['short_url'];
           	$result = $mysqli->query("SELECT * FROM `".$sql_table."` WHERE `link_url`='".$link_url."' OR `code`='".$short_url."' ");
            $row=mysqli_fetch_assoc($result);
            if (isset($row['link_id'])) {
                $link_short=dec2link($row['link_id']);
                $short_url = $row['code'];
                $content='<div class="error">Ошибка! Такая ссылка уже существует: </div><br>';
                $content.='<input type="text" class="url" value="http://'.getenv('HTTP_HOST').'/'.$short_url.'" onclick="this.select();"><br><br>';
                page($content);
                exit;
            } else {
               	$mysqli->query("INSERT INTO `".$sql_table."` SET
                    `link_url`='".mysql_real_escape_string($link_url)."'");
                $result = $mysqli->query("SELECT LAST_INSERT_ID() AS `link_id`");
                $row=mysqli_fetch_assoc($result);
                $link_short=dec2link($row['link_id']);
				//Проверяем, ввел ли пользователь собственную ссылку
                if(!empty($_POST['short_url'])) {
        	        $short_url=$_POST['short_url'];
        	        $result = $mysqli->query("UPDATE `".$sql_table."` SET 
                	`code`='".$short_url."' WHERE `link_id`='".$row['link_id']."'");
                } else  {
                    $result = $mysqli->query("UPDATE `".$sql_table."` SET `code`='".$link_short."' WHERE `link_id`='".$row['link_id']."'");
                    $short_url = $link_short; 
                }    
                
            }
            $content.='<br>Ваша ссылка: ';
            $content.='<input type="text" class="url" value="http://'.getenv('HTTP_HOST').'/'.$short_url.'" onclick="this.select();"><br><br>';
            page($content);
        } else {
            echo"Ссылка не передана";
            exit;
        }
    }
        
} else {
    page();
}
?>