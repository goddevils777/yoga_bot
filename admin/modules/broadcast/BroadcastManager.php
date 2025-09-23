<?php
require_once '../../core/config/app.php';

class BroadcastManager {
    private $db;
    
    public function __construct() {
        CoreConfig::getInstance();
        $this->db = new Medoo\Medoo([
            'type' => CoreConfig::get('dbType'),
            'host' => CoreConfig::get('dbHost'),
            'database' => CoreConfig::get('dbDatabase'),
            'username' => CoreConfig::get('dbUsername'),
            'password' => CoreConfig::get('dbPassword'),
            'charset' => 'utf8mb4'
        ]);
        
        $this->createBroadcastTables();
    }
    
    private function createBroadcastTables() {
        // Таблица рассылок
        $this->db->query("CREATE TABLE IF NOT EXISTS broadcasts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bot_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            media_id VARCHAR(255),
            media_type ENUM('photo', 'video', 'document'),
            target_type ENUM('all', 'active', 'group', 'custom') DEFAULT 'all',
            target_groups JSON,
            status ENUM('draft', 'sending', 'completed', 'failed') DEFAULT 'draft',
            total_recipients INT DEFAULT 0,
            sent_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP NULL,
            FOREIGN KEY (bot_id) REFERENCES bots(id)
        )");
        
        // Таблица для группового управления
        $this->db->query("CREATE TABLE IF NOT EXISTS group_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bot_id INT NOT NULL,
            action_type ENUM('block', 'unblock', 'restrict') NOT NULL,
            target_id BIGINT NOT NULL,
            reason VARCHAR(255),
            admin_id BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bot_id) REFERENCES bots(id)
        )");
    }
    
    public function createBroadcast($bot_id, $data) {
        $broadcast_id = $this->db->insert('broadcasts', [
            'bot_id' => $bot_id,
            'title' => $data['title'],
            'message' => $data['message'],
            'media_id' => $data['media_id'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'target_type' => $data['target_type'] ?? 'all',
            'target_groups' => json_encode($data['target_groups'] ?? [])
        ]);
        
        return $this->db->id();
    }
    
    public function startBroadcast($broadcast_id) {
        $broadcast = $this->db->get('broadcasts', '*', ['id' => $broadcast_id]);
        if (!$broadcast) return false;
        
        // Получаем список получателей
        $recipients = $this->getRecipients($broadcast['bot_id'], $broadcast['target_type'], 
                                         json_decode($broadcast['target_groups'], true));
        
        $this->db->update('broadcasts', [
            'status' => 'sending',
            'total_recipients' => count($recipients),
            'sent_at' => date('Y-m-d H:i:s')
        ], ['id' => $broadcast_id]);
        
        // Запускаем отправку в фоне
        $this->sendMessages($broadcast, $recipients);
        
        return true;
    }
    
    private function getRecipients($bot_id, $target_type, $target_groups) {
        $where = ['active_bot' => true];
        
        switch ($target_type) {
            case 'active':
                $where['date_register[>=]'] = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'group':
                // Логика для групповой рассылки
                break;
        }
        
        return $this->db->select('users', ['telegram_id'], $where);
    }
    
    private function sendMessages($broadcast, $recipients) {
        $bot_token = $this->getBotToken($broadcast['bot_id']);
        $sent = 0;
        
        foreach ($recipients as $recipient) {
            $success = $this->sendTelegramMessage(
                $bot_token, 
                $recipient['telegram_id'], 
                $broadcast['message'],
                $broadcast['media_id'],
                $broadcast['media_type']
            );
            
            if ($success) $sent++;
            
            // Пауза между сообщениями (защита от лимитов)
            usleep(100000); // 0.1 секунда
        }
        
        $this->db->update('broadcasts', [
            'status' => 'completed',
            'sent_count' => $sent
        ], ['id' => $broadcast['id']]);
    }
    
    private function sendTelegramMessage($token, $chat_id, $text, $media_id = null, $media_type = null) {
        $url = "https://api.telegram.org/bot{$token}/";
        
        if ($media_id) {
            $method = $media_type === 'photo' ? 'sendPhoto' : 'sendDocument';
            $data = [
                'chat_id' => $chat_id,
                $media_type => $media_id,
                'caption' => $text
            ];
        } else {
            $method = 'sendMessage';
            $data = [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];
        }
        
        $ch = curl_init($url . $method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
    
    private function getBotToken($bot_id) {
        return $this->db->get('bots', 'token', ['id' => $bot_id]);
    }
    
    public function blockUser($bot_id, $user_id, $admin_id, $reason = '') {
        // Блокируем в базе
        $this->db->update('users', ['active_bot' => false], ['telegram_id' => $user_id]);
        
        // Логируем действие
        $this->db->insert('group_actions', [
            'bot_id' => $bot_id,
            'action_type' => 'block',
            'target_id' => $user_id,
            'reason' => $reason,
            'admin_id' => $admin_id
        ]);
        
        return true;
    }
    
    public function getBroadcasts($bot_id) {
        return $this->db->select('broadcasts', '*', ['bot_id' => $bot_id], ['ORDER' => ['created_at' => 'DESC']]);
    }
}
?>