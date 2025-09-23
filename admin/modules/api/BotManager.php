<?php
require_once '../../core/config/app.php';

class BotManager {
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
    }
    
    // Добавить нового бота
    public function addBot($data) {
        $result = $this->db->insert('bots', [
            'name' => $data['name'],
            'token' => $data['token'], 
            'webhook_url' => $data['webhook_url'],
            'theme' => $data['theme'] ?? 'general'
        ]);
        
        return $this->db->id();
    }
    
    // Получить все боты
    public function getAllBots() {
        return $this->db->select('bots', '*', ['status' => 'active']);
    }
    
    // Получить контент бота
    public function getBotContent($bot_id, $content_key = null) {
        $where = ['bot_id' => $bot_id, 'status' => 'active'];
        if ($content_key) {
            $where['content_key'] = $content_key;
        }
        
        return $this->db->select('bot_content', '*', $where);
    }
    
    // Добавить/обновить контент
    public function saveContent($bot_id, $content_key, $data) {
        $existing = $this->db->get('bot_content', 'id', [
            'bot_id' => $bot_id,
            'content_key' => $content_key
        ]);
        
        $content_data = [
            'title' => $data['title'],
            'text' => $data['text'],
            'media_id' => $data['media_id'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'buttons' => json_encode($data['buttons'] ?? [])
        ];
        
        if ($existing) {
            $this->db->update('bot_content', $content_data, ['id' => $existing]);
            return $existing;
        } else {
            $content_data['bot_id'] = $bot_id;
            $content_data['content_key'] = $content_key;
            $this->db->insert('bot_content', $content_data);
            return $this->db->id();
        }
    }
    
    // Проверить права доступа
    public function checkAccess($telegram_id, $bot_id = null) {
        $admin = $this->db->get('admins', ['role', 'bot_access'], [
            'telegram_id' => $telegram_id
        ]);
        
        if (!$admin) return false;
        
        if ($admin['role'] === 'owner') return true;
        
        if ($bot_id && $admin['bot_access']) {
            $access = json_decode($admin['bot_access'], true);
            return in_array($bot_id, $access ?? []);
        }
        
        return true;
    }
}
?>