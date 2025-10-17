<?php
// 定义存储聊天数据的JSON文件
$chatFile = 'chat_data.json';

// 设置响应头为JSON格式
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 确保请求方法正确
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_messages') {
        getMessages();
    } else {
        echo json_encode(['success' => false, 'error' => '未知操作']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] === 'send_message') {
        sendMessage();
    } else {
        echo json_encode(['success' => false, 'error' => '未知操作']);
    }
} else {
    echo json_encode(['success' => false, 'error' => '无效请求']);
}

// 获取消息函数
function getMessages() {
    global $chatFile;
    
    // 如果文件不存在，则返回空数组
    if (!file_exists($chatFile)) {
        echo json_encode([]);
        return;
    }
    
    // 读取并返回消息
    $content = file_get_contents($chatFile);
    if ($content === false) {
        echo json_encode(['success' => false, 'error' => '无法读取消息文件']);
        return;
    }
    
    $messages = json_decode($content, true);
    
    // 如果解码失败，返回空数组
    if (!is_array($messages)) {
        echo json_encode([]);
        return;
    }
    
    // 限制消息数量，防止文件过大（保留最后100条消息）
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
        file_put_contents($chatFile, json_encode($messages));
    }
    
    echo json_encode($messages);
}

// 发送消息函数
function sendMessage() {
    global $chatFile;
    
    // 获取POST数据
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // 验证数据
    if (empty($username) || empty($message)) {
        echo json_encode(['success' => false, 'error' => '用户名和消息不能为空']);
        return;
    }
    
    // 限制消息长度
    if (strlen($username) > 20) {
        echo json_encode(['success' => false, 'error' => '用户名不能超过20个字符']);
        return;
    }
    
    if (strlen($message) > 500) {
        echo json_encode(['success' => false, 'error' => '消息不能超过500个字符']);
        return;
    }
    
    // 如果文件不存在，则创建空数组
    if (!file_exists($chatFile)) {
        $initialData = [];
        file_put_contents($chatFile, json_encode($initialData));
    }
    
    // 读取现有消息
    $content = file_get_contents($chatFile);
    $messages = [];
    
    if ($content !== false) {
        $messages = json_decode($content, true);
        if (!is_array($messages)) {
            $messages = [];
        }
    }
    
    // 添加新消息
    $newMessage = [
        'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
        'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    array_push($messages, $newMessage);
    
    // 保存消息到文件
    if (file_put_contents($chatFile, json_encode($messages))) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => '无法保存消息，请检查文件权限']);
    }
}
?>