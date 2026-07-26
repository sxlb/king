<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 百度收录检测
 */
function joe_baidu_check($url)
{
    if (joe_get('baiduCheck') !== '1') return -1; // 未开启
    $cache_key = 'joe_baidu_check_' . md5($url);
    $cache = Typecho_Widget::widget('Widget_Options')->{$cache_key};
    if ($cache !== null) return (int)$cache;

    $checkUrl = 'https://www.baidu.com/s?wd=' . urlencode($url);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (compatible; BaiduCheck/1.0)\r\n",
            'timeout' => 5,
        ],
    ]);
    $html = @file_get_contents($checkUrl, false, $ctx);
    $indexed = 0;
    if ($html !== false) {
        // 检测页面中是否有搜索结果（排除安全验证页面）
        if (strpos($html, '百度安全验证') === false &&
            (strpos($html, 'class="result') !== false || strpos($html, 'class="c-abstract') !== false)) {
            $indexed = 1;
        }
    }
    // 缓存24小时
    try {
        $db = Typecho_Db::get();
        $db->query($db->insert('table.options')
            ->rows(['name' => $cache_key, 'user' => 0, 'value' => $indexed])
            ->onDuplicateKeyUpdate(['value' => $indexed]));
    } catch (Exception $e) {}
    return $indexed;
}

/**
 * 百度手动推送 AJAX 处理
 */
function joe_baidu_push_ajax()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $action = $_POST['action'] ?? '';
    if ($action !== 'joe_baidu_push') return;

    header('Content-Type: application/json');
    $token = trim(joe_get('baiduToken') ?: '');
    if (!$token) {
        echo json_encode(['code' => 0, 'msg' => '未配置百度推送Token'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $url = trim(strip_tags($_POST['url'] ?? ''));
    if (empty($url)) {
        echo json_encode(['code' => 0, 'msg' => 'URL不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $siteUrl = Helper::options()->siteUrl;
    $host = parse_url($siteUrl, PHP_URL_HOST);
    $api = 'http://data.zz.baidu.com/urls?site=' . urlencode($host) . '&token=' . urlencode($token);

    $fp = @fsockopen('data.zz.baidu.com', 80, $errno, $errstr, 3);
    if (!$fp) {
        echo json_encode(['code' => 0, 'msg' => '推送服务连接失败'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = $url;
    $path = '/urls?site=' . urlencode($host) . '&token=' . urlencode($token);
    $out = "POST {$path} HTTP/1.1\r\n";
    $out .= "Host: data.zz.baidu.com\r\n";
    $out .= "Content-Type: text/plain\r\n";
    $out .= "Content-Length: " . strlen($data) . "\r\n";
    $out .= "Connection: Close\r\n\r\n";
    $out .= $data;
    @fwrite($fp, $out);
    @fclose($fp);

    echo json_encode(['code' => 1, 'msg' => '已提交百度收录'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 百度主动推送（文章发布时调用）
 */
function joe_baidu_push($cid, $publish = true)
{
    if (joe_get('baiduPush') !== '1') return;
    $token = trim(joe_get('baiduToken') ?: '');
    if (!$token) return;

    $options = Helper::options();
    $siteUrl = rtrim($options->siteUrl, '/');

    try {
        $db = Typecho_Db::get();
        $row = $db->fetchRow($db->select('cid', 'slug', 'created', 'type')
            ->from('table.contents')
            ->where('cid = ?', $cid));
        if (!$row) return;
        $t = new Typecho_Widget_Helper_Empty();
        $t->cid = $row['cid'];
        $t->slug = $row['slug'];
        $t->type = $row['type'];
        $t->created = $row['created'];
        $url = Typecho_Router::url($row['type'], $t, $siteUrl);
    } catch (Exception $e) {
        $url = $siteUrl . '/index.php/' . $cid . '.html';
    }

    $api = 'http://data.zz.baidu.com/urls?site=' . urlencode(parse_url($siteUrl, PHP_URL_HOST)) . '&token=' . urlencode($token);
    if (!$publish) {
        $api = 'http://data.zz.baidu.com/del?site=' . urlencode(parse_url($siteUrl, PHP_URL_HOST)) . '&token=' . urlencode($token);
    }

    // 使用 fsockopen 避免 curl 依赖
    $host = 'data.zz.baidu.com';
    $path = parse_url($api, PHP_URL_PATH) . '?' . parse_url($api, PHP_URL_QUERY);
    $data = $url;

    $fp = @fsockopen($host, 80, $errno, $errstr, 3);
    if (!$fp) return;

    $out = "POST {$path} HTTP/1.1\r\n";
    $out .= "Host: {$host}\r\n";
    $out .= "Content-Type: text/plain\r\n";
    $out .= "Content-Length: " . strlen($data) . "\r\n";
    $out .= "Connection: Close\r\n\r\n";
    $out .= $data;
    @fwrite($fp, $out);
    @fclose($fp);
}

/**
 * 必应收录推送
 */
function joe_bing_push($cid)
{
    if (joe_get('bingPush') !== '1') return;
    $apiKey = trim(joe_get('bingApiKey') ?: '');
    if (!$apiKey) return;

    $siteUrl = rtrim(Helper::options()->siteUrl, '/');
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('cid', 'slug', 'created', 'type')
        ->from('table.contents')
        ->where('cid = ?', $cid));
    if (!$row) return;
    $t = new Typecho_Widget_Helper_Empty();
    $t->cid = $row['cid'];
    $t->slug = $row['slug'];
    $t->type = $row['type'];
    $t->created = $row['created'];
    try {
        $url = Typecho_Router::url($row['type'], $t, $siteUrl);
    } catch (Exception $e) {
        $url = $siteUrl . '/index.php/' . $cid . '.html';
    }

    $apiHost = parse_url($siteUrl, PHP_URL_HOST);
    $api = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl?site=' . urlencode($apiHost) . '&apikey=' . urlencode($apiKey);
    $body = json_encode(['siteUrl' => $apiHost, 'url' => $url]);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n",
            'content' => $body,
            'timeout' => 5,
        ],
    ]);
    @file_get_contents($api, false, $ctx);
}
