-- Tabel untuk menyimpan log aktivitas robot
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL DEFAULT 'info',
  `message` varchar(255) NOT NULL,
  `details` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample logs
INSERT INTO `activity_logs` (`type`, `message`, `details`) VALUES
('info', 'Sistem dimulai', 'Robot navigation system initialized'),
('success', 'GPS terhubung', 'GPS tracking aktif'),
('info', 'Mode manual diaktifkan', 'User mengubah mode ke manual');
