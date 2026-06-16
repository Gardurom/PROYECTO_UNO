<?php
/**
 * Sistema de logging
 */

class Logger {
    private $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];
    
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log('critical', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        $currentLevel = $this->logLevels[LOG_LEVEL] ?? 0;
        if ($this->logLevels[$level] < $currentLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logEntry = sprintf("[%s] %s: %s %s (IP: %s)\n",
            $timestamp,
            strtoupper($level),
            $message,
            $contextStr,
            $ip
        );
        
        $logFile = LOGS_PATH . '/' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function cleanOldLogs($days = 30) {
        $files = glob(LOGS_PATH . '/*.log');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > ($days * 86400)) {
                unlink($file);
            }
        }
    }
}
?>