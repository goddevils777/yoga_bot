<?php
class JWTAuth {
    private $secret_key;
    private $pdo;
    
    public function __construct() {
        $this->secret_key = 'yoga-bot-admin-secret-key-2024-secure';
        
        // Читаем локальную базу
        // Приоритет: .env.local → .env
        $envFileLocal = __DIR__ . '/../../../bot/api/.env.local';
        $envFile = file_exists($envFileLocal) ? $envFileLocal : __DIR__ . '/../../../bot/api/.env';
        $env = $this->loadEnv($envFile);
        
        $dsn = "mysql:host={$env['dbHost']};dbname={$env['dbDatabase']};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $env['dbUsername'], $env['dbPassword'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    private function loadEnv($file) {
        $env = [];
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && $line[0] !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $env[trim($key)] = trim($value);
                }
            }
        }
        return $env;
    }
    
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    public function generateToken($user_data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user_data['telegram_id'],
            'role' => $user_data['role'],
            'exp' => time() + (24 * 60 * 60) // 24 часа
        ]);
        
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $this->secret_key, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    public function verifyToken($token) {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        $header = $this->base64UrlDecode($tokenParts[0]);
        $payload = $this->base64UrlDecode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];
        
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], $this->secret_key, true)
        );
        
        if (!hash_equals($expectedSignature, $signatureProvided)) {
            throw new Exception('Invalid token signature');
        }
        
        $payloadData = json_decode($payload, true);
        
        if ($payloadData['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        return $payloadData;
    }
    
    public function login($telegram_id, $auth_code) {
        // Проверяем код доступа
        if ($auth_code !== 'admin123') {
            throw new Exception('Invalid auth code');
        }
        
        // Ищем или создаем админа
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE telegram_id = ?");
        $stmt->execute([$telegram_id]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            // Проверяем есть ли уже owner
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM admins WHERE role = 'owner'");
            $stmt->execute();
            $ownerExists = $stmt->fetchColumn() > 0;
            
            $role = $ownerExists ? 'admin' : 'owner';
            
            $stmt = $this->pdo->prepare("INSERT INTO admins (telegram_id, role, bot_access) VALUES (?, ?, ?)");
            $stmt->execute([$telegram_id, $role, json_encode([])]);
            
            $admin = [
                'telegram_id' => $telegram_id,
                'role' => $role
            ];
        }
        
        return $this->generateToken($admin);
    }
}
?>